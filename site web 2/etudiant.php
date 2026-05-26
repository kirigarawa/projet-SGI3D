<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>SGI3D – Espace Étudiant</title>
<link rel="stylesheet" href="style.css">
<script src="theme.js"></script>
<style>
  body{padding-top:60px}

  .feed-item{display:flex;gap:1rem;align-items:flex-start;padding:.8rem 0;border-bottom:1px solid rgba(255,255,255,.07)}
  .feed-item:last-child{border:none}
  .feed-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
  .feed-meta{font-size:.8rem;color:rgba(255,255,255,.5);margin-top:.2rem}

  .request-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
  @media(max-width:600px){.request-grid{grid-template-columns:1fr}}

  .printer-item{
    display:flex;align-items:center;gap:1rem;
    padding:.9rem 1rem;background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.08);border-radius:12px;margin-bottom:.6rem;
  }
  .printer-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
  .dot-green {background:#2ecc71;box-shadow:0 0 6px rgba(46,204,113,.5)}
  .dot-orange{background:#f39c12}
  .dot-red   {background:#e74c3c}

  /* ── Zone d'upload ── */
  .upload-zone{
    border:2px dashed rgba(255,255,255,.25);border-radius:var(--radius-sm);
    padding:1.4rem;text-align:center;transition:all .2s;cursor:pointer;
    color:rgba(255,255,255,.75);user-select:none;
  }
  .upload-zone:hover,.upload-zone.drag-over{
    border-color:var(--accent);background:rgba(52,152,219,.08);color:#fff;
  }
  .upload-zone.drag-over{transform:scale(1.01)}
  @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}
  .upload-zone.shake{animation:shake .35s ease}

  /* Barre de progression */
  .upload-bar{height:6px;background:rgba(255,255,255,.1);border-radius:3px;overflow:hidden}
  .upload-bar-fill{height:100%;width:0%;background:var(--accent);border-radius:3px;transition:width .1s}

  [data-theme="light"] .upload-zone{
    border-color:rgba(0,0,0,.2);color:rgba(30,40,80,.7);
  }
  [data-theme="light"] .upload-zone:hover,
  [data-theme="light"] .upload-zone.drag-over{
    border-color:var(--accent);background:rgba(52,152,219,.06);color:#1a1a2e;
  }
  [data-theme="light"] .upload-bar{background:rgba(0,0,0,.1)}
</style>
</head>
<body>

<nav class="site-nav">
  <a href="index.php" class="nav-logo">SGI3D</a>
  <div class="nav-links">
    <a href="index.php">🏠 <span>Accueil</span></a>
    <a href="printers.php">🖨️ <span>Imprimantes</span></a>
    <a href="cameras.php">📷 <span>Caméras</span></a>
    <a href="etudiant.php" class="active">🎓 <span>Mon Espace</span></a>
    <a href="logout.php" class="btn-nav">🚪 <span>Déconnexion</span></a>
  </div>
</nav>

<div class="admin-layout">
  <aside class="sidebar">
    <div class="s-section">Navigation</div>
    <a href="index.php"><span class="s-icon">🏠</span> Accueil</a>
    <a href="printers.php"><span class="s-icon">🖨️</span> Imprimantes</a>

    <div class="s-section">Mon Espace</div>
    <a href="etudiant.php" class="active"><span class="s-icon">🎓</span> Tableau de bord</a>
    <a href="#" onclick="scrollToSection('request-section');return false"><span class="s-icon">➕</span> Nouvelle demande</a>
    <a href="#" onclick="scrollToSection('history-section');return false"><span class="s-icon">📋</span> Mes impressions</a>

    <div class="s-section">Compte</div>
    <a href="sitemap.php"><span class="s-icon">🗺️</span> Plan du site</a>
    <a href="logout.php"><span class="s-icon">🚪</span> Déconnexion</a>
  </aside>

  <main class="admin-content">
    <div class="page-header">
      <h1>🎓 Espace Étudiant</h1>
      <p>Gérez vos demandes d'impression 3D · <span id="welcome-msg"></span></p>
    </div>

    <!-- Stats personnelles -->
    <div class="stats-grid" id="stats-grid"></div>

    <!-- Formulaire nouvelle demande -->
    <div class="card-dark" id="request-section" style="padding:1.5rem;margin-bottom:1.5rem">
      <h2 style="font-size:1rem;margin-bottom:1.2rem;font-weight:700">➕ Nouvelle demande d'impression</h2>
      <div class="request-grid">
        <div class="form-group">
          <label>Fichier à imprimer</label>
          <div class="upload-zone" id="upload-zone">
            <input type="file" id="req-file-input" accept=".stl,.gcode,.gco" style="display:none"
                   onchange="updateFileUI(this.files[0])">
            <div id="upload-placeholder" onclick="document.getElementById('req-file-input').click()" style="cursor:pointer">
              <div style="font-size:2rem;margin-bottom:.4rem">📁</div>
              <div style="font-weight:600">Cliquez ou glissez un fichier ici</div>
              <div style="font-size:.8rem;opacity:.5;margin-top:.3rem">.stl · .gcode · .gco — max 50 Mo</div>
            </div>
            <div id="upload-info" style="display:none;align-items:center;gap:.8rem;flex-wrap:wrap">
              <span id="upload-icon" style="font-size:1.6rem"></span>
              <div style="flex:1;min-width:0">
                <div id="upload-name" style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
                <div id="upload-size" style="font-size:.8rem;opacity:.55"></div>
              </div>
              <button type="button" onclick="clearFile()" style="background:none;border:none;cursor:pointer;font-size:1.1rem;opacity:.6">✕</button>
            </div>
          </div>
          <div id="upload-progress" style="display:none;margin-top:.5rem">
            <div class="upload-bar"><div class="upload-bar-fill" id="upload-bar-fill"></div></div>
            <div id="upload-progress-label" style="font-size:.8rem;opacity:.6;margin-top:.3rem;text-align:center">Upload…</div>
          </div>
        </div>
        <div class="form-group">
          <label>Matériau</label>
          <select class="form-control" id="req-material">
            <option value="PLA">PLA (standard)</option>
            <option value="ABS">ABS (résistant)</option>
            <option value="TPU">TPU (flexible)</option>
            <option value="PETG">PETG (semi-rigide)</option>
            <option value="CPE+">CPE+ (haute résistance chimique)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Durée estimée</label>
          <input class="form-control" id="req-duration" placeholder="ex: 2h30">
        </div>
        <div class="form-group">
          <label>Imprimante souhaitée</label>
          <select class="form-control" id="req-printer">
            <option value="">– Peu importe –</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Notes (optionnel)</label>
        <input class="form-control" id="req-notes" placeholder="Résolution, remplissage, support, couleur filament…">
      </div>
      <button class="btn btn-success" id="submit-btn" onclick="submitRequest()">🖨️ Soumettre la demande</button>
    </div>

    <!-- État des imprimantes -->
    <div class="card-dark" style="padding:1.5rem;margin-bottom:1.5rem">
      <h2 style="font-size:1rem;margin-bottom:1rem;font-weight:700">🖨️ État des imprimantes</h2>
      <div id="printers-status"><p style="color:rgba(255,255,255,.4);font-size:.85rem">Chargement…</p></div>
    </div>

    <!-- Historique des impressions -->
    <div class="card-dark" id="history-section" style="padding:1.5rem">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 style="font-size:1rem;margin:0;font-weight:700">📋 Mes impressions</h2>
        <button class="btn btn-ghost btn-sm" onclick="renderAll()">🔄 Actualiser</button>
      </div>
      <div class="table-wrap">
        <table id="my-prints-table"></table>
      </div>
    </div>
  </main>
</div>

<div id="toast-container"></div>

<script src="db.js"></script>
<script>
if (!SGI3D_DB.requireAuth('login.php')) {}

const session = SGI3D_DB.getSession();
if (session) {
  document.getElementById('welcome-msg').textContent = 'Connecté : ' + session.name;
}

function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Charger les imprimantes dans le select
async function loadPrinterSelect() {
  try {
    const printers = await SGI3D_DB.getImprimantes();
    const select = document.getElementById('req-printer');
    printers.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.nom || ('Imprimante ' + p.id);
      select.appendChild(opt);
    });
  } catch (e) {
    // Fallback liste statique
    [{ id: 1, nom: 'Ultimaker 2+' }, { id: 2, nom: 'Elegoo Neptune 4 Pro' }].forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.nom;
      document.getElementById('req-printer').appendChild(opt);
    });
  }
}

