<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Bot-Token');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}
$expected=getenv('BOT_TOKEN')?:'scout-secret';
$provided=$_SERVER['HTTP_X_BOT_TOKEN']??null;
if($provided!==$expected){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'unauthorized']);exit;}
require_once __DIR__ . '/../config/db.php';
$bot_id=$_GET['bot_id']??$_POST['bot_id']??null;
if($bot_id===null||$bot_id===''){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'bot_id required']);exit;}
try{
    $lc=$pdo->prepare("SELECT supabase_token, license_id FROM bot_license_capture WHERE bot_id=:id");
    $lc->execute([':id'=>(int)$bot_id]);
    $lr=$lc->fetch();
    if(!$lr || !$lr['supabase_token'] || !$lr['license_id']){
        http_response_code(404); echo json_encode(['ok'=>false,'error'=>'no supabase+license capture for bot — hit license_select first']);
        exit;
    }
    $supa=$lr['supabase_token']; $lic=$lr['license_id'];
    $b=$pdo->prepare("SELECT proxy_email FROM bots WHERE id=:id");
    $b->execute([':id'=>(int)$bot_id]);
    $br=$b->fetch();
    $email=$br['proxy_email']??'';
    $ch=curl_init('https://auth.unityedge.io/api/auth/create-code');
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode(["supabaseToken"=>$supa,"licenseId"=>$lic,"email"=>$email]),CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$supa,'Content-Type: application/json','X-Unity-Api-Key: unet-sharable:aIa_p693uDWFJLrfwsR0za53CG05UgpIB_hzTFc61I4','Origin: https://scoutandrunner.com','Referer: https://scoutandrunner.com/'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>true]);
    $resp=curl_exec($ch); $http=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    $j=json_decode($resp,true);
    $code=$j['code']??$j['data']['code']??null;
    if(!$code || $http<200 || $http>=300){
        http_response_code(502); echo json_encode(['ok'=>false,'error'=>'create-code failed','http'=>$http,'body'=>substr($resp??'',0,400)]);
        exit;
    }
    // also update bot_unetwork for history
    try{ $pdo->prepare("INSERT INTO bot_unetwork (bot_id, code, supabase_token, updated_at) VALUES (:id,:code,:sup,NOW()) ON CONFLICT (bot_id) DO UPDATE SET code=EXCLUDED.code, supabase_token=EXCLUDED.supabase_token, updated_at=NOW()")->execute([':id'=>(int)$bot_id,':code'=>$code,':sup'=>$supa]); }catch(Exception $e){}
    echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'code'=>$code,'supabaseToken'=>$supa,'licenseId'=>$lic]);
}catch(Exception $e){http_response_code(500);echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}
