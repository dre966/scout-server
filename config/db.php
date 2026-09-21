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
    $port = getenv('DB_PORT') ?: getenv('PGPORT') ?: '5432';
    $db   = getenv('DB_NAME') ?: getenv('PGDATABASE') ?: 'scout_db';
    $user = getenv('DB_USER') ?: getenv('PGUSER') ?: 'postgres';
    $pass = getenv('DB_PASS') ?: getenv('PGPASSWORD') ?: '';
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
        // generic in prod - no host/db leak (previous InfinityFree leak fixed)
        echo json_encode(['ok' => false, 'error' => 'DB connection failed']);
        exit;
    }
    throw $e;
}

function getPDO(): PDO {
    global $pdo;
    return $pdo;
}