// Afficher l'état des imprimantes
async function renderPrinterStatus() {
  const el = document.getElementById('printers-status');
  try {
    const printers = await SGI3D_DB.getImprimantes();
    if (!printers.length) {
      el.innerHTML = '<p style="color:rgba(255,255,255,.4);font-size:.85rem">Aucune imprimante enregistrée.</p>';
      return;
    }
    el.innerHTML = printers.map(p => {
      const statut = p.statut || 'inconnu';
      const dotClass   = statut === 'en_ligne' ? 'dot-green' : statut === 'en_impression' ? 'dot-orange' : statut === 'maintenance' ? 'dot-orange' : 'dot-red';
      const badgeClass = statut === 'en_ligne' ? 'badge-success' : statut === 'en_impression' ? 'badge-warning' : statut === 'maintenance' ? 'badge-warning' : 'badge-danger';
      const label      = statut === 'en_ligne' ? 'Disponible' : statut === 'en_impression' ? 'En cours d\'impression' : statut === 'maintenance' ? 'Maintenance' : 'Hors ligne';
      return `
        <div class="printer-item">
          <div class="printer-dot ${dotClass}"></div>
          <div style="flex:1">
            <strong>${p.nom || 'Imprimante ' + p.id}</strong>
            ${p.modele ? '<span style="color:rgba(255,255,255,.5);font-size:.85rem;margin-left:.5rem">(' + p.modele + ')</span>' : ''}
          </div>
          <span class="badge ${badgeClass}">${label}</span>
        </div>`;
    }).join('');
  } catch (e) {
    el.innerHTML = `
      <div class="printer-item"><div class="printer-dot dot-orange"></div><div style="flex:1"><strong>Ultimaker 2+</strong></div><span class="badge badge-neutral">État inconnu</span></div>
      <div class="printer-item"><div class="printer-dot dot-orange"></div><div style="flex:1"><strong>Elegoo Neptune 4 Pro</strong></div><span class="badge badge-neutral">État inconnu</span></div>`;
  }
}

