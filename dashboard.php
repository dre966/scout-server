<?php $BOT_TOKEN = getenv('BOT_TOKEN') ?: 'scout-secret'; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Scout Fleet — Detailed Minimal</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#080c18;--card:#0f1426;--line:rgba(255,255,255,.06);--line2:rgba(255,255,255,.10);--text:#eef2fb;--muted:#8ea0c0;--dim:#6b7fa0;--accent:#3b82f6;--ok:#22c55e;--warn:#eab308;--danger:#ef4444;--r:12px}
html,body{height:100%}body{font-family:Inter,ui-sans-serif,system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.5;-webkit-font-smoothing:antialiased}
a{color:var(--accent)}
header{position:sticky;top:0;z-index:20;display:flex;align-items:center;gap:10px;padding:10px 14px;background:rgba(8,12,24,.85);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
header h1{font-size:14px;font-weight:800;letter-spacing:.02em;flex:1}
.meta{font-size:11px;color:var(--muted)}
.wrap{max-width:1280px;margin:0 auto;padding:12px 12px 80px}
.card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);overflow:hidden;margin-bottom:12px}
.card-h{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 14px;border-bottom:1px solid var(--line)}
.card-h h2{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.badge{padding:2px 7px;border-radius:999px;font-size:10px;font-weight:800;border:1px solid var(--line)}
.badge-green{background:rgba(34,197,94,.12);color:#86efac;border-color:rgba(34,197,94,.25)}
.badge-yellow{background:rgba(234,179,8,.12);color:#fde68a;border-color:rgba(234,179,8,.25)}
.badge-red{background:rgba(239,68,68,.12);color:#fecaca;border-color:rgba(239,68,68,.25)}
.badge-gray{background:rgba(255,255,255,.04);color:#cbd5e1}
.dot{width:7px;height:7px;border-radius:50%;display:inline-block}
.dot-green{background:var(--ok)} .dot-yellow{background:var(--warn)} .dot-red{background:var(--danger)}
.controls{display:flex;flex-wrap:wrap;gap:6px;padding:10px}
.controls input,.controls select{padding:7px 10px;border-radius:8px;border:1px solid var(--line);background:#0a0f1f;color:var(--text);font-size:12px;min-height:34px}
.btn{padding:7px 10px;border-radius:8px;border:1px solid var(--line);background:transparent;color:var(--text);font-weight:700;font-size:11px;cursor:pointer}
.btn-primary{background:var(--accent);border-color:var(--accent);color:#fff}
.btn-danger{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.3);color:#fecaca}
.btn:disabled{opacity:.5}
.table-wrap{overflow:auto}
table{width:100%;border-collapse:collapse;font-size:12px}
th,td{padding:8px 10px;text-align:left;border-bottom:1px solid var(--line);white-space:nowrap}
th{color:var(--dim);font-size:10px;letter-spacing:.05em;text-transform:uppercase;font-weight:700;background:rgba(255,255,255,.015)}
tr:hover td{background:rgba(255,255,255,.02)}
tr.selected td{background:rgba(59,130,246,.08)}
.muted{color:var(--muted);font-size:11px}
.small{font-size:10px;color:var(--dim)}
.logs{max-height:44vh;overflow:auto;padding:8px;font-family:ui-monospace,monospace;font-size:11px;background:#0a0f1f;border-top:1px solid var(--line)}
.log-line{padding:4px 0;border-bottom:1px solid var(--line)}
.tab{display:none}.tab.active{display:block}
.nav{position:fixed;bottom:0;left:0;right:0;display:flex;gap:6px;padding:8px;background:rgba(8,12,24,.9);border-top:1px solid var(--line)}
.nav button{flex:1;padding:8px;border-radius:10px;border:1px solid transparent;background:transparent;color:var(--muted);font-size:11px;font-weight:700}
.nav button.active{background:rgba(255,255,255,.06);color:var(--text);border-color:var(--line)}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;padding:16px;z-index:50}
.sheet{width:min(640px,96vw);max-height:86vh;overflow:auto;background:var(--card);border:1px solid var(--line2);border-radius:16px}
.sheet-h{padding:12px 14px;border-bottom:1px solid var(--line);font-weight:800;display:flex;align-items:center;justify-content:space-between}
.sheet-b{padding:12px}
.detail-grid{display:grid;grid-template-columns:120px 1fr;gap:6px 12px;font-size:12px}
.detail-grid dt{color:var(--muted)} .detail-grid dd{color:var(--text);word-break:break-all}
.progress{height:6px;background:rgba(255,255,255,.08);border-radius:999px;overflow:hidden}
.progress>div{height:100%}
</style></head><body>
<header><h1>⬢ Scout Fleet — Detailed</h1><span class="meta" id="status">—</span><span class="meta" id="clock"></span><button class="btn" onclick="pollNow()">↻ Refresh</button></header>
<div class="wrap">
<div id="bannerHigh" style="display:none;margin:10px 0;padding:10px 12px;border-radius:10px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fecaca;font-weight:700;font-size:12px"></div>
<div style="display:flex;gap:6px;margin-bottom:8px">
<button class="btn" id="tFleet" onclick="showTab('fleet')" style="flex:1">Fleet</button>
<button class="btn" id="tLive" onclick="showTab('live')" style="flex:1">Live</button>
<button class="btn" id="tDevices" onclick="showTab('devices')" style="flex:1">Devices</button>
<button class="btn" id="tAlerts" onclick="showTab('alerts')" style="flex:1">Alerts</button>
</div>

<div id="tab-fleet" class="tab active">
<div class="card">
<div class="card-h"><h2>Fleet — <span id="botCount">0</span> bots</h2><div style="display:flex;gap:6px"><input id="filter" placeholder="filter id/email/state" oninput="render()" style="width:160px"><select id="autoPoll" onchange="resetTimer()"><option value="2000" selected>2s</option><option value="5000">5s</option><option value="0">off</option></select></div></div>
<div class="table-wrap" style="max-height:58vh"><table><thead><tr><th>#</th><th>State / Progress</th><th>Proxy → Poll</th><th>HB / Uptime</th><th>URL</th><th>Actions</th></tr></thead><tbody id="tbody"></tbody></table></div>
<div id="botsMuted" class="muted" style="display:none">No bots — check <code>SERVER_URL</code> + <code>BOT_ID</code></div>
<div class="controls">
<input id="customBotId" placeholder="bot_id" type="number" style="width:80px">
<select id="cmdSelect" onchange="onCmdChange()"><option value="PAUSE">PAUSE</option><option value="RESUME">RESUME</option><option value="RESTART">RESTART</option><option value="LOGOUT">LOGOUT</option></select>
<input id="cmdArgs" placeholder='args {"wait":60}' style="flex:1">
<button class="btn btn-primary" onclick="sendCustomCommand()">Send</button>
<span id="logoutHint" style="display:none;gap:6px;align-items:center"><input id="logoutWait" type="number" value="60" style="width:70px"><button class="btn btn-danger" onclick="sendLogout()">Logout & Wait</button></span>
</div>
</div>
</div>

<div id="tab-live" class="tab">
<div class="card"><div class="card-h"><h2>Live — Bot <span id="liveId">—</span></h2><div style="display:flex;gap:6px"><button class="btn" onclick="loadLogs()">Logs</button><button class="btn" onclick="loadNotifications()">Notes</button><button class="btn btn-primary" onclick="openCallStatus()">Call Status</button></div></div>
<div id="botDetail" class="muted">Select a bot in Fleet.</div>
<div id="liveDetail" class="muted" style="display:none"></div>
<div id="logs" class="logs" style="display:none"></div>
<div id="notifs" class="logs" style="display:none"></div>
<div id="liveLogs" class="logs" style="display:none"></div>
<div class="controls"><button class="btn" onclick="sendCmd('PAUSE')">Pause</button><button class="btn" onclick="sendCmd('RESUME')">Resume</button><button class="btn btn-danger" onclick="sendCmd('RESTART')">Restart</button><span style="display:flex;gap:6px;flex:1"><input id="logoutWaitLive" type="number" value="60" style="width:70px"><button class="btn btn-danger" onclick="sendLogoutLive()" style="flex:1">Logout & Wait</button></span></div>
</div>
</div>

<div id="tab-devices" class="tab">
<div class="card">
<div class="card-h"><h2>Devices & SIMs</h2><button class="btn btn-primary" id="btnLoadTokens" onclick="loadDevicesAndTokens()">Load tokens</button></div>
<div class="table-wrap" style="max-height:30vh"><table><thead><tr><th><input type="checkbox" id="chkAll" onchange="toggleAll(this.checked)"></th><th>Bot</th><th>Proxy → Poll</th><th>Token</th><th>State / HB</th></tr></thead><tbody id="tokenTbody"></tbody></table></div>
<div id="tokenStatus" class="muted">No tokens yet — heartbeat → Load.</div>
<div class="controls" id="postTokenControls" style="display:none">
<button class="btn btn-primary" onclick="goToSite()">↗ Go to site</button>
<button class="btn btn-primary" onclick="openCallStatus()">Call Status</button>
<button class="btn btn-secondary" onclick="copyLicenseCapture()">Copy Supabase + License</button>
<button class="btn btn-danger" onclick="deleteAccountNow()">Delete Account</button>
<button class="btn btn-primary" onclick="openSimRegisterFlow()">Register SIMs</button>
</div>
<div id="simMappingArea" style="display:none;border-top:1px solid var(--line);padding:10px">
<div style="display:flex;gap:8px;align-items:center;margin-bottom:8px"><label style="font-size:12px">Device <select id="simBotSelect"></select></label><button class="btn" onclick="fetchSimsAndPackages()">Fetch SIMs & Packages</button><span id="simFetchStatus" class="muted"></span></div>
<div id="simMappingTableWrap" class="table-wrap" style="max-height:36vh"></div>
<div class="controls"><button class="btn btn-primary" id="btnRegisterSims" onclick="registerSims()" disabled>Register</button><span class="muted">Empty = skip.</span></div>
<div id="registerResult" class="logs" style="display:none"></div>
</div>
</div>
</div>

<div id="tab-alerts" class="tab">
<div class="card"><div class="card-h"><h2>Alerts — High & Recent</h2></div><div id="globalNotifs" class="logs" style="max-height:70vh"></div></div>
</div>
</div>

<div id="callStatusOverlay" class="overlay" onclick="if(event.target===this) closeCallStatus()"><div class="sheet"><div class="sheet-h">Call Status — Bot <span id="csBotId">—</span> <span id="csUpdated" class="muted"></span> <button class="btn" onclick="closeCallStatus()" style="margin-left:auto">✕</button></div><div class="sheet-b" id="csBody" style="max-height:62vh;overflow:auto"></div><div style="display:flex;gap:8px;justify-content:flex-end;padding:10px;border-top:1px solid var(--line)"><button class="btn" onclick="closeCallStatus()">Close</button><button class="btn btn-primary" onclick="refreshCallStatus()">Refresh</button></div></div></div>
<div id="deleteOverlay" class="overlay" onclick="if(event.target===this) closeDeleteModal()"><div class="sheet"><div class="sheet-h">Delete Account — Bot <span id="deleteBotId">—</span></div><div class="sheet-b"><div class="muted">DELETE https://scoutandrunner.com/api/auth/delete-account {"confirmation":"DELETE_MY_ACCOUNT"}</div><input id="deleteConfirm" placeholder="DELETE_MY_ACCOUNT"><div id="deleteResult" class="muted" style="display:none"></div></div><div style="display:flex;gap:8px;justify-content:flex-end;padding:10px;border-top:1px solid var(--line)"><button class="btn" onclick="closeDeleteModal()">Cancel</button><button class="btn btn-danger" id="btnDeleteConfirm" onclick="confirmDeleteAccount()">Delete</button></div></div></div>

<nav class="nav"><button id="nFleet" class="active" onclick="showTab('fleet')">Fleet</button><button id="nLive" onclick="showTab('live')">Live</button><button id="nDevices" onclick="showTab('devices')">Devices</button><button id="nAlerts" onclick="showTab('alerts')">Alerts</button></nav>
<script>
const TOKEN = <?= json_encode($BOT_TOKEN) ?>;
const HEADERS = {'Content-Type':'application/json','X-Bot-Token': TOKEN};
let bots=[],selected=null,timer=null;
function fmtAge(s){if(!s) return '<span class="badge badge-gray">never</span>';let t=s.replace(' ','T');if(!/[Z+\-]/.test(t.slice(10))) t+='Z';else if(/\+\d{2}$/.test(t)) t+=':00';const d=new Date(t);const diff=Math.floor((Date.now()-d.getTime())/1000);if(isNaN(diff)) return s;let cls='badge-red',dot='dot-red',label=diff+'s ago';if(diff<15){cls='badge-green';dot='dot-green';}else if(diff<60){cls='badge-yellow';dot='dot-yellow';}else if(diff<300){cls='badge-yellow';}if(diff<60) label=diff+'s ago';else if(diff<3600) label=Math.floor(diff/60)+'m ago';else label=Math.floor(diff/3600)+'h ago';return `<span class="dot ${dot}"></span><span class="badge ${cls}">${label}</span>`}
function fmtTime(s){if(!s) return '—';try{let t=s.replace(' ','T');if(!/[Z+\-]/.test(t.slice(10))) t+='Z';else if(/\+\d{2}$/.test(t)) t+=':00';return new Date(t).toLocaleString();}catch{return s;}}
function esc(s){return String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]))}
async function fetchBots(){try{const c=new AbortController();const to=setTimeout(()=>c.abort(),4000);const r=await fetch('api/state.php',{headers:HEADERS,signal:c.signal});clearTimeout(to);const j=await r.json();if(j.ok){bots=j.bots||[];render();renderGlobalNotifs(j.notifications||[]);document.getElementById('status').textContent=bots.length+' bots • '+new Date().toLocaleTimeString();}else document.getElementById('status').textContent='error: '+(j.error||'unknown');if(selected) loadLogs(false);}catch(e){document.getElementById('status').textContent='poll '+e.message;}}
function render(){
  const q=document.getElementById('filter').value.toLowerCase();
  const tbody=document.getElementById('tbody'); const count=document.getElementById('botCount'); const muted=document.getElementById('botsMuted');
  let filtered=bots; if(q) filtered=bots.filter(b=> String(b.id).includes(q) || (b.proxy_email||'').toLowerCase().includes(q) || (b.state||'').toLowerCase().includes(q));
  count.textContent=filtered.length; muted.style.display=filtered.length?'none':'block';
  tbody.innerHTML=filtered.map(b=>{
    const sims=b.sims_count??0; const pct=Math.min(100,Math.round((sims/8)*100));
    return `<tr class="${selected==b.id?'selected':''}" onclick="selectBot(${b.id})" style="cursor:pointer">
      <td><b>${esc(b.id)}</b><div class="small">${esc((b.container_id||'').slice(0,10))}</div></td>
      <td><div><span class="badge ${b.state==='scout_dashboard'?'badge-green':'badge-gray'}">${esc((b.state||'—').slice(0,18))}</span></div><div class="progress" style="margin-top:4px"><div style="width:${pct}%;background:${pct>=100?'var(--danger)':pct>=50?'var(--warn)':'var(--ok)'}"></div></div><div class="small">${sims}/8 sims</div></td>
      <td><div>${esc((b.proxy_email||'').split('@')[0])}</div><div class="small">${esc(b.poll_inbox||'')}</div></td>
      <td>${fmtAge(b.heartbeat_at)}<div class="small">${esc((b.current_url||'').slice(0,22))}</div></td>
      <td><div style="display:flex;gap:4px"><button class="btn" onclick="event.stopPropagation();sendWake(${b.id})">Wake</button><button class="btn" onclick="event.stopPropagation();sendSleep(${b.id})">Sleep</button><button class="btn btn-danger" onclick="event.stopPropagation();removeBot(${b.id})">✕</button><button class="btn" onclick="event.stopPropagation();selectBot(${b.id});sendCmd('RESTART')">↻</button></div></td>
    </tr>`;
  }).join('');
}
let bannerTimer=null;
function renderGlobalNotifs(list){
  const el=document.getElementById('globalNotifs'); const bHigh=document.getElementById('bannerHigh');
  const highAll=list.filter(n=> ['nosimsregistered','nonumberstotest','accountdeleted','stuckslots'].includes(String(n.type||'').toLowerCase()));
  const high=highAll.filter(n=>{let t=n.created_at.replace(' ','T');if(!/[Z+\-]/.test(t.slice(10))) t+='Z';else if(/\+\d{2}$/.test(t)) t+=':00';const d=new Date(t).getTime();return !isNaN(d) && (Date.now()-d)<120000;});
  if(bHigh){
    if(high.length){bHigh.style.display='block';bHigh.innerHTML='🚨 '+high.map(h=>`${esc(h.type)} — Bot ${esc(h.bot_id)}: ${esc(h.message)} <span style="opacity:.7">(${fmtLogAge(h.created_at)})</span>`).join(' • '); if(bannerTimer) clearTimeout(bannerTimer); bannerTimer=setTimeout(()=>{bHigh.style.display='none';},120000);}
    else {bHigh.style.display='none'; if(bannerTimer){clearTimeout(bannerTimer);bannerTimer=null;}}
  }
  if(!list.length){el.innerHTML='<div class="muted">No notifications</div>';return;}
  el.innerHTML=list.map(n=>{
    const t=String(n.type||'').toLowerCase(); const isHigh=['nosimsregistered','nonumberstotest','accountdeleted','stuckslots'].includes(t);
    return `<div class="log-line"><span class="small">${esc(fmtLogAge(n.created_at))}</span> <b>[${esc(n.bot_id)}]</b> <span style="color:${isHigh?'#fecaca':'#fbbf24'}">${esc(n.type)}</span>${isHigh?' <span class="badge badge-red">HIGH</span>':''} ${esc(n.message)}<div class="small">${esc((n.details||'').slice(0,200))}</div></div>`;
  }).join('');
}
function showTab(name){document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));document.getElementById('tab-'+name).classList.add('active');document.querySelectorAll('#tFleet,#tLive,#tDevices,#tAlerts,#nFleet,#nLive,#nDevices,#nAlerts').forEach(b=>b.classList.remove('active'));['t','n'].forEach(p=>{const el=document.getElementById(p+name[0].toUpperCase()+name.slice(1)); if(el) el.classList.add('active');}); if(name==='live'&&selected) loadLogs(false); location.hash=name;}
window.addEventListener('hashchange',()=>{const h=location.hash.replace('#','');if(['fleet','live','devices','alerts'].includes(h)) showTab(h);});
async function selectBot(id){selected=id;try{document.getElementById('liveId').textContent=id;}catch(e){}render();showTab('live');const det=document.getElementById('botDetail');if(det){det.style.display='block';det.innerHTML='Bot '+id+' — loading…';}const logsEl=document.getElementById('logs');if(logsEl) logsEl.style.display='block';const notifsEl=document.getElementById('notifs');if(notifsEl) notifsEl.style.display='none';await loadLogs(false);updateLiveDetail();}
function updateLiveDetail(){
  if(selected===null||selected===undefined||selected==='') return;
  const b=bots.find(x=>String(x.id)===String(selected));
  const el=document.getElementById('liveDetail');
  if(!b){el.style.display='none';return;}
  el.style.display='block';
  el.innerHTML=`<dl class="detail-grid"><dt>Proxy</dt><dd>${esc(b.proxy_email||'—')} → ${esc(b.poll_inbox||'—')}</dd><dt>State</dt><dd><span class="badge badge-gray">${esc(b.state||'—')}</span> <span class="small">${esc(b.current_url||'')}</span></dd><dt>Sims</dt><dd>${esc(b.sims_count??0)}/8 <span class="small">container ${esc(b.container_id||'')}</span></dd><dt>HB</dt><dd>${fmtAge(b.heartbeat_at)} <span class="small">${esc(b.heartbeat_at||'')}</span></dd><dt>Token</dt><dd>${b.auth_token?esc(b.auth_token.slice(0,6))+'…'+esc(b.auth_token.slice(-4)):'—'} <span class="small">${b.token_updated_at?fmtTime(b.token_updated_at):''}</span></dd></dl>`;
}
function fmtLogAge(s){if(!s) return '';let t=s.replace(' ','T');if(!/[Z+\-]/.test(t.slice(10))) t+='Z';else if(/\+\d{2}$/.test(t)) t+=':00';const d=new Date(t);const diff=Math.floor((Date.now()-d.getTime())/1000);if(isNaN(diff)) return esc(s);if(diff<60) return diff+'s ago';if(diff<3600) return Math.floor(diff/60)+'m ago';return Math.floor(diff/3600)+'h ago';}
async function loadLogs(showNotif){
  if(selected===null||selected===undefined||selected==='') return;
  try{
    const r=await fetch('api/state.php?bot_id='+selected,{headers:HEADERS});const j=await r.json();if(!j.ok) throw new Error(j.error);
    const html=(j.logs&&j.logs.length)?j.logs.map(l=>{const age=fmtLogAge(l.created_at);return `<div class="log-line"><span class="small">${esc(age)}</span> <span style="color:#7dd3fc">[${esc(l.state||'')}]</span> ${esc(l.message||'')} <span class="small">${esc((l.current_url||'').slice(0,40))}</span></div>`;}).join(''):'<div class="muted">No logs</div>';
    const a=document.getElementById('logs');if(a) a.innerHTML=html;const b=document.getElementById('liveLogs');if(b) b.innerHTML=html;updateLiveDetail();
  }catch(e){const m='<div class="muted">load '+esc(e.message)+'</div>';const a=document.getElementById('logs');if(a) a.innerHTML=m;const b=document.getElementById('liveLogs');if(b) b.innerHTML=m;}
}
async function loadNotifications(){if(selected===null||selected===undefined||selected==='') return alert('Select a bot');document.getElementById('logs').style.display='none';document.getElementById('notifs').style.display='block';try{const r=await fetch('api/state.php?bot_id='+selected,{headers:HEADERS});const j=await r.json();const list=j.notifications||[];document.getElementById('notifs').innerHTML=list.length?list.map(n=>`<div class="log-line"><span class="small">${esc(fmtLogAge(n.created_at))}</span> <b>${esc(n.type)}</b> ${esc(n.message)}<div class="small">${esc(n.details||'')}</div></div>`).join(''):'<div class="muted">No notifications</div>';}catch(e){document.getElementById('notifs').innerHTML='error '+esc(e.message);}}
function onCmdChange(){const v=document.getElementById('cmdSelect').value;document.getElementById('logoutHint').style.display=(v==='LOGOUT')?'flex':'none';if(v==='LOGOUT'&&!document.getElementById('cmdArgs').value.trim()) document.getElementById('cmdArgs').value=JSON.stringify({wait:parseInt(document.getElementById('logoutWait').value||60)});}
async function sendLogout(){const bot_id=document.getElementById('customBotId').value;const wait=parseInt(document.getElementById('logoutWait').value||0);if(!bot_id) return alert('bot_id required');await sendCommand(bot_id,'LOGOUT',{wait});}
async function sendLogoutLive(){if(selected===null||selected===undefined||selected==='') return alert('Select a bot');const wait=parseInt(document.getElementById('logoutWaitLive').value||0);await sendCommand(selected,'LOGOUT',{wait});}
async function sendCmd(cmd){if(selected===null||selected===undefined||selected==='') return alert('Select a bot');await sendCommand(selected,cmd,null);}
async function sendCustomCommand(){const bot_id=document.getElementById('customBotId').value;let cmd=document.getElementById('cmdSelect').value;const argsRaw=document.getElementById('cmdArgs').value.trim();let args=null;if(argsRaw){try{args=JSON.parse(argsRaw);}catch{return alert('Invalid JSON');}}if(cmd==='LOGOUT'&&!args) args={wait:parseInt(document.getElementById('logoutWait').value||0)};if(!bot_id) return alert('bot_id required');await sendCommand(bot_id,cmd,args);}
async function sendCommand(bot_id,cmd,args){try{const r=await fetch('api/command.php',{method:'POST',headers:HEADERS,body:JSON.stringify({bot_id:parseInt(bot_id),cmd,args})});const j=await r.json();if(j.ok) alert('Queued '+cmd+' for '+bot_id);else alert('Failed: '+(j.error||'unknown'));}catch(e){alert('Send failed: '+e.message);}}
let fetchInFlight=false;async function fetchBotsSafe(){if(fetchInFlight) return;fetchInFlight=true;try{await fetchBots();}finally{fetchInFlight=false;}}function pollNow(){fetchBotsSafe();fetchTokensTable();}function resetTimer(){const v=parseInt(document.getElementById('autoPoll').value);if(timer) clearInterval(timer);if(v>0) timer=setInterval(fetchBotsSafe,v);}async function sendWake(bot_id){const id=bot_id??selected;if(id===null||id===undefined||id==='') return alert('Select bot');await sendCommand(id,'WAKE',{});}async function sendSleep(bot_id){const id=bot_id??selected;if(id===null||id===undefined||id==='') return alert('Select bot');const v=prompt('Sleep seconds for bot '+id+' (0-86400):','60');if(v===null) return;const wait=parseInt(v||0);if(isNaN(wait)||wait<0) return alert('Invalid');await sendCommand(id,'SLEEP',{wait});}async function removeBot(bot_id){const id=bot_id??selected;if(id===null||id===undefined||id==='') return alert('Select bot');if(!confirm('Remove Bot '+id+'?')) return;try{const r=await fetch('api/delete_bot.php',{method:'POST',headers:HEADERS,body:JSON.stringify({bot_id:parseInt(id)})});const j=await r.json();if(j.ok){if(selected==id) selected=null;fetchBotsSafe();fetchTokensTable();}else alert('Failed: '+(j.error||'unknown'));}catch(e){alert('Remove failed: '+e.message);}}
setInterval(()=>{document.getElementById('clock').textContent=new Date().toLocaleTimeString();},1000);fetchBotsSafe();let timer=setInterval(fetchBotsSafe,5000);
let tokenPollTimer=null;let simsCache=[],packagesCache=[];
function maskToken(t){if(!t) return '<span class="badge badge-gray">—</span>';t=String(t);if(t.length<10) return esc(t);return esc(t.slice(0,6))+'…'+esc(t.slice(-4))+' <span style="color:var(--ok)">●</span>';}
function fmtTime(s){if(!s) return '—';try{let t=s.replace(' ','T');if(!/[Z+\-]/.test(t.slice(10))) t+='Z';else if(/\+\d{2}$/.test(t)) t+=':00';return new Date(t).toLocaleString();}catch{return s;}}
function fetchTokensTable(){if(bots.length) renderTokensTable();}
function renderTokensTable(){
  const tbody=document.getElementById('tokenTbody');const sel=document.getElementById('simBotSelect');if(!tbody) return;
  const checked=new Set([...tbody.querySelectorAll('input[type=checkbox][data-bot]:checked')].map(e=>e.getAttribute('data-bot')));
  tbody.innerHTML=bots.map(b=>{
    const masked=b.auth_token?maskToken(b.auth_token):'<span class="badge badge-gray">—</span>';
    const isChecked=checked.has(String(b.id))?'checked':'';
    return `<tr><td><input type="checkbox" data-bot="${b.id}" ${isChecked} onchange="onTokenCheck()"></td><td><b>${esc(b.id)}</b><div class="small">${esc((b.proxy_email||'').split('@')[0])}</div></td><td>${masked}</td><td><span class="badge badge-gray">${esc(b.state||'—')}</span><div class="small">${fmtAge(b.heartbeat_at)}</div></td></tr>`;
  }).join('');
  if(sel){const prev=sel.value;sel.innerHTML=bots.map(b=>`<option value="${b.id}">Bot ${b.id} — ${esc((b.proxy_email||'').slice(0,16))} ${b.auth_token?'●':''}</option>`).join('');if(prev) sel.value=prev;}
  document.getElementById('tokenStatus').textContent=bots.length?`${bots.length} device(s) — ${bots.filter(b=>b.auth_token).length}/${bots.length} tokens`:'No bots';
  document.getElementById('postTokenControls').style.display=bots.length?'flex':'none';
}
function toggleAll(checked){document.querySelectorAll('#tokenTbody input[type=checkbox][data-bot]').forEach(e=>e.checked=checked);onTokenCheck();}
function onTokenCheck(){const any=document.querySelector('#tokenTbody input[type=checkbox][data-bot]:checked');document.getElementById('postTokenControls').style.display=any?'flex':'none';}
function getSelectedBotId(){const cb=document.querySelector('#tokenTbody input[type=checkbox][data-bot]:checked');if(cb) return cb.getAttribute('data-bot');const sel=document.getElementById('simBotSelect');if(sel&&sel.value) return sel.value;if(selected!==null&&selected!==undefined&&selected!=='') return selected;return bots[0]?.id??null;}
async function loadDevicesAndTokens(){const btn=document.getElementById('btnLoadTokens');btn.disabled=true;btn.textContent='Loading…';try{const r=await fetch('api/state.php',{headers:HEADERS});const j=await r.json();if(j.ok){bots=j.bots||[];render();renderTokensTable();}if(!bots.length){alert('No bots');btn.disabled=false;btn.textContent='Load tokens';return;}document.getElementById('tokenStatus').textContent=`Queuing get_auth_token to ${bots.length} bot(s)…`;for(const b of bots){try{await fetch('api/command.php',{method:'POST',headers:HEADERS,body:JSON.stringify({bot_id:parseInt(b.id),cmd:'get_auth_token'})});}catch(e){}}document.getElementById('tokenStatus').textContent=`Polling tokens…`;let polls=0;if(tokenPollTimer) clearInterval(tokenPollTimer);tokenPollTimer=setInterval(async()=>{polls++;try{const pr=await fetch('api/state.php',{headers:HEADERS});const pj=await pr.json();if(pj.ok){bots=pj.bots||bots;render();renderTokensTable();}}catch{}if(polls>=10){clearInterval(tokenPollTimer);document.getElementById('tokenStatus').textContent=`Done — ${bots.filter(b=>b.auth_token).length}/${bots.length} tokens.`;btn.disabled=false;btn.textContent='Load tokens';}},1500);}catch(e){document.getElementById('tokenStatus').textContent='Load failed: '+e.message;btn.disabled=false;btn.textContent='Load tokens';}}
function openSimRegisterFlow(){const bid=getSelectedBotId();if(bid===null||bid===undefined||bid==='') return alert('Select device');document.getElementById('simMappingArea').style.display='block';document.getElementById('simBotSelect').value=bid;document.getElementById('simFetchStatus').textContent='Ready — Fetch';document.getElementById('simMappingArea').scrollIntoView({behavior:'smooth'});}
async function fetchSimsAndPackages(){const bid=document.getElementById('simBotSelect').value;if(bid===null||bid===undefined||bid==='') return alert('Select device');const status=document.getElementById('simFetchStatus');const wrap=document.getElementById('simMappingTableWrap');const btnReg=document.getElementById('btnRegisterSims');status.textContent='Fetching…';wrap.innerHTML='<div class="muted">Loading…</div>';btnReg.disabled=true;try{const [simsRes,pkgsRes]=await Promise.all([fetch('api/sims.php?bot_id='+encodeURIComponent(bid),{headers:HEADERS}),fetch('api/packages.php?bot_id='+encodeURIComponent(bid),{headers:HEADERS})]);const simsJ=await simsRes.json();const pkgsJ=await pkgsRes.json();if(!simsJ.ok) throw new Error('SIMs: '+(simsJ.error||'unknown'));if(!pkgsJ.ok) throw new Error('Packages: '+(pkgsJ.error||'unknown'));simsCache=simsJ.sims||[];packagesCache=pkgsJ.packages||[];status.textContent=`${simsCache.length} SIM(s), ${packagesCache.length} pkg(s)`;if(!simsCache.length){wrap.innerHTML='<div class="muted">No SIMs.</div>';return;}const pkgOptions=['<option value="">— skip —</option>'].concat(packagesCache.map(p=>`<option value="${esc(p.categoryId)}">${esc(p.name)} — $${esc(p.price)}</option>`)).join('');wrap.innerHTML=`<table><thead><tr><th>SIM</th><th>Status</th><th>Package</th></tr></thead><tbody>${simsCache.map(s=>`<tr><td><b>${esc(s.phoneNumber)}</b><br><span class="small">${esc(s.id)}</span></td><td>${esc(s.status)}</td><td><select data-sim="${esc(s.id)}" style="min-width:180px">${pkgOptions}</select></td></tr>`).join('')}</tbody></table>`;btnReg.disabled=false;}catch(e){status.textContent='Fetch failed: '+e.message;wrap.innerHTML=`<div class="muted" style="color:#f87171">Error: ${esc(e.message)}</div>`;}}
async function registerSims(){const bid=document.getElementById('simBotSelect').value;if(bid===null||bid===undefined||bid==='') return alert('Select device');const mappings=[...document.querySelectorAll('#simMappingTableWrap select[data-sim]')].map(s=>({simId:s.getAttribute('data-sim'),packageId:s.value||null}));const toRegister=mappings.filter(m=>m.packageId);if(!toRegister.length) return alert('Select at least one package');if(!confirm(`Register ${toRegister.length} SIM(s) via bot ${bid}?`)) return;const btn=document.getElementById('btnRegisterSims');btn.disabled=true;btn.textContent='Registering…';const resultEl=document.getElementById('registerResult');resultEl.style.display='block';resultEl.innerHTML='<div class="muted">Posting…</div>';try{const r=await fetch('api/register_sims.php',{method:'POST',headers:HEADERS,body:JSON.stringify({bot_id:parseInt(bid),mappings})});const j=await r.json();if(!j.ok) throw new Error(j.error||'unknown');resultEl.innerHTML=j.results.map(rr=> rr.skipped ? `<div class="log-line" style="color:var(--muted)">[skip] ${esc(rr.simId)}</div>` : `<div class="log-line"><span style="color:${rr.ok?'var(--ok)':'var(--danger)'}">${rr.ok?'[ok]':'[fail]'}</span> ${esc(rr.simId)} → ${esc(rr.packageId)} (http ${esc(rr.http)}) ${esc((rr.error||JSON.stringify(rr.response||'')).slice(0,120))}</div>`).join('');}catch(e){resultEl.innerHTML=`<div style="color:#f87171">Failed: ${esc(e.message)}</div>`;}finally{btn.disabled=false;btn.textContent='Register';}}
async function goToSite(){const bid=getSelectedBotId();if(bid===null||bid===undefined||bid==='') return alert('Select device checkbox first');try{const r=await fetch('api/unetwork_fresh_code.php?bot_id='+encodeURIComponent(bid),{headers:HEADERS});const j=await r.json();if(!j.ok||!j.code) throw new Error(j.error||'no code');window.open('https://scoutandrunner.com/auth/unetwork?code='+encodeURIComponent(j.code),'_blank');}catch(e){alert('Go to site failed: '+e.message+' — bot must hit license_select to capture supabase+license first');}}
async function copyLicenseCapture(){const bid=getSelectedBotId();if(bid===null||bid===undefined||bid==='') return alert('Select device checkbox first');try{const r=await fetch('api/license_capture.php?bot_id='+encodeURIComponent(bid),{headers:HEADERS});const j=await r.json();if(!j.ok) throw new Error(j.error);const txt=`supabaseToken:\n${j.supabaseToken||'—'}\n\nlicenseId:\n${j.licenseId||'—'}`;await navigator.clipboard.writeText(txt);alert('Copied supabaseToken + licenseId');}catch(e){alert('Copy failed: '+e.message);}}
function openCallStatus(){const bid=getSelectedBotId();if(bid===null||bid===undefined||bid==='') return alert('Select a device (checkbox) in Devices');document.getElementById('csBotId').textContent=bid;document.getElementById('csUpdated').textContent='';document.getElementById('csBody').innerHTML='<div class="muted">Loading…</div>';document.getElementById('callStatusOverlay').style.display='flex';refreshCallStatus();}
function closeCallStatus(){document.getElementById('callStatusOverlay').style.display='none';}
async function refreshCallStatus(){
  const bid=getSelectedBotId() ?? selected; if(bid===null||bid===undefined||bid==='') return;
  const body=document.getElementById('csBody'); const upd=document.getElementById('csUpdated');
  try{
    let sims=[],updated=null;
    try{const r=await fetch('api/sims_status.php?bot_id='+encodeURIComponent(bid),{headers:HEADERS});const j=await r.json();if(j.ok){sims=j.sims||[];updated=j.updated_at;}}catch(e){}
    if(!sims.length || (sims.length===1 && sims[0].phone==='dashboard_count')){
      try{const rs=await fetch('api/scout_sims.php?bot_id='+encodeURIComponent(bid),{headers:HEADERS});const js=await rs.json();if(js.ok&&js.sims&&js.sims.length){sims=js.sims.map(s=>{const cyc=parseInt(s.testsInCycle??0);return {phone:s.phoneNumber||'?',cycle:cyc,status:s.status||'?',isMax:cyc>=8,isCurrent:false,cooldownEndsAt:s.cooldownEndsAt||null,id:s.id};});updated='live-scout';} }catch(e){}
    }
    if(!sims.length || (sims.length===1 && sims[0].phone==='dashboard_count')){
      try{const rr=await fetch('api/sims.php?bot_id='+encodeURIComponent(bid),{headers:HEADERS});const jr=await rr.json();if(jr.ok&&jr.sims&&jr.sims.length){sims=jr.sims.map(s=>{const cyc=parseInt(s.testsInCycle??0);return {phone:s.phoneNumber||'?',cycle:cyc,status:s.status||'?',isMax:cyc>=8,isCurrent:false,cooldownEndsAt:s.cooldownEndsAt||null,id:s.id};});updated='live-runner';} }catch(e){}
    }
    if(upd) upd.textContent=updated?' — '+(updated==='live'?'live':fmtTime(updated)):'';
    if(!sims.length || (sims.length===1 && sims[0].phone==='dashboard_count')){body.innerHTML='<div class="muted">No SIM data — bot not yet reported and live fetch empty. Dashboard count: '+(sims[0]?.status||'—')+'</div>';return;}
    const maxed=sims.filter(s=>s.isMax),cur=sims.find(s=>s.isCurrent)||null;
    let html=`<div class="muted" style="padding:0 0 8px">${sims.length} SIM(s) • <span style="color:#fecaca">${maxed.length} at 8/8 max</span> • current ${cur?cur.phone+' '+cur.cycle+'/8':'—'}</div>`;
    html+=sims.map(s=>{const pct=Math.min(100,Math.round((s.cycle/8)*100));const barColor=s.isMax?'#ef4444':(s.isCurrent?'#3b82f6':'#22c55e');const badge=s.isMax?'<span class="badge badge-red">MAX 8/8</span>':(s.isCurrent?'<span class="badge badge-green">CURRENT '+s.cycle+'/8</span>':'<span class="badge badge-gray">'+s.cycle+'/8</span>');const border=s.isCurrent?'border:1px solid #3b82f6;':(s.isMax?'border:1px solid rgba(239,68,68,.3);':'');return `<div style="background:rgba(255,255,255,.04);border-radius:10px;padding:8px;margin-bottom:6px;${border}"><div style="display:flex;justify-content:space-between;gap:8px;align-items:center"><b>${esc(s.phone)}</b> ${badge}</div><div style="height:6px;background:rgba(255,255,255,.08);border-radius:999px;margin-top:6px;overflow:hidden"><div style="width:${pct}%;height:100%;background:${barColor}"></div></div><div class="muted" style="padding:2px 0 0;font-size:10px">${esc(s.status)}${s.cooldownEndsAt?' • cooldown '+fmtTime(s.cooldownEndsAt):''}</div></div>`;}).join('');
    body.innerHTML=html;
  }catch(e){body.innerHTML='<div class="muted" style="color:#f87171">Load failed: '+esc(e.message)+'</div>';}
}
async function deleteAccountNow(){const bid=getSelectedBotId();if(bid===null||bid===undefined||bid==='') return alert('Select device checkbox first');if(!confirm('Delete account for bot '+bid+'? This is irreversible.')) return;const overlay=document.getElementById('deleteOverlay');const resEl=document.getElementById('deleteResult');document.getElementById('deleteBotId').textContent=bid;if(resEl){resEl.style.display='block';resEl.innerHTML='<span class="muted">Deleting…</span>';}if(overlay) overlay.style.display='flex';try{const r=await fetch('api/delete_account.php',{method:'POST',headers:HEADERS,body:JSON.stringify({bot_id:parseInt(bid),confirmation:'DELETE_MY_ACCOUNT'})});const t=await r.text();let j;try{j=JSON.parse(t);}catch{j={raw:t,http:r.status}}if(r.ok&&(j.ok||r.status===200)){if(resEl) resEl.innerHTML=`<span style="color:var(--ok)">✓ Deleted (http ${r.status}) — bot → landing</span>`;setTimeout(()=>{const o=document.getElementById('deleteOverlay');if(o) o.style.display='none';fetchBots();},900);}else{if(resEl) resEl.innerHTML=`<span style="color:var(--danger)">✗ Failed http ${r.status}</span><br><span style="color:var(--muted)">${esc(t.slice(0,600))}</span>`;}}catch(e){if(resEl) resEl.innerHTML=`<span style="color:var(--danger)">Error: ${esc(e.message)}</span>`;}}
function openDeleteModal(){return deleteAccountNow();}
function closeDeleteModal(){const o=document.getElementById('deleteOverlay');if(o) o.style.display='none';}
async function confirmDeleteAccount(){return deleteAccountNow();}
</script></body></html>
