<?php
// ── Authentification ──────────────────────────────────────────
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Utilisateur';
$role     = $_SESSION['role']     ?? 'operateur';
$isAdmin  = $role === 'admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>SGI3D – Caméras</title>
<link rel="stylesheet" href="style.css">
<style>
  body{padding-top:60px}
  .cameras-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem}
  .cam-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:var(--radius);overflow:hidden}
  .cam-card.offline{opacity:.6}
  .cam-header{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;background:rgba(0,0,0,.3)}
  .cam-header h3{font-size:.95rem;font-weight:700;margin:0}
  .cam-header .cam-loc{font-size:.75rem;color:rgba(255,255,255,.5)}
  .cam-status{display:flex;align-items:center;gap:.4rem;font-size:.8rem;font-weight:600}
  .cam-status.online{color:#2ecc71}.cam-status.offline{color:#e74c3c}
  .cam-body{position:relative;background:#0a0a0a;aspect-ratio:16/9;overflow:hidden}
  .cam-canvas{width:100%;height:100%;display:block}
  .cam-overlay{position:absolute;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);color:rgba(255,255,255,.5);font-size:1.5rem}
  .cam-info-bar{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.8));padding:.5rem .8rem;display:flex;justify-content:space-between;align-items:center;font-size:.72rem;color:rgba(255,255,255,.7)}
  .motion-badge{background:rgba(231,76,60,.8);color:#fff;font-size:.7rem;font-weight:700;padding:.2rem .5rem;border-radius:4px;animation:blink .8s infinite;display:none}
  @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
  .cam-footer{padding:.9rem 1.2rem;display:flex;gap:.6rem;flex-wrap:wrap;border-top:1px solid rgba(255,255,255,.08)}
  .btn-fullscreen{background:none;border:1px solid rgba(255,255,255,.3);color:#fff;padding:.3rem .7rem;border-radius:6px;cursor:pointer;font-size:.8rem}
  .btn-fullscreen:hover{background:rgba(255,255,255,.1)}
  .no-cam{text-align:center;padding:3rem;color:rgba(255,255,255,.4);font-size:1rem}
</style>
</head>
<body>

<nav class="site-nav">
  <a href="index.php" class="nav-logo">SGI3D</a>
  <div class="nav-links">
    <a href="index.php">🏠 <span>Accueil</span></a>
    <a href="printers.php">🖨️ <span>Imprimantes</span></a>
    <a href="cameras.php" class="active">📷 <span>Caméras</span></a>
    <a href="alerts.php">🔔 <span>Alertes</span></a>
    <a href="dashboard.php">📊 <span>Dashboard</span></a>
    <a href="logout.php" class="btn-nav">🚪 <span>Déconnexion</span></a>
  </div>
</nav>

<div class="admin-container">
  <aside class="sidebar">
    <div class="s-section">Navigation</div>
    <a href="index.php"><span class="s-icon">🏠</span> Accueil</a>
    <a href="printers.php"><span class="s-icon">🖨️</span> Imprimantes</a>
    <div class="s-section">Administration</div>
    <a href="dashboard.php"><span class="s-icon">📊</span> Dashboard</a>
    <a href="database.php"><span class="s-icon">🗄️</span> Base de données</a>
    <a href="cameras.php" class="active"><span class="s-icon">📷</span> Caméras</a>
    <a href="alerts.php"><span class="s-icon">🔔</span> Alertes <span class="s-badge" id="sb-alerts">0</span></a>
    <div class="s-section">Compte</div>
    <a href="sitemap.php"><span class="s-icon">🗺️</span> Plan du site</a>
    <a href="export.php?format=json"><span class="s-icon">📤</span> Exporter JSON</a>
    <a href="logout.php"><span class="s-icon">🚪</span> Déconnexion</a>
  </aside>

  <main class="admin-content">
    <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
      <div>
        <h1>📷 Surveillance Caméras</h1>
        <p>
          Monitoring en temps réel ·
          <span id="cam-count">…</span> caméras ·
          <span id="cam-online">…</span> en ligne
          <?php if ($isAdmin): ?>
            · <strong><?= htmlspecialchars($username) ?></strong>
          <?php endif; ?>
        </p>
      </div>
      <div style="display:flex;gap:.8rem;flex-wrap:wrap">
        <?php if ($isAdmin): ?>
          <button class="btn btn-success btn-sm" onclick="showAddCam()">➕ Ajouter caméra</button>
        <?php endif; ?>
        <button class="btn btn-accent btn-sm" onclick="toggleAllMotion()">🎯 Détection mvt</button>
        <button class="btn btn-ghost btn-sm"   onclick="refreshAll()">🔄 Rafraîchir</button>
      </div>
    </div>

    <!-- Grille : un placeholder par caméra sera injecté ici par le JS -->
    <div class="cameras-grid" id="cameras-grid">
      <div class="no-cam" style="grid-column:1/-1">
        <span style="font-size:2rem">⏳</span>
        <p style="margin-top:.8rem">Chargement des caméras…</p>
      </div>
    </div>
  </main>
</div>

<!-- Modal ajout caméra (admin uniquement) -->
<?php if ($isAdmin): ?>
<div class="modal-overlay" id="addCamModal">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('addCamModal').classList.remove('open')">✕</button>
    <h2>📷 Ajouter une caméra</h2>
    <div class="form-group">
      <label>Nom de la caméra</label>
      <input id="ac-name" class="form-control" placeholder="Caméra Atelier 3">
    </div>
    <div class="form-group">
      <label>Emplacement</label>
      <input id="ac-loc" class="form-control" placeholder="Zone impression">
    </div>
    <div class="form-group">
      <label>URL du flux (optionnel)</label>
      <input id="ac-url" class="form-control" placeholder="http://192.168.1.x/stream">
    </div>
    <div class="form-group" style="display:flex;align-items:center;gap:.8rem">
      <input type="checkbox" id="ac-motion" checked>
      <label for="ac-motion" style="color:rgba(255,255,255,.85);cursor:pointer">Activer la détection de mouvement</label>
    </div>
    <div style="display:flex;gap:1rem;margin-top:1.5rem">
      <button class="btn btn-success" onclick="doAddCam()">✅ Ajouter</button>
      <button class="btn btn-ghost" onclick="document.getElementById('addCamModal').classList.remove('open')">Annuler</button>
    </div>
    <p id="ac-msg" style="margin-top:.8rem;font-size:.85rem;color:#e74c3c"></p>
  </div>
</div>
<?php endif; ?>

<div id="toast-container"></div>

<script>
// ── Constantes PHP → JS ──────────────────────────────────
const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;

// ── Appel API centralisé ─────────────────────────────────
async function api(action, params = {}) {
  const r = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, ...params })
  });
  if (r.status === 401) { window.location.href = 'login.php'; return {}; }
  return r.json();
}

