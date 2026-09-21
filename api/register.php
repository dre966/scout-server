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

$bot_id       = $data['bot_id'] ?? $data['id'] ?? null;
$proxy_email  = $data['proxy_email'] ?? null;
$poll_inbox   = $data['poll_inbox'] ?? null;
$container_id = $data['container_id'] ?? null;

if ($bot_id === null || $bot_id === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_id required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO bots (id, proxy_email, poll_inbox, container_id, heartbeat_at, created_at)
        VALUES (:id, :proxy_email, :poll_inbox, :container_id, NOW(), NOW())
        ON CONFLICT (id) DO UPDATE SET
            proxy_email = EXCLUDED.proxy_email,
            poll_inbox = EXCLUDED.poll_inbox,
            container_id = EXCLUDED.container_id,
            heartbeat_at = NOW(),
            updated_at = NOW()");
    $stmt->execute([
        ':id' => (int)$bot_id,
        ':proxy_email' => $proxy_email,
        ':poll_inbox' => $poll_inbox,
        ':container_id' => $container_id
    ]);

    $log = $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bot_id, :msg, 'info')");
    $log->execute([':bot_id' => (int)$bot_id, ':msg' => 'registered container=' . $container_id]);

    echo json_encode(['ok' => true, 'bot_id' => (int)$bot_id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
