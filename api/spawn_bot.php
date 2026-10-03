<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Bot-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$expected = getenv('BOT_TOKEN') ?: 'scout-secret';
$provided = $_SERVER['HTTP_X_BOT_TOKEN'] ?? '';
if ($provided !== $expected) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // GET -> routing list (BOT_ID -> proxy) merged with live heartbeat status
    $candidates = array_filter([getenv('ROUTING_JSON'), __DIR__ . '/../data/routing.json']);
    $routingFile = null;
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) { $routingFile = $candidate; break; }
    }
    if ($routingFile === null) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'routing.json not found; tried: ' . implode(', ', $candidates)]);
        exit;
    }
    $routing = json_decode((string)file_get_contents($routingFile), true);
    if (!is_array($routing)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'routing.json unreadable']);
        exit;
    }
    $live = [];
    try {
        require_once __DIR__ . '/../config/db.php';
        foreach ($pdo->query("SELECT id, state, heartbeat_at FROM bots") as $row) {
            $live[(int)$row['id']] = $row;
        }
    } catch (Exception $e) { /* db down -> routing only */ }
    $out = [];
    foreach ($routing as $id => $r) {
        $hb = $live[$id]['heartbeat_at'] ?? null;
        $fresh = $hb !== null && (time() - strtotime($hb)) < 90;
        $out[] = [
            'id' => (int)$id,
            'proxy' => $r['proxy'] ?? '',
            'poll_inbox' => $r['poll_inbox'] ?? '',
            'type' => $r['type'] ?? '',
            'state' => $live[$id]['state'] ?? null,
            'heartbeat_at' => $hb,
            'running' => $fresh,
        ];
    }
    echo json_encode(['ok' => true, 'spawn_sh' => is_file('/var/www/scout-bot/spawn.sh'), 'bots' => $out]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$spec = $data['bot_ids'] ?? $data['ids'] ?? $data['spec'] ?? null;
if (is_array($spec)) $spec = implode(',', $spec);
$spec = strtolower(trim((string)$spec));
if ($spec === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_ids required (e.g. "14", "0,1,2", "0-5", "all")']);
    exit;
}
// strict whitelist: digits, commas, single ranges, or "all"
if (!preg_match('/^(all|[0-9]+([,-][0-9]+)*)$/', $spec)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid bot_ids format']);
    exit;
}

$script = '/var/www/scout-bot/spawn.sh';
if (!is_file($script)) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'spawn.sh not available on this host']);
    exit;
}

$cmd = 'bash ' . escapeshellarg($script) . ' ' . escapeshellarg($spec) . ' 2>&1';
$start = microtime(true);
$output = shell_exec($cmd);
$elapsed = round(microtime(true) - $start, 2);
$output = (string)$output;

$ok = stripos($output, '[!]') === false && stripos($output, 'error') === false;
echo json_encode([
    'ok' => $ok,
    'bot_ids' => $spec,
    'elapsed_s' => $elapsed,
    'output' => $output,
]);