// Afficher les stats personnelles
async function renderStats() {
  try {
    const jobs = await SGI3D_DB.getPrintJobs();
    const uid  = session ? parseInt(session.userId) : -1;
    const mine = jobs.filter(j => parseInt(j.utilisateur_id) === uid);
    document.getElementById('stats-grid').innerHTML = `
      <div class="stat-card blue">  <div class="s-val">${mine.length}</div>                              <div class="s-label">Total impressions</div></div>
      <div class="stat-card orange"><div class="s-val">${mine.filter(j=>j.statut==='en_cours').length}</div><div class="s-label">En cours</div></div>
      <div class="stat-card green"> <div class="s-val">${mine.filter(j=>j.statut==='termine').length}</div> <div class="s-label">Terminées</div></div>
      <div class="stat-card red">   <div class="s-val">${mine.filter(j=>j.statut==='erreur').length}</div>  <div class="s-label">Erreurs</div></div>`;
  } catch (e) {
    document.getElementById('stats-grid').innerHTML = '';
  }
}

// Afficher l'historique personnel
async function renderMyPrints() {
  const table = document.getElementById('my-prints-table');
  try {
    const jobs = await SGI3D_DB.getPrintJobs();
    const uid  = session ? parseInt(session.userId) : -1;
    const mine = jobs.filter(j => parseInt(j.utilisateur_id) === uid);

    if (!mine.length) {
      table.innerHTML = `<tr><td colspan="5" style="text-align:center;color:rgba(255,255,255,.4);padding:2.5rem;font-size:.9rem">
        Aucune impression pour le moment.<br>
        <span style="font-size:.8rem">Soumettez votre première demande ci-dessus !</span>
      </td></tr>`;
      return;
    }

    const icon  = s => s === 'termine' ? '✅' : s === 'erreur' ? '❌' : '🔄';
    const badge = s => s === 'termine' ? 'badge-success' : s === 'erreur' ? 'badge-danger' : 'badge-info';
    const lbl   = s => s === 'termine' ? 'Terminé' : s === 'erreur' ? 'Erreur' : 'En cours';

    table.innerHTML = `
      <thead><tr><th>Fichier</th><th>Matériau</th><th>Durée estimée</th><th>Date</th><th>Statut</th></tr></thead>
      <tbody>
        ${mine.map(j => `
          <tr>
            <td>${j.nom_fichier || '—'}</td>
            <td>${j.materiau || '—'}</td>
            <td>${j.duree_estimee || '—'}</td>
            <td>${SGI3D_DB.formatDate(j.demarre_le)}</td>
            <td><span class="badge ${badge(j.statut)}">${icon(j.statut)} ${lbl(j.statut)}</span></td>
          </tr>`).join('')}
      </tbody>`;
  } catch (e) {
    table.innerHTML = '<tr><td colspan="5" style="color:rgba(231,76,60,.6);padding:1rem">Erreur de chargement des données.</td></tr>';
  }
}

