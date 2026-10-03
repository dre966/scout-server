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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Bot polling: ?bot_id=14 => return pending commands
    $bot_id = $_GET['bot_id'] ?? $_GET['id'] ?? null;
    if ($bot_id === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bot_id required']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT id, bot_id, cmd, args, status, created_at FROM commands WHERE bot_id = :id AND status = 'pending' ORDER BY created_at ASC");
        $stmt->execute([':id' => (int)$bot_id]);
        $rows = $stmt->fetchAll();
        // Decode args JSON
        foreach ($rows as &$r) {
            $r['args'] = $r['args'] ? json_decode($r['args'], true) : null;
        }
        echo json_encode(['ok' => true, 'commands' => $rows]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    // Dashboard sends command: {bot_id, cmd, args}
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;

    $bot_id = $data['bot_id'] ?? $data['id'] ?? null;
    $cmd    = $data['cmd'] ?? $data['command'] ?? null;
    $args   = $data['args'] ?? null;

    if ($bot_id === null || $cmd === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bot_id and cmd required']);
        exit;
    }

    $allowed = ['PAUSE','RESUME','RESTART','STOP','START','REFRESH','CLEAR','CUSTOM'];
    $cmdUpper = strtoupper($cmd);
    // Allow any cmd but normalize if in allowed list

    try {
        $stmt = $pdo->prepare("INSERT INTO commands (bot_id, cmd, args, status, created_at) VALUES (:bot_id, :cmd, :args, 'pending', NOW())");
        $stmt->execute([
            ':bot_id' => (int)$bot_id,
            ':cmd' => $cmdUpper,
            ':args' => $args !== null ? json_encode($args) : null
        ]);
        // Log it
        $log = $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bot_id, :msg, 'info')");
        $log->execute([':bot_id' => (int)$bot_id, ':msg' => 'command queued: ' . $cmdUpper]);

        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId(), 'cmd' => $cmdUpper]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'method not allowed']);