// ── Simulation flux vidéo (canvas 2D animé) ───────────────
const CAM_COLORS   = ['#1a3a2a', '#1a2a3a', '#2a1a3a', '#3a2a1a'];
const camAnimators = {};

function startCamSimulation(canvasId, camIndex) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width  = canvas.offsetWidth  || 320;
  const H = canvas.height = canvas.offsetHeight || 180;
  let t = 0;
  const baseColor = CAM_COLORS[camIndex % CAM_COLORS.length];

  const objects = Array.from({ length: 3 }, () => ({
    x: Math.random() * W, y: Math.random() * H,
    vx: (Math.random() - .5) * 1.2, vy: (Math.random() - .5) * .8,
    size: 8 + Math.random() * 15,
    color: `rgba(${Math.floor(Math.random()*200+50)},${Math.floor(Math.random()*200+50)},${Math.floor(Math.random()*200+50)},.8)`
  }));

  function draw() {
    // Fond
    ctx.fillStyle = baseColor;
    ctx.fillRect(0, 0, W, H);

    // Grain / scanlines
    for (let i = 0; i < H; i += 3) {
      ctx.fillStyle = `rgba(0,0,0,${0.05 + Math.random() * .05})`;
      ctx.fillRect(0, i, W, 1);
    }

    // Grille perspective
    ctx.strokeStyle = 'rgba(255,255,255,0.06)';
    ctx.lineWidth = 1;
    for (let x = 0; x < W; x += W / 8) { ctx.beginPath(); ctx.moveTo(x, H); ctx.lineTo(W / 2, H * .4); ctx.stroke(); }
    for (let i = 0; i < 8; i++) { const y = H * .4 + i * (H * .6 / 8); ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke(); }

    // Objets mobiles
    objects.forEach(obj => {
      obj.x += obj.vx; obj.y += obj.vy;
      if (obj.x < 0 || obj.x > W) obj.vx *= -1;
      if (obj.y < 0 || obj.y > H) obj.vy *= -1;
      ctx.beginPath(); ctx.arc(obj.x, obj.y, obj.size, 0, Math.PI * 2);
      ctx.fillStyle = obj.color; ctx.fill();
      ctx.beginPath(); ctx.ellipse(obj.x, H * .9, obj.size * .8, obj.size * .2, 0, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(0,0,0,.3)'; ctx.fill();
    });

    // Horodatage
    ctx.fillStyle = 'rgba(255,255,255,.7)';
    ctx.font = 'bold 11px monospace';
    ctx.fillText(new Date().toLocaleTimeString('fr-FR'), 8, H - 8);

    // Témoin REC
    ctx.fillText('REC ●', W - 55, H - 8);
    ctx.beginPath(); ctx.arc(W - 66, H - 12, 4, 0, Math.PI * 2);
    ctx.fillStyle = Math.sin(t * .1) > 0 ? '#e74c3c' : 'rgba(231,76,60,.3)';
    ctx.fill();

    t++;
    camAnimators[canvasId] = requestAnimationFrame(draw);
  }
  draw();
}

