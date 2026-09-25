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
try{ $pdo->exec("CREATE TABLE IF NOT EXISTS bot_unetwork (bot_id INTEGER PRIMARY KEY, code TEXT, supabase_token TEXT, unetwork_token TEXT, updated_at TIMESTAMPTZ DEFAULT NOW())"); }catch(Exception $e){}
$method=$_SERVER['REQUEST_METHOD'];
if($method==='POST'){
    $raw=file_get_contents('php://input'); $data=json_decode($raw,true); if(!is_array($data)) $data=$_POST;
    $bot_id=$data['bot_id']??$data['id']??null;
    $code=$data['code']??null;
    $supabaseToken=$data['supabaseToken']??null;
    $unetworkToken=$data['unetworkToken']??null;
    if($bot_id===null||$bot_id===''){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'bot_id required']);exit;}
    if(!$code){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'code required']);exit;}
    $pdo->prepare("INSERT INTO bot_unetwork (bot_id, code, supabase_token, unetwork_token, updated_at) VALUES (:id,:code,:sup,:unet,NOW()) ON CONFLICT (bot_id) DO UPDATE SET code=EXCLUDED.code, supabase_token=EXCLUDED.supabase_token, unetwork_token=EXCLUDED.unetwork_token, updated_at=NOW()")
        ->execute([':id'=>(int)$bot_id,':code'=>$code,':sup'=>$supabaseToken,':unet'=>$unetworkToken]);
    echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id]);
    exit;
}
if($method==='GET'){
    $bot_id=$_GET['bot_id']??$_GET['id']??null;
    $fresh=$_GET['fresh']??null;
    if($bot_id===null||$bot_id===''){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'bot_id required']);exit;}
    $stmt=$pdo->prepare("SELECT code, supabase_token, unetwork_token, updated_at FROM bot_unetwork WHERE bot_id=:id");
    $stmt->execute([':id'=>(int)$bot_id]);
    $row=$stmt->fetch();
    if($fresh!=='0'){
        $lc=$pdo->prepare("SELECT supabase_token, license_id FROM bot_license_capture WHERE bot_id=:id");
        $lc->execute([':id'=>(int)$bot_id]);
        $lr=$lc->fetch();
        if((!$lr || !$lr['supabase_token']) && $row && $row['supabase_token']){
            $lr=['supabase_token'=>$row['supabase_token'],'license_id'=>null];
            if(!$lr['license_id']){
                try{
                    $supaTmp=$lr['supabase_token'];
                    $ch=curl_init('https://api.unityedge.io/functions/v1/licenses_get_licenses');
                    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode(new stdClass()),CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$supaTmp,'apikey: sb_publishable_yKqi0fu5vV6G4ryUIMJuzw_NCoFEl1c','Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>true]);
                    $resp=curl_exec($ch); curl_close($ch);
                    $j=json_decode($resp,true);
                    $arr=$j['licenses']??$j['data']??$j??[];
                    if(is_array($arr) && $arr) $lr['license_id']=$arr[0]['id']??null;
                }catch(Exception $e){}
            }
        }
        if($lr && $lr['supabase_token'] && $lr['license_id']){
            $supa=$lr['supabase_token']; $lic=$lr['license_id'];
            $b=$pdo->prepare("SELECT proxy_email FROM bots WHERE id=:id");
            $b->execute([':id'=>(int)$bot_id]);
            $br=$b->fetch();
            $email=$br['proxy_email']??'';
            $ch=curl_init('https://auth.unityedge.io/api/auth/create-code');
            curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode(["supabaseToken"=>$supa,"licenseId"=>$lic,"email"=>$email]),CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$supa,'Content-Type: application/json','X-Unity-Api-Key: unet-sharable:aIa_p693uDWFJLrfwsR0za53CG05UgpIB_hzTFc61I4','Origin: https://scoutandrunner.com','Referer: https://scoutandrunner.com/'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>true]);
            $resp=curl_exec($ch); $http=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
            $j=json_decode($resp,true);
            $newCode=$j['code']??$j['data']['code']??null;
            if($newCode && $http>=200 && $http<300){
                if($row){
                    $pdo->prepare("UPDATE bot_unetwork SET code=:code, updated_at=NOW() WHERE bot_id=:id")->execute([':code'=>$newCode,':id'=>(int)$bot_id]);
                } else {
                    $pdo->prepare("INSERT INTO bot_unetwork (bot_id, code, supabase_token, updated_at) VALUES (:id,:code,:sup,NOW()) ON CONFLICT (bot_id) DO UPDATE SET code=EXCLUDED.code, updated_at=NOW()")->execute([':id'=>(int)$bot_id,':code'=>$newCode,':sup'=>$supa]);
                }
                if(!$row) $row=['code'=>$newCode,'supabase_token'=>$supa,'unetwork_token'=>$supa,'updated_at'=>date('c')];
                else { $row['code']=$newCode; $row['updated_at']=date('c'); }
            } else if(!$row){
                http_response_code(404); echo json_encode(['ok'=>false,'error'=>'no code for bot and fresh create failed','http'=>$http,'body'=>substr($resp??'',0,300)]); exit;
            }
        } else if(!$row){
            http_response_code(404); echo json_encode(['ok'=>false,'error'=>'no capture for bot — bot must hit license_select to store supabase+license']); exit;
        }
    }
    if(!$row){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'no code for bot']);exit;}
    echo json_encode(['ok'=>true,'bot_id'=>(int)$bot_id,'code'=>$row['code'],'supabaseToken'=>$row['supabase_token'],'unetworkToken'=>$row['unetwork_token'],'updated_at'=>$row['updated_at']]);
    exit;
}
http_response_code(405); echo json_encode(['ok'=>false,'error'=>'method not allowed']);
