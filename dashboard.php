<?php
$BOT_TOKEN = getenv('BOT_TOKEN') ?: 'scout-secret';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Scout Fleet — Note 9 Glass</title>
<style>
/* — Minimalist glass — optimized for Galaxy Note 9 (360×740 CSS, thumb reach) — */
*{box-sizing:border-box;margin:0;padding:0}
:root{--glass:rgba(255,255,255,.045);--glass-2:rgba(255,255,255,.08);--line:rgba(255,255,255,.07);--text:#e6edf7;--muted:#8ea0bd;--accent:#3b82f6;--danger:#ef4444;--ok:#22c55e;--warn:#eab308;--r:14px;--blur:14px}
html,body{height:100%}
body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:
  radial-gradient(900px 600px at 20% -10%, rgba(59,130,246,.14), transparent 65%),
  linear-gradient(180deg,#070b14 0%,#0a1226 100%);color:var(--text);line-height:1.4;-webkit-font-smoothing:antialiased}
a{color:inherit}
header{position:sticky;top:0;z-index:20;display:flex;align-items:center;gap:8px;padding:7px 10px;
  background:rgba(10,18,38,.42);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
  border-bottom:1px solid var(--line)}
header h1{font-size:13px;font-weight:800;letter-spacing:.03em;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.meta{font-size:10px;color:var(--muted);white-space:nowrap}
.wrap{max-width:1100px;margin:0 auto;padding:6px 6px 72px}
.card{background:var(--glass);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
  border:1px solid var(--line);border-radius:var(--r);overflow:hidden;box-shadow:none;margin-bottom:8px}
.card-h{display:flex;align-items:center;justify-content:space-between;gap:6px;padding:8px 10px;border-bottom:1px solid var(--line);background:transparent}
.card-h h2{font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#9fb0cc}
.badge{padding:2px 8px;border-radius:999px;font-size:11px;font-weight:800;display:inline-block}
.badge-green{background:rgba(34,197,94,.15);color:#86efac;border:1px solid rgba(34,197,94,.3)}
.badge-yellow{background:rgba(234,179,8,.14);color:#fde68a;border:1px solid rgba(234,179,8,.3)}
.badge-red{background:rgba(239,68,68,.14);color:#fecaca;border:1px solid rgba(239,68,68,.3)}
.badge-gray{background:rgba(255,255,255,.06);color:#cbd5e1;border:1px solid var(--line)}
.dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:6px;vertical-align:middle}
.dot-green{background:var(--ok);box-shadow:0 0 8px rgba(34,197,94,.7)}
.dot-yellow{background:var(--warn);box-shadow:0 0 8px rgba(234,179,8,.7)}
.dot-red{background:var(--danger);box-shadow:0 0 8px rgba(239,68,68,.7)}
.controls{display:flex;flex-wrap:wrap;gap:6px;padding:8px}
.controls input,.controls select{padding:7px 9px;border-radius:10px;border:1px solid var(--line);background:rgba(255,255,255,.05);color:var(--text);font-size:12px;min-height:36px;outline:none}
.controls input:focus,.controls select:focus{border-color:rgba(59,130,246,.4);background:rgba(255,255,255,.07)}
.btn{padding:7px 11px;border:none;border-radius:10px;cursor:pointer;font-weight:700;font-size:11px;letter-spacing:.01em;min-height:36px;transition:.12s}
.btn:active{transform:scale(.98)}
.btn-primary{background:#2563eb;color:#fff;box-shadow:none}
.btn-secondary{background:rgba(255,255,255,.06);color:var(--text);border:1px solid var(--line)}
.btn-danger{background:rgba(239,68,68,.11);color:#fecaca;border:1px solid rgba(239,68,68,.22)}
.btn:disabled{opacity:.5;cursor:not-allowed}
.table-wrap{overflow:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:separate;border-spacing:0;font-size:11px;min-width:0}
th,td{padding:6px 8px;text-align:left;border-bottom:1px solid var(--line);white-space:nowrap}
th{position:sticky;top:0;background:rgba(10,18,38,.6);backdrop-filter:blur(6px);color:var(--muted);font-weight:700;letter-spacing:.04em;text-transform:uppercase;font-size:9px}
tr:hover td{background:rgba(255,255,255,.02)}
tr.selected td{background:rgba(59,130,246,.10)}
.logs{max-height:38vh;overflow:auto;padding:6px;font-family:ui-monospace,Consolas,monospace;font-size:10px;background:rgba(0,0,0,.14);border-top:1px solid var(--line)}
.log-line{padding:4px 0;border-bottom:1px solid rgba(255,255,255,.04);word-break:break-all}
.log-time{color:#7a8aa6}
.log-state{color:#7dd3fc}
.muted{color:var(--muted);font-size:11px;padding:6px 10px}
.flex{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.grid{display:grid;gap:12px}
/* — tabbed pages — */
.tab{display:none}
.tab.active{display:block;animation:fade .18s ease}
@keyframes fade{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
.nav{position:fixed;bottom:0;left:0;right:0;z-index:30;display:flex;gap:4px;padding:6px 6px calc(6px + env(safe-area-inset-bottom));background:rgba(7,11,22,.55);backdrop-filter:blur(12px);border-top:1px solid var(--line)}
.nav button{flex:1;display:flex;flex-direction:column;align-items:center;gap:1px;padding:6px 2px;border-radius:12px;border:1px solid transparent;background:transparent;color:var(--muted);font-weight:700;font-size:9px;letter-spacing:.06em;text-transform:uppercase}
.nav button.active{background:rgba(255,255,255,.06);border-color:var(--line);color:var(--text)}
.nav button span.i{font-size:14px;line-height:1}
.top-tabs{display:flex;gap:6px;padding:0 10px 8px}
.top-tabs button{padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;border:1px solid var(--line);background:rgba(255,255,255,.06);color:var(--muted)}
.top-tabs button.active{background:#fff;color:#0a1226;border-color:#fff}
@media(min-width:900px){.grid{grid-template-columns:1.7fr 1fr}}
.banner{display:none;margin:10px;border-radius:14px;padding:10px 12px;font-weight:800;font-size:13px;border:1px solid}
.banner-red{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35);color:#fecaca}
.banner-amber{background:rgba(234,179,8,.12);border-color:rgba(234,179,8,.3);color:#fde68a}
/* Glass modal */
.overlay{position:fixed;inset:0;background:rgba(3,7,18,.55);backdrop-filter:blur(10px);display:none;align-items:center;justify-content:center;z-index:50;padding:16px}
.sheet{width:min(420px,96vw);background:rgba(18,27,52,.82);backdrop-filter:blur(var(--blur)) saturate(150%);border:1px solid var(--line);border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.sheet-h{padding:14px 16px;border-bottom:1px solid var(--line);font-weight:800}
.sheet-b{padding:14px 16px;display:grid;gap:10px}
.sheet-b input{width:100%}
.sheet-f{display:flex;gap:8px;justify-content:flex-end;padding:12px 16px;border-top:1px solid var(--line);background:rgba(255,255,255,.02)}
/* Note 9 thumb bar */
.tabbar{display:none}
@media(max-width:700px){
  header h1{font-size:14px}
  .controls input,.controls select{flex:1 1 100%}
  table{min-width:620px}
}
@media(max-width:420px){
  /* Galaxy Note 9 narrow: stack, larger touch */
  .wrap{padding:8px 8px 90px}
  .card-h{padding:10px}
  .btn{flex:1 1 auto}
}
</style>
</head>
<body>
<header>
  <h1>⬢ Scout Fleet</h1>
  <span class="meta" id="status">—</span>
  <span class="meta" id="clock"></span>
  <button class="btn btn-secondary" onclick="pollNow()" style="min-height:36px;padding:8px 12px">↻</button>
</header>
<div class="wrap">
  <div id="bannerHigh" class="banner banner-red"></div>
  <div id="bannerWarn" class="banner banner-amber"></div>
  <div class="top-tabs" id="topTabs">
    <button id="tFleet" class="active" onclick="showTab('fleet')">Fleet</button>
    <button id="tLive" onclick="showTab('live')">Live</button>
    <button id="tDevices" onclick="showTab('devices')">Devices</button>
    <button id="tAlerts" onclick="showTab('alerts')">Alerts</button>
  </div>

  <!-- FLEET PAGE -->
  <div id="tab-fleet" class="tab active">
    <div class="card">
      <div class="card-h"><h2>Bots <span id="botCount" style="opacity:.7">0</span></h2>
        <span class="flex"><input id="filter" placeholder="filter" oninput="render()" style="width:120px;min-height:32px"><select id="autoPoll" onchange="resetTimer()" style="min-height:32px"><option value="2000" selected>2s</option><option value="5000">5s</option><option value="0">off</option></select></span>
      </div>
      <div class="table-wrap" style="max-height:56vh"><table><thead><tr><th>#</th><th>State</th><th>Proxy</th><th>HB</th><th></th></tr></thead><tbody id="tbody"></tbody></table></div>
      <div id="botsMuted" class="muted" style="display:none">No bots. Bots POST to <code>api/register.php</code></div>
      <div class="controls">
        <input id="customBotId" placeholder="bot_id" type="number" style="width:80px">
        <select id="cmdSelect" onchange="onCmdChange()"><option value="PAUSE">PAUSE</option><option value="RESUME">RESUME</option><option value="RESTART">RESTART</option><option value="STOP">STOP</option><option value="REFRESH">REFRESH</option><option value="LOGOUT">LOGOUT</option></select>
        <input id="cmdArgs" placeholder='args {"wait":60}' style="flex:1;min-width:110px">
        <button class="btn btn-primary" onclick="sendCustomCommand()">Send</button>
        <span id="logoutHint" class="flex" style="display:none;width:100%"><input id="logoutWait" type="number" min="0" max="86400" value="60" style="width:88px"> <button class="btn btn-danger" onclick="sendLogout()">Logout &amp; Wait</button></span>
      </div>
      <div class="muted" style="border-top:1px solid var(--line)">Tap a row → Live tab.</div>
    </div>
  </div><!-- /fleet -->

  <!-- LIVE PAGE -->
  <div id="tab-live" class="tab">
    <div class="card"><div class="card-h"><h2>Live — Bot <span id="liveId">—</span></h2><span class="flex"><button class="btn btn-secondary" onclick="loadLogs()">Logs</button><button class="btn btn-secondary" onclick="loadNotifications()">🔔</button><button class="btn btn-primary" onclick="openCallStatus()">Call Status</button></span></div>
      <div class="controls"><button class="btn btn-secondary" onclick="sendCmd('PAUSE')">Pause</button><button class="btn btn-secondary" onclick="sendCmd('RESUME')">Resume</button><button class="btn btn-danger" onclick="sendCmd('RESTART')">Restart</button>
        <span class="flex" style="width:100%"><input id="logoutWaitLive" type="number" min="0" max="86400" value="60" style="width:88px"><button class="btn btn-danger" onclick="sendLogoutLive()" style="flex:1">Logout &amp; Wait</button></span>
      </div>
      <div id="botDetail" class="muted">No bot selected — tap a row in Fleet.</div>
      <div id="logs" class="logs" style="display:none;max-height:46vh"></div>
      <div id="notifs" class="logs" style="display:none;max-height:46vh"></div>
      <div id="liveLogs" class="logs" style="display:none"></div>
    </div>
  </div>
  <!-- CALL STATUS (opens as page within Live) -->
  <div id="callStatusOverlay" class="overlay" onclick="if(event.target===this) closeCallStatus()">
    <div class="sheet" style="max-width:520px">
      <div class="sheet-h">Call Status — Bot <span id="csBotId">—</span> <span id="csUpdated" class="muted" style="padding:0"></span> <button class="btn btn-secondary" style="float:right;min-height:28px;padding:4px 8px" onclick="closeCallStatus()">✕</button></div>
      <div class="sheet-b" id="csBody" style="max-height:64vh;overflow:auto"></div>
      <div class="sheet-f"><button class="btn btn-secondary" onclick="closeCallStatus()">Close</button><button class="btn btn-primary" onclick="refreshCallStatus()">Refresh</button></div>
    </div>
  </div>

  <!-- DEVICES PAGE -->
  <div id="tab-devices" class="tab">
  <div class="card">
    <div class="card-h"><h2>Devices &amp; SIMs</h2><button class="btn btn-primary" id="btnLoadTokens" onclick="loadDevicesAndTokens()" style="min-height:36px">Load tokens</button></div>
    <div class="muted">Heartbeat devices → Load tokens (asks bot for bearer) → select device → Delete or Register.</div>
    <div class="table-wrap" style="max-height:32vh"><table><thead><tr><th><input type="checkbox" id="chkAll" onchange="toggleAll(this.checked)"></th><th>Bot</th><th>Proxy</th><th>Token</th><th>State</th><th>HB</th></tr></thead><tbody id="tokenTbody"></tbody></table></div>
    <div id="tokenStatus" class="muted">No tokens yet.</div>
    <div class="controls" id="postTokenControls" style="display:none">
      <button class="btn btn-primary" onclick="goToSite()">↗ Go to site</button>
      <button class="btn btn-danger" onclick="deleteAccountNow()">Delete Account</button>
      <button class="btn btn-primary" onclick="openSimRegisterFlow()">Register SIMs</button>
      <span class="muted" style="font-size:11px">Select checkbox first. Go to site opens Scout in new tab (token auto-copied).</span>
    </div>
    <div id="simMappingArea" style="display:none;border-top:1px solid var(--line);padding:10px">
      <div class="flex" style="margin-bottom:8px"><label style="font-size:13px">Device <select id="simBotSelect"></select></label><button class="btn btn-secondary" onclick="fetchSimsAndPackages()">Fetch SIMs &amp; Packages</button><span id="simFetchStatus" class="muted"></span></div>
      <div id="simMappingTableWrap" class="table-wrap" style="max-height:38vh"></div>
      <div class="controls"><button class="btn btn-primary" id="btnRegisterSims" onclick="registerSims()" disabled>Register</button><span class="muted">Empty = skip. Posts to <code>/runner/sim/{id}/package</code>.</span></div>
      <div id="registerResult" class="logs" style="display:none"></div>
    </div>
  </div>

  </div><!-- /devices -->

  <!-- ALERTS PAGE -->
  <div id="tab-alerts" class="tab">
    <div class="card"><div class="card-h"><h2>Notifications</h2></div><div id="globalNotifs" class="logs" style="max-height:60vh"></div></div>
  </div>
</div><!-- /wrap -->
<nav class="nav" id="bottomNav">
  <button id="nFleet" class="active" onclick="showTab('fleet')"><span class="i">⬢</span>Fleet</button>
  <button id="nLive" onclick="showTab('live')"><span class="i">◉</span>Live</button>
  <button id="nDevices" onclick="showTab('devices')"><span class="i">▦</span>Devices</button>
  <button id="nAlerts" onclick="showTab('alerts')"><span class="i">⚑</span>Alerts</button>
</nav>

<!-- Glass delete modal -->
<div id="deleteOverlay" class="overlay" onclick="if(event.target===this) closeDeleteModal()">
  <div class="sheet">
    <div class="sheet-h">Delete Account <span style="font-weight:400;color:var(--muted)">— Bot <span id="deleteBotId">—</span></span></div>
    <div class="sheet-b">
      <div class="muted" style="padding:0">This calls <code>DELETE https://scoutandrunner.com/api/auth/delete-account</code> with <code>{"confirmation":"DELETE_MY_ACCOUNT"}</code> using the bot's stored token. Irreversible.</div>
      <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:12px;padding:10px;font-size:12px;color:#fecaca">Type <b>DELETE_MY_ACCOUNT</b> to confirm.</div>
      <input id="deleteConfirm" placeholder="DELETE_MY_ACCOUNT" autocomplete="off" spellcheck="false">
      <div id="deleteResult" class="muted" style="display:none"></div>
    </div>
    <div class="sheet-f"><button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button><button class="btn btn-danger" id="btnDeleteConfirm" onclick="confirmDeleteAccount()">Delete</button></div>
  </div>
</div>

<script>
const TOKEN = <?= json_encode($BOT_TOKEN) ?>;
const HEADERS = {'Content-Type':'application/json','X-Bot-Token': TOKEN};
let bots = [];
let selected = null;
let timer = null;

function fmtAge(heartbeat_at){
  if(!heartbeat_at) return '<span class="badge badge-gray">never</span>';
  let s = heartbeat_at.replace(' ','T');
  if(!/[Z+\-]/.test(s.slice(10))) s += 'Z';
  else if(/\+\d{2}$/.test(s)) s += ':00';
  const t = new Date(s);
  const diff = Math.floor((Date.now()-t.getTime())/1000);
  if(isNaN(diff)) return heartbeat_at;
  let cls='badge-red', dot='dot-red', label=diff+'s ago';
  if(diff<15){cls='badge-green'; dot='dot-green';}
  else if(diff<60){cls='badge-yellow'; dot='dot-yellow';}
  else if(diff<300){cls='badge-yellow';}
  if(diff<60) label=diff+'s ago';
  else if(diff<3600) label=Math.floor(diff/60)+'m ago';
  else label=Math.floor(diff/3600)+'h ago';
  return `<span class="dot ${dot}"></span><span class="badge ${cls}">${label}</span>`;
}
function esc(s){return String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]))}

async function fetchBots(){
  try{
    const r = await fetch('api/state.php', {headers: HEADERS});
    const j = await r.json();
    if(j.ok){ bots=j.bots||[]; render(); renderGlobalNotifs(j.notifications||[]); document.getElementById('status').textContent=bots.length+' bots • '+new Date().toLocaleTimeString(); }
    else document.getElementById('status').textContent='error: '+(j.error||'unknown');
    if(selected) loadLogs(false);
  }catch(e){ document.getElementById('status').textContent='poll '+e.message; }
}
function render(){
  const q = document.getElementById('filter').value.toLowerCase();
  const tbody=document.getElementById('tbody');
  const count=document.getElementById('botCount');
  const muted=document.getElementById('botsMuted');
  let filtered=bots;
  if(q) filtered=bots.filter(b=> String(b.id).includes(q) || (b.proxy_email||'').toLowerCase().includes(q) || (b.state||'').toLowerCase().includes(q));
  count.textContent=filtered.length;
  muted.style.display = filtered.length? 'none':'block';
  tbody.innerHTML = filtered.map(b=>`
    <tr class="${selected==b.id?'selected':''}" onclick="selectBot(${b.id})" style="cursor:pointer">
      <td><b>${esc(b.id)}</b></td>
      <td><span class="badge ${b.state==='running'?'badge-green':b.state==='paused'?'badge-yellow':'badge-gray'}" style="font-size:10px;padding:1px 6px">${esc((b.state||'—').slice(0,14))}</span></td>
      <td title="${esc(b.proxy_email)}">${esc((b.proxy_email||'').split('@')[0].slice(0,16))}</td>
      <td>${fmtAge(b.heartbeat_at)}</td>
      <td><button class="btn btn-secondary" onclick="event.stopPropagation(); selectBot(${b.id}); sendCmd('RESTART')" style="min-height:28px;padding:4px 8px;font-size:10px">↻</button></td>
    </tr>
  `).join('');
}
function renderGlobalNotifs(list){
  const el=document.getElementById('globalNotifs');
  const bHigh=document.getElementById('bannerHigh');
  const bWarn=document.getElementById('bannerWarn');
  const high = list.filter(n=> ['nosimsregistered','nonumberstotest','accountdeleted'].includes(String(n.type||'').toLowerCase()));
  const warn = list.filter(n=> !high.includes(n));
  if(bHigh){
    if(high.length){ bHigh.style.display='block'; bHigh.innerHTML='🚨 '+high.map(h=>`${esc(h.type)} — Bot ${esc(h.bot_id)}: ${esc(h.message)} <span style="opacity:.7">(${fmtLogAge(h.created_at)})</span>`).join(' • '); }
    else { bHigh.style.display='none'; bHigh.innerHTML=''; }
  }
  if(bWarn){
    if(warn.length && high.length===0){ bWarn.style.display='block'; bWarn.textContent=warn[0].type+': '+warn[0].message; }
    else { bWarn.style.display='none'; }
  }
  if(!list.length){ el.innerHTML='<div class="muted">No notifications</div>'; return; }
  el.innerHTML=list.map(n=>{
    const t=String(n.type||'').toLowerCase();
    const isHigh=['nosimsregistered','nonumberstotest','accountdeleted'].includes(t);
    const bg = isHigh ? 'background:rgba(239,68,68,.10);border-left:3px solid #ef4444;padding-left:6px;' : '';
    const col = isHigh ? '#fecaca' : '#fbbf24';
    const badge = isHigh ? ' <span class="badge badge-red">HIGH</span>' : '';
    return `<div class="log-line" style="${bg}"><span class="log-time" title="${esc(n.created_at)}">${esc(fmtLogAge(n.created_at))}</span> <b>[${esc(n.bot_id)}]</b> <span style="color:${col}">${esc(n.type)}</span>${badge} ${esc(n.message)} <span style="color:#64748b">${esc((n.details||'').slice(0,180))}</span></div>`;
  }).join('');
}

function showTab(name){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  document.querySelectorAll('#topTabs button, #bottomNav button').forEach(b=>b.classList.remove('active'));
  const m={fleet:['tFleet','nFleet'],live:['tLive','nLive'],devices:['tDevices','nDevices'],alerts:['tAlerts','nAlerts']}[name]||[];
  m.forEach(id=>{ const el=document.getElementById(id); if(el) el.classList.add('active'); });
  if(name==='live' && selected) loadLogs(false);
  location.hash=name;
}
window.addEventListener('hashchange',()=>{ const h=location.hash.replace('#',''); if(['fleet','live','devices','alerts'].includes(h)) showTab(h); });
async function selectBot(id){
  selected=id;
  try{ document.getElementById('liveId').textContent=id; }catch(e){}
  try{ const l2=document.getElementById('liveId2'); if(l2) l2.textContent=id; }catch(e){}
  try{ const c=document.getElementById('customBotId'); if(c) c.value=id; }catch(e){}
  render();
  showTab('live');
  // update detail + logs
  const det=document.getElementById('botDetail');
  if(det){ det.style.display='block'; det.textContent='Bot '+id+' — loading…'; }
  const logsEl=document.getElementById('logs'); if(logsEl) logsEl.style.display='block';
  const notifsEl=document.getElementById('notifs'); if(notifsEl) notifsEl.style.display='none';
  await loadLogs(false);
}
function fmtLogAge(s){
  if(!s) return '';
  let t=s.replace(' ','T'); if(!/[Z+\-]/.test(t.slice(10))) t+='Z'; else if(/\+\d{2}$/.test(t)) t+=':00';
  const d=new Date(t); const diff=Math.floor((Date.now()-d.getTime())/1000);
  if(isNaN(diff)) return esc(s);
  if(diff<60) return diff+'s ago';
  if(diff<3600) return Math.floor(diff/60)+'m ago';
  return Math.floor(diff/3600)+'h ago';
}
async function loadLogs(showNotif){
  if(!selected) return;
  try{
    const r=await fetch('api/state.php?bot_id='+selected, {headers: HEADERS});
    const j=await r.json();
    if(!j.ok) throw new Error(j.error);
    const logsEl=document.getElementById('logs');
    const liveEl=document.getElementById('liveLogs');
    const html = (j.logs && j.logs.length) ? j.logs.map(l=>{
      let t=l.created_at.replace(' ','T'); if(!/[Z+\-]/.test(t.slice(10))) t+='Z'; else if(/\+\d{2}$/.test(t)) t+=':00';
      const age=fmtLogAge(l.created_at);
      return `<div class="log-line"><span class="log-time" title="${esc(l.created_at)}">${esc(age)}</span> <span class="log-state">[${esc(l.state||'')} ]</span> ${esc(l.message||'')} <span style="color:#64748b">${esc((l.current_url||'').slice(0,40))}</span></div>`;
    }).join('') : '<div class="muted">No logs</div>';
    if(logsEl) logsEl.innerHTML=html;
    if(liveEl) liveEl.innerHTML=html;
  }catch(e){ const m='<div class="muted">load '+esc(e.message)+'</div>'; const a=document.getElementById('logs'); if(a) a.innerHTML=m; const b=document.getElementById('liveLogs'); if(b) b.innerHTML=m; }
}
async function loadNotifications(){
  if(!selected) return alert('Select a bot');
  document.getElementById('logs').style.display='none';
  document.getElementById('notifs').style.display='block';
  try{
    const r=await fetch('api/state.php?bot_id='+selected, {headers: HEADERS});
    const j=await r.json();
    const list=j.notifications||[];
    document.getElementById('notifs').innerHTML = list.length? list.map(n=>`<div class="log-line"><span class="log-time">${esc(n.created_at)}</span> <b>${esc(n.type)}</b> ${esc(n.message)}<br><span style="color:#94a3b8">${esc(n.details||'')}</span></div>`).join('') : '<div class="muted">No notifications</div>';
  }catch(e){ document.getElementById('notifs').innerHTML='error '+esc(e.message); }
}

function onCmdChange(){
  const v=document.getElementById('cmdSelect').value;
  document.getElementById('logoutHint').style.display = (v==='LOGOUT') ? 'flex' : 'none';
  if(v==='LOGOUT' && !document.getElementById('cmdArgs').value.trim()) document.getElementById('cmdArgs').value = JSON.stringify({wait: parseInt(document.getElementById('logoutWait').value||60)});
}
async function sendLogout(){
  const bot_id=document.getElementById('customBotId').value;
  const wait=parseInt(document.getElementById('logoutWait').value||0);
  if(!bot_id) return alert('bot_id required');
  await sendCommand(bot_id, 'LOGOUT', {wait});
}
async function sendLogoutLive(){
  if(!selected) return alert('Select a bot');
  const wait=parseInt(document.getElementById('logoutWaitLive').value||0);
  await sendCommand(selected, 'LOGOUT', {wait});
}
async function sendCmd(cmd){
  if(!selected) return alert('Select a bot');
  await sendCommand(selected, cmd, null);
}
async function sendCustomCommand(){
  const bot_id=document.getElementById('customBotId').value;
  let cmd=document.getElementById('cmdSelect').value;
  const argsRaw=document.getElementById('cmdArgs').value.trim();
  let args=null;
  if(argsRaw){ try{ args=JSON.parse(argsRaw);}catch{ return alert('Invalid JSON'); } }
  if(cmd==='LOGOUT' && !args) args={wait: parseInt(document.getElementById('logoutWait').value||0)};
  if(!bot_id) return alert('bot_id required');
  await sendCommand(bot_id, cmd, args);
}
async function sendCommand(bot_id, cmd, args){
  try{
    const r=await fetch('api/command.php', {method:'POST', headers: HEADERS, body: JSON.stringify({bot_id: parseInt(bot_id), cmd, args})});
    const j=await r.json();
    if(j.ok) alert('Queued '+cmd+' for '+bot_id+' (id '+j.id+')');
    else alert('Failed: '+(j.error||'unknown'));
  }catch(e){ alert('Send failed: '+e.message); }
}

function pollNow(){ fetchBots(); fetchTokensTable(); }
function resetTimer(){
  const v=parseInt(document.getElementById('autoPoll').value);
  if(timer) clearInterval(timer);
  if(v>0) timer=setInterval(fetchBots, v);
}
setInterval(()=>{ document.getElementById('clock').textContent=new Date().toLocaleTimeString(); },1000);
fetchBots();
timer=setInterval(fetchBots, 2000);

// — tokens / SIMs —
let tokenPollTimer = null;
let simsCache = [];
let packagesCache = [];

function maskToken(t){
  if(!t) return '<span class="badge badge-gray">—</span>';
  t = String(t);
  if(t.length < 10) return esc(t);
  return esc(t.slice(0,6)) + '...' + esc(t.slice(-4)) + ' <span style="color:var(--ok)">●</span>';
}
function fmtTime(s){
  if(!s) return '—';
  try{ let t=s.replace(' ','T'); if(!/[Z+\-]/.test(t.slice(10))) t+='Z'; else if(/\+\d{2}$/.test(t)) t+=':00'; return new Date(t).toLocaleString(); }catch{ return s; }
}
function fetchTokensTable(){ if(bots.length) renderTokensTable(); }
function renderTokensTable(){
  const tbody = document.getElementById('tokenTbody');
  const sel = document.getElementById('simBotSelect');
  if(!tbody) return;
  const checked = new Set([...tbody.querySelectorAll('input[type=checkbox][data-bot]:checked')].map(e=>e.getAttribute('data-bot')));
  tbody.innerHTML = bots.map(b=>{
    const masked = b.auth_token ? maskToken(b.auth_token) : '<span class="badge badge-gray">—</span>';
    const isChecked = checked.has(String(b.id)) ? 'checked' : '';
    return `<tr><td><input type="checkbox" data-bot="${b.id}" ${isChecked} onchange="onTokenCheck()"></td><td><b>${esc(b.id)}</b></td><td title="${esc(b.proxy_email)}">${esc((b.proxy_email||'').slice(0,18))}</td><td title="${esc(b.auth_token||'')}">${masked}</td><td><span class="badge badge-gray">${esc(b.state||'—')}</span></td><td>${fmtAge(b.heartbeat_at)}</td></tr>`;
  }).join('');
  if(sel){
    const prev = sel.value;
    sel.innerHTML = bots.map(b=>`<option value="${b.id}">Bot ${b.id} — ${esc((b.proxy_email||'').slice(0,16))} ${b.auth_token?'●':''}</option>`).join('');
    if(prev) sel.value = prev;
  }
  document.getElementById('tokenStatus').textContent = bots.length ? `${bots.length} device(s) — ${bots.filter(b=>b.auth_token).length}/${bots.length} tokens` : 'No bots';
  document.getElementById('postTokenControls').style.display = bots.length ? 'flex' : 'none';
}
function toggleAll(checked){ document.querySelectorAll('#tokenTbody input[type=checkbox][data-bot]').forEach(e=> e.checked = checked); onTokenCheck(); }
function onTokenCheck(){ const any = document.querySelector('#tokenTbody input[type=checkbox][data-bot]:checked'); document.getElementById('postTokenControls').style.display = any ? 'flex' : 'none'; }
function getSelectedBotId(){
  const cb = document.querySelector('#tokenTbody input[type=checkbox][data-bot]:checked');
  if(cb) return cb.getAttribute('data-bot');
  const sel = document.getElementById('simBotSelect');
  if(sel && sel.value) return sel.value;
  if(selected) return selected;
  return bots[0]?.id || null;
}
async function loadDevicesAndTokens(){
  const btn = document.getElementById('btnLoadTokens');
  btn.disabled = true; btn.textContent = 'Loading…';
  try{
    const r = await fetch('api/state.php', {headers: HEADERS});
    const j = await r.json();
    if(j.ok){ bots = j.bots||[]; render(); renderTokensTable(); }
    if(!bots.length){ alert('No bots'); btn.disabled=false; btn.textContent='Load tokens'; return; }
    document.getElementById('tokenStatus').textContent = `Queuing get_auth_token to ${bots.length} bot(s)…`;
    for(const b of bots){ try{ await fetch('api/command.php', {method:'POST', headers: HEADERS, body: JSON.stringify({bot_id: parseInt(b.id), cmd: 'get_auth_token'})}); }catch(e){} }
    document.getElementById('tokenStatus').textContent = `Polling tokens…`;
    let polls = 0;
    if(tokenPollTimer) clearInterval(tokenPollTimer);
    tokenPollTimer = setInterval(async ()=>{
      polls++;
      try{ const pr = await fetch('api/state.php', {headers: HEADERS}); const pj = await pr.json(); if(pj.ok){ bots = pj.bots||bots; render(); renderTokensTable(); } }catch{}
      if(polls >= 10){ clearInterval(tokenPollTimer); document.getElementById('tokenStatus').textContent = `Done — ${bots.filter(b=>b.auth_token).length}/${bots.length} tokens.`; btn.disabled=false; btn.textContent='Load tokens'; }
    }, 1500);
  }catch(e){ document.getElementById('tokenStatus').textContent = 'Load failed: '+e.message; btn.disabled=false; btn.textContent='Load tokens'; }
}
function openSimRegisterFlow(){
  const bid = getSelectedBotId();
  if(!bid) return alert('Select device');
  document.getElementById('simMappingArea').style.display = 'block';
  document.getElementById('simBotSelect').value = bid;
  document.getElementById('simFetchStatus').textContent = 'Ready — Fetch';
  document.getElementById('simMappingArea').scrollIntoView({behavior:'smooth'});
}
async function fetchSimsAndPackages(){
  const bid = document.getElementById('simBotSelect').value;
  if(!bid) return alert('Select device');
  const status = document.getElementById('simFetchStatus');
  const wrap = document.getElementById('simMappingTableWrap');
  const btnReg = document.getElementById('btnRegisterSims');
  status.textContent = 'Fetching…'; wrap.innerHTML = '<div class="muted">Loading…</div>'; btnReg.disabled = true;
  try{
    const [simsRes, pkgsRes] = await Promise.all([
      fetch('api/sims.php?bot_id='+encodeURIComponent(bid), {headers: HEADERS}),
      fetch('api/packages.php?bot_id='+encodeURIComponent(bid), {headers: HEADERS})
    ]);
    const simsJ = await simsRes.json(); const pkgsJ = await pkgsRes.json();
    if(!simsJ.ok) throw new Error('SIMs: '+(simsJ.error||'unknown'));
    if(!pkgsJ.ok) throw new Error('Packages: '+(pkgsJ.error||'unknown'));
    simsCache = simsJ.sims||[]; packagesCache = pkgsJ.packages||[];
    status.textContent = `${simsCache.length} SIM(s), ${packagesCache.length} pkg(s)`;
    if(!simsCache.length){ wrap.innerHTML = '<div class="muted">No SIMs.</div>'; return; }
    const pkgOptions = ['<option value="">— skip —</option>'].concat(packagesCache.map(p=>`<option value="${esc(p.categoryId)}">${esc(p.name)} — $${esc(p.price)}</option>`)).join('');
    wrap.innerHTML = `<table><thead><tr><th>SIM</th><th>Carrier</th><th>Status</th><th>Package</th></tr></thead><tbody>${simsCache.map(s=>`
        <tr><td><b>${esc(s.phoneNumber)}</b><br><span style="color:var(--muted);font-size:10px">${esc(s.id)}</span></td><td>${esc(s.carrier)}</td><td>${esc(s.status)}</td><td><select data-sim="${esc(s.id)}" style="min-width:180px">${pkgOptions}</select></td></tr>
      `).join('')}</tbody></table>`;
    btnReg.disabled = false;
  }catch(e){ status.textContent = 'Fetch failed: '+e.message; wrap.innerHTML = `<div class="muted" style="color:#f87171">Error: ${esc(e.message)}</div>`; }
}
async function registerSims(){
  const bid = document.getElementById('simBotSelect').value;
  if(!bid) return alert('Select device');
  const mappings = [...document.querySelectorAll('#simMappingTableWrap select[data-sim]')].map(s=>({simId: s.getAttribute('data-sim'), packageId: s.value || null}));
  const toRegister = mappings.filter(m=>m.packageId);
  if(!toRegister.length) return alert('Select at least one package');
  if(!confirm(`Register ${toRegister.length} SIM(s) via bot ${bid}?`)) return;
  const btn = document.getElementById('btnRegisterSims'); btn.disabled = true; btn.textContent = 'Registering…';
  const resultEl = document.getElementById('registerResult'); resultEl.style.display = 'block'; resultEl.innerHTML = '<div class="muted">Posting…</div>';
  try{
    const r = await fetch('api/register_sims.php', {method:'POST', headers: HEADERS, body: JSON.stringify({bot_id: parseInt(bid), mappings})});
    const j = await r.json(); if(!j.ok) throw new Error(j.error||'unknown');
    resultEl.innerHTML = j.results.map(rr=> rr.skipped ? `<div class="log-line" style="color:var(--muted)">[skip] ${esc(rr.simId)}</div>` : `<div class="log-line"><span style="color:${rr.ok?'var(--ok)':'var(--danger)'}">${rr.ok?'[ok]':'[fail]'}</span> ${esc(rr.simId)} → ${esc(rr.packageId)} (http ${esc(rr.http)}) ${esc((rr.error||JSON.stringify(rr.response||'')).slice(0,120))}</div>`).join('');
  }catch(e){ resultEl.innerHTML = `<div style="color:#f87171">Failed: ${esc(e.message)}</div>`; }
  finally{ btn.disabled = false; btn.textContent = 'Register'; }
}
function goToSite(){
  const bid=getSelectedBotId();
  if(!bid) return alert('Select device checkbox first');
  const bot=bots.find(b=>String(b.id)===String(bid));
  const token=bot?.auth_token||'';
  if(token){
    try{ navigator.clipboard.writeText(token); }catch(e){}
    // Use S.token extra (intent extras) — query string truncates JWT on some Chrome
    const intent = `intent://go#Intent;scheme=scout;package=com.scout.webview;S.token=${encodeURIComponent(token)};S.url=${encodeURIComponent('https://scoutandrunner.com/scout')};end`;
    const helper = `api/goto.php?bot_id=${encodeURIComponent(bid)}`;
    window.location.href = intent;
    // fallback to helper if WebView not installed (intent fails silently) — open helper after 1.1s
    setTimeout(()=>{ window.open(helper,'_blank'); }, 1100);
  } else {
    window.open('https://scoutandrunner.com/scout','_blank');
    alert('No token stored for bot '+bid+' — Load tokens first. Opened Scout login.');
  }
}
function openCallStatus(){
  if(!selected) return alert('Select a bot first (tap row in Fleet)');
  document.getElementById('csBotId').textContent=selected;
  document.getElementById('csUpdated').textContent='';
  document.getElementById('csBody').innerHTML='<div class="muted">Loading…</div>';
  document.getElementById('callStatusOverlay').style.display='flex';
  refreshCallStatus();
}
function closeCallStatus(){ document.getElementById('callStatusOverlay').style.display='none'; }
async function refreshCallStatus(){
  const bid=selected; if(!bid) return;
  const body=document.getElementById('csBody'); const upd=document.getElementById('csUpdated');
  try{
    let sims=[], updated=null;
    try{
      const r=await fetch('api/sims_status.php?bot_id='+encodeURIComponent(bid), {headers: HEADERS});
      const j=await r.json(); if(j.ok){ sims=j.sims||[]; updated=j.updated_at; }
    }catch(e){}
    // fallback to live Scout API (api/scout/sims) if bot cache empty — you said json is there
    if(!sims.length || (sims.length===1 && sims[0].phone==='dashboard_count')){
      try{
        const rs=await fetch('api/sims.php?bot_id='+encodeURIComponent(bid), {headers: HEADERS});
        const js=await rs.json();
        if(js.ok && js.sims && js.sims.length){
          sims = js.sims.map(s=>({phone:s.phoneNumber||s.phone||'?', cycle:0, status:s.status||'?', isMax:false, isCurrent:false, cooldownEndsAt:null, id:s.id}));
          // try to parse cycle if present in raw
          sims = js.sims.map(s=>{
            const cyc=parseInt(s.testsInCycle??s.testsThisCycle??0);
            return {phone:s.phoneNumber||'?', cycle:cyc, status:s.status||'?', isMax:cyc>=8, isCurrent:false, cooldownEndsAt:s.cooldownEndsAt||null, id:s.id};
          });
          updated='live';
        }
      }catch(e){}
    }
    if(upd) upd.textContent = updated ? ' — '+(updated==='live'?'live':fmtTime(updated)) : '';
    if(!sims.length || (sims.length===1 && sims[0].phone==='dashboard_count')){ body.innerHTML='<div class="muted">No SIM data — bot not yet reported and live fetch empty. Dashboard count: '+(sims[0]?.status||'—')+'</div>'; return; }
    const maxed=sims.filter(s=>s.isMax), cur=sims.find(s=>s.isCurrent) || null;
    let html=`<div class="muted" style="padding:0 0 8px">${sims.length} SIM(s) • <span style="color:#fecaca">${maxed.length} at 8/8 max</span> • current ${cur?cur.phone+' '+cur.cycle+'/8':'—'}</div>`;
    html+=sims.map(s=>{
      const pct=Math.min(100, Math.round((s.cycle/8)*100));
      const barColor=s.isMax?'#ef4444':(s.isCurrent?'#3b82f6':'#22c55e');
      const badge=s.isMax?'<span class="badge badge-red">MAX 8/8</span>':(s.isCurrent?'<span class="badge badge-green">CURRENT '+s.cycle+'/8</span>':'<span class="badge badge-gray">'+s.cycle+'/8</span>');
      const border=s.isCurrent?'border:1px solid #3b82f6;':(s.isMax?'border:1px solid rgba(239,68,68,.3);':'');
      return `<div style="background:rgba(255,255,255,.04);border-radius:10px;padding:8px;margin-bottom:6px;${border}"><div style="display:flex;justify-content:space-between;gap:8px;align-items:center"><b>${esc(s.phone)}</b> ${badge}</div><div style="height:6px;background:rgba(255,255,255,.08);border-radius:999px;margin-top:6px;overflow:hidden"><div style="width:${pct}%;height:100%;background:${barColor}"></div></div><div class="muted" style="padding:2px 0 0;font-size:10px">${esc(s.status)}${s.cooldownEndsAt?' • cooldown '+fmtTime(s.cooldownEndsAt):''}</div></div>`;
    }).join('');
    body.innerHTML=html;
  }catch(e){ body.innerHTML='<div class="muted" style="color:#f87171">Load failed: '+esc(e.message)+'</div>'; }
}
async function deleteAccountNow(){
  const bid = getSelectedBotId();
  if(!bid) return alert('Select device checkbox first');
  // no confirmation per request — direct delete
  if(!confirm('Delete account for bot '+bid+'? This is irreversible.')) return;
  const overlay=document.getElementById('deleteOverlay');
  const resEl=document.getElementById('deleteResult');
  document.getElementById('deleteBotId').textContent=bid;
  if(resEl){ resEl.style.display='block'; resEl.innerHTML='<span class="muted">Deleting…</span>'; }
  if(overlay) overlay.style.display='flex';
  try{
    const r=await fetch('api/delete_account.php', {method:'POST', headers: HEADERS, body: JSON.stringify({bot_id: parseInt(bid), confirmation:'DELETE_MY_ACCOUNT'})});
    const t=await r.text(); let j; try{ j=JSON.parse(t);}catch{ j={raw:t, http:r.status} }
    if(r.ok && (j.ok || r.status===200)){
      if(resEl) resEl.innerHTML=`<span style="color:var(--ok)">✓ Deleted (http ${r.status}) — bot → landing</span>`;
      setTimeout(()=>{ const o=document.getElementById('deleteOverlay'); if(o) o.style.display='none'; fetchBots(); },900);
    } else {
      if(resEl) resEl.innerHTML=`<span style="color:var(--danger)">✗ Failed http ${r.status}</span><br><span style="color:var(--muted)">${esc(t.slice(0,600))}</span>`;
    }
  }catch(e){ if(resEl) resEl.innerHTML=`<span style="color:var(--danger)">Error: ${esc(e.message)}</span>`; }
}
// keep modal helpers for goto flow (delete now uses deleteAccountNow)
function openDeleteModal(){ return deleteAccountNow(); }
function closeDeleteModal(){ const o=document.getElementById('deleteOverlay'); if(o) o.style.display='none'; }
async function confirmDeleteAccount(){ return deleteAccountNow(); }
</script>
</body>
</html>
