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
        count_total INTEGER,
        updated_at TIMESTAMPTZ DEFAULT NOW()
    )");
} catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE bot_sims_status ADD COLUMN IF NOT EXISTS count_total INTEGER"); } catch (Exception $e) {}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST;
    $bot_id = $data['bot_id'] ?? $data['id'] ?? null;
    $sims = $data['sims'] ?? null;
    if ($bot_id === null) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bot_id required']); exit; }
    if ($sims === null) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'sims array required']); exit; }

    // Count-only ping ("dashboard_count") -> store the number, never touch the real SIM list
    if (is_array($sims) && count($sims) === 1 && (($sims[0]['phone'] ?? '') === 'dashboard_count')) {
        $n = null;
        if (preg_match('/total:(\d+)/', (string)($sims[0]['status'] ?? ''), $m)) $n = (int)$m[1];
        try {
            if ($n !== null) {
                $pdo->prepare("INSERT INTO bot_sims_status (bot_id, sims_json, count_total, updated_at) VALUES (:id, NULL, :n, NOW())
                               ON CONFLICT (bot_id) DO UPDATE SET count_total=EXCLUDED.count_total, updated_at=NOW()")
                    ->execute([':id'=>(int)$bot_id, ':n'=>$n]);
            } else {
                $pdo->prepare("INSERT INTO bot_sims_status (bot_id, updated_at) VALUES (:id, NOW())
                               ON CONFLICT (bot_id) DO UPDATE SET updated_at=NOW()")
                    ->execute([':id'=>(int)$bot_id]);
            }
            echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'count_total'=>$n,'count_only'=>true]);
        } catch (Exception $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
        exit;
    }
    try {
        $json = json_encode($sims);
        $pdo->prepare("INSERT INTO bot_sims_status (bot_id, sims_json, count_total, updated_at) VALUES (:id, :json, :n, NOW())
                       ON CONFLICT (bot_id) DO UPDATE SET sims_json=EXCLUDED.sims_json, count_total=EXCLUDED.count_total, updated_at=NOW()")
            ->execute([':id'=>(int)$bot_id, ':json'=>$json, ':n'=>count($sims)]);
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
        $stmt = $pdo->prepare("SELECT sims_json, count_total, updated_at FROM bot_sims_status WHERE bot_id=:id");
        $stmt->execute([':id'=>(int)$bot_id]);
        $row = $stmt->fetch();
        if (!$row) { echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'sims'=>[],'updated_at'=>null]); exit; }
        $sims = $row['sims_json'] ? json_decode($row['sims_json'], true) : [];
        echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'sims'=>$sims,'count_total'=>$row['count_total'],'updated_at'=>$row['updated_at']]);
    } catch (Exception $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
    exit;
}
http_response_code(405); echo json_encode(['ok'=>false,'error'=>'method not allowed']);
