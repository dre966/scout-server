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

$bot_id = $data['bot_id'] ?? $data['id'] ?? null;
$confirm = $data['confirmation'] ?? $data['confirm'] ?? 'DELETE_MY_ACCOUNT';

if ($bot_id === null || $bot_id === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_id required']);
    exit;
}
if ($confirm !== 'DELETE_MY_ACCOUNT') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'confirmation must be DELETE_MY_ACCOUNT']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT auth_token, proxy_email FROM bots WHERE id = :id");
    $stmt->execute([':id' => (int)$bot_id]);
    $row = $stmt->fetch();
    if (!$row || empty($row['auth_token'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'no auth_token for bot_id '.(int)$bot_id.' — click Load devices & tokens first']);
        exit;
    }
    $token = trim($row['auth_token']);
    if (stripos($token, 'Bearer ') === 0) $token = trim(substr($token, 7));

    // Proxy to scoutandrunner: mirrors scripts/delete.py
    $url = 'https://scoutandrunner.com/api/auth/delete-account';
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
        'Origin: https://scoutandrunner.com',
        'Referer: https://scoutandrunner.com/scout',
    ];
    $body = json_encode(['confirmation' => 'DELETE_MY_ACCOUNT']);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => 'curl error: '.$err]);
        exit;
    }

    // Log outcome
    $msg = $http >= 200 && $http < 300 ? 'delete_account ok' : 'delete_account fail http='.$http;
    $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bid, :msg, :lvl)")
        ->execute([':bid'=>(int)$bot_id, ':msg'=>$msg.' '.substr($resp?:'',0,300), ':lvl'=>($http>=200&&$http<300?'info':'warn')]);
    if ($http >= 200 && $http < 300) {
        // notify high-priority so banner shows
        $pdo->prepare("INSERT INTO notifications (bot_id, type, message, details, priority, created_at) VALUES (:bid, 'AccountDeleted', :msg, :det, 'high', NOW())")
            ->execute([':bid'=>(int)$bot_id, ':msg'=>'Account deleted for bot '.(int)$bot_id.' ('.($row['proxy_email']??'').')', ':det'=>substr($resp?:'',0,500)]);
        // make bot go to landing page after delete (no confirmation flow)
        try {
            $pdo->prepare("INSERT INTO commands (bot_id, cmd, args, status, created_at) VALUES (:bid, 'LOGOUT', :args, 'pending', NOW())")
                ->execute([':bid'=>(int)$bot_id, ':args'=>json_encode(['wait'=>0])]);
            $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bid, :msg, 'info')")
                ->execute([':bid'=>(int)$bot_id, ':msg'=>'delete_account -> queued LOGOUT to landing']);
        } catch (Exception $e) {}
    }

    http_response_code($http);
    header('Content-Type: application/json');
    echo $resp ?: json_encode(['ok'=>($http>=200&&$http<300), 'http'=>$http]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
