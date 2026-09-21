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
    echo json_encode(['ok'=>false,'error'=>'unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/db.php';

// Ensure cache table exists (Postgres)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_sims_status (
        bot_id INTEGER PRIMARY KEY,
        sims_json TEXT,
        updated_at TIMESTAMPTZ DEFAULT NOW()
    )");
} catch (Exception $e) {}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;
    $bot_id = $data['bot_id'] ?? $data['id'] ?? null;
    $sims = $data['sims'] ?? null;
    if ($bot_id === null) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bot_id required']); exit; }
    if ($sims === null) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'sims array required']); exit; }
    try {
        $json = json_encode($sims);
        $pdo->prepare("INSERT INTO bot_sims_status (bot_id, sims_json, updated_at) VALUES (:id, :json, NOW()) ON CONFLICT (bot_id) DO UPDATE SET sims_json=EXCLUDED.sims_json, updated_at=NOW()")
            ->execute([':id'=>(int)$bot_id, ':json'=>$json]);
        // also log counts for fleet badge
        $pdo->prepare("INSERT INTO bot_logs (bot_id, message, level) VALUES (:id, :msg, 'info')")
            ->execute([':id'=>(int)$bot_id, ':msg'=>'sims_status '.count($sims).' sims']);
        echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'count'=>count($sims)]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
    exit;
}
if ($method === 'GET') {
    $bot_id = $_GET['bot_id'] ?? $_GET['id'] ?? null;
    if ($bot_id === null || $bot_id === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bot_id required']); exit; }
    try {
        $stmt = $pdo->prepare("SELECT sims_json, updated_at FROM bot_sims_status WHERE bot_id=:id");
        $stmt->execute([':id'=>(int)$bot_id]);
        $row = $stmt->fetch();
        if (!$row) { echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'sims'=>[],'updated_at'=>null]); exit; }
        $sims = $row['sims_json'] ? json_decode($row['sims_json'], true) : [];
        echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'sims'=>$sims,'updated_at'=>$row['updated_at']]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
    exit;
}
http_response_code(405); echo json_encode(['ok'=>false,'error'=>'method not allowed']);
