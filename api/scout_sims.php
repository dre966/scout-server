<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Bot-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$expected = getenv('BOT_TOKEN') ?: 'scout-secret';
$provided = $_SERVER['HTTP_X_BOT_TOKEN'] ?? null;
if ($provided !== null && $provided !== $expected) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }
require_once __DIR__ . '/../config/db.php';

$bot_id = $_GET['bot_id'] ?? $_GET['id'] ?? null;
if ($bot_id === null) { $raw=file_get_contents('php://input'); $j=json_decode($raw,true); if(is_array($j)) $bot_id=$j['bot_id']??$j['id']??null; }
if ($bot_id===null||$bot_id==='') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bot_id required']); exit; }

try {
    $stmt=$pdo->prepare("SELECT auth_token FROM bots WHERE id=:id");
    $stmt->execute([':id'=>(int)$bot_id]);
    $row=$stmt->fetch();
    if (!$row||empty($row['auth_token'])) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'no auth_token for bot_id '.(int)$bot_id]); exit; }
    $token=trim($row['auth_token']); if(stripos($token,'Bearer ')===0) $token=trim(substr($token,7));

    // Exact fetch you specified: first check role via api/auth/me, if scout then hit api/scout/sims
    $authHeaders = [
        'accept: application/json, text/plain, */*',
        'accept-language: en-US,en;q=0.9',
        'authorization: Bearer '.$token,
        'if-none-match: W/"c15e-miiOcAirEkGn21TZs2tjqgK0AkA"',
        'origin: https://scoutandrunner.com',
        'referer: https://scoutandrunner.com/scout/sims',
    ];
    $ch=curl_init('https://scoutandrunner.com/api/auth/me');
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10,
        CURLOPT_HTTPHEADER=>$authHeaders,
        CURLOPT_SSL_VERIFYPEER=>true,
    ]);
    $resp=curl_exec($ch); $http=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    if($err){ http_response_code(502); echo json_encode(['ok'=>false,'error'=>'auth/me curl '.$err]); exit; }
    if($http>=400){ http_response_code($http); echo json_encode(['ok'=>false,'error'=>'auth/me failed','http'=>$http,'body'=>substr($resp,0,600)]); exit; }
    $me=json_decode($resp,true);
    $role=$me['user']['role'] ?? $me['role'] ?? $me['data']['role'] ?? null;
    if($role!=='scout'){
        // not scout — return empty with role for dashboard to show
        echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'role'=>$role,'sims'=>[],'count'=>0,'note'=>'role is '.$role.' not scout']);
        exit;
    }

    // Now fetch scout sims exactly as you specified
    $scoutHeaders = [
        'accept: application/json, text/plain, */*',
        'accept-language: en-US,en;q=0.9',
        'authorization: Bearer '.$token,
        'if-none-match: W/"c15e-miiOcAirEkGn21TZs2tjqgK0AkA"',
        'referer: https://scoutandrunner.com/scout/sims',
    ];
    $ch=curl_init('https://scoutandrunner.com/api/scout/sims');
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
        CURLOPT_HTTPHEADER=>$scoutHeaders,
        CURLOPT_SSL_VERIFYPEER=>true,
    ]);
    $resp=curl_exec($ch); $http=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    if($err){ http_response_code(502); echo json_encode(['ok'=>false,'error'=>'scout/sims curl '.$err]); exit; }
    if($http>=400){ http_response_code($http); echo json_encode(['ok'=>false,'error'=>'scout/sims error','http'=>$http,'body'=>substr($resp,0,800)]); exit; }
    $data=json_decode($resp,true);
    $sims_raw = $data['sims'] ?? $data['data'] ?? $data;
    if(!is_array($sims_raw)) $sims_raw=[];
    $out=[];
    foreach($sims_raw as $item){
        if(!$item) continue;
        $sim=$item['sim']??$item;
        $out[]=[
            'id'=>$sim['id']??$item['id']??null,
            'phoneNumber'=>$sim['phoneNumber']??$sim['phone']??'?','status'=>$sim['status']??'?',
            'testsInCycle'=>$sim['testsInCycle']??$item['testsInCycle']??0,
            'cooldownEndsAt'=>$sim['cooldownEndsAt']??null,
            'carrier'=>$sim['carrier']??'?','raw'=>$item
        ];
    }
    echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'role'=>'scout','sims'=>$out,'count'=>count($out)]);
} catch(Exception $e){ http_response_code(500); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
