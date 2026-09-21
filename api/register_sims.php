<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Bot-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$expected = getenv('BOT_TOKEN') ?: 'scout-secret';
$provided = $_SERVER['HTTP_X_BOT_TOKEN'] ?? null;
if ($provided !== null && $provided !== $expected) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$bot_id   = $data['bot_id'] ?? $data['id'] ?? null;
$mappings = $data['mappings'] ?? $data['sims'] ?? null;

if ($bot_id === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_id required']);
    exit;
}
if ($mappings === null || !is_array($mappings)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'mappings array required: [{simId, packageId}]']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT auth_token, proxy_email FROM bots WHERE id = :id");
    $stmt->execute([':id' => (int)$bot_id]);
    $row = $stmt->fetch();
    if (!$row || empty($row['auth_token'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'no auth_token for bot_id ' . (int)$bot_id]);
        exit;
    }
    $token = trim($row['auth_token']);
    if (stripos($token, 'Bearer ') === 0) $token = trim(substr($token, 7));

    function scout_req_reg($token, $method, $path, $body = null) {
        $url = 'https://scoutandrunner.com/api' . $path;
        $headers = [
            'Authorization: Bearer ' . $token,
            'apikey: ' . $token,
            'Content-Type: application/json',
            'Accept: application/json, text/plain, */*',
            'Origin: https://scoutandrunner.com',
            'Referer: https://scoutandrunner.com/runner/sims',
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return [$http, $resp, $err];
    }

    function ensure_runner($token) {
        [$http, $resp] = scout_req_reg($token, 'GET', '/auth/me');
        if ($http === 200 && $resp) {
            $j = json_decode($resp, true);
            $role = $j['user']['role'] ?? $j['role'] ?? null;
            if ($role === 'runner') return true;
            scout_req_reg($token, 'POST', '/auth/switch-role', new stdClass());
            sleep(1);
            [$http2, $resp2] = scout_req_reg($token, 'GET', '/auth/me');
            $j2 = json_decode($resp2, true);
            return (($j2['user']['role'] ?? $j2['role'] ?? null) === 'runner');
        }
        return false;
    }

    ensure_runner($token);

    $results = [];
    foreach ($mappings as $m) {
        $simId = $m['simId'] ?? $m['sim_id'] ?? $m['id'] ?? null;
        $packageId = $m['packageId'] ?? $m['package_id'] ?? $m['categoryId'] ?? null;
        if (!$simId) {
            $results[] = ['simId' => null, 'skipped' => true, 'error' => 'missing simId'];
            continue;
        }
        if (!$packageId) {
            $results[] = ['simId' => $simId, 'skipped' => true, 'packageId' => null, 'msg' => 'empty package - skipped'];
            continue;
        }
        // POST /runner/sim/{simId}/package {categoryId, photoProofUrl: ""}
        [$http, $resp, $err] = scout_req_reg($token, 'POST', "/runner/sim/{$simId}/package", ['categoryId' => $packageId, 'photoProofUrl' => '']);
        $bodyPreview = $resp ? substr($resp, 0, 600) : $err;
        $j = $resp ? json_decode($resp, true) : null;
        if ($http >= 200 && $http < 300) {
            $results[] = ['simId' => $simId, 'packageId' => $packageId, 'ok' => true, 'http' => $http, 'response' => $j ?? $bodyPreview];
            // log success
            $log = $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bot_id, :msg, 'info')");
            $log->execute([':bot_id' => (int)$bot_id, ':msg' => "register sim {$simId} -> {$packageId} ok http={$http}"]);
        } else {
            $results[] = ['simId' => $simId, 'packageId' => $packageId, 'ok' => false, 'http' => $http, 'error' => $bodyPreview];
            $log = $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:bot_id, :msg, 'warn')");
            $log->execute([':bot_id' => (int)$bot_id, ':msg' => "register sim {$simId} -> {$packageId} fail http={$http} body=" . substr($bodyPreview,0,200)]);
        }
        usleep(400000); // 0.4s between calls
    }

    // After registering, queue refresh command for bot so its state handler picks up next move
    try {
        $cmdStmt = $pdo->prepare("INSERT INTO commands (bot_id, cmd, args, status, created_at) VALUES (:bot_id, 'REFRESH', NULL, 'pending', NOW())");
        $cmdStmt->execute([':bot_id' => (int)$bot_id]);
        $cmdId = $pdo->lastInsertId();
    } catch (Exception $e) { $cmdId = null; }

    echo json_encode(['ok' => true, 'bot_id' => (int)$bot_id, 'results' => $results, 'refresh_command_id' => $cmdId ? (int)$cmdId : null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
