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

$bot_id = $_GET['bot_id'] ?? $_GET['id'] ?? null;
if ($bot_id === null || $bot_id === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bot_id required']);
    exit;
}
$bot_id = (int)$bot_id;

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_up_balance (
        bot_id INTEGER PRIMARY KEY,
        balance BIGINT,
        error TEXT,
        fetched_at TIMESTAMPTZ DEFAULT NOW()
    )");
} catch (Exception $e) {}

const UP_TTL = 60;          // seconds before re-calling UnityEdge
const UP_RPC = 'https://api.unityedge.io/rest/v1/rpc/rewards_get_balance';

function up_http_post($url, $headers, $body) {
    $headerLine = [];
    foreach ($headers as $k => $v) $headerLine[] = "$k: $v";
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headerLine,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) return [0, $err];
        return [$code, $resp];
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headerLine),
        'content' => $body,
        'timeout' => 20,
        'ignore_errors' => true,
    ]]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return [0, 'http request failed'];
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
    return [$code, $resp];
}

function up_parse_balance($data) {
    if (is_bool($data)) return null;
    if (is_int($data) || is_float($data)) return (int)$data;
    if (is_string($data)) {
        $s = trim($data);
        if (ctype_digit($s)) return (int)$s;
        if (is_numeric($s)) return (int)floatval($s);
        return null;
    }
    if (is_array($data)) {
        foreach (['balance', 'rewards_balance', 'rewards', 'up_balance', 'amount', 'value', 'data', 'result'] as $k) {
            if (array_key_exists($k, $data)) {
                $v = up_parse_balance($data[$k]);
                if ($v !== null) return $v;
            }
        }
        foreach ($data as $v) {
            $r = up_parse_balance($v);
            if ($r !== null) return $r;
        }
    }
    if (is_array($data) && isset($data[0])) return up_parse_balance($data[0]);
    return null;
}

// ---- cache check ----------------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT balance, error, fetched_at FROM bot_up_balance WHERE bot_id = :id");
    $stmt->execute([':id' => $bot_id]);
    $cached = $stmt->fetch();
    if ($cached && strtotime($cached['fetched_at']) !== false && (time() - strtotime($cached['fetched_at'])) < UP_TTL) {
        $bal = $cached['balance'] !== null ? (int)$cached['balance'] : null;
        echo json_encode([
            'ok' => true, 'bot_id' => $bot_id, 'cached' => true,
            'balance' => $bal,
            'up' => $bal !== null ? round($bal / 1000000, 2) : null,
            'error' => $cached['error'],
            'fetched_at' => $cached['fetched_at'],
        ]);
        exit;
    }
} catch (Exception $e) {}

// ---- token ----------------------------------------------------------------
$token = null;
try {
    $stmt = $pdo->prepare("SELECT supabase_token FROM bot_license_capture WHERE bot_id = :id");
    $stmt->execute([':id' => $bot_id]);
    $row = $stmt->fetch();
    if ($row) $token = $row['supabase_token'];
} catch (Exception $e) {}

if (!$token) {
    echo json_encode(['ok' => true, 'bot_id' => $bot_id, 'balance' => null, 'up' => null, 'error' => 'no supabase token captured', 'fetched_at' => null]);
    exit;
}

// ---- call UnityEdge -------------------------------------------------------
$headers = [
    'accept' => '*/*',
    'accept-language' => 'en-US,en;q=0.9,es;q=0.8',
    'apikey' => 'sb_publishable_yKqi0fu5vV6G4ryUIMJuzw_NCoFEl1c',
    'content-profile' => 'public',
    'content-type' => 'application/json',
    'origin' => 'https://manage.unetwork.io',
    'referer' => 'https://manage.unetwork.io/',
    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36',
    'x-client-info' => 'supabase-js-web/2.87.1',
    'authorization' => 'Bearer ' . $token,
];
list($code, $body) = up_http_post(UP_RPC, $headers, '{}');
$balance = null;
$err = null;
if ($code === 200) {
    $json = json_decode($body, true);
    $balance = up_parse_balance($json !== null ? $json : $body);
    if ($balance === null) $err = 'unparsed: ' . substr((string)$body, 0, 160);
} else {
    $err = "http $code " . substr((string)$body, 0, 160);
}

try {
    $pdo->prepare("INSERT INTO bot_up_balance (bot_id, balance, error, fetched_at) VALUES (:id, :b, :e, NOW())
                   ON CONFLICT (bot_id) DO UPDATE SET balance=EXCLUDED.balance, error=EXCLUDED.error, fetched_at=NOW()")
        ->execute([':id' => $bot_id, ':b' => $balance, ':e' => $err]);
} catch (Exception $e) {}

echo json_encode([
    'ok' => true, 'bot_id' => $bot_id, 'cached' => false,
    'balance' => $balance,
    'up' => $balance !== null ? round($balance / 1000000, 2) : null,
    'http' => $code,
    'error' => $err,
    'fetched_at' => gmdate('c'),
]);
