<?php
// config/db.php - PDO for Postgres (Render)
// Supports DATABASE_URL (postgres://user:pass@host:port/db) and discrete DB_* vars

$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 5432;
    $user = $parts['user'] ?? 'postgres';
    $pass = $parts['pass'] ?? '';
    $db   = isset($parts['path']) ? ltrim($parts['path'], '/') : 'postgres';
    // handle query string ?sslmode=require
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $q);
        $sslmode = $q['sslmode'] ?? null;
    } else {
        $sslmode = null;
    }
} else {
    $host = getenv('DB_HOST') ?: getenv('PGHOST') ?: 'localhost';
    // Render dashboard truncates display - ensure full hostname: dpg-xxx-a.oregon-postgres.render.com
    // If your screenshot shows "dpg-daoeabmgekts73bunkkg-a" without suffix, append it in dashboard.
    if ($host && !str_contains($host, '.') && str_starts_with($host, 'dpg-')) {
        $host .= '.oregon-postgres.render.com';
    }
    $port = getenv('DB_PORT') ?: getenv('PGPORT') ?: '5432';
    $db   = getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: getenv('PGDATABASE') ?: 'scout_db';
    $user = getenv('DB_USERNAME') ?: getenv('DB_USER') ?: getenv('PGUSER') ?: 'postgres';
    $pass = getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: getenv('PGPASSWORD') ?: '';
    $sslmode = getenv('PGSSLMODE') ?: null;
}

$dsn = "pgsql:host={$host};port={$port};dbname={$db}";
if ($sslmode) {
    $dsn .= ";sslmode={$sslmode}";
} else {
    // Render Postgres requires sslmode=require; add by default if host is not localhost
    if ($host !== 'localhost' && $host !== '127.0.0.1') {
        $dsn .= ";sslmode=require";
    }
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("DB connect failed host={$host} db={$db} user={$user}: " . $e->getMessage());
    if (php_sapi_name() !== 'cli' && strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        // transient diagnosis: include the driver message (API is token-gated)
        echo json_encode(['ok' => false, 'error' => 'DB connection failed', 'db_error' => $e->getMessage()]);
        exit;
    }
    throw $e;
}

function getPDO(): PDO {
    global $pdo;
    return $pdo;
}

// Lazily add bots.public_ip once per PHP process (bots started sending it 2026-10-03)
function ensure_public_ip_col(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $pdo = getPDO();
        $found = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'bots' AND column_name = 'public_ip'")->fetch();
        if (!$found) {
            $pdo->exec("ALTER TABLE bots ADD COLUMN public_ip TEXT");
        }
    } catch (Throwable $e) {
        error_log("ensure_public_ip_col: " . $e->getMessage());
    }
}

// rest/country tags for routing entries (set from the app, read by bots)
function ensure_routing_tags_table(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        getPDO()->exec("CREATE TABLE IF NOT EXISTS routing_tags (
            bot_id INTEGER PRIMARY KEY,
            rest BOOLEAN NOT NULL DEFAULT FALSE,
            country TEXT
        )");
    } catch (Throwable $e) {
        error_log("ensure_routing_tags_table: " . $e->getMessage());
    }
}
