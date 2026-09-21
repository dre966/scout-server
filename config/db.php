<?php
// config/db.php - PDO connection for MariaDB (XAMPP)
// Uses env vars with defaults: DB_HOST=localhost, DB_NAME=scout_db, DB_USER=root, DB_PASS=""

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'scout_db';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$DB_PORT = getenv('DB_PORT') ?: '3306';

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Return JSON error if called via API, otherwise show plain
    if (php_sapi_name() !== 'cli' && strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'DB connection failed', 'details' => $e->getMessage()]);
        exit;
    }
    // For dashboard / direct include, expose message
    throw $e;
}

function getPDO(): PDO {
    global $pdo;
    return $pdo;
}
