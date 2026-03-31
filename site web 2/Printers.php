<?php
// ── Authentification ──────────────────────────────────────────
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ── Config imprimantes (depuis DB ou tableau statique) ────────
// Option A : depuis la base de données
/*
require_once 'db.php'; // ton fichier de connexion PDO
$stmt = $pdo->query("SELECT id, name, host, port FROM printers ORDER BY id");
$printers = $stmt->fetchAll(PDO::FETCH_ASSOC);
*/

// Option B : tableau statique (identique à l'ancien JS)
$printers = [
    ['id' => 1, 'name' => 'Ultimaker 2+',          'host' => '192.168.0.19',  'port' => 5000],
    ['id' => 2, 'name' => 'Creality Ender V2 Neo', 'host' => '192.168.1.101', 'port' => 5000],
];

// Serialisation JSON pour injection dans le JS
$printersJson = json_encode($printers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>SGI3D – Imprimantes</title>
<link rel="stylesheet" href="style.css">
<style>
body{padding-top:60px}
.printers-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(500px,1fr));gap:1.5rem}
.printer-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:16px;overflow:hidden;transition:border-color .2s,box-shadow .2s}
.printer-card:hover{border-color:rgba(255,255,255,.2);box-shadow:0 8px 32px rgba(0,0,0,.3)}
.printer-card.offline{opacity:.7}
.pc-header{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.4rem;background:rgba(0,0,0,.25);border-bottom:1px solid rgba(255,255,255,.07)}
.pc-title{display:flex;align-items:center;gap:.8rem}
.pc-title h2{font-size:1rem;font-weight:700;margin:0}
.pc-ip{font-size:.72rem;color:rgba(255,255,255,.35);font-family:monospace;margin-top:2px}
.pc-status{display:flex;align-items:center;gap:.4rem;font-size:.78rem;font-weight:600;padding:.3rem .9rem;border-radius:20px;border:1px solid}
.st-online  {color:#2ecc71;border-color:rgba(46,204,113,.35);background:rgba(46,204,113,.08)}
.st-offline {color:#e74c3c;border-color:rgba(231,76,60,.35); background:rgba(231,76,60,.08)}
.st-printing{color:#3498db;border-color:rgba(52,152,219,.35);background:rgba(52,152,219,.08)}
.st-paused  {color:#f39c12;border-color:rgba(243,156,18,.35);background:rgba(243,156,18,.08)}
.pc-body{padding:1.2rem 1.4rem;display:flex;flex-direction:column;gap:1rem}
.temps-row{display:flex;gap:1rem}
.temp-gauge{flex:1;background:rgba(0,0,0,.2);border-radius:10px;padding:.8rem 1rem;border:1px solid rgba(255,255,255,.07);display:flex;flex-direction:column;gap:.4rem}
.temp-label{font-size:.7rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.6px;font-weight:600}
.temp-vals{display:flex;align-items:baseline;gap:.5rem}
.temp-val{font-size:1.6rem;font-weight:700;font-family:monospace;line-height:1}
.t-hot   {color:#e74c3c}
.t-warm  {color:#f39c12}
.t-ok    {color:#2ecc71}
.t-cold  {color:rgba(255,255,255,.35)}
.temp-tgt{font-size:.78rem;color:rgba(255,255,255,.35)}
.tbar-wrap{height:4px;background:rgba(255,255,255,.08);border-radius:2px;overflow:hidden}
.tbar{height:100%;border-radius:2px;transition:width .6s ease}
.temp-ctrl{display:flex;gap:.4rem;align-items:center;margin-top:.3rem}
.temp-ctrl input{width:60px;padding:.25rem .5rem;border-radius:6px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);color:#fff;font-size:.8rem;font-family:monospace;text-align:center;outline:none}
.temp-ctrl input:focus{border-color:#3498db}
.tbtn{padding:.25rem .7rem;font-size:.75rem;border-radius:6px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:rgba(255,255,255,.8);cursor:pointer;transition:all .2s}
.tbtn:hover{background:rgba(255,255,255,.18);color:#fff}
.tbtn.off{border-color:rgba(231,76,60,.3);color:#e74c3c}
.tbtn.off:hover{background:rgba(231,76,60,.15)}
.pbar-section{display:flex;flex-direction:column;gap:.5rem}
.pbar-head{display:flex;justify-content:space-between;align-items:center;gap:.5rem}
.pbar-file{font-size:.88rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:320px}
.pbar-pct{font-size:1.1rem;font-weight:700;font-family:monospace;color:#3498db;flex-shrink:0}
.pbar-wrap{height:8px;background:rgba(255,255,255,.08);border-radius:4px;overflow:hidden}
.pbar{height:100%;background:linear-gradient(90deg,#3498db,#2ecc71);border-radius:4px;transition:width .8s ease}
.pbar-times{display:flex;justify-content:space-between;font-size:.72rem;color:rgba(255,255,255,.38);font-family:monospace;flex-wrap:wrap;gap:.2rem}
.no-job{text-align:center;padding:.9rem;color:rgba(255,255,255,.3);font-size:.85rem}
.ctrl-row{display:flex;gap:.5rem;flex-wrap:wrap;padding-top:.9rem;border-top:1px solid rgba(255,255,255,.07);align-items:center}
.cb{display:flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border-radius:8px;border:1px solid;cursor:pointer;font-size:.82rem;font-weight:500;font-family:inherit;transition:all .2s}
.cb:disabled{opacity:.3;cursor:not-allowed;transform:none!important}
.cb:not(:disabled):hover{filter:brightness(1.25);transform:translateY(-1px)}
.cb-pause  {color:#f39c12;border-color:rgba(243,156,18,.35);background:rgba(243,156,18,.08)}
.cb-resume {color:#2ecc71;border-color:rgba(46,204,113,.35); background:rgba(46,204,113,.08)}
.cb-cancel {color:#e74c3c;border-color:rgba(231,76,60,.35);  background:rgba(231,76,60,.08)}
.cb-connect{color:#2ecc71;border-color:rgba(46,204,113,.35); background:rgba(46,204,113,.08)}
.cb-neutral{color:rgba(255,255,255,.5);border-color:rgba(255,255,255,.15);background:rgba(255,255,255,.04)}
.pc-error{text-align:center;padding:1.5rem;color:#e74c3c;font-size:.85rem}
.sync-label{font-size:.72rem;color:rgba(255,255,255,.3);font-family:monospace}
@keyframes spin{to{transform:rotate(360deg)}}
.spin{display:inline-block;animation:spin .8s linear infinite}
.move-section,.fan-section{padding:.75rem 1.4rem;border-top:1px solid rgba(255,255,255,.07)}
.sect-title{font-size:.68rem;color:rgba(255,255,255,.38);text-transform:uppercase;letter-spacing:.7px;font-weight:600;margin-bottom:.55rem}
.dist-row{display:flex;align-items:center;gap:.4rem;margin-bottom:.65rem}
.dist-lbl{font-size:.72rem;color:rgba(255,255,255,.38)}
.dbtn{padding:.2rem .55rem;font-size:.74rem;border-radius:5px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.05);color:rgba(255,255,255,.55);cursor:pointer;transition:all .15s}
.dbtn.active{border-color:#3498db;color:#3498db;background:rgba(52,152,219,.12)}
.move-body{display:flex;gap:1.2rem;align-items:flex-start;flex-wrap:wrap}
.xy-grid{display:grid;grid-template-columns:repeat(3,38px);grid-template-rows:repeat(3,30px);gap:.3rem}
.z-col{display:flex;flex-direction:column;gap:.3rem;align-items:center;margin-top:0}
.mbtn{display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;border-radius:7px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.07);color:rgba(255,255,255,.7);cursor:pointer;transition:all .15s;width:38px;height:30px;padding:0}
.mbtn:hover{background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.3)}
.mbtn.mhome{background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.35);color:#3498db}
.mbtn.mhome:hover{background:rgba(52,152,219,.22)}
.mbtn.mz{width:34px}
.axis-lbl{font-size:.62rem;color:rgba(255,255,255,.28);text-align:center;margin-top:.25rem;letter-spacing:.4px}
.fan-ctrl{display:flex;align-items:center;gap:.6rem}
.fan-ctrl input[type=range]{flex:1;accent-color:#3498db;min-width:80px}
.fan-val{font-size:.82rem;font-family:monospace;color:#3498db;min-width:38px;text-align:right}
.gcode-section{border-top:1px solid rgba(255,255,255,.07)}
.gcode-toggle{width:100%;padding:.65rem 1.4rem;background:none;border:none;color:rgba(255,255,255,.42);font-size:.8rem;cursor:pointer;display:flex;align-items:center;justify-content:space-between;font-family:inherit;transition:color .15s}
.gcode-toggle:hover{color:rgba(255,255,255,.75);background:rgba(255,255,255,.03)}
.gcode-body{padding:0 1.4rem .9rem;display:none}
.gcode-body.open{display:block}
.gcode-canvas{width:100%;height:260px;border-radius:8px;background:#0d1117;display:block;margin-bottom:.6rem;cursor:crosshair}
.gcode-ctrl{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}
.gcode-ctrl input[type=range]{flex:1;min-width:60px;accent-color:#3498db}
.gcode-lbl{font-size:.7rem;color:rgba(255,255,255,.35);font-family:monospace;white-space:nowrap}
.gcode-status{font-size:.72rem;color:rgba(255,255,255,.3);margin-top:.4rem;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cost-section{padding:.8rem 1.4rem 1rem;border-top:1px solid rgba(255,255,255,.07)}
.cost-inputs{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.5rem;margin-bottom:.7rem}
.cost-field{display:flex;flex-direction:column;gap:.2rem}
.cost-field label{font-size:.62rem;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.5px}
.cost-field input{padding:.28rem .5rem;border-radius:6px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);color:#fff;font-size:.8rem;font-family:monospace;outline:none;width:100%;box-sizing:border-box}
.cost-field input:focus{border-color:#f39c12}
.cost-results{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.5rem;margin-top:.7rem}
.cost-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:.55rem .85rem}
.cost-card-lbl{font-size:.62rem;color:rgba(255,255,255,.32);text-transform:uppercase;letter-spacing:.5px}
.cost-card-val{font-size:.98rem;font-weight:700;font-family:monospace;color:#2ecc71;margin-top:.12rem}
.cost-card.total .cost-card-val{color:#f39c12;font-size:1.15rem}
.print-section{padding:.75rem 1.4rem;border-top:1px solid rgba(255,255,255,.07)}
.print-row{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}
.fsel{flex:1;min-width:120px;padding:.3rem .6rem;border-radius:7px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.15);color:#fff;font-size:.8rem;font-family:inherit;outline:none;cursor:pointer}
.fsel:focus{border-color:#3498db}
.fsel option{background:#1a1a2e;color:#fff}
.cb-start{color:#2ecc71;border-color:rgba(46,204,113,.35);background:rgba(46,204,113,.08)}
</style>
</head>
<body>

<nav class="site-nav">
  <a href="index.php" class="nav-logo">SGI3D</a>
  <div class="nav-links">
    <a href="index.php">🏠 <span>Accueil</span></a>
    <a href="printers.php" class="active">🖨️ <span>Imprimantes</span></a>
    <a href="cameras.php">📷 <span>Caméras</span></a>
    <a href="alerts.php">🔔 <span>Alertes</span></a>
    <a href="dashboard.php">📊 <span>Dashboard</span></a>
    <a href="logout.php" class="btn-nav">🚪 <span>Déconnexion</span></a>
  </div>
</nav>

<div class="admin-layout">
  <aside class="sidebar">
    <div class="s-section">Navigation</div>
    <a href="index.php"><span class="s-icon">🏠</span> Accueil</a>
    <a href="printers.php" class="active"><span class="s-icon">🖨️</span> Imprimantes</a>
    <div class="s-section">Administration</div>
    <a href="dashboard.php"><span class="s-icon">📊</span> Dashboard</a>
    <a href="database.php"><span class="s-icon">🗄️</span> Base de données</a>
    <a href="cameras.php"><span class="s-icon">📷</span> Caméras</a>
    <a href="alerts.php"><span class="s-icon">🔔</span> Alertes <span class="s-badge" id="sb-alerts">0</span></a>
    <div class="s-section">Compte</div>
    <a href="sitemap.php"><span class="s-icon">🗺️</span> Plan du site</a>
    <a href="logout.php"><span class="s-icon">🚪</span> Déconnexion</a>
  </aside>

  <main class="admin-content">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
      <div>
        <h1>🖨️ Imprimantes 3D</h1>
        <p>Monitoring et contrôle en temps réel via OctoPrint</p>
        <?php if (!empty($_SESSION['username'])): ?>
          <p style="font-size:.75rem;color:rgba(255,255,255,.3);margin-top:.2rem">
            Connecté en tant que <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
          </p>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:.8rem;align-items:center">
        <span class="sync-label" id="sync-label">Chargement…</span>
        <button class="btn btn-ghost btn-sm" onclick="refreshAll()">🔄 Rafraîchir</button>
      </div>
    </div>

    <div class="printers-grid" id="grid">
      <?php foreach ($printers as $p): ?>
        <!-- Placeholder de chargement par imprimante -->
        <div id="card-<?= (int)$p['id'] ?>" class="printer-card offline">
          <div class="pc-header">
            <div class="pc-title">
              <span style="font-size:1.5rem">🖨️</span>
              <div>
                <h2><?= htmlspecialchars($p['name']) ?></h2>
                <div class="pc-ip"><?= htmlspecialchars($p['host']) ?>:<?= (int)$p['port'] ?></div>
              </div>
            </div>
            <div class="pc-status st-offline">⚫ Chargement…</div>
          </div>
          <div class="pc-body">
            <div style="text-align:center;padding:1.5rem;color:rgba(255,255,255,.3)">
              <span class="spin">⚙️</span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</div>

<div id="toast-container"></div>

<script type="application/json" id="printers-data"><?= $printersJson ?></script>
<script>
// ── Config injectée depuis PHP ────────────────────────────────
const PRINTERS = JSON.parse(document.getElementById('printers-data').textContent);

// ── Appel API SGI3D ──────────────────────────────────────────
async function api(action, params = {}) {
  const r = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, ...params })
  });
  if (r.status === 401) { window.location.href = 'login.php'; return {}; }
  return r.json();
}

async function doLogout() {
  window.location.href = 'logout.php';
}

// ── Charger l'état d'une imprimante ─────────────────────────
async function loadPrinter(p) {
  try {
    const j = await api('octoprintState', { printer_id: p.id });
    j.ok ? renderCard(p, j.data) : renderOffline(p, j.error || 'Erreur API');
  } catch (e) {
    renderOffline(p, 'Réseau inaccessible');
  }
}

// ── Rafraîchir tout ──────────────────────────────────────────
async function refreshAll() {
  document.getElementById('sync-label').textContent = '⏳ Sync…';
  await Promise.all(PRINTERS.map(loadPrinter));
  document.getElementById('sync-label').textContent =
    'Sync ' + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  // Badges alertes via API
  api('getAlerts', { unread: true }).then(a => {
    const el = document.getElementById('sb-alerts');
    if (el) el.textContent = Array.isArray(a.data) ? a.data.length : 0;
  }).catch(() => {});
}

// ── Obtenir la carte existante (déjà créée par PHP) ──────────
function card(p) {
  return document.getElementById('card-' + p.id);
}

// ── Rendu carte opérationnelle ───────────────────────────────
function renderCard(p, s) {
  const el = card(p);
  if (!el) return;
  if (!s.online) { el.className = 'printer-card offline'; el.innerHTML = offHtml(p, s.error || 'OctoPrint inaccessible'); return; }

  const cs = s.connection?.state || 'Unknown';
  const isPrint = cs === 'Printing', isPause = cs === 'Paused', isOp = cs === 'Operational';
  const isClosed = ['Closed', 'Detecting serial connection'].includes(cs);

  const stMap = {
    'Printing':    ['st-printing', '🔵 En impression'],
    'Paused':      ['st-paused',   '🟡 En pause'],
    'Operational': ['st-online',   '🟢 Opérationnelle'],
    'Cancelling':  ['st-paused',   '🔴 Annulation…'],
    'Finishing':   ['st-online',   '🟢 Finalisation…'],
    'Closed':      ['st-offline',  '⚫ Déconnectée'],
  };
  const [stCls, stLbl] = stMap[cs] || ['st-offline', '⚫ ' + cs];
  const t = s.temperatures || {};

  if (!DIST[p.id]) DIST[p.id] = 1;
  el.className = 'printer-card';
  el.innerHTML = `
    <div class="pc-header">
      <div class="pc-title">
        <span style="font-size:1.5rem">🖨️</span>
        <div><h2>${p.name}</h2><div class="pc-ip">${p.host}:${p.port}</div></div>
      </div>
      <div class="pc-status ${stCls}">${stLbl}</div>
    </div>
    <div class="pc-body">
      <div class="temps-row">
        ${gauge('Buse (tool0)', t.tool0?.actual ?? null, t.tool0?.target ?? 0, 300, p.id, 'tool0')}
        ${gauge('Lit chauffant', t.bed?.actual ?? null, t.bed?.target ?? 0, 120, p.id, 'bed')}
      </div>
      ${s.job?.file ? jobHtml(s.job) : '<div class="no-job">Aucune impression en cours</div>'}
      <div class="ctrl-row">
        ${isPrint ? `<button class="cb cb-pause"  onclick="cmd(${p.id},'pause')">⏸ Pause</button>` : ''}
        ${isPause ? `<button class="cb cb-resume" onclick="cmd(${p.id},'resume')">▶ Reprendre</button>` : ''}
        ${(isPrint || isPause) ? `<button class="cb cb-cancel" onclick="cancelConfirm(${p.id})">⏹ Annuler</button>` : ''}
        ${isOp     ? `<button class="cb cb-neutral" onclick="setConn(${p.id},'disconnect')">🔌 Déconnecter</button>` : ''}
        ${isClosed ? `<button class="cb cb-connect" onclick="setConn(${p.id},'connect')">🔌 Connecter</button>` : ''}
        <button class="cb cb-neutral" style="margin-left:auto" title="Rafraîchir" onclick="loadPrinter(PRINTERS.find(x=>x.id===${p.id}))">🔄</button>
      </div>
      ${printStartHtml(p.id)}
      ${moveHtml(p.id)}
      ${fanHtml(p.id)}
      ${gcodeHtml(p.id)}
      ${costHtml(p.id)}
    </div>`;
}

function renderOffline(p, msg) {
  const el = card(p);
  if (el) { el.className = 'printer-card offline'; el.innerHTML = offHtml(p, msg); }
}

function offHtml(p, msg) {
  return `
    <div class="pc-header">
      <div class="pc-title">
        <span style="font-size:1.5rem">🖨️</span>
        <div><h2>${p.name}</h2><div class="pc-ip">${p.host}:${p.port}</div></div>
      </div>
      <div class="pc-status st-offline">⚫ Hors ligne</div>
    </div>
    <div class="pc-body">
      <div class="pc-error">⚠️ ${msg}</div>
      <div class="ctrl-row">
        <button class="cb cb-connect" onclick="setConn(${p.id},'connect')">🔌 Tenter connexion</button>
        <button class="cb cb-neutral" style="margin-left:auto" onclick="loadPrinter(PRINTERS.find(x=>x.id===${p.id}))">🔄</button>
      </div>
    </div>`;
}

// ── Jauge de température ─────────────────────────────────────
function gauge(lbl, actual, target, max, pid, heater) {
  const pct = actual !== null ? Math.min(100, (actual / max) * 100) : 0;
  const cls = actual === null ? 't-cold' : actual > max * .9 ? 't-hot' : actual > max * .5 ? 't-warm' : actual > 30 ? 't-ok' : 't-cold';
  const barClr = { 't-hot': '#e74c3c', 't-warm': '#f39c12', 't-ok': '#2ecc71', 't-cold': 'rgba(255,255,255,.15)' }[cls] || '#2ecc71';
  return `
    <div class="temp-gauge">
      <div class="temp-label">${lbl}</div>
      <div class="temp-vals">
        <span class="temp-val ${cls}">${actual !== null ? actual.toFixed(1) + '°C' : '—'}</span>
        <span class="temp-tgt">${target > 0 ? '→ ' + target + '°C' : 'éteint'}</span>
      </div>
      <div class="tbar-wrap"><div class="tbar" style="width:${pct}%;background:${barClr}"></div></div>
      <div class="temp-ctrl">
        <input type="number" id="t-${pid}-${heater}" value="${target || ''}" min="0" max="${max}" placeholder="°C">
        <button class="tbtn" onclick="setTemp(${pid},'${heater}')">✓ Appliquer</button>
        <button class="tbtn off" onclick="setTempOff(${pid},'${heater}')">Off</button>
      </div>
    </div>`;
}

// ── Barre de progression ─────────────────────────────────────
function jobHtml(job) {
  const pct = job.progress || 0;
  return `
    <div class="pbar-section">
      <div class="pbar-head">
        <span class="pbar-file" title="${job.file}">📄 ${job.file}</span>
        <span class="pbar-pct">${pct}%</span>
      </div>
      <div class="pbar-wrap"><div class="pbar" style="width:${pct}%"></div></div>
      <div class="pbar-times">
        <span>⏱ Écoulé : ${ftime(job.time)}</span>
        <span>⏳ Restant : ${ftime(job.timeLeft)}</span>
        <span>📊 Total : ${ftime(job.estimatedTotal)}</span>
      </div>
    </div>`;
}

function ftime(s) {
  if (!s && s !== 0) return '—';
  const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = Math.floor(s % 60);
  return h > 0 ? `${h}h ${String(m).padStart(2, '0')}m` : `${m}m ${String(sec).padStart(2, '0')}s`;
}

// ── Contrôles ────────────────────────────────────────────────
async function cmd(pid, command) {
  toast({ pause: '⏸ Pause envoyée…', resume: '▶ Reprise…', cancel: '⏹ Annulation…' }[command] || command, 'info');
  const j = await api('octoprintJobControl', { printer_id: pid, command });
  if (j.ok && j.data?.ok) {
    toast('✅ Commande envoyée', 'success');
    setTimeout(() => loadPrinter(PRINTERS.find(p => p.id === pid)), 1500);
  } else {
    toast('❌ ' + (j.error || j.data?.error || 'Erreur OctoPrint'), 'error');
  }
}

function cancelConfirm(pid) {
  if (!confirm('Annuler l\'impression en cours ?\nCette action est irréversible.')) return;
  cmd(pid, 'cancel');
}

async function setTemp(pid, heater) {
  const inp = document.getElementById(`t-${pid}-${heater}`);
  const temp = parseFloat(inp?.value);
  if (isNaN(temp) || temp < 0) { toast('Température invalide', 'warning'); return; }
  const j = await api('octoprintSetTemp', { printer_id: pid, heater, temp });
  toast(j.ok ? `🌡️ Cible ${temp}°C envoyée` : '❌ ' + (j.error || 'Erreur'), j.ok ? 'success' : 'error');
}

async function setTempOff(pid, heater) {
  const inp = document.getElementById(`t-${pid}-${heater}`);
  if (inp) inp.value = '';
  const j = await api('octoprintSetTemp', { printer_id: pid, heater, temp: 0 });
  toast(j.ok ? '🌡️ Chauffage éteint' : '❌ Erreur', j.ok ? 'info' : 'error');
}

async function setConn(pid, action) {
  toast(action === 'connect' ? '🔌 Connexion…' : '🔌 Déconnexion…', 'info');
  const j = await api('octoprintConnection', { printer_id: pid, action });
  if (j.ok) setTimeout(() => loadPrinter(PRINTERS.find(p => p.id === pid)), 2500);
  else toast('❌ ' + (j.error || 'Erreur'), 'error');
}

function toast(msg, type = 'info') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// ── Déplacements & ventilateur ───────────────────────────────
const DIST = {};

function moveHtml(pid) {
  const d = DIST[pid] || 1;
  return `
    <div class="move-section">
      <div class="sect-title">🕹️ Déplacements</div>
      <div class="dist-row">
        <span class="dist-lbl">Pas :</span>
        <button class="dbtn${d === 0.1 ? ' active' : ''}" onclick="setDist(${pid},0.1,this)">0.1</button>
        <button class="dbtn${d === 1   ? ' active' : ''}" onclick="setDist(${pid},1,this)">1</button>
        <button class="dbtn${d === 10  ? ' active' : ''}" onclick="setDist(${pid},10,this)">10</button>
        <span class="dist-lbl">mm</span>
      </div>
      <div class="move-body">
        <div>
          <div class="xy-grid">
            <div></div>
            <button class="mbtn" onclick="jog(${pid},{y:DIST[${pid}]})">▲</button>
            <div></div>
            <button class="mbtn" onclick="jog(${pid},{x:-DIST[${pid}]})">◄</button>
            <button class="mbtn mhome" onclick="home(${pid},['x','y'])">⌂</button>
            <button class="mbtn" onclick="jog(${pid},{x:DIST[${pid}]})">►</button>
            <div></div>
            <button class="mbtn" onclick="jog(${pid},{y:-DIST[${pid}]})">▼</button>
            <div></div>
          </div>
          <div class="axis-lbl">X / Y</div>
        </div>
        <div class="z-col">
          <button class="mbtn mz" onclick="jog(${pid},{z:DIST[${pid}]})">▲</button>
          <button class="mbtn mz mhome" onclick="home(${pid},['z'])">⌂</button>
          <button class="mbtn mz" onclick="jog(${pid},{z:-DIST[${pid}]})">▼</button>
          <div class="axis-lbl">Z</div>
        </div>
        <button class="cb cb-neutral" style="align-self:flex-end;font-size:.75rem;padding:.35rem .8rem" onclick="home(${pid},['x','y','z'])">⌂ Home All</button>
      </div>
    </div>`;
}

function fanHtml(pid) {
  return `
    <div class="fan-section">
      <div class="sect-title">🌀 Ventilateur</div>
      <div class="fan-ctrl">
        <input type="range" id="fan-${pid}" min="0" max="100" value="0"
          oninput="document.getElementById('fan-val-${pid}').textContent=this.value+'%'">
        <span class="fan-val" id="fan-val-${pid}">0%</span>
        <button class="tbtn" onclick="setFan(${pid})">✓ Appliquer</button>
        <button class="tbtn off" onclick="setFanOff(${pid})">Off</button>
      </div>
    </div>`;
}

function setDist(pid, d, btn) {
  DIST[pid] = d;
  btn.closest('.dist-row').querySelectorAll('.dbtn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

async function jog(pid, axes) {
  const j = await api('octoprintJog', { printer_id: pid, ...axes });
  if (!j.ok || !j.data?.ok) toast('❌ Erreur déplacement', 'error');
}

async function home(pid, axes) {
  toast('⌂ Homing…', 'info');
  const j = await api('octoprintHome', { printer_id: pid, axes });
  if (!j.ok || !j.data?.ok) toast('❌ Erreur home', 'error');
}

async function setFan(pid) {
  const speed = parseInt(document.getElementById('fan-' + pid).value);
  const j = await api('octoprintFan', { printer_id: pid, speed });
  toast(j.ok ? '🌀 Ventilateur ' + speed + '%' : '❌ Erreur', j.ok ? 'success' : 'error');
}

async function setFanOff(pid) {
  const inp = document.getElementById('fan-' + pid);
  if (inp) { inp.value = 0; document.getElementById('fan-val-' + pid).textContent = '0%'; }
  const j = await api('octoprintFan', { printer_id: pid, speed: 0 });
  toast(j.ok ? '🌀 Ventilateur éteint' : '❌ Erreur', j.ok ? 'info' : 'error');
}

// ── Lancer une impression ────────────────────────────────────
function printStartHtml(pid) {
  return `
    <div class="print-section">
      <div class="sect-title">▶ Lancer une impression</div>
      <div class="print-row">
        <select id="fsel-${pid}" class="fsel">
          <option value="">— Cliquez sur 📂 pour charger —</option>
        </select>
        <button class="tbtn" onclick="loadFiles(${pid})" title="Charger la liste des fichiers">📂</button>
        <button class="cb cb-start" onclick="startPrint(${pid})">▶ Imprimer</button>
      </div>
    </div>`;
}

async function loadFiles(pid) {
  const sel = document.getElementById('fsel-' + pid);
  sel.innerHTML = '<option>⏳ Chargement…</option>';
  const j = await api('octoprintFiles', { printer_id: pid });
  if (!j.ok || !j.data?.ok) {
    sel.innerHTML = '<option>❌ ' + (j.data?.error || 'Erreur') + '</option>';
    return;
  }
  const files = (j.data.files || []).filter(f => f.type === 'machinecode');
  if (!files.length) { sel.innerHTML = '<option>Aucun fichier G-code</option>'; return; }
  sel.innerHTML = '<option value="">— Sélectionner un fichier —</option>'
    + files.map(f => `<option value="${f.name}|${f.origin || 'local'}">${f.name}</option>`).join('');
}

async function startPrint(pid) {
  const sel = document.getElementById('fsel-' + pid);
  if (!sel?.value) { toast('Sélectionnez un fichier d\'abord', 'warning'); return; }
  const [filename, location] = sel.value.split('|');
  if (!confirm('Démarrer l\'impression de "' + filename + '" ?')) return;
  toast('▶ Lancement…', 'info');
  const j = await api('octoprintStartPrint', { printer_id: pid, filename, location });
  if (j.ok && j.data?.ok) {
    toast('✅ Impression lancée', 'success');
    setTimeout(() => loadPrinter(PRINTERS.find(p => p.id === pid)), 2000);
  } else {
    toast('❌ ' + (j.data?.error || j.error || 'Erreur OctoPrint'), 'error');
  }
}

// ── Coût d'impression ────────────────────────────────────────
const COST_DATA = {};

function costHtml(pid) {
  return `
    <div class="cost-section" id="cost-section-${pid}" style="display:none">
      <div class="sect-title">💰 Coût d'impression</div>
      <div class="cost-inputs">
        <div class="cost-field"><label>Prix filament (€/kg)</label><input id="ci-kg-${pid}"  type="number" value="25"   min="0" step="1"></div>
        <div class="cost-field"><label>Prix élec. (€/kWh)</label>  <input id="ci-kwh-${pid}" type="number" value="0.20" min="0" step="0.01"></div>
        <div class="cost-field"><label>Puissance (W)</label>        <input id="ci-pow-${pid}" type="number" value="200"  min="0" step="10"></div>
        <div class="cost-field"><label>Diamètre fil. (mm)</label>   <input id="ci-dia-${pid}" type="number" value="1.75" min="0.1" step="0.05"></div>
        <div class="cost-field"><label>Densité (g/cm³)</label>      <input id="ci-den-${pid}" type="number" value="1.24" min="0.1" step="0.01"></div>
      </div>
      <button class="tbtn" onclick="calcCost(${pid})">💰 Calculer</button>
      <div class="cost-results" id="cost-results-${pid}" style="display:none">
        <div class="cost-card"><div class="cost-card-lbl">Filament utilisé</div><div class="cost-card-val" id="cr-fil-${pid}">—</div></div>
        <div class="cost-card"><div class="cost-card-lbl">Temps estimé</div>    <div class="cost-card-val" id="cr-tps-${pid}">—</div></div>
        <div class="cost-card"><div class="cost-card-lbl">Coût filament</div>   <div class="cost-card-val" id="cr-cfl-${pid}">—</div></div>
        <div class="cost-card"><div class="cost-card-lbl">Coût électricité</div><div class="cost-card-val" id="cr-cel-${pid}">—</div></div>
        <div class="cost-card total"><div class="cost-card-lbl">Total estimé</div><div class="cost-card-val" id="cr-tot-${pid}">—</div></div>
      </div>
    </div>`;
}

function parseCostMetrics(text) {
  let totalE = 0, e = 0, eAbs = true, f = 3000;
  let x = 0, y = 0, z = 0, abs = true, totalTime = 0;
  for (const raw of text.split('\n')) {
    const line = raw.split(';')[0].trim();
    if (!line) continue;
    const tok = line.toUpperCase().split(/\s+/);
    const cmd = tok[0];
    if (cmd === 'G90') { abs = true; continue; }
    if (cmd === 'G91') { abs = false; continue; }
    if (cmd === 'M82') { eAbs = true; continue; }
    if (cmd === 'M83') { eAbs = false; continue; }
    if (cmd === 'G92') {
      const p = {};
      for (let i = 1; i < tok.length; i++) { const v = parseFloat(tok[i].slice(1)); if (!isNaN(v)) p[tok[i][0]] = v; }
      if (p.E !== undefined) e = p.E;
      continue;
    }
    if (cmd === 'G0' || cmd === 'G1') {
      const p = {};
      for (let i = 1; i < tok.length; i++) { const v = parseFloat(tok[i].slice(1)); if (!isNaN(v)) p[tok[i][0]] = v; }
      if (p.F !== undefined) f = p.F;
      const nx = p.X !== undefined ? (abs ? p.X : x + p.X) : x;
      const ny = p.Y !== undefined ? (abs ? p.Y : y + p.Y) : y;
      const nz = p.Z !== undefined ? (abs ? p.Z : z + p.Z) : z;
      const de = p.E !== undefined ? (eAbs ? p.E - e : p.E) : 0;
      if (p.E !== undefined) e = eAbs ? p.E : e + p.E;
      if (de > 0) totalE += de;
      const dist = Math.sqrt((nx - x) ** 2 + (ny - y) ** 2 + (nz - z) ** 2);
      if (dist > 0 && f > 0) totalTime += dist / (f / 60);
      x = nx; y = ny; z = nz;
    }
  }
  return { totalExtrusion: totalE, estimatedTime: totalTime };
}

function calcCost(pid) {
  const d = COST_DATA[pid];
  if (!d) { toast('Chargez d\'abord un G-code', 'warning'); return; }
  const priceKg  = parseFloat(document.getElementById('ci-kg-' + pid).value)  || 25;
  const priceKwh = parseFloat(document.getElementById('ci-kwh-' + pid).value) || 0.20;
  const power    = parseFloat(document.getElementById('ci-pow-' + pid).value) || 200;
  const diameter = parseFloat(document.getElementById('ci-dia-' + pid).value) || 1.75;
  const density  = parseFloat(document.getElementById('ci-den-' + pid).value) || 1.24;
  const volumeMm3 = d.totalExtrusion * Math.PI * (diameter / 2) ** 2;
  const weightG   = (volumeMm3 / 1000) * density;
  const filCost   = (weightG / 1000) * priceKg;
  const elecCost  = (power / 1000) * (d.estimatedTime / 3600) * priceKwh;
  const totalCost = filCost + elecCost;
  const s = Math.round(d.estimatedTime);
  const timeStr = s >= 3600
    ? Math.floor(s / 3600) + 'h ' + String(Math.floor((s % 3600) / 60)).padStart(2, '0') + 'm'
    : Math.floor(s / 60) + 'm ' + String(s % 60).padStart(2, '0') + 's';
  document.getElementById('cr-fil-' + pid).textContent = weightG.toFixed(1) + 'g / ' + (d.totalExtrusion / 1000).toFixed(2) + 'm';
  document.getElementById('cr-tps-' + pid).textContent = timeStr;
  document.getElementById('cr-cfl-' + pid).textContent = filCost.toFixed(3) + ' €';
  document.getElementById('cr-cel-' + pid).textContent = elecCost.toFixed(3) + ' €';
  document.getElementById('cr-tot-' + pid).textContent = totalCost.toFixed(2) + ' €';
  document.getElementById('cost-results-' + pid).style.display = '';
}

// ── G-code Viewer ────────────────────────────────────────────
const GCODE_DATA = {};

function gcodeHtml(pid) {
  return `
    <div class="gcode-section">
      <button class="gcode-toggle" onclick="toggleGcode(${pid})">
        <span>📄 G-code Viewer</span>
        <span id="gcode-chev-${pid}">▼</span>
      </button>
      <div class="gcode-body" id="gcode-body-${pid}">
        <canvas class="gcode-canvas" id="gcode-cv-${pid}"></canvas>
        <div class="gcode-ctrl">
          <button class="tbtn" onclick="loadGcode(${pid})">⬇ Charger G-code</button>
          <input type="range" id="gcode-sl-${pid}" min="0" max="0" value="0"
            oninput="renderLayer(${pid},+this.value)">
          <span class="gcode-lbl" id="gcode-lbl-${pid}">—</span>
        </div>
        <div class="gcode-status" id="gcode-st-${pid}"></div>
      </div>
    </div>`;
}

function toggleGcode(pid) {
  const body = document.getElementById('gcode-body-' + pid);
  const chev = document.getElementById('gcode-chev-' + pid);
  const open = body.classList.toggle('open');
  chev.textContent = open ? '▲' : '▼';
  if (open) initCanvas(pid);
}

function initCanvas(pid) {
  const cv = document.getElementById('gcode-cv-' + pid);
  if (!cv) return;
  cv.width  = cv.offsetWidth * (window.devicePixelRatio || 1);
  cv.height = 260            * (window.devicePixelRatio || 1);
  cv.style.width  = cv.offsetWidth + 'px';
  cv.style.height = '260px';
  GCODE_DATA[pid] ? renderLayer(pid, +document.getElementById('gcode-sl-' + pid).value) : drawEmpty(pid);
}

function drawEmpty(pid) {
  const cv = document.getElementById('gcode-cv-' + pid);
  if (!cv) return;
  const ctx = cv.getContext('2d');
  ctx.fillStyle = '#0d1117';
  ctx.fillRect(0, 0, cv.width, cv.height);
  ctx.fillStyle = 'rgba(255,255,255,.2)';
  ctx.font = '13px monospace';
  ctx.textAlign = 'center';
  ctx.fillText('Cliquez sur "Charger G-code"', cv.width / 2, cv.height / 2);
}

async function loadGcode(pid) {
  const st = document.getElementById('gcode-st-' + pid);
  st.textContent = '⏳ Téléchargement…';
  const j = await api('octoprintGcode', { printer_id: pid });
  if (!j.ok || !j.data?.ok) {
    st.textContent = '❌ ' + (j.data?.error || j.error || 'Erreur');
    return;
  }
  st.textContent = '⚙️ Analyse du G-code…';
  setTimeout(() => {
    const parsed = parseGcode(j.data.content);
    GCODE_DATA[pid] = parsed;
    COST_DATA[pid]  = parseCostMetrics(j.data.content);
    const sl = document.getElementById('gcode-sl-' + pid);
    sl.max   = Math.max(0, parsed.layers.length - 1);
    sl.value = sl.max;
    renderLayer(pid, +sl.value);
    st.textContent = j.data.filename
      + ' — ' + parsed.layers.length + ' couche(s)'
      + (j.data.truncated ? ' (aperçu 500 Ko)' : '');
    const cs = document.getElementById('cost-section-' + pid);
    if (cs) cs.style.display = '';
  }, 10);
}

function parseGcode(text) {
  const layers = [[]], zH = [0];
  let x = 0, y = 0, z = 0, e = 0, abs = true, eAbs = true, path = [];
  let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
  for (const raw of text.split('\n')) {
    const line = raw.split(';')[0].trim();
    if (!line) continue;
    const tok = line.toUpperCase().split(/\s+/);
    const cmd = tok[0];
    if (cmd === 'G90') { abs = true; continue; }
    if (cmd === 'G91') { abs = false; continue; }
    if (cmd === 'M82') { eAbs = true; continue; }
    if (cmd === 'M83') { eAbs = false; continue; }
    if (cmd === 'G92') {
      const p = {};
      for (let i = 1; i < tok.length; i++) { const v = parseFloat(tok[i].slice(1)); if (!isNaN(v)) p[tok[i][0]] = v; }
      if (p.E !== undefined) e = p.E;
      continue;
    }
    if (cmd === 'G0' || cmd === 'G1') {
      const p = {};
      for (let i = 1; i < tok.length; i++) { const v = parseFloat(tok[i].slice(1)); if (!isNaN(v)) p[tok[i][0]] = v; }
      const nx = p.X !== undefined ? (abs ? p.X : x + p.X) : x;
      const ny = p.Y !== undefined ? (abs ? p.Y : y + p.Y) : y;
      const nz = p.Z !== undefined ? (abs ? p.Z : z + p.Z) : z;
      const de = p.E !== undefined ? (eAbs ? p.E - e : p.E) : 0;
      if (p.E !== undefined) e = eAbs ? p.E : e + p.E;
      if (nz !== z) {
        if (path.length > 1) layers[layers.length - 1].push([...path]);
        path = [];
        layers.push([]);
        zH.push(nz);
        z = nz;
      }
      if (de > 0) {
        if (!path.length) path.push({ x, y });
        path.push({ x: nx, y: ny });
        minX = Math.min(minX, nx); maxX = Math.max(maxX, nx);
        minY = Math.min(minY, ny); maxY = Math.max(maxY, ny);
      } else {
        if (path.length > 1) layers[layers.length - 1].push([...path]);
        path = [];
      }
      x = nx; y = ny;
    }
  }
  if (path.length > 1) layers[layers.length - 1].push([...path]);
  return { layers, zH, bounds: { minX, maxX, minY, maxY } };
}

function renderLayer(pid, idx) {
  const d  = GCODE_DATA[pid];
  const cv = document.getElementById('gcode-cv-' + pid);
  if (!cv) return;
  const ctx = cv.getContext('2d');
  const W = cv.width, H = cv.height;
  ctx.fillStyle = '#0d1117';
  ctx.fillRect(0, 0, W, H);
  if (!d) return;
  const { layers, zH, bounds: { minX, maxX, minY, maxY } } = d;
  const pad = 20 * window.devicePixelRatio;
  const sx = (W - pad * 2) / ((maxX - minX) || 1);
  const sy = (H - pad * 2) / ((maxY - minY) || 1);
  const sc = Math.min(sx, sy);
  const ox = pad + (W - pad * 2 - (maxX - minX) * sc) / 2;
  const oy = pad + (H - pad * 2 - (maxY - minY) * sc) / 2;
  const tx = x => ox + (x - minX) * sc;
  const ty = y => H - oy - (y - minY) * sc;
  ctx.lineWidth = .8;
  for (let li = 0; li < idx && li < layers.length; li++) {
    ctx.strokeStyle = 'rgba(52,152,219,.12)';
    for (const path of layers[li]) {
      if (path.length < 2) continue;
      ctx.beginPath();
      ctx.moveTo(tx(path[0].x), ty(path[0].y));
      for (let i = 1; i < path.length; i++) ctx.lineTo(tx(path[i].x), ty(path[i].y));
      ctx.stroke();
    }
  }
  ctx.strokeStyle = '#3498db';
  ctx.lineWidth = 1.5;
  for (const path of (layers[idx] || [])) {
    if (path.length < 2) continue;
    ctx.beginPath();
    ctx.moveTo(tx(path[0].x), ty(path[0].y));
    for (let i = 1; i < path.length; i++) ctx.lineTo(tx(path[i].x), ty(path[i].y));
    ctx.stroke();
  }
  const lbl = document.getElementById('gcode-lbl-' + pid);
  if (lbl) lbl.textContent =
    'Couche ' + (idx + 1) + '/' + layers.length
    + (zH[idx] !== undefined ? ' — Z=' + zH[idx].toFixed(2) + 'mm' : '');
}

// ── Démarrage ────────────────────────────────────────────────
refreshAll();
setInterval(refreshAll, 15000);
</script>
</body>
</html>