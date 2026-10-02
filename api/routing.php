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

// Same relative layout on EC2 (/var/www/...) and locally (scouts/...).
$file = __DIR__ . '/../../scout-bot/data/routing.json';

function routing_load($file) {
    $raw = @file_get_contents($file);
    if ($raw === false) throw new Exception('cannot read routing.json');
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new Exception('routing.json is not an array');
    return $data;
}

try {
    $entries = routing_load($file);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $out = [];
        foreach ($entries as $i => $e) {
            $out[] = [
                'id' => (int)$i,
                'proxy' => isset($e['proxy']) ? (string)$e['proxy'] : '',
                'rest' => !empty($e['rest']),
            ];
        }
        echo json_encode(['ok' => true, 'entries' => $out]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) $data = $_POST;
        $id = $data['bot_id'] ?? null;
        if ($id === null || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'bot_id required']);
            exit;
        }
        $id = (int)$id;
        if (!array_key_exists($id, $entries)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'no routing entry for bot ' . $id]);
            exit;
        }
        $rest = filter_var($data['rest'] ?? null, FILTER_VALIDATE_BOOLEAN);
        if ($rest) {
            $entries[$id]['rest'] = true;
        } else {
            unset($entries[$id]['rest']);
        }

        // Atomic write (same dir) so containers never read a half-written file.
        $tmp = $file . '.tmp.' . getmypid();
        $json = json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($tmp, $json) === false) {
            throw new Exception('cannot write routing.json');
        }
        if (!rename($tmp, $file)) {
            @unlink($tmp);
            throw new Exception('cannot replace routing.json');
        }
        @chmod($file, 0666); // keep the world-writable mode scp expects

        echo json_encode([
            'ok' => true,
            'id' => $id,
            'rest' => $rest,
            'entry' => $entries[$id],
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
