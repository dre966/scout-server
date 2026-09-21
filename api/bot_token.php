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

require_once __DIR__ . '/../config/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$bot_id      = $data['bot_id'] ?? $data['id'] ?? null;
$token       = $data['token'] ?? $data['auth_token'] ?? null;
$proxy_email = $data['proxy_email'] ?? null;
$poll_inbox  = $data['poll_inbox'] ?? null;

if ($bot_id === null || $token === null || $token === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_id and token required']);
    exit;
}

$token = trim($token);
if (stripos($token, 'Bearer ') === 0) {
    $token = trim(substr($token, 7));
}

if (strlen($token) < 20) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'token too short']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO bots (id, auth_token, token_updated_at, heartbeat_at, created_at)
        VALUES (:id, :token, NOW(), NOW(), NOW())
        ON CONFLICT (id) DO UPDATE SET
            auth_token = EXCLUDED.auth_token,
            token_updated_at = NOW(),
            heartbeat_at = NOW(),
            updated_at = NOW(),
            proxy_email = COALESCE(:proxy_email, bots.proxy_email),
            poll_inbox = COALESCE(:poll_inbox, bots.poll_inbox)");
    $stmt->execute([
        ':id' => (int)$bot_id,
        ':token' => $token,
        ':proxy_email' => $proxy_email,
        ':poll_inbox' => $poll_inbox
    ]);

    $log = $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bot_id, :msg, 'info')");
    $log->execute([':bot_id' => (int)$bot_id, ':msg' => 'auth_token updated ...' . substr($token, -8)]);

    echo json_encode(['ok' => true, 'bot_id' => (int)$bot_id, 'masked' => substr($token, 0, 6) . '...' . substr($token, -4)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