// ── Timers de détection mouvement ────────────────────────
let motionTimers = {};

function scheduleMotionAlert(camId, camName) {
  const delay = 8000 + Math.random() * 20000;
  motionTimers[camId] = setTimeout(async () => {
    const badge = document.getElementById('motion-' + camId);
    if (!badge) return;

    badge.style.display = 'inline-block';

    // Crée l'alerte via l'API
    await api('addAlert', {
      type:    'avertissement',
      titre:   'Mouvement détecté',
      message: 'Mouvement détecté sur ' + (camName || 'Caméra ' + camId),
      source:  'Caméra ' + camId
    });

    // Met à jour le badge alertes
    const ja = await api('getAlerts', { unread: true });
    const el = document.getElementById('sb-alerts');
    if (el) el.textContent = (ja.data || []).length;

    setTimeout(() => {
      badge.style.display = 'none';
      scheduleMotionAlert(camId, camName);
    }, 5000);
  }, delay);
}

// ── Rendu de la grille caméras ────────────────────────────
async function renderCameras() {
  const j    = await api('getCameras');
  const cams = j.data || [];
  const grid = document.getElementById('cameras-grid');

  // Annuler les animations et timers existants
  Object.values(camAnimators).forEach(id => cancelAnimationFrame(id));
  Object.values(motionTimers).forEach(t => clearTimeout(t));

  // Compteurs
  document.getElementById('cam-count').textContent  = cams.length;
  document.getElementById('cam-online').textContent = cams.filter(c => c.statut === 'en_ligne').length;

  // Badge alertes sidebar
  api('getAlerts', { unread: true }).then(ja => {
    const el = document.getElementById('sb-alerts');
    if (el) el.textContent = (ja.data || []).length;
  });

  if (!cams.length) {
    grid.innerHTML = '<div class="no-cam" style="grid-column:1/-1">📷 Aucune caméra configurée<br><small>Cliquez sur "Ajouter caméra" pour commencer</small></div>';
    return;
  }

  grid.innerHTML = cams.map((c, i) => `
    <div class="cam-card ${c.statut === 'en_ligne' ? 'online' : 'offline'}" id="card-${c.id}">
      <div class="cam-header">
        <div>
          <h3>${c.nom}</h3>
          <div class="cam-loc">📍 ${c.localisation || '—'}</div>
        </div>
        <div class="cam-status ${c.statut === 'en_ligne' ? 'online' : 'offline'}">
          ${c.statut === 'en_ligne' ? '🟢 En ligne' : '🔴 Hors ligne'}
        </div>
      </div>
      <div class="cam-body">
        ${c.statut === 'en_ligne'
          ? `<canvas id="canvas-${c.id}" class="cam-canvas"></canvas>`
          : `<div class="cam-overlay">📷<br><small style="font-size:.7rem;margin-top:.5rem;display:block">Caméra hors ligne</small></div>`
        }
        <div class="cam-info-bar">
          <span>CAM ${String(i + 1).padStart(2, '0')} · ${c.nom.toUpperCase()}</span>
          <span id="motion-${c.id}" class="motion-badge">⚠️ MOUVEMENT</span>
        </div>
      </div>
      <div class="cam-footer">
        ${IS_ADMIN
          ? `<button class="btn btn-ghost btn-xs" onclick="toggleCam(${c.id})">${c.statut === 'en_ligne' ? '🔴 Désactiver' : '🟢 Activer'}</button>`
          : ''
        }
        <button class="btn btn-accent btn-xs" onclick="goFullscreen(${c.id})">⛶ Plein écran</button>
        ${c.detection_mvt
          ? `<span class="badge badge-info" style="font-size:.7rem">🎯 Détection mvt</span>`
          : `<span class="badge badge-neutral" style="font-size:.7rem">Pas de détection</span>`
        }
        ${IS_ADMIN
          ? `<button class="btn btn-danger btn-xs" style="margin-left:auto" onclick="deleteCam(${c.id})">🗑️</button>`
          : ''
        }
      </div>
    </div>`).join('');

  cams.forEach((c, i) => {
    if (c.statut === 'en_ligne') {
      setTimeout(() => startCamSimulation('canvas-' + c.id, i), 50);
      if (c.detection_mvt) scheduleMotionAlert(c.id, c.nom);
    }
  });
}

