<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Bot-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Auth check - allow dashboard GET without token if no header sent, but enforce if header present
// For bot POST we require token; for dashboard polling we embed token in JS
$expected = getenv('BOT_TOKEN') ?: 'scout-secret';
$provided = $_SERVER['HTTP_X_BOT_TOKEN'] ?? null;
$isBotPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($provided !== null && $provided !== $expected) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}
if ($isBotPost && $provided !== $expected) {
    // POST must have valid token
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $bot_id = $_GET['bot_id'] ?? $_GET['id'] ?? null;
    $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $limit = max(1, min($limit, 200));

    try {
        if ($bot_id !== null && $bot_id !== '') {
            // Single bot + recent logs
            $stmt = $pdo->prepare("SELECT * FROM bots WHERE id = :id");
            $stmt->execute([':id' => (int)$bot_id]);
            $bot = $stmt->fetch();

            $logsStmt = $pdo->prepare("SELECT * FROM bot_logs WHERE bot_id = :id ORDER BY created_at DESC, id DESC LIMIT :lim");
            $logsStmt->bindValue(':id', (int)$bot_id, PDO::PARAM_INT);
            $logsStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $logsStmt->execute();
            $logs = $logsStmt->fetchAll();

            $cmdStmt = $pdo->prepare("SELECT * FROM commands WHERE bot_id = :id ORDER BY created_at DESC LIMIT 20");
            $cmdStmt->execute([':id' => (int)$bot_id]);
            $commands = $cmdStmt->fetchAll();

            $noteStmt = $pdo->prepare("SELECT * FROM notifications WHERE bot_id = :id ORDER BY created_at DESC LIMIT 20");
            $noteStmt->execute([':id' => (int)$bot_id]);
            $notifications = $noteStmt->fetchAll();

            echo json_encode(['ok' => true, 'bot' => $bot, 'logs' => $logs, 'commands' => $commands, 'notifications' => $notifications]);
        } else {
            // All bots
            $bots = $pdo->query("SELECT * FROM bots ORDER BY heartbeat_at DESC, id ASC")->fetchAll();
            // Also fetch counts
            $logsCnt = $pdo->query("SELECT bot_id, COUNT(*) as cnt FROM bot_logs GROUP BY bot_id")->fetchAll(PDO::FETCH_KEY_PAIR);
            // Attach log counts
            foreach ($bots as &$b) { $b['logs_count'] = $logsCnt[$b['id']] ?? 0; }
            unset($b);

            // Recent notifications overall
            $recentNotes = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20")->fetchAll();

            echo json_encode(['ok' => true, 'bots' => $bots, 'notifications' => $recentNotes]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;

    $bot_id  = $data['bot_id'] ?? $data['id'] ?? null;
    $message = $data['message'] ?? $data['log'] ?? null;
    $level   = $data['level'] ?? 'info';
    $state   = $data['state'] ?? null;

    if ($bot_id === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'bot_id required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO bot_logs (bot_id, state, message, level) VALUES (:bot_id, :state, :message, :level)");
        $stmt->execute([
            ':bot_id' => (int)$bot_id,
            ':state' => $state,
            ':message' => $message,
            ':level' => $level
        ]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'method not allowed']);
