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
function up_find_refresh($node){
    if(!is_array($node)) return null;
    foreach($node as $k=>$v){
        if(is_string($v) && stripos((string)$k,'refresh')!==false && strlen($v)>20) return $v;
    }
    foreach($node as $v){
        $r=up_find_refresh($v);
        if($r) return $r;
    }
    return null;
}
try{ $pdo->exec("CREATE TABLE IF NOT EXISTS bot_license_capture (bot_id INTEGER PRIMARY KEY, supabase_token TEXT, license_id TEXT, updated_at TIMESTAMPTZ DEFAULT NOW())"); }catch(Exception $e){}
try{ $pdo->exec("ALTER TABLE bot_license_capture ADD COLUMN IF NOT EXISTS raw_json TEXT"); }catch(Exception $e){}
try{ $pdo->exec("ALTER TABLE bot_license_capture ADD COLUMN IF NOT EXISTS refresh_token TEXT"); }catch(Exception $e){}
$method=$_SERVER['REQUEST_METHOD'];
if($method==='POST'){
    $raw=file_get_contents('php://input'); $data=json_decode($raw,true); if(!is_array($data)) $data=$_POST;
    $bot_id=$data['bot_id']??$data['id']??null;
    $supa=$data['supabaseToken']??$data['supabase_token']??null;
    $lic=$data['licenseId']??$data['license_id']??null;
    $rawJson=$data['rawJson']??$data['raw_json']??null;
    $refresh=null;
    if($rawJson){
        $rj=json_decode($rawJson,true);
        if(is_array($rj)) $refresh=up_find_refresh($rj);
    }
    if($bot_id===null||$bot_id===''){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'bot_id required']);exit;}
    $pdo->prepare("INSERT INTO bot_license_capture (bot_id, supabase_token, license_id, refresh_token, raw_json, updated_at) VALUES (:id,:supa,:lic,:ref,:raw,NOW()) ON CONFLICT (bot_id) DO UPDATE SET supabase_token=EXCLUDED.supabase_token, license_id=EXCLUDED.license_id, refresh_token=COALESCE(EXCLUDED.refresh_token, bot_license_capture.refresh_token), raw_json=EXCLUDED.raw_json, updated_at=NOW()")
        ->execute([':id'=>(int)$bot_id,':supa'=>$supa,':lic'=>$lic,':ref'=>$refresh,':raw'=>$rawJson]);
    echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'refreshToken'=>($refresh?'yes':'no')]);
    exit;
}
if($method==='GET'){
    $bot_id=$_GET['bot_id']??$_GET['id']??null;
    if($bot_id===null||$bot_id===''){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'bot_id required']);exit;}
    $stmt=$pdo->prepare("SELECT supabase_token, license_id, updated_at FROM bot_license_capture WHERE bot_id=:id");
    $stmt->execute([':id'=>(int)$bot_id]);
    $row=$stmt->fetch();
    if(!$row){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'no capture for bot']);exit;}
    echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'supabaseToken'=>$row['supabase_token'],'licenseId'=>$row['license_id'],'updated_at'=>$row['updated_at']]);
    exit;
}
http_response_code(405); echo json_encode(['ok'=>false,'error'=>'method not allowed']);
