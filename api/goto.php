<?php
// api/goto.php?bot_id=20 — phone-friendly: one-tap bookmarklet (auto-ish, no F12)
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
$jsInject = "localStorage.setItem('cv-auth-storage', JSON.stringify({state:{token:".json_encode($token)."}})); localStorage.setItem('auth', ".json_encode($token)."); sessionStorage.setItem('auth', ".json_encode($token)."); location.href='https://scoutandrunner.com/scout';";
$bookmarklet = "javascript:(function(){".str_replace("'","\\'", $jsInject)."})()";
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Go to Scout — Bot <?=$botIdEsc?></title>
<style>*{box-sizing:border-box}body{margin:0;font-family:system-ui,sans-serif;background:#070b16;color:#e6edf7;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:12px}
.card{max-width:480px;width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:14px;backdrop-filter:blur(12px)}
.btn{width:100%;padding:14px 12px;border-radius:12px;border:none;font-weight:800;cursor:pointer;font-size:15px}
.btn-p{background:#2563eb;color:#fff} .btn-s{background:rgba(255,255,255,.08);color:#e6edf7;border:1px solid rgba(255,255,255,.1)}
.muted{color:#8ea0bd;font-size:12px;line-height:1.4}
pre{white-space:break-all;word-break:break-all;background:rgba(0,0,0,.25);padding:8px;border-radius:10px;font-size:10px;max-height:100px;overflow:auto;border:1px solid rgba(255,255,255,.07)}
a{color:#93c5fd}
.step{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:10px;display:flex;gap:10px;align-items:flex-start}
.num{background:#2563eb;color:#fff;min-width:28px;height:28px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-weight:800}
code{background:rgba(255,255,255,.08);padding:2px 6px;border-radius:6px}</style>
</head><body><div class="card">
<h3 style="margin:0 0 2px">Bot <?=$botIdEsc?> — <?=htmlspecialchars($proxy,ENT_QUOTES)?></h3>
<p class="muted" style="margin:0 0 10px">Phone: no F12. Save bookmarklet once, then tap it on Scout to auto-login.</p>

<div style="display:grid;gap:8px;margin-bottom:10px">
<button class="btn btn-p" id="btnCopySnippet">① Copy inject snippet</button>
<button class="btn btn-s" id="btnOpen">② Open Scout</button>
<a class="btn btn-s" id="bm" href="<?=htmlspecialchars($bookmarklet,ENT_QUOTES)?>" style="text-align:center;text-decoration:none;display:block">★ Hold → Add bookmark: Scout Login</a>
</div>

<div style="display:grid;gap:8px">
<div class="step"><div class="num">1</div><div class="muted"><b style="color:#fff">Copy snippet</b> — tap above. Then <b>Open Scout</b>.</div></div>
<div class="step"><div class="num">2</div><div class="muted">On <code>scoutandrunner.com</code> address bar, type <b>Scout Login</b> → tap the bookmark. Instant login — no console. (Chrome Android: tap ★ → edit → paste bookmarklet if hold-add fails.)</div></div>
<div class="step"><div class="num">3</div><div class="muted">Alternatively after opening Scout: tap address bar → type <code>javascript:</code> → paste snippet → Go. Or paste snippet in <code>chrome://inspect</code> console.</div></div>
</div>

<p class="muted" id="status" style="margin-top:8px"></p>
<pre id="snippet"><?=htmlspecialchars($jsInject,ENT_QUOTES)?></pre>
</div>
<script>
const SNIPPET = document.getElementById('snippet').textContent;
const TOKEN = <?=json_encode($token)?>;
document.getElementById('btnCopySnippet').onclick = async ()=>{ try{ await navigator.clipboard.writeText(SNIPPET); document.getElementById('status').textContent='Snippet copied — now tap Open Scout, then tap bookmark Scout Login.'; }catch(e){ document.getElementById('status').textContent=SNIPPET; } };
document.getElementById('btnOpen').onclick = ()=> window.open('https://scoutandrunner.com/scout','_blank');
document.getElementById('bm').addEventListener('click', (e)=>{ 
  // On phone, tap shows how to add; long-press is real add
  if(!confirm('Long-press this link → Add bookmark / Add to bookmarks. Then on scoutandrunner.com, tap address bar, type \"Scout Login\" and select bookmark to auto-login. Tap OK to try bookmarklet now (only works on scoutandrunner.com).')) e.preventDefault();
});
</script>
</body></html>
