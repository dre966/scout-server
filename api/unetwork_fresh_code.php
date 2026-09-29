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
require_once __DIR__ . '/_unet_auth.php';
$bot_id=$_GET['bot_id']??$_POST['bot_id']??null;
if($bot_id===null||$bot_id===''){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'bot_id required']);exit;}
try{
    $b=$pdo->prepare("SELECT proxy_email FROM bots WHERE id=:id");
    $b->execute([':id'=>(int)$bot_id]);
    $br=$b->fetch();
    $email=$br['proxy_email']??'';

    list($code,$http,$resp,$supa,$lic)=unet_fresh_code_for_bot($pdo,(int)$bot_id,$email);
    if(!$code){
        if($resp==='no capture'){
            http_response_code(404); echo json_encode(['ok'=>false,'error'=>'no supabase+license capture for bot — hit license_select first']); exit;
        }
        http_response_code(502); echo json_encode(['ok'=>false,'error'=>'create-code failed','http'=>$http,'body'=>substr((string)$resp,0,400)]);
        exit;
    }
    // also update bot_unetwork for history
    try{ $pdo->prepare("INSERT INTO bot_unetwork (bot_id, code, supabase_token, updated_at) VALUES (:id,:code,:sup,NOW()) ON CONFLICT (bot_id) DO UPDATE SET code=EXCLUDED.code, supabase_token=EXCLUDED.supabase_token, updated_at=NOW()")->execute([':id'=>(int)$bot_id,':code'=>$code,':sup'=>$supa]); }catch(Exception $e){}
    echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'code'=>$code,'supabaseToken'=>$supa,'licenseId'=>$lic]);
}catch(Exception $e){http_response_code(500);echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}
