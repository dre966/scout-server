<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$bot_id = $_GET['bot_id'] ?? $_GET['id'] ?? null;
if ($bot_id === null) {
    // also try JSON body
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    if (is_array($j)) $bot_id = $j['bot_id'] ?? $j['id'] ?? null;
}
if ($bot_id === null || $bot_id === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_id required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT auth_token FROM bots WHERE id = :id");
    $stmt->execute([':id' => (int)$bot_id]);
    $row = $stmt->fetch();
    if (!$row || empty($row['auth_token'])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'no auth_token for bot_id ' . (int)$bot_id . ' - click Load devices & tokens first']);
        exit;
    }
    $token = trim($row['auth_token']);
    if (stripos($token, 'Bearer ') === 0) $token = trim(substr($token, 7));

    $BASE = 'https://scoutandrunner.com/api';

    function scout_request($token, $method, $path, $body = null) {
        $BASE = 'https://scoutandrunner.com/api';
        $url = $BASE . $path;
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

    function ensure_role($token, $target) {
        [$http, $resp] = scout_request($token, 'GET', '/auth/me');
        if ($http === 200 && $resp) {
            $j = json_decode($resp, true);
            $role = $j['user']['role'] ?? $j['role'] ?? null;
            if ($role === $target) return [true, $role];
            // switch
            [$sh, $sresp] = scout_request($token, 'POST', '/auth/switch-role', new stdClass());
            // empty json object: {} -> stdClass encodes as {}
            // retry with {} if stdClass fails
            if ($sh !== 200) {
                // try with empty array
                [$sh, $sresp] = scout_request($token, 'POST', '/auth/switch-role', []);
            }
            sleep(1);
            [$http2, $resp2] = scout_request($token, 'GET', '/auth/me');
            if ($http2 === 200) {
                $j2 = json_decode($resp2, true);
                $role2 = $j2['user']['role'] ?? $j2['role'] ?? '?';
                return [$role2 === $target, $role2];
            }
            return [false, $role ?? '?'];
        }
        return [false, '?'];
    }

    // Ensure runner role for sims
    ensure_role($token, 'runner');

    [$http, $resp, $err] = scout_request($token, 'GET', '/runner/sims');
    if ($err) {
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => 'curl error: ' . $err]);
        exit;
    }
    if ($http === 401) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'scout auth failed (401) - token expired or invalid', 'http' => $http, 'body' => substr($resp, 0, 500)]);
        exit;
    }
    if ($http >= 400) {
        http_response_code($http);
        echo json_encode(['ok' => false, 'error' => 'scout API error', 'http' => $http, 'body' => substr($resp, 0, 800)]);
        exit;
    }

    $data = json_decode($resp, true);
    // normalize: list_packages expects either array or {sims: [...] } or {data: [...]}
    $sims = [];
    if (is_array($data)) {
        if (isset($data[0]) || empty($data)) {
            $sims = $data;
        } elseif (isset($data['sims'])) {
            $sims = $data['sims'];
        } elseif (isset($data['data'])) {
            $sims = $data['data'];
        } else {
            $sims = $data;
        }
    } elseif (isset($data['sims'])) {
        $sims = $data['sims'];
    }

    // Normalize each sim entry to {id, phoneNumber, carrier, status, package}
    $out = [];
    foreach ($sims as $item) {
        if (!$item) continue;
        $sim = $item['sim'] ?? $item;
        $pkg = $item['package'] ?? null;
        $out[] = [
            'id' => $sim['id'] ?? $item['id'] ?? null,
            'phoneNumber' => $sim['phoneNumber'] ?? $sim['phone'] ?? $item['phoneNumber'] ?? '?',
            'carrier' => $sim['carrier'] ?? '?',
            'status' => $sim['status'] ?? '?',
            'countryCode' => $sim['countryCode'] ?? $sim['country'] ?? '?',
            'package' => $pkg,
            'testsInCycle' => $sim['testsInCycle'] ?? $item['testsInCycle'] ?? $sim['testsThisCycle'] ?? 0,
            'cooldownEndsAt' => $sim['cooldownEndsAt'] ?? $item['cooldownEndsAt'] ?? null,
            'raw' => $item
        ];
    }

    echo json_encode(['ok' => true, 'bot_id' => (int)$bot_id, 'sims' => $out, 'count' => count($out)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
