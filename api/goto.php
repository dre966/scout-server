<?php
// api/goto.php?bot_id=20 — injects stored bearer into scoutandrunner.com storage (cross-origin requires console/bookmarklet)
header('Content-Type: text/html; charset=utf-8');
$bot_id = $_GET['bot_id'] ?? $_GET['id'] ?? null;
if ($bot_id === null || $bot_id === '') { http_response_code(400); echo 'bot_id required'; exit; }
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->prepare("SELECT auth_token, proxy_email FROM bots WHERE id = :id");
$stmt->execute([':id' => (int)$bot_id]);
$row = $stmt->fetch();
$token = trim($row['auth_token'] ?? '');
if (!$token || strlen($token) < 20) { header('Location: https://scoutandrunner.com/scout'); exit; }
if (stripos($token, 'Bearer ') === 0) $token = trim(substr($token, 7));
$proxy = $row['proxy_email'] ?? '';
$botIdEsc = (int)$bot_id;
// JS snippet that actually logs in (matches bot.py _extract_auth_token: cv-auth-storage {state:{token}})
$jsInject = "localStorage.setItem('cv-auth-storage', JSON.stringify({state:{token:".json_encode($token)."}})); localStorage.setItem('auth', ".json_encode($token)."); sessionStorage.setItem('auth', ".json_encode($token)."); location.href='https://scoutandrunner.com/scout';";
$bookmarklet = "javascript:(function(){".str_replace("'","\\'", $jsInject)."})()";
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Go to Scout — Bot <?=$botIdEsc?></title>
<style>body{margin:0;font-family:system-ui,sans-serif;background:#070b16;color:#e6edf7;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:14px}
.card{max-width:520px;width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:16px;backdrop-filter:blur(12px)}
.btn{padding:9px 12px;border-radius:10px;border:none;font-weight:800;cursor:pointer;font-size:13px}
.btn-p{background:#2563eb;color:#fff} .btn-s{background:rgba(255,255,255,.08);color:#e6edf7;border:1px solid rgba(255,255,255,.1)}
.muted{color:#8ea0bd;font-size:11px;line-height:1.4}
pre{white-space:break-all;word-break:break-all;background:rgba(0,0,0,.25);padding:8px;border-radius:10px;font-size:10px;max-height:110px;overflow:auto;border:1px solid rgba(255,255,255,.07)}
a{color:#93c5fd}
code{background:rgba(255,255,255,.08);padding:2px 6px;border-radius:6px;font-size:11px}</style>
</head><body><div class="card">
<h3 style="margin:0 0 4px">Bot <?=$botIdEsc?> — <?=htmlspecialchars($proxy,ENT_QUOTES)?></h3>
<p class="muted" style="margin:0 0 10px">Cross-origin blocks direct <code>localStorage</code> from this domain. Use the inject snippet on <code>scoutandrunner.com</code> itself.</p>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
<button class="btn btn-p" id="btnCopySnippet">Copy inject snippet</button>
<button class="btn btn-s" id="btnOpen">Open Scout</button>
<button class="btn btn-s" id="btnCopyToken">Copy token only</button>
</div>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:8px" class="muted">
<b style="color:#fecaca">Steps:</b> 1) Click <b>Copy inject snippet</b> → 2) <b>Open Scout</b> → 3) Press <b>F12 → Console</b> → <b>Paste (Ctrl+V) → Enter</b> → auto-redirects to <code>/scout</code> logged in.<br>
Or drag this <a id="bm" href="<?=htmlspecialchars($bookmarklet,ENT_QUOTES)?>">Scout Login</a> to bookmarks bar, then click it while on Scout.
</div>
<p class="muted" id="status" style="margin-top:8px"></p>
<pre id="snippet"><?=htmlspecialchars($jsInject,ENT_QUOTES)?></pre>
<pre><?=htmlspecialchars($token,ENT_QUOTES)?></pre>
</div>
<script>
const TOKEN = <?=json_encode($token)?>;
const SNIPPET = document.getElementById('snippet').textContent;
document.getElementById('btnCopySnippet').onclick = async ()=>{ try{ await navigator.clipboard.writeText(SNIPPET); document.getElementById('status').textContent='Snippet copied — open Scout, F12 Console, paste & Enter.'; }catch(e){ document.getElementById('status').textContent=SNIPPET; } };
document.getElementById('btnCopyToken').onclick = async ()=>{ try{ await navigator.clipboard.writeText(TOKEN); document.getElementById('status').textContent='Token copied.'; }catch(e){} };
document.getElementById('btnOpen').onclick = ()=> window.open('https://scoutandrunner.com/scout','_blank');
document.getElementById('bm').onclick = (e)=>{ e.preventDefault(); alert('Drag this link to your bookmarks bar. Then on scoutandrunner.com click the bookmark to inject.'); };
</script>
</body></html>
