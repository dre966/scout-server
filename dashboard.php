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
  if(!list.length){ el.innerHTML='<div class="muted">No notifications</div>'; return; }
  el.innerHTML=list.map(n=>`<div class="log-line"><span class="log-time">${esc(n.created_at)}</span> <b>[${esc(n.bot_id)}]</b> <span style="color:#fbbf24">${esc(n.type)}</span> ${esc(n.message)} <span style="color:#64748b">${esc(n.details||'')}</span></div>`).join('');
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

function pollNow(){ fetchBots(); }
function resetTimer(){
  const v=parseInt(document.getElementById('autoPoll').value);
  if(timer) clearInterval(timer);
  if(v>0) timer=setInterval(fetchBots, v);
}
setInterval(()=>{ document.getElementById('clock').textContent=new Date().toLocaleString(); },1000);
fetchBots();
timer=setInterval(fetchBots, 2000);
</script>
</body>
</html>
