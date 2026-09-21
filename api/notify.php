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

$bot_id  = $data['bot_id'] ?? $data['id'] ?? null;
$type    = $data['type'] ?? null;
$message = $data['message'] ?? null;
$details = $data['details'] ?? null;

if ($bot_id === null || $type === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_id and type required']);
    exit;
}

try {
    $detailsJson = $details !== null ? (is_string($details) ? $details : json_encode($details)) : null;
    // Ensure priority column exists (high priority for NoSimsRegistered)
    try { $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS priority VARCHAR(16) DEFAULT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS `priority` VARCHAR(16) DEFAULT NULL"); } catch (Exception $ignored) {}
    $priority = $data['priority'] ?? null;
    if (!$priority && $type === 'NoSimsRegistered') $priority = 'high';
    $hasPriority = false;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'priority'")->fetch();
        $hasPriority = !!$col;
    } catch (Exception $e) {}
    if ($hasPriority) {
        $stmt = $pdo->prepare("INSERT INTO notifications (bot_id, type, message, details, priority, created_at) VALUES (:bot_id, :type, :message, :details, :priority, NOW())");
        $stmt->execute([
            ':bot_id' => (int)$bot_id,
            ':type' => $type,
            ':message' => $message,
            ':details' => $detailsJson,
            ':priority' => $priority
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO notifications (bot_id, type, message, details, created_at) VALUES (:bot_id, :type, :message, :details, NOW())");
        $stmt->execute([
            ':bot_id' => (int)$bot_id,
            ':type' => $type,
            ':message' => $message,
            ':details' => $detailsJson
        ]);
    }
    $nid = $pdo->lastInsertId();

    // Also log
    $log = $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bot_id, :msg, 'warn')");
    $log->execute([':bot_id' => (int)$bot_id, ':msg' => "notify {$type}: {$message}"]);

    // Optional Telegram webhook
    $tgToken = getenv('TELEGRAM_BOT_TOKEN') ?: '';
    $tgChat  = getenv('TELEGRAM_CHAT_ID') ?: '';
    $tgUrl   = getenv('TELEGRAM_WEBHOOK_URL') ?: '';
    $sentTelegram = false;

    if ($tgToken && $tgChat) {
        $text = "[BOT {$bot_id}] {$type}: {$message}";
        if ($detailsJson) $text .= "\n" . substr($detailsJson, 0, 800);
        $tgApi = "https://api.telegram.org/bot{$tgToken}/sendMessage";
        $payload = json_encode(['chat_id' => $tgChat, 'text' => $text]);
        $ch = curl_init($tgApi);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $sentTelegram = $res !== false;
    } elseif ($tgUrl) {
        $payload = json_encode(['bot_id' => (int)$bot_id, 'type' => $type, 'message' => $message, 'details' => $details]);
        $ch = curl_init($tgUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        curl_exec($ch);
        curl_close($ch);
        $sentTelegram = true;
    }

    echo json_encode(['ok' => true, 'id' => (int)$nid, 'telegram_sent' => $sentTelegram]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
