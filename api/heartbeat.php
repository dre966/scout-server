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

$bot_id      = $data['bot_id'] ?? $data['id'] ?? null;
$state       = $data['state'] ?? null;
$sims_count  = $data['sims_count'] ?? null;
$uptime      = $data['uptime'] ?? null;
$current_url = $data['current_url'] ?? null;
$message     = $data['message'] ?? null;

if ($bot_id === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_id required']);
    exit;
}

try {
    // Update bots table
    $stmt = $pdo->prepare("UPDATE bots SET
        state = COALESCE(:state, state),
        sims_count = COALESCE(:sims_count, sims_count),
        current_url = COALESCE(:current_url, current_url),
        uptime = COALESCE(:uptime, uptime),
        heartbeat_at = NOW(),
        updated_at = NOW()
        WHERE id = :id");
    $stmt->execute([
        ':state' => $state,
        ':sims_count' => $sims_count !== null ? (int)$sims_count : null,
        ':current_url' => $current_url,
        ':uptime' => $uptime,
        ':id' => (int)$bot_id
    ]);

    // If bot row didn't exist, create it
    if ($stmt->rowCount() === 0) {
        $ins = $pdo->prepare("INSERT INTO bots (id, state, sims_count, current_url, uptime, heartbeat_at, created_at)
            VALUES (:id, :state, :sims_count, :current_url, :uptime, NOW(), NOW())
            ON DUPLICATE KEY UPDATE state=VALUES(state), sims_count=VALUES(sims_count), current_url=VALUES(current_url), uptime=VALUES(uptime), heartbeat_at=NOW()");
        $ins->execute([
            ':id' => (int)$bot_id,
            ':state' => $state,
            ':sims_count' => $sims_count !== null ? (int)$sims_count : 0,
            ':current_url' => $current_url,
            ':uptime' => $uptime
        ]);
    }

    // Insert into bot_logs for history
    $log = $pdo->prepare("INSERT INTO bot_logs (bot_id, state, sims_count, current_url, uptime, message, level) VALUES (:bot_id, :state, :sims_count, :current_url, :uptime, :message, 'info')");
    $log->execute([
        ':bot_id' => (int)$bot_id,
        ':state' => $state,
        ':sims_count' => $sims_count !== null ? (int)$sims_count : null,
        ':current_url' => $current_url,
        ':uptime' => $uptime,
        ':message' => $message
    ]);

    echo json_encode(['ok' => true, 'bot_id' => (int)$bot_id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
