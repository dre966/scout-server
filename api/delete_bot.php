<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Bot-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$expected = getenv('BOT_TOKEN') ?: 'scout-secret';
$provided = $_SERVER['HTTP_X_BOT_TOKEN'] ?? null;
if ($provided !== $expected) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;
$bot_id = $data['bot_id'] ?? $data['id'] ?? ($_GET['bot_id'] ?? null);
if ($bot_id === null || $bot_id === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_id required']);
    exit;
}
$bid = (int)$bot_id;

try {
    $stmt = $pdo->prepare("SELECT id, proxy_email FROM bots WHERE id = :id");
    $stmt->execute([':id' => $bid]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'bot '.$bid.' not found']);
        exit;
    }

    // cancel pending commands + drop per-bot rows (no FK cascade in schema)
    $tables = [
        'commands'          => 'bot_id = :bid',
        'bot_license_capture'=> 'bot_id = :bid',
        'bot_unetwork'      => 'bot_id = :bid',
        'bot_sims_status'   => 'bot_id = :bid',
        'notifications'     => 'bot_id = :bid',
        'bot_logs'          => 'bot_id = :bid',
    ];
    $removed = [];
    foreach ($tables as $tbl => $where) {
        try {
            $pdo->prepare("DELETE FROM $tbl WHERE $where")->execute([':bid' => $bid]);
            $removed[] = $tbl;
        } catch (Exception $e) {}
    }
    $pdo->prepare("DELETE FROM bots WHERE id = :id")->execute([':id' => $bid]);

    $msg = 'bot '.$bid.' removed ('.($row['proxy_email'] ?? '').')';
    try {
        $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bid, :msg, 'warn')")
            ->execute([':bid' => $bid, ':msg' => $msg]);
    } catch (Exception $e) {}

    echo json_encode(['ok' => true, 'bot_id' => $bid, 'removed' => $removed]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
