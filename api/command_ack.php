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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$command_id = $data['command_id'] ?? $data['id'] ?? null;
$status     = $data['status'] ?? 'acked'; // acked|done|failed
$bot_id     = $data['bot_id'] ?? null;
$message    = $data['message'] ?? null;

if ($command_id === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'command_id required']);
    exit;
}

$allowedStatus = ['acked','done','failed','pending'];
if (!in_array($status, $allowedStatus, true)) $status = 'acked';

try {
    $stmt = $pdo->prepare("UPDATE commands SET status = :status, acked_at = NOW(), updated_at = NOW() WHERE id = :id");
    $stmt->execute([':status' => $status, ':id' => (int)$command_id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'command not found']);
        exit;
    }

    if ($bot_id !== null) {
        $log = $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bot_id, :msg, 'info')");
        $log->execute([':bot_id' => (int)$bot_id, ':msg' => "command {$command_id} {$status}" . ($message ? ": {$message}" : "")]);
    }

    echo json_encode(['ok' => true, 'id' => (int)$command_id, 'status' => $status]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
