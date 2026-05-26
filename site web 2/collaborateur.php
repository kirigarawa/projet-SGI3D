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
<title>SGI3D – Espace Collaborateur</title>
<link rel="stylesheet" href="style.css">
<script src="theme.js"></script>
<style>
  body{padding-top:60px}

  .feed-item{display:flex;gap:1rem;align-items:flex-start;padding:.8rem 0;border-bottom:1px solid rgba(255,255,255,.07)}
  .feed-item:last-child{border:none}
  .feed-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
  .feed-icon.print{background:rgba(46,204,113,.2)}
  .feed-icon.alert{background:rgba(231,76,60,.2)}
  .feed-icon.blue {background:rgba(52,152,219,.2)}
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

  .two-col{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
  @media(max-width:768px){.two-col{grid-template-columns:1fr}}
</style>
</head>
<body>

<nav class="site-nav">
  <a href="index.php" class="nav-logo">SGI3D</a>
  <div class="nav-links">
    <a href="index.php">🏠 <span>Accueil</span></a>
    <a href="printers.php">🖨️ <span>Imprimantes</span></a>
    <a href="cameras.php">📷 <span>Caméras</span></a>
    <a href="alerts.php">🔔 <span>Alertes</span></a>
    <a href="collaborateur.php" class="active">🤝 <span>Mon Espace</span></a>
    <a href="logout.php" class="btn-nav">🚪 <span>Déconnexion</span></a>
  </div>
</nav>

<div class="admin-layout">
  <aside class="sidebar">
    <div class="s-section">Navigation</div>
    <a href="index.php"><span class="s-icon">🏠</span> Accueil</a>
    <a href="printers.php"><span class="s-icon">🖨️</span> Imprimantes</a>
    <a href="cameras.php"><span class="s-icon">📷</span> Caméras</a>
    <a href="alerts.php"><span class="s-icon">🔔</span> Alertes <span class="s-badge" id="sb-alerts">0</span></a>

    <div class="s-section">Mon Espace</div>
    <a href="collaborateur.php" class="active"><span class="s-icon">🤝</span> Tableau de bord</a>
    <a href="#" onclick="scrollToSection('request-section');return false"><span class="s-icon">➕</span> Nouvelle demande</a>
    <a href="#" onclick="scrollToSection('activity-section');return false"><span class="s-icon">📊</span> Activité récente</a>
    <a href="#" onclick="scrollToSection('history-section');return false"><span class="s-icon">📋</span> Mes impressions</a>

    <div class="s-section">Compte</div>
    <a href="sitemap.php"><span class="s-icon">🗺️</span> Plan du site</a>
    <a href="logout.php"><span class="s-icon">🚪</span> Déconnexion</a>
  </aside>

  <main class="admin-content">
    <div class="page-header">
      <h1>🤝 Espace Collaborateur</h1>
      <p>Suivi des impressions et gestion de vos projets · <span id="welcome-msg"></span></p>
    </div>

    <!-- Stats globales + personnelles -->
    <div class="stats-grid" id="stats-grid"></div>

    <!-- Formulaire nouvelle demande -->
    <div class="card-dark" id="request-section" style="padding:1.5rem;margin-bottom:1.5rem">
      <h2 style="font-size:1rem;margin-bottom:1.2rem;font-weight:700">➕ Nouvelle demande d'impression</h2>
      <div class="request-grid">
        <div class="form-group">
          <label>Nom du fichier (.stl / .gcode)</label>
          <input class="form-control" id="req-file" placeholder="mon_projet.stl">
        </div>
        <div class="form-group">
          <label>Matériau</label>
          <select class="form-control" id="req-material">
            <option value="PLA">PLA (standard)</option>
            <option value="ABS">ABS (résistant)</option>
            <option value="TPU">TPU (flexible)</option>
            <option value="PETG">PETG (semi-rigide)</option>
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
      <button class="btn btn-accent" id="submit-btn" onclick="submitRequest()">🖨️ Soumettre la demande</button>
    </div>

    <!-- Activité récente (toutes impressions + état imprimantes) -->
    <div class="two-col" id="activity-section" style="margin-bottom:1.5rem">

      <!-- Impressions récentes (tous utilisateurs) -->
      <div class="card-dark" style="padding:1.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
          <h2 style="font-size:1rem;margin:0;font-weight:700">🖨️ Impressions récentes</h2>
          <span style="font-size:.75rem;color:rgba(255,255,255,.4)">Atelier</span>
        </div>
        <div id="recent-jobs"></div>
      </div>

      <!-- État des imprimantes -->
      <div class="card-dark" style="padding:1.5rem">
        <h2 style="font-size:1rem;margin-bottom:1rem;font-weight:700">🖨️ État des imprimantes</h2>
        <div id="printers-status"><p style="color:rgba(255,255,255,.4);font-size:.85rem">Chargement…</p></div>
      </div>
    </div>

    <!-- Mes impressions -->
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

// Charger la liste des imprimantes dans le select
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
    [{ id: 1, nom: 'Ultimaker 2+' }, { id: 2, nom: 'Elegoo Neptune 4 Pro' }].forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.nom;
      document.getElementById('req-printer').appendChild(opt);
    });
  }
}

