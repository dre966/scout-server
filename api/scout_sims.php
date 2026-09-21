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

    function scout_req($token,$method,$path,$body=null){
        $url='https://scoutandrunner.com/api'.$path;
        $headers=['Authorization: Bearer '.$token,'apikey: '.$token,'Content-Type: application/json','Accept: application/json, text/plain, */*','Origin: https://scoutandrunner.com','Referer: https://scoutandrunner.com/scout/test-numbers'];
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>$headers,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_SSL_VERIFYPEER=>true]);
        if($body!==null) curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body));
        $resp=curl_exec($ch); $http=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
        return [$http,$resp,$err];
    }
    function ensure_scout($token){
        [$http,$resp]=scout_req($token,'GET','/auth/me');
        if($http===200&&$resp){ $j=json_decode($resp,true); $role=$j['user']['role']??$j['role']??null; if($role==='scout') return true; scout_req($token,'POST','/auth/switch-role', new stdClass()); sleep(1); }
        return true;
    }
    ensure_scout($token);
    [$http,$resp,$err]=scout_req($token,'GET','/scout/sims');
    if($err){ http_response_code(502); echo json_encode(['ok'=>false,'error'=>'curl '.$err]); exit; }
    if($http>=400){ http_response_code($http); echo json_encode(['ok'=>false,'error'=>'scout API error','http'=>$http,'body'=>substr($resp,0,800)]); exit; }
    $data=json_decode($resp,true);
    $sims_raw = $data;
    if(isset($data['sims'])) $sims_raw=$data['sims'];
    elseif(isset($data['data'])) $sims_raw=$data['data'];
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
    echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'sims'=>$out,'count'=>count($out)]);
} catch(Exception $e){ http_response_code(500); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
