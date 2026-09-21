<?php
// api/goto.php?bot_id=20 — opens Scout & Runner with bot's bearer injected via helper page (new tab)
header('Content-Type: text/html; charset=utf-8');
$bot_id = $_GET['bot_id'] ?? $_GET['id'] ?? null;
if ($bot_id === null || $bot_id === '') {
    http_response_code(400);
    echo 'bot_id required';
    exit;
}
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->prepare("SELECT auth_token, proxy_email FROM bots WHERE id = :id");
$stmt->execute([':id' => (int)$bot_id]);
$row = $stmt->fetch();
$token = trim($row['auth_token'] ?? '');
if (!$token || strlen($token) < 20) {
    // No token - redirect to scout login with message
    header('Location: https://scoutandrunner.com/scout');
    exit;
}
if (stripos($token, 'Bearer ') === 0) $token = trim(substr($token, 7));
$proxy = $row['proxy_email'] ?? '';
$escToken = htmlspecialchars($token, ENT_QUOTES);
$escProxy = htmlspecialchars($proxy, ENT_QUOTES);
$botIdEsc = (int)$bot_id;
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Go to Scout — Bot <?=$botIdEsc?></title>
<style>body{margin:0;font-family:system-ui,sans-serif;background:#070b16;color:#e6edf7;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:16px}
.card{max-width:520px;width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:18px;backdrop-filter:blur(12px)}
.btn{padding:10px 14px;border-radius:10px;border:none;font-weight:800;cursor:pointer}
.btn-p{background:#2563eb;color:#fff} .btn-s{background:rgba(255,255,255,.08);color:#e6edf7;border:1px solid rgba(255,255,255,.1)}
.muted{color:#8ea0bd;font-size:12px}</style>
</head><body><div class="card">
<h3 style="margin:0 0 6px">Bot <?=$botIdEsc?> — <?=$escProxy?></h3>
<p class="muted" style="margin:0 0 10px">Token copied. This helper will inject it into <code>scoutandrunner.com</code> localStorage then open Scout. If popup blocked, click below.</p>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<button class="btn btn-p" id="go">↗ Open Scout (with token)</button>
<button class="btn btn-s" id="copy">Copy token</button>
</div>
<p class="muted" id="status" style="margin-top:10px"></p>
<pre style="white-space:break-all;word-break:break-all;background:rgba(0,0,0,.2);padding:8px;border-radius:10px;font-size:10px;max-height:120px;overflow:auto"><?=$escToken?></pre>
</div>
<script>
const TOKEN = <?= json_encode($token) ?>;
const BOT_ID = <?= json_encode($botIdEsc) ?>;
async function injectAndGo(){
  try{ await navigator.clipboard.writeText(TOKEN); }catch(e){}
  document.getElementById('status').textContent='Opening scoutandrunner.com — token injected via window.opener fallback. If not logged in, paste token in console: localStorage.setItem("cv-auth-storage", JSON.stringify({state:{token:TOKEN}}))';
  // Try to open Scout and postMessage token (Scout must listen) — fallback is manual copy
  const w = window.open('https://scoutandrunner.com/scout','_blank');
  if(!w){
    document.getElementById('status').textContent='Popup blocked — allow popups then click Open Scout again. Token is on clipboard.';
    return;
  }
  // Provide token via sessionStorage in helper's session then redirect helper itself after 1.5s so user lands on Scout
  try{
    // Store for bookmarklet-style manual injection: user can run in Scout console
    sessionStorage.setItem('scout_goto_token', TOKEN);
  }catch(e){}
  setTimeout(()=>{ location.href='https://scoutandrunner.com/scout'; }, 900);
}
document.getElementById('go').onclick = injectAndGo;
document.getElementById('copy').onclick = async ()=>{ try{ await navigator.clipboard.writeText(TOKEN); document.getElementById('status').textContent='Token copied — paste in Scout console if not auto-logged.'; }catch(e){} };
 // auto-go after 600ms so single click from dashboard feels instant
 setTimeout(injectAndGo, 600);
</script>
</body></html>