// Stats : personnelles + globales
async function renderStats() {
  try {
    const [jobs, stats] = await Promise.all([SGI3D_DB.getPrintJobs(), SGI3D_DB.getStats()]);
    const uid  = session ? parseInt(session.userId) : -1;
    const mine = jobs.filter(j => parseInt(j.utilisateur_id) === uid);
    document.getElementById('stats-grid').innerHTML = `
      <div class="stat-card blue">  <div class="s-val">${mine.length}</div>                                <div class="s-label">Mes impressions</div></div>
      <div class="stat-card orange"><div class="s-val">${mine.filter(j=>j.statut==='en_cours').length}</div><div class="s-label">Mes en cours</div></div>
      <div class="stat-card green"> <div class="s-val">${stats.totalPrintJobs}</div>                       <div class="s-label">Total atelier</div></div>
      <div class="stat-card purple"><div class="s-val">${stats.activePrintJobs}</div>                      <div class="s-label">En cours atelier</div></div>
      <div class="stat-card red">   <div class="s-val">${stats.unresolvedAlerts}</div>                     <div class="s-label">Alertes actives</div></div>
      <div class="stat-card blue">  <div class="s-val">${stats.onlineCameras}/${stats.totalCameras}</div>  <div class="s-label">Caméras en ligne</div></div>`;
    document.getElementById('sb-alerts').textContent = stats.unresolvedAlerts;
  } catch (e) {
    document.getElementById('stats-grid').innerHTML = '';
  }
}

// Impressions récentes de tout l'atelier (8 dernières)
async function renderRecentJobs() {
  const el = document.getElementById('recent-jobs');
  try {
    const jobs = await SGI3D_DB.getPrintJobs(8);
    if (!jobs.length) {
      el.innerHTML = '<p style="color:rgba(255,255,255,.4);font-size:.85rem">Aucune impression enregistrée</p>';
      return;
    }
    const icon  = s => s === 'termine' ? '✅' : s === 'erreur' ? '❌' : '🔄';
    const badge = s => s === 'termine' ? 'badge-success' : s === 'erreur' ? 'badge-danger' : 'badge-info';
    const lbl   = s => s === 'termine' ? 'Terminé' : s === 'erreur' ? 'Erreur' : 'En cours';
    el.innerHTML = jobs.map(j => `
      <div class="feed-item">
        <div class="feed-icon print">${icon(j.statut)}</div>
        <div>
          <strong>${j.nom_fichier || 'Impression'}</strong>
          <div class="feed-meta">
            ${j.nom_utilisateur || '—'} · ${SGI3D_DB.timeAgo(j.demarre_le)}
            · <span class="badge ${badge(j.statut)}">${lbl(j.statut)}</span>
          </div>
        </div>
      </div>`).join('');
  } catch (e) {
    el.innerHTML = '<p style="color:rgba(231,76,60,.6);font-size:.85rem">Erreur de chargement</p>';
  }
}

// État des imprimantes
async function renderPrinterStatus() {
  const el = document.getElementById('printers-status');
  try {
    const printers = await SGI3D_DB.getImprimantes();
    if (!printers.length) {
      el.innerHTML = '<p style="color:rgba(255,255,255,.4);font-size:.85rem">Aucune imprimante enregistrée.</p>';
      return;
    }
    el.innerHTML = printers.map(p => {
      const statut     = p.statut || 'inconnu';
      const dotClass   = statut === 'en_ligne' ? 'dot-green' : statut === 'en_impression' ? 'dot-orange' : statut === 'maintenance' ? 'dot-orange' : 'dot-red';
      const badgeClass = statut === 'en_ligne' ? 'badge-success' : statut === 'en_impression' ? 'badge-warning' : statut === 'maintenance' ? 'badge-warning' : 'badge-danger';
      const label      = statut === 'en_ligne' ? 'Disponible' : statut === 'en_impression' ? 'En cours' : statut === 'maintenance' ? 'Maintenance' : 'Hors ligne';
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

// Mes impressions personnelles
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

// Soumettre une demande
async function submitRequest() {
  const file     = document.getElementById('req-file').value.trim();
  const material = document.getElementById('req-material').value;
  const duration = document.getElementById('req-duration').value.trim();
  const printer  = document.getElementById('req-printer').value;

  if (!file) {
    showToast('Veuillez indiquer le nom du fichier', 'warning');
    document.getElementById('req-file').focus();
    return;
  }

  const btn = document.getElementById('submit-btn');
  btn.textContent = '⏳ Envoi en cours…';
  btn.disabled = true;

  try {
    await SGI3D_DB.createPrintJob({
      nom_fichier:   file,
      materiau:      material,
      duree_estimee: duration || null,
      imprimante_id: printer  || null
    });
    document.getElementById('req-file').value     = '';
    document.getElementById('req-duration').value = '';
    document.getElementById('req-notes').value    = '';
    showToast('✅ Demande soumise avec succès !', 'success');
    renderAll();
  } catch (e) {
    showToast('❌ Erreur lors de la soumission', 'error');
  } finally {
    btn.textContent = '🖨️ Soumettre la demande';
    btn.disabled = false;
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
  await Promise.all([renderStats(), renderRecentJobs(), renderPrinterStatus(), renderMyPrints()]);
}

loadPrinterSelect();
renderAll();
setInterval(renderAll, 30000);
</script>
</body>
</html>