// ── Actions caméras ──────────────────────────────────────
function showAddCam() {
  if (!IS_ADMIN) return;
  document.getElementById('addCamModal').classList.add('open');
}

async function toggleCam(id) {
  if (!IS_ADMIN) return;
  const j = await api('getCameraById', { id });
  if (!j.data) return;
  await api('updateCamera', { id, statut: j.data.statut === 'en_ligne' ? 'hors_ligne' : 'en_ligne' });
  renderCameras();
}

async function deleteCam(id) {
  if (!IS_ADMIN) return;
  if (!confirm('Supprimer cette caméra ?')) return;
  const j = await api('deleteCamera', { id });
  if (j.ok) { renderCameras(); showToast('📷 Caméra supprimée', 'warning'); }
  else showToast('❌ ' + (j.error || 'Erreur'), 'error');
}

function goFullscreen(id) {
  const canvas = document.getElementById('canvas-' + id);
  if (canvas && canvas.requestFullscreen) canvas.requestFullscreen();
  else showToast('Plein écran non disponible dans ce navigateur', 'warning');
}

async function toggleAllMotion() {
  const j = await api('getCameras');
  const cams = j.data || [];
  const allOn = cams.every(c => c.detection_mvt == 1);
  for (const cam of cams) {
    await api('updateCamera', { id: cam.id, detection_mvt: allOn ? 0 : 1 });
  }
  renderCameras();
  showToast(allOn ? '🎯 Détection mouvement désactivée' : '🎯 Détection mouvement activée', 'info');
}

function refreshAll() { renderCameras(); showToast('🔄 Rafraîchi', 'info'); }

async function doAddCam() {
  if (!IS_ADMIN) return;
  const n   = document.getElementById('ac-name').value.trim();
  const l   = document.getElementById('ac-loc').value.trim();
  const u   = document.getElementById('ac-url').value.trim();
  const m   = document.getElementById('ac-motion').checked;
  const msg = document.getElementById('ac-msg');
  msg.textContent = '';

  if (!n || !l) { showToast('Nom et emplacement requis', 'warning'); return; }

  const j = await api('addCamera', { nom: n, localisation: l, url_flux: u, detection_mvt: m ? 1 : 0 });
  if (j.ok) {
    document.getElementById('addCamModal').classList.remove('open');
    // Réinitialise le formulaire
    ['ac-name', 'ac-loc', 'ac-url'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('ac-motion').checked = true;
    renderCameras();
    showToast('📷 Caméra ajoutée', 'success');
  } else {
    msg.textContent = '❌ ' + (j.error || 'Erreur lors de l\'ajout');
  }
}

// ── Toast ────────────────────────────────────────────────
function showToast(msg, type = 'info') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// ── Init ─────────────────────────────────────────────────
renderCameras();

// Rafraîchit le badge alertes toutes les 10 secondes
setInterval(async () => {
  const ja = await api('getAlerts', { unread: true });
  const el = document.getElementById('sb-alerts');
  if (el) el.textContent = (ja.data || []).length;
}, 10000);
</script>
</body>
</html>