<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Bot-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$expected = getenv('BOT_TOKEN') ?: 'scout-secret';
$provided = $_SERVER['HTTP_X_BOT_TOKEN'] ?? '';
if ($provided !== $expected) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_up_balance (
        bot_id INTEGER PRIMARY KEY,
        balance BIGINT,
        error TEXT,
        fetched_at TIMESTAMPTZ DEFAULT NOW()
    )");
} catch (Exception $e) {}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_up_balance_history (
        id BIGSERIAL PRIMARY KEY,
        bot_id INTEGER NOT NULL,
        balance BIGINT NOT NULL,
        recorded_at TIMESTAMPTZ DEFAULT NOW()
    )");
    $pdo->exec("CREATE INDEX IF NOT EXISTS ubh_bot_time ON bot_up_balance_history (bot_id, recorded_at)");
} catch (Exception $e) {}

// Rolling 24h gross gains per bot: sum of positive balance steps.
// Baseline = last snapshot at/before window start, so the change across the
// window boundary is included; withdrawals (negative steps) are not counted.
$gains = [];
try {
    $gains = $pdo->query("
        WITH base AS (
            SELECT DISTINCT ON (bot_id) bot_id, balance, recorded_at AS rt, true AS is_base
            FROM bot_up_balance_history
            WHERE recorded_at <= NOW() - INTERVAL '24 hours'
            ORDER BY bot_id, recorded_at DESC
        ),
        win AS (
            SELECT bot_id, balance, recorded_at AS rt, false AS is_base
            FROM bot_up_balance_history
            WHERE recorded_at > NOW() - INTERVAL '24 hours'
        ),
        comb AS (SELECT * FROM base UNION ALL SELECT * FROM win),
        d AS (
            SELECT bot_id, is_base,
                   balance - COALESCE(LAG(balance) OVER (PARTITION BY bot_id ORDER BY rt), balance) AS delta
            FROM comb
        )
        SELECT bot_id,
               SUM(GREATEST(delta, 0)) AS gained,
               MAX(CASE WHEN is_base THEN 1 ELSE 0 END) AS has_base
        FROM d
        GROUP BY bot_id
    ")->fetchAll();
} catch (Exception $e) {}

// UP made today (since 00:00 UTC): sum of positive balance steps in today's
// window. Withdrawals never reduce it, so it only grows as bots earn.
$madeToday = null;
try {
    $madeToday = (int)$pdo->query("
        WITH base AS (
            SELECT DISTINCT ON (bot_id) bot_id, balance, recorded_at AS rt
            FROM bot_up_balance_history
            WHERE recorded_at <= date_trunc('day', NOW())
            ORDER BY bot_id, recorded_at DESC
        ),
        win AS (
            SELECT bot_id, balance, recorded_at AS rt
            FROM bot_up_balance_history
            WHERE recorded_at > date_trunc('day', NOW())
        ),
        comb AS (SELECT * FROM base UNION ALL SELECT * FROM win),
        d AS (
            SELECT balance - COALESCE(LAG(balance) OVER (PARTITION BY bot_id ORDER BY rt), balance) AS delta
            FROM comb
        )
        SELECT COALESCE(SUM(GREATEST(delta, 0)), 0)
        FROM d
    ")->fetchColumn();
} catch (Exception $e) {}

// Peak of the fleet total over the rolling 24h window: forward-fill each
// bot's balance (baseline + signed steps), sum them over time, take the max.
// A withdrawal lowers the running total but never lowers the peak.
$peak = null;
try {
    $peak = (int)$pdo->query("
        WITH base AS (
            SELECT DISTINCT ON (bot_id) bot_id, balance, recorded_at AS rt, true AS is_base
            FROM bot_up_balance_history
            WHERE recorded_at <= NOW() - INTERVAL '24 hours'
            ORDER BY bot_id, recorded_at DESC
        ),
        win AS (
            SELECT bot_id, balance, recorded_at AS rt, false AS is_base
            FROM bot_up_balance_history
            WHERE recorded_at > NOW() - INTERVAL '24 hours'
        ),
        comb AS (SELECT * FROM base UNION ALL SELECT * FROM win),
        seq AS (
            SELECT bot_id, rt, is_base, balance,
                   LAG(balance) OVER (PARTITION BY bot_id ORDER BY rt) AS prev
            FROM comb
        ),
        steps AS (
            SELECT rt, bot_id,
                   CASE
                       WHEN is_base THEN 0
                       WHEN prev IS NULL THEN balance
                       ELSE balance - prev
                   END AS delta
            FROM seq
        ),
        running AS (
            SELECT COALESCE((SELECT SUM(balance) FROM base), 0)
                   + SUM(delta) OVER (ORDER BY rt, bot_id ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS total
            FROM steps
        )
        SELECT GREATEST(
            COALESCE((SELECT SUM(balance) FROM base), 0),
            COALESCE(MAX(total), 0)
        ) AS v
        FROM running
    ")->fetchColumn();
} catch (Exception $e) {}

$gainByBot = [];
foreach ($gains as $g) {
    $gainByBot[(int)$g['bot_id']] = [
        'gained' => (int)$g['gained'],
        'has_base' => ((int)$g['has_base'] === 1),
    ];
}

$cur = [];
try {
    $cur = $pdo->query("SELECT bot_id, balance FROM bot_up_balance WHERE balance IS NOT NULL ORDER BY bot_id")->fetchAll();
} catch (Exception $e) {}

$perBot = [];
$partial = false;
foreach ($cur as $r) {
    $id = (int)$r['bot_id'];
    $now = (int)$r['balance'];
    $g = $gainByBot[$id] ?? null;
    if ($g === null) {
        $made = null;
        $full = false;
        $partial = true;
    } else {
        $made = $g['gained'] / 1000000;
        $full = $g['has_base'];
        if (!$full) $partial = true;
    }
    $perBot[] = [
        'bot_id' => $id,
        'up' => round($now / 1000000, 2),
        'made_24h' => $made !== null ? round($made, 2) : null,
        'full_window' => $full,
    ];
}

if ($peak === null) $partial = true;
if ($madeToday === null) $partial = true;

echo json_encode([
    'ok' => true,
    'made_today' => $madeToday !== null ? round($madeToday / 1000000, 2) : null,
    'total_24h' => round(($peak ?? 0) / 1000000, 2),
    'partial' => $partial,
    'bots' => $perBot,
]);
