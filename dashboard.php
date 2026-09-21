<?php
$BOT_TOKEN = getenv('BOT_TOKEN') ?: 'scout-secret';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scout Bot Dashboard</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0f172a;color:#e2e8f0;line-height:1.5}
header{background:#1e293b;padding:12px 16px;position:sticky;top:0;z-index:10;display:flex;flex-wrap:wrap;align-items:center;gap:12px;border-bottom:1px solid #334155}
header h1{font-size:18px;flex:1;min-width:180px}
header .meta{font-size:12px;color:#94a3b8}
.grid{display:grid;grid-template-columns:1fr;gap:12px;padding:12px}
@media(min-width:900px){.grid{grid-template-columns:1.7fr 1fr}}
.card{background:#1e293b;border:1px solid #334155;border-radius:10px;overflow:hidden}
.card h2{font-size:14px;padding:10px 12px;border-bottom:1px solid #334155;background:#0f172a;display:flex;justify-content:space-between;align-items:center}
.card h2 button{font-size:12px}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{padding:8px 10px;text-align:left;border-bottom:1px solid #334155;white-space:nowrap}
th{background:#0f172a;color:#94a3b8;font-weight:600;position:sticky;top:0}
tr:hover{background:#0f2847}
tr.selected{background:#1e3a5f}
.badge{padding:2px 7px;border-radius:999px;font-size:11px;font-weight:700;display:inline-block}
.badge-green{background:#065f46;color:#6ee7b7}
.badge-yellow{background:#7c5800;color:#fde68a}
.badge-red{background:#7f1d1d;color:#fecaca}
.badge-gray{background:#334155;color:#cbd5e1}
.dot{width:9px;height:9px;border-radius:50%;display:inline-block;margin-right:6px;vertical-align:middle}
.dot-green{background:#22c55e;box-shadow:0 0 6px #22c55e}
.dot-yellow{background:#eab308;box-shadow:0 0 6px #eab308}
.dot-red{background:#ef4444;box-shadow:0 0 6px #ef4444}
.controls{display:flex;flex-wrap:wrap;gap:6px;padding:10px}
.controls input,.controls select{padding:7px 8px;border-radius:8px;border:1px solid #334155;background:#0f172a;color:#e2e8f0;font-size:13px}
.btn{padding:7px 12px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:12px}
.btn-primary{background:#2563eb;color:#fff}
.btn-secondary{background:#334155;color:#e2e8f0}
.btn-danger{background:#dc2626;color:#fff}
.btn:disabled{opacity:.5;cursor:not-allowed}
.logs{max-height:55vh;overflow:auto;padding:8px;font-family:ui-monospace,Consolas,monospace;font-size:12px;background:#0b1220}
.log-line{padding:3px 0;border-bottom:1px solid #1e293b;word-break:break-all}
.log-time{color:#64748b}
.log-state{color:#38bdf8}
.muted{color:#64748b;font-size:12px;padding:8px}
.flex{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
@media(max-width:600px){table{font-size:12px} th,td{padding:6px 7px} header h1{font-size:16px}}
</style>
</head>
<body>
<div id="noSimsAlert" style="display:none;background:#7f1d1d;color:#fecaca;padding:10px 16px;border:1px solid #dc2626;margin:12px 12px 0 12px;border-radius:8px;font-weight:700"></div>
<header>
  <h1>🛰️ Scout Fleet Dashboard</h1>
  <span class="meta" id="status">polling…</span>
  <span class="meta" id="clock"></span>
  <button class="btn btn-secondary" onclick="pollNow()">Refresh</button>
</header>

<div class="grid">
  <div class="card">
    <h2>
      <span>Bots (<span id="botCount">0</span>)</span>
      <span class="flex">
        <input id="filter" placeholder="filter bot_id/email" oninput="render()" style="width:160px">
        <select id="autoPoll" onchange="resetTimer()"><option value="2000" selected>2s poll</option><option value="5000">5s</option><option value="0">off</option></select>
      </span>
    </h2>
    <div style="overflow:auto;max-height:65vh">
      <table>
        <thead><tr><th>#</th><th>State</th><th>Sims</th><th>Proxy Email</th><th>Poll Inbox</th><th>Heartbeat</th><th>URL</th><th>Action</th></tr></thead>
        <tbody id="tbody"></tbody>
      </table>
    </div>
    <div id="botsMuted" class="muted" style="display:none">No bots registered. Have bots POST to <code>api/register.php</code></div>
    <div class="controls">
      <input id="customBotId" placeholder="bot_id" type="number" style="width:90px">
      <select id="cmdSelect"><option value="PAUSE">PAUSE</option><option value="RESUME">RESUME</option><option value="RESTART">RESTART</option><option value="STOP">STOP</option><option value="REFRESH">REFRESH</option></select>
      <input id="cmdArgs" placeholder='args JSON (optional) e.g. {"delay":5}' style="flex:1;min-width:160px">
      <button class="btn btn-primary" onclick="sendCustomCommand()">Send</button>
    </div>
  </div>

  <div class="card">
    <h2>
      <span>Live: Bot <span id="liveId">—</span></span>
      <span class="flex">
        <button class="btn btn-secondary" onclick="loadLogs()">Logs</button>
        <button class="btn btn-secondary" onclick="loadNotifications()">🔔 Notes</button>
      </span>
    </h2>
    <div class="controls">
      <button class="btn btn-secondary" onclick="sendCmd('PAUSE')">Pause</button>
      <button class="btn btn-secondary" onclick="sendCmd('RESUME')">Resume</button>
      <button class="btn btn-danger" onclick="sendCmd('RESTART')">Restart</button>
    </div>
    <div id="botDetail" class="muted">Select a bot row to view logs/commands.</div>
    <div id="logs" class="logs" style="display:none"></div>
    <div id="notifs" class="logs" style="display:none"></div>
  </div>
</div>

<div class="card" style="margin:12px">
  <h2>Device Tokens &amp; SIM Packages — Server-Coordinated Flow
    <button class="btn btn-primary" id="btnLoadTokens" onclick="loadDevicesAndTokens()">Load devices &amp; tokens</button>
  </h2>
  <div class="muted">Active bots from heartbeat appear here. Click <b>Load devices &amp; tokens</b> to ask each bot for its bearer token (from localStorage). Tokens are stored server-side and used to proxy scout API.</div>
  <div style="overflow:auto;max-height:38vh">
    <table>
      <thead><tr><th><input type="checkbox" id="chkAll" onchange="toggleAll(this.checked)"></th><th>Bot</th><th>Proxy Email</th><th>Poll Inbox</th><th>State</th><th>Token</th><th>Updated</th><th>Heartbeat</th></tr></thead>
      <tbody id="tokenTbody"></tbody>
    </table>
  </div>
  <div id="tokenStatus" class="muted">No tokens loaded yet.</div>
  <div class="controls" id="postTokenControls" style="display:none">
    <button class="btn btn-secondary" onclick="deleteAccountPlaceholder()">Delete Account</button>
    <button class="btn btn-primary" onclick="openSimRegisterFlow()">Register SIMs under package</button>
    <span class="muted">Select a device (checkbox) first. Physical SIM must already be registered to the account.</span>
  </div>

  <!-- SIM / package mapping area -->
  <div id="simMappingArea" style="display:none; border-top:1px solid #334155; padding:10px">
    <div class="flex" style="margin-bottom:8px">
      <label style="font-size:13px">Device:
        <select id="simBotSelect" style="min-width:140px"></select>
      </label>
      <button class="btn btn-secondary" onclick="fetchSimsAndPackages()">Fetch SIMs &amp; Packages</button>
      <span id="simFetchStatus" class="muted"></span>
    </div>
    <div id="simMappingTableWrap" style="overflow:auto;max-height:42vh"></div>
    <div class="controls">
      <button class="btn btn-primary" id="btnRegisterSims" onclick="registerSims()" disabled>Register</button>
      <span class="muted">Choose a package per SIM (empty = not register). Server will POST to <code>/runner/sim/{id}/package</code> using selected device's token, then tell bot to refresh.</span>
    </div>
    <div id="registerResult" class="logs" style="max-height:22vh; display:none"></div>
  </div>
</div>

<div class="card" style="margin:12px">
  <h2>Recent Notifications</h2>
  <div id="globalNotifs" class="logs" style="max-height:30vh"></div>
</div>

<script>
const TOKEN = <?= json_encode($BOT_TOKEN) ?>;
const HEADERS = {'Content-Type':'application/json','X-Bot-Token': TOKEN};
let bots = [];
let selected = null;
let timer = null;

function fmtAge(heartbeat_at){
  if(!heartbeat_at) return '<span class="badge badge-gray">never</span>';
  const t = new Date(heartbeat_at.replace(' ','T'));
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
    if(j.ok){ bots=j.bots||[]; render(); renderGlobalNotifs(j.notifications||[]); document.getElementById('status').textContent='last poll '+new Date().toLocaleTimeString()+' • '+bots.length+' bots'; }
    else document.getElementById('status').textContent='error: '+(j.error||'unknown');
    if(selected) loadLogs(false);
  }catch(e){ document.getElementById('status').textContent='poll failed: '+e.message; }
}
function render(){
  const q = document.getElementById('filter').value.toLowerCase();
  const tbody=document.getElementById('tbody');
  const count=document.getElementById('botCount');
  const muted=document.getElementById('botsMuted');
  let filtered=bots;
  if(q) filtered=bots.filter(b=> String(b.id).includes(q) || (b.proxy_email||'').toLowerCase().includes(q) || (b.poll_inbox||'').toLowerCase().includes(q) || (b.state||'').toLowerCase().includes(q));
  count.textContent=filtered.length;
  muted.style.display = filtered.length? 'none':'block';
  tbody.innerHTML = filtered.map(b=>`
    <tr class="${selected==b.id?'selected':''}" onclick="selectBot(${b.id})" style="cursor:pointer">
      <td><b>${esc(b.id)}</b> <span style="color:#64748b">${esc(b.container_id||'')}</span></td>
      <td><span class="badge ${b.state==='running'?'badge-green':b.state==='paused'?'badge-yellow':'badge-gray'}">${esc(b.state||'—')}</span></td>
      <td>${esc(b.sims_count??0)}</td>
      <td title="${esc(b.proxy_email)}">${esc((b.proxy_email||'').slice(0,22))}</td>
      <td title="${esc(b.poll_inbox)}">${esc((b.poll_inbox||'').slice(0,22))}</td>
      <td>${fmtAge(b.heartbeat_at)}</td>
      <td title="${esc(b.current_url)}">${esc((b.current_url||'').slice(0,28))}</td>
      <td><button class="btn btn-secondary" onclick="event.stopPropagation(); selectBot(${b.id}); sendCmd('RESTART')">↻</button></td>
    </tr>
  `).join('');
}
function renderGlobalNotifs(list){
  const el=document.getElementById('globalNotifs');
  const alertEl=document.getElementById('noSimsAlert');
  // show red banner if any NoSimsRegistered in recent list
  const noSims = list.filter(n=> String(n.type||'').toLowerCase()==='nosimsregistered');
  if(alertEl){
    if(noSims.length){
      const latest = noSims[0];
      alertEl.style.display='block';
      alertEl.innerHTML='🚨 NoSimsRegistered — Bot '+esc(latest.bot_id)+' : '+esc(latest.message)+' <span style="font-weight:400;color:#fecaca">('+esc(latest.created_at)+')</span> <span style="color:#fca5a5">'+esc((latest.details||'').slice(0,120))+'</span>';
    } else {
      alertEl.style.display='none';
      alertEl.innerHTML='';
    }
  }
  if(!list.length){ el.innerHTML='<div class="muted">No notifications</div>'; return; }
  el.innerHTML=list.map(n=>{
    const isNoSim = String(n.type||'').toLowerCase()==='nosimsregistered';
    const bg = isNoSim ? 'background:#7f1d1d;border-left:3px solid #dc2626;padding-left:6px;' : '';
    const typeColor = isNoSim ? '#fecaca' : '#fbbf24';
    const priorityBadge = isNoSim ? ' <span class="badge badge-red">HIGH</span>' : '';
    return `<div class="log-line" style="${bg}"><span class="log-time">${esc(n.created_at)}</span> <b>[${esc(n.bot_id)}]</b> <span style="color:${typeColor}">${esc(n.type)}</span>${priorityBadge} ${esc(n.message)} <span style="color:#64748b">${esc(n.details||'')}</span></div>`;
  }).join('');
}


async function selectBot(id){
  selected=id;
  document.getElementById('liveId').textContent=id;
  document.getElementById('botDetail').style.display='none';
  document.getElementById('logs').style.display='block';
  document.getElementById('notifs').style.display='none';
  document.getElementById('customBotId').value=id;
  render();
  await loadLogs();
}
async function loadLogs(showNotif){
  if(!selected) return;
  try{
    const r=await fetch('api/state.php?bot_id='+selected, {headers: HEADERS});
    const j=await r.json();
    if(!j.ok) throw new Error(j.error);
    const logsEl=document.getElementById('logs');
    const det=document.getElementById('botDetail');
    if(j.bot){
      det.style.display='block';
      det.innerHTML=`<b>Bot ${esc(j.bot.id)}</b> — ${esc(j.bot.state||'—')} • sims ${esc(j.bot.sims_count)} • <span title="${esc(j.bot.current_url)}">${esc(j.bot.current_url||'')}</span><br><span style="color:#94a3b8">proxy ${esc(j.bot.proxy_email)} | inbox ${esc(j.bot.poll_inbox)} | heartbeat ${esc(j.bot.heartbeat_at||'never')}</span>`;
      det.style.display='none';
    }
    if(j.logs && j.logs.length){
      logsEl.innerHTML=j.logs.map(l=>`<div class="log-line"><span class="log-time">${esc(l.created_at)}</span> <span class="log-state">[${esc(l.state||'')} sims:${esc(l.sims_count??'')} ]</span> ${esc(l.message||'')} <span style="color:#64748b">${esc(l.current_url||'')}</span></div>`).join('');
    } else {
      logsEl.innerHTML='<div class="muted">No logs yet</div>';
    }
    if(showNotif!==false && j.notifications){
      const nEl=document.getElementById('notifs');
      // keep hidden unless toggled
    }
  }catch(e){ document.getElementById('logs').innerHTML='<div class="muted">load failed: '+esc(e.message)+'</div>'; }
}
async function loadNotifications(){
  if(!selected) return alert('Select a bot first');
  const logsEl=document.getElementById('logs');
  const notifsEl=document.getElementById('notifs');
  logsEl.style.display='none';
  notifsEl.style.display='block';
  try{
    const r=await fetch('api/state.php?bot_id='+selected, {headers: HEADERS});
    const j=await r.json();
    const list=j.notifications||[];
    notifsEl.innerHTML = list.length? list.map(n=>`<div class="log-line"><span class="log-time">${esc(n.created_at)}</span> <b>${esc(n.type)}</b> ${esc(n.message)}<br><span style="color:#94a3b8">${esc(n.details||'')}</span></div>`).join('') : '<div class="muted">No notifications</div>';
  }catch(e){ notifsEl.innerHTML='error '+esc(e.message); }
}

async function sendCmd(cmd){
  if(!selected) return alert('Select a bot first');
  await sendCommand(selected, cmd, null);
}
async function sendCustomCommand(){
  const bot_id=document.getElementById('customBotId').value;
  const cmd=document.getElementById('cmdSelect').value;
  const argsRaw=document.getElementById('cmdArgs').value.trim();
  let args=null;
  if(argsRaw){ try{ args=JSON.parse(argsRaw);}catch{ return alert('Invalid JSON args'); } }
  if(!bot_id) return alert('bot_id required');
  await sendCommand(bot_id, cmd, args);
}
async function sendCommand(bot_id, cmd, args){
  try{
    const r=await fetch('api/command.php', {method:'POST', headers: HEADERS, body: JSON.stringify({bot_id: parseInt(bot_id), cmd, args})});
    const j=await r.json();
    if(j.ok){ alert('Command '+cmd+' queued for bot '+bot_id+' (id '+j.id+')'); }
    else alert('Failed: '+(j.error||'unknown'));
  }catch(e){ alert('Send failed: '+e.message); }
}

function pollNow(){ fetchBots(); fetchTokensTable(); }
function resetTimer(){
  const v=parseInt(document.getElementById('autoPoll').value);
  if(timer) clearInterval(timer);
  if(v>0) timer=setInterval(fetchBots, v);
}
setInterval(()=>{ document.getElementById('clock').textContent=new Date().toLocaleString(); },1000);
fetchBots();
timer=setInterval(fetchBots, 2000);

// -------- Token / SIM flow --------
let tokenPollTimer = null;
let simsCache = [];
let packagesCache = [];

function maskToken(t){
  if(!t) return '<span class="badge badge-gray">—</span>';
  t = String(t);
  if(t.length < 10) return esc(t);
  return esc(t.slice(0,6)) + '...' + esc(t.slice(-4)) + ' <span style="color:#22c55e">●</span>';
}
function fmtTime(s){
  if(!s) return '—';
  try{
    const d = new Date(s.replace(' ','T'));
    return d.toLocaleString();
  }catch{ return s; }
}
function fetchTokensTable(){
  // refresh token table without re-queueing commands
  if(bots.length) renderTokensTable();
}
function renderTokensTable(){
  const tbody = document.getElementById('tokenTbody');
  const sel = document.getElementById('simBotSelect');
  if(!tbody) return;
  // keep checked ids
  const checked = new Set([...tbody.querySelectorAll('input[type=checkbox][data-bot]:checked')].map(e=>e.getAttribute('data-bot')));
  tbody.innerHTML = bots.map(b=>{
    const masked = b.auth_token ? maskToken(b.auth_token) : '<span class="badge badge-gray">no token</span>';
    const isChecked = checked.has(String(b.id)) ? 'checked' : '';
    return `<tr>
      <td><input type="checkbox" data-bot="${b.id}" ${isChecked} onchange="onTokenCheck()"></td>
      <td><b>${esc(b.id)}</b></td>
      <td title="${esc(b.proxy_email)}">${esc((b.proxy_email||'').slice(0,28))}</td>
      <td title="${esc(b.poll_inbox)}">${esc((b.poll_inbox||'').slice(0,24))}</td>
      <td><span class="badge badge-gray">${esc(b.state||'—')}</span></td>
      <td title="${esc(b.auth_token||'')}">${masked}</td>
      <td>${fmtTime(b.token_updated_at)}</td>
      <td>${fmtAge(b.heartbeat_at)}</td>
    </tr>`;
  }).join('');
  if(sel){
    const prev = sel.value;
    sel.innerHTML = bots.map(b=>`<option value="${b.id}">Bot ${b.id} — ${esc((b.proxy_email||'').slice(0,18))} ${b.auth_token?'●':''}</option>`).join('');
    if(prev) sel.value = prev;
  }
  document.getElementById('tokenStatus').textContent = bots.length ? `${bots.length} device(s) shown — tokens ${bots.filter(b=>b.auth_token).length}/${bots.length} loaded` : 'No bots';
  document.getElementById('postTokenControls').style.display = bots.length ? 'flex' : 'none';
}
function toggleAll(checked){
  document.querySelectorAll('#tokenTbody input[type=checkbox][data-bot]').forEach(e=> e.checked = checked);
  onTokenCheck();
}
function onTokenCheck(){
  const any = document.querySelector('#tokenTbody input[type=checkbox][data-bot]:checked');
  document.getElementById('postTokenControls').style.display = any ? 'flex' : 'none';
}
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
    // fresh bots list first
    const r = await fetch('api/state.php', {headers: HEADERS});
    const j = await r.json();
    if(j.ok){ bots = j.bots||[]; render(); renderTokensTable(); }
    if(!bots.length){ alert('No active bots found (heartbeat). Ensure bots are running and posting to api/heartbeat.php'); btn.disabled=false; btn.textContent='Load devices & tokens'; return; }
    document.getElementById('tokenStatus').textContent = `Dispatching get_auth_token to ${bots.length} bot(s)…`;
    // dispatch command to each bot
    for(const b of bots){
      try{
        await fetch('api/command.php', {method:'POST', headers: HEADERS, body: JSON.stringify({bot_id: parseInt(b.id), cmd: 'get_auth_token'})});
      }catch(e){}
    }
    document.getElementById('tokenStatus').textContent = `Commands queued — polling for tokens (bots reply in ~3s)…`;
    // poll state every 1.5s for ~12s to update masked tokens
    let polls = 0;
    if(tokenPollTimer) clearInterval(tokenPollTimer);
    tokenPollTimer = setInterval(async ()=>{
      polls++;
      try{
        const pr = await fetch('api/state.php', {headers: HEADERS});
        const pj = await pr.json();
        if(pj.ok){ bots = pj.bots||bots; render(); renderTokensTable(); }
      }catch{}
      if(polls >= 10){ clearInterval(tokenPollTimer); document.getElementById('tokenStatus').textContent = `Done — ${bots.filter(b=>b.auth_token).length}/${bots.length} token(s) loaded. Select a device then Register SIMs under package.`; btn.disabled=false; btn.textContent='Load devices & tokens'; }
    }, 1500);
  }catch(e){
    document.getElementById('tokenStatus').textContent = 'Load failed: '+e.message;
    btn.disabled=false; btn.textContent='Load devices & tokens';
  }
}
function deleteAccountPlaceholder(){
  const bid = getSelectedBotId();
  if(!bid) return alert('Select a device first');
  alert('Delete Account flow not yet implemented (placeholder). Selected bot '+bid+' would dispatch delete_account command.');
}
function openSimRegisterFlow(){
  const bid = getSelectedBotId();
  if(!bid) return alert('Select a device (checkbox) first');
  document.getElementById('simMappingArea').style.display = 'block';
  document.getElementById('simBotSelect').value = bid;
  document.getElementById('simFetchStatus').textContent = 'Ready — click Fetch SIMs & Packages';
  document.getElementById('simMappingArea').scrollIntoView({behavior:'smooth'});
}
async function fetchSimsAndPackages(){
  const bid = document.getElementById('simBotSelect').value;
  if(!bid) return alert('Select device');
  const status = document.getElementById('simFetchStatus');
  const wrap = document.getElementById('simMappingTableWrap');
  const btnReg = document.getElementById('btnRegisterSims');
  status.textContent = 'Fetching SIMs…';
  wrap.innerHTML = '<div class="muted">Loading…</div>';
  btnReg.disabled = true;
  simsCache = []; packagesCache = [];
  try{
    const [simsRes, pkgsRes] = await Promise.all([
      fetch('api/sims.php?bot_id='+encodeURIComponent(bid), {headers: HEADERS}),
      fetch('api/packages.php?bot_id='+encodeURIComponent(bid), {headers: HEADERS})
    ]);
    const simsJ = await simsRes.json();
    const pkgsJ = await pkgsRes.json();
    if(!simsJ.ok) throw new Error('SIMs: '+(simsJ.error||simsJ.body||'unknown'));
    if(!pkgsJ.ok) throw new Error('Packages: '+(pkgsJ.error||pkgsJ.body||'unknown'));
    simsCache = simsJ.sims||[];
    packagesCache = pkgsJ.packages||[];
    status.textContent = `${simsCache.length} SIM(s), ${packagesCache.length} package(s) — select per SIM then Register`;
    if(!simsCache.length){
      wrap.innerHTML = '<div class="muted">No SIMs found for this account. Register physical SIM first.</div>';
      return;
    }
    // dedup already done server-side, but keep as-is
    const pkgOptions = ['<option value="">— not register —</option>'].concat(packagesCache.map(p=>`<option value="${esc(p.categoryId)}">${esc(p.name)} — $${esc(p.price)} (${esc(p.carrier)})</option>`)).join('');
    wrap.innerHTML = `<table>
      <thead><tr><th>SIM</th><th>Carrier</th><th>Status</th><th>Current pkg</th><th>Select package</th></tr></thead>
      <tbody>${simsCache.map((s,i)=>`
        <tr>
          <td><b>${esc(s.phoneNumber)}</b><br><span style="color:#64748b;font-size:11px">${esc(s.id)}</span></td>
          <td>${esc(s.carrier)}</td>
          <td>${esc(s.status)}</td>
          <td>${esc(s.package?.name || s.package?.variantLabel || 'none')}</td>
          <td><select data-sim="${esc(s.id)}" style="min-width:220px">${pkgOptions}</select></td>
        </tr>
      `).join('')}</tbody>
    </table>`;
    btnReg.disabled = false;
  }catch(e){
    status.textContent = 'Fetch failed: '+e.message;
    wrap.innerHTML = `<div class="muted" style="color:#f87171">Error: ${esc(e.message)}</div>`;
  }
}
async function registerSims(){
  const bid = document.getElementById('simBotSelect').value;
  if(!bid) return alert('Select device');
  const selects = document.querySelectorAll('#simMappingTableWrap select[data-sim]');
  const mappings = [...selects].map(s=>({simId: s.getAttribute('data-sim'), packageId: s.value || null}));
  const toRegister = mappings.filter(m=>m.packageId);
  if(!toRegister.length) return alert('Select at least one package (non-empty)');
  if(!confirm(`Register ${toRegister.length} SIM(s) using bot ${bid}?`)) return;
  const btn = document.getElementById('btnRegisterSims');
  btn.disabled = true; btn.textContent = 'Registering…';
  const resultEl = document.getElementById('registerResult');
  resultEl.style.display = 'block';
  resultEl.innerHTML = '<div class="muted">Posting to api/register_sims.php…</div>';
  try{
    const r = await fetch('api/register_sims.php', {method:'POST', headers: HEADERS, body: JSON.stringify({bot_id: parseInt(bid), mappings})});
    const j = await r.json();
    if(!j.ok) throw new Error(j.error||'unknown');
    const lines = j.results.map(rr=>{
      if(rr.skipped) return `<div class="log-line"><span style="color:#94a3b8">[skip]</span> ${esc(rr.simId)} — ${esc(rr.msg||rr.error||'skipped')}</div>`;
      const col = rr.ok ? '#22c55e' : '#ef4444';
      const detail = rr.ok ? JSON.stringify(rr.response).slice(0,180) : esc(rr.error||'fail');
      return `<div class="log-line"><span style="color:${col}">${rr.ok?'[ok]':'[fail]'}</span> ${esc(rr.simId)} → ${esc(rr.packageId)} (http ${esc(rr.http)}) ${detail}</div>`;
    }).join('');
    resultEl.innerHTML = `<div style="color:#e2e8f0">Bot ${esc(bid)} — refresh cmd ${esc(j.refresh_command_id||'queued')}<br>${lines}</div>`;
    document.getElementById('simFetchStatus').textContent = `Registered ${toRegister.length} — bot will refresh (state handler decides next move)`;
  }catch(e){
    resultEl.innerHTML = `<div style="color:#f87171">Register failed: ${esc(e.message)}</div>`;
  }finally{
    btn.disabled = false; btn.textContent = 'Register';
  }
}
</script>
</body>
</html>
