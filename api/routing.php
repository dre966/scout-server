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

// Base routing list ships with the server repo; rest/country tags live in
// Postgres so edits survive redeploys and are readable by the Railway bots.
$baseFile = __DIR__ . '/../data/routing.json';

function routing_load($file) {
    $raw = @file_get_contents($file);
    if ($raw === false) throw new Exception('cannot read routing.json');
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new Exception('routing.json is not an array');
    return $data;
}

function routing_tags_all(PDO $pdo): array {
    $tags = [];
    foreach ($pdo->query("SELECT bot_id, rest, country FROM routing_tags") as $row) {
        $tags[(int)$row['bot_id']] = [
            'rest' => (bool)$row['rest'],
            'country' => $row['country'] !== null ? strtoupper((string)$row['country']) : null,
        ];
    }
    return $tags;
}

try {
    ensure_routing_tags_table();
    $entries = routing_load($baseFile);
    $tags = routing_tags_all($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $out = [];
        foreach ($entries as $i => $e) {
            $tag = $tags[(int)$i] ?? null;
            $rest = $tag ? $tag['rest'] : !empty($e['rest']);
            $c = $tag && $tag['country'] !== null
                ? $tag['country']
                : (isset($e['country']) ? strtoupper((string)$e['country']) : '');
            $out[] = [
                'id' => (int)$i,
                'proxy' => isset($e['proxy']) ? (string)$e['proxy'] : '',
                'rest' => $rest,
                'country' => in_array($c, ['US', 'CA'], true) ? $c : null,
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

        $existing = $tags[$id] ?? ['rest' => !empty($entries[$id]['rest']), 'country' => null];

        // Only touch keys the caller sent - a country-only POST must not
        // wipe an existing rest tag (and vice versa).
        $rest = $existing['rest'];
        if (array_key_exists('rest', $data)) {
            $rest = filter_var($data['rest'], FILTER_VALIDATE_BOOLEAN);
        }

        $country = $existing['country'];
        if (array_key_exists('country', $data)) {
            $c = strtoupper(trim((string)($data['country'] ?? '')));
            $country = in_array($c, ['US', 'CA'], true) ? $c : null;
        }

        $stmt = $pdo->prepare("INSERT INTO routing_tags (bot_id, rest, country) VALUES (:id, :rest, :country)
            ON CONFLICT (bot_id) DO UPDATE SET rest = EXCLUDED.rest, country = EXCLUDED.country");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':rest', $rest, PDO::PARAM_BOOL);
        $stmt->bindValue(':country', $country, $country === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        $entry = $entries[$id];
        $entry['rest'] = $rest;
        if ($country !== null) {
            $entry['country'] = $country;
        } else {
            unset($entry['country']);
        }

        echo json_encode([
            'ok' => true,
            'id' => $id,
            'rest' => $rest,
            'country' => $country,
            'entry' => $entry,
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