// ── Zone de dépôt ───────────────────────────────────────────
function updateFileUI(file) {
  const ph   = document.getElementById('upload-placeholder');
  const info = document.getElementById('upload-info');
  if (!file) { ph.style.display = ''; info.style.display = 'none'; return; }
  const icons = { stl:'🧊', gcode:'📋', gco:'📋' };
  const ext   = file.name.split('.').pop().toLowerCase();
  document.getElementById('upload-icon').textContent = icons[ext] || '📄';
  document.getElementById('upload-name').textContent = file.name;
  document.getElementById('upload-size').textContent = formatBytes(file.size);
  ph.style.display = 'none';
  info.style.display = 'flex';
}

function clearFile() {
  document.getElementById('req-file-input').value = '';
  updateFileUI(null);
}

function formatBytes(b) {
  if (b < 1024)        return b + ' o';
  if (b < 1048576)     return (b / 1024).toFixed(1) + ' Ko';
  return (b / 1048576).toFixed(1) + ' Mo';
}

// Drag & drop sur la zone
(function () {
  const zone = document.getElementById('upload-zone');
  zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('drag-over');
    const f = e.dataTransfer.files[0];
    if (!f) return;
    document.getElementById('req-file-input').files = e.dataTransfer.files;
    updateFileUI(f);
  });
  // Clic sur la zone (hors bouton ✕)
  zone.addEventListener('click', e => {
    if (e.target.closest('button')) return;
    if (document.getElementById('upload-info').style.display !== 'none') return;
    document.getElementById('req-file-input').click();
  });
})();

// Soumettre une demande d'impression
async function submitRequest() {
  const fileInput = document.getElementById('req-file-input');
  const file      = fileInput.files[0];
  const material  = document.getElementById('req-material').value;
  const duration  = document.getElementById('req-duration').value.trim();
  const printer   = document.getElementById('req-printer').value;

  if (!file) {
    showToast('Sélectionnez un fichier à imprimer', 'warning');
    document.getElementById('upload-zone').classList.add('shake');
    setTimeout(() => document.getElementById('upload-zone').classList.remove('shake'), 500);
    return;
  }

  const btn = document.getElementById('submit-btn');
  btn.disabled = true;

  // ── Étape 1 : upload du fichier ─────────────────────────
  btn.textContent = '⏳ Upload en cours…';
  document.getElementById('upload-progress').style.display = '';
  const fill  = document.getElementById('upload-bar-fill');
  const label = document.getElementById('upload-progress-label');

  let uploadedName;
  try {
    uploadedName = await new Promise((resolve, reject) => {
      const xhr  = new XMLHttpRequest();
      const form = new FormData();
      form.append('file', file);

      xhr.upload.onprogress = e => {
        if (!e.lengthComputable) return;
        const pct = Math.round(e.loaded / e.total * 100);
        fill.style.width  = pct + '%';
        label.textContent = pct + '%';
      };

      xhr.onload = () => {
        const j = JSON.parse(xhr.responseText);
        j.ok ? resolve(j.filename) : reject(new Error(j.error));
      };
      xhr.onerror = () => reject(new Error('Erreur réseau'));
      xhr.open('POST', 'upload.php');
      xhr.send(form);
    });
  } catch (e) {
    showToast('❌ ' + e.message, 'error');
    btn.textContent = '🖨️ Soumettre la demande';
    btn.disabled = false;
    document.getElementById('upload-progress').style.display = 'none';
    return;
  }

  // ── Étape 2 : enregistrement en base ───────────────────
  btn.textContent = '⏳ Enregistrement…';
  fill.style.width  = '100%';
  label.textContent = 'Finalisation…';

  try {
    await SGI3D_DB.createPrintJob({
      nom_fichier:   uploadedName,
      materiau:      material,
      duree_estimee: duration || null,
      imprimante_id: printer  || null,
    });
    clearFile();
    document.getElementById('req-duration').value = '';
    document.getElementById('req-notes').value    = '';
    showToast('✅ Demande soumise avec succès !', 'success');
    renderAll();
  } catch (e) {
    showToast('❌ Erreur lors de l\'enregistrement', 'error');
  } finally {
    btn.textContent = '🖨️ Soumettre la demande';
    btn.disabled = false;
    document.getElementById('upload-progress').style.display = 'none';
    fill.style.width = '0%';
  }
}

function showToast(msg, type = 'info') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

async function renderAll() {
  await Promise.all([renderStats(), renderMyPrints(), renderPrinterStatus()]);
}

loadPrinterSelect();
renderAll();
setInterval(renderAll, 30000);
</script>
</body>
</html>
