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
    $priority = $data['priority'] ?? null;
    if (!$priority && in_array($type, ['NoSimsRegistered','NoNumbersToTest','AccountDeleted'], true)) $priority = 'high';

    // Postgres: priority column exists in schema.sql; no SHOW COLUMNS check needed
    $stmt = $pdo->prepare("INSERT INTO notifications (bot_id, type, message, details, priority, created_at) VALUES (:bot_id, :type, :message, :details, :priority, NOW())");
    $stmt->execute([
        ':bot_id' => (int)$bot_id,
        ':type' => $type,
        ':message' => $message,
        ':details' => $detailsJson,
        ':priority' => $priority
    ]);
    $nid = $pdo->lastInsertId();

    $log = $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bot_id, :msg, 'warn')");
    $log->execute([':bot_id' => (int)$bot_id, ':msg' => "notify {$type}: {$message}"]);

    $tgToken = getenv('TELEGRAM_BOT_TOKEN') ?: '';
    $tgChat  = getenv('TELEGRAM_CHAT_ID') ?: '';
    $tgUrl   = getenv('TELEGRAM_WEBHOOK_URL') ?: '';
    $ntfyTopic = getenv('NTFY_TOPIC') ?: '';
    $ntfyUrl = getenv('NTFY_URL') ?: ($ntfyTopic ? 'https://ntfy.sh/'.ltrim($ntfyTopic,'/') : '');
    $sentTelegram = false; $sentNtfy = false;

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
    if ($ntfyUrl) {
        $title = "[BOT {$bot_id}] {$type}";
        $prio = ($priority==='high') ? '5' : '3';
        $body = $message . ($detailsJson ? "\n".substr($detailsJson,0,600) : "");
        $ch = curl_init($ntfyUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Title: '.$title,'Priority: '.$prio,'Tags: warning,scout'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $sentNtfy = $res !== false;
    }

    echo json_encode(['ok' => true, 'id' => (int)$nid, 'telegram_sent' => $sentTelegram, 'ntfy_sent' => $sentNtfy]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
