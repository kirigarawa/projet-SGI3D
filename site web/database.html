<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>SGI3D – Base de données</title>
<!-- Feuille de style globale partagée par toutes les pages -->
<link rel="stylesheet" href="style.css">
<style>
  /* Décalage vers le bas pour ne pas passer sous la barre de navigation fixe */
  body{padding-top:60px}

  /* Barre d'onglets horizontale au-dessus du contenu de chaque table */
  .tab-bar{display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap}

  /* Bouton d'onglet par défaut (inactif) */
  .tab-btn{
    padding:.6rem 1.3rem;border-radius:20px;border:1px solid rgba(255,255,255,.2);
    background:rgba(255,255,255,.06);color:rgba(255,255,255,.7);cursor:pointer;
    font-size:.88rem;font-family:var(--font);transition:all .2s;
  }
  /* Bouton d'onglet actif : fond bleu transparent + bordure bleue */
  .tab-btn.active{background:rgba(52,152,219,.25);border-color:#3498db;color:#3498db;font-weight:700}

  /* Panneau d'onglet : caché par défaut, visible quand la classe "active" est présente */
  .tab-panel{display:none}.tab-panel.active{display:block}

  /* Barre de recherche/filtre au-dessus des tableaux Connexions et Impressions */
  .search-bar{display:flex;gap:.8rem;margin-bottom:1rem;flex-wrap:wrap}
  .search-bar input{flex:1;min-width:200px;padding:.65rem 1rem;border-radius:20px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:#fff;font-family:var(--font);font-size:.9rem}
  .search-bar input:focus{outline:none;border-color:var(--accent)}
  .search-bar input::placeholder{color:rgba(255,255,255,.4)}

  /* Rangée de boutons d'export/action en haut de chaque panneau d'onglet */
  .export-row{display:flex;gap:.8rem;flex-wrap:wrap;margin-bottom:1rem}
</style>
</head>
<body>

<!-- ════════════════════════════════════════════════════════
     NAVIGATION PRINCIPALE (barre en haut, fixe sur toutes les pages)
     ════════════════════════════════════════════════════════ -->
<nav class="site-nav">
  <a href="index.html" class="nav-logo">SGI3D</a>
  <div class="nav-links">
    <a href="index.html">🏠 <span>Accueil</span></a>
    <a href="printers.html">🖨️ <span>Imprimantes</span></a>
    <a href="cameras.html">📷 <span>Caméras</span></a>
    <a href="alerts.html">🔔 <span>Alertes</span></a>
    <a href="dashboard.html">📊 <span>Dashboard</span></a>
    <a href="#" onclick="doLogout()" class="btn-nav">🚪 <span>Déconnexion</span></a>
  </div>
</nav>

<!-- ════════════════════════════════════════════════════════
     MISE EN PAGE ADMIN (sidebar gauche + contenu principal)
     ════════════════════════════════════════════════════════ -->
<div class="admin-layout">

  <!-- ── SIDEBAR ──────────────────────────────────────────
       Liens de navigation groupés par section.
       La section "Export" propose des téléchargements directs
       en JSON ou CSV pour chaque table de la base de données.
       ──────────────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="s-section">Navigation</div>
    <a href="index.html"><span class="s-icon">🏠</span> Accueil</a>
    <a href="printers.html"><span class="s-icon">🖨️</span> Imprimantes</a>

    <div class="s-section">Administration</div>
    <a href="dashboard.html"><span class="s-icon">📊</span> Dashboard</a>
    <a href="database.html" class="active"><span class="s-icon">🗄️</span> Base de données</a>
    <a href="cameras.html"><span class="s-icon">📷</span> Caméras</a>
    <!-- Badge mis à jour dynamiquement avec le nombre d'alertes non résolues -->
    <a href="alerts.html"><span class="s-icon">🔔</span> Alertes <span class="s-badge" id="sb-alerts">0</span></a>

    <div class="s-section">Export</div>
    <!-- Export JSON de toute la base de données (toutes les tables) -->
    <a href="#" onclick="SGI3D_DB.exportJSON()"><span class="s-icon">📤</span> Exporter JSON</a>
    <!-- Import d'un fichier JSON pour restaurer les données -->
    <a href="#" onclick="importFile()"><span class="s-icon">📥</span> Importer JSON</a>
    <!-- Export CSV ciblé par table -->
    <a href="#" onclick="SGI3D_DB.exportCSV('login_logs')"><span class="s-icon">📊</span> CSV Connexions</a>
    <a href="#" onclick="SGI3D_DB.exportCSV('print_jobs')"><span class="s-icon">📊</span> CSV Impressions</a>

    <div class="s-section">Compte</div>
    <a href="sitemap.html"><span class="s-icon">🗺️</span> Plan du site</a>
    <a href="#" onclick="doLogout()"><span class="s-icon">🚪</span> Déconnexion</a>
  </aside>

  <!-- ── CONTENU PRINCIPAL ────────────────────────────────
       Affiche les données de la base organisées en 5 onglets :
       Utilisateurs | Connexions | Impressions | Caméras | Alertes
       ──────────────────────────────────────────────────── -->
  <main class="admin-content">
    <div class="page-header">
      <h1>🗄️ Base de données</h1>
      <p>Consultez et gérez toutes les données enregistrées par SGI3D</p>
    </div>

    <!-- ── BARRE D'ONGLETS ───────────────────────────────────
         Chaque bouton appelle switchTab(name) qui masque tous les
         panneaux puis affiche celui correspondant au nom cliqué.
         ──────────────────────────────────────────────────── -->
    <div class="tab-bar">
      <button class="tab-btn active" onclick="switchTab('users')">👥 Utilisateurs</button>
      <button class="tab-btn" onclick="switchTab('logins')">🔐 Connexions</button>
      <button class="tab-btn" onclick="switchTab('prints')">🖨️ Impressions</button>
      <button class="tab-btn" onclick="switchTab('cameras')">📷 Caméras</button>
      <button class="tab-btn" onclick="switchTab('alerts')">🔔 Alertes</button>
    </div>

    <!-- ════════════════════════════════════════════════════
         ONGLET UTILISATEURS
         Tableau de tous les comptes avec CRUD complet.
         ════════════════════════════════════════════════════ -->
    <div id="tab-users" class="tab-panel active">
      <!-- Actions disponibles : ajouter, exporter CSV, exporter JSON complet -->
      <div class="export-row">
        <button class="btn btn-success btn-sm" onclick="showAddUserModal()">➕ Ajouter utilisateur</button>
        <button class="btn btn-accent btn-sm" onclick="SGI3D_DB.exportCSV('users')">📊 Export CSV</button>
        <button class="btn btn-primary btn-sm" onclick="SGI3D_DB.exportJSON()">📤 Export JSON complet</button>
      </div>
      <div class="card-dark" style="padding:1.5rem">
        <div class="table-wrap">
          <!-- Tableau rempli dynamiquement par renderUsers() -->
          <table id="t-users">
            <thead><tr><th>ID</th><th>Avatar</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Créé le</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         ONGLET CONNEXIONS
         Journal de toutes les tentatives de connexion/déconnexion.
         Filtrable par nom ou email via le champ de recherche.
         Limité aux 200 entrées les plus récentes.
         ════════════════════════════════════════════════════ -->
    <div id="tab-logins" class="tab-panel">
      <!-- Actions : export CSV et suppression totale des logs -->
      <div class="export-row">
        <button class="btn btn-accent btn-sm" onclick="SGI3D_DB.exportCSV('login_logs')">📊 Export CSV</button>
        <button class="btn btn-danger btn-sm" onclick="clearLogs('login_logs')">🗑️ Vider les logs</button>
      </div>
      <!-- Filtre texte : recharge le tableau à chaque frappe via oninput -->
      <div class="search-bar">
        <input id="s-login" type="text" placeholder="🔍 Filtrer par nom ou email…" oninput="renderLogins()">
      </div>
      <div class="card-dark" style="padding:1.5rem">
        <div class="table-wrap">
          <!-- Tableau rempli dynamiquement par renderLogins() -->
          <table id="t-logins">
            <thead><tr><th>Date/Heure</th><th>Utilisateur</th><th>Email</th><th>Résultat</th><th>IP</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         ONGLET IMPRESSIONS
         Historique des travaux d'impression 3D.
         Filtrable par fichier, utilisateur ou imprimante.
         Les travaux "en_cours" ont des boutons Terminer/Erreur.
         ════════════════════════════════════════════════════ -->
    <div id="tab-prints" class="tab-panel">
      <!-- Actions : créer un travail manuellement et exporter CSV -->
      <div class="export-row">
        <button class="btn btn-success btn-sm" onclick="showAddJobModal()">➕ Nouveau travail</button>
        <button class="btn btn-accent btn-sm" onclick="SGI3D_DB.exportCSV('print_jobs')">📊 Export CSV</button>
      </div>
      <!-- Filtre texte : recharge le tableau à chaque frappe via oninput -->
      <div class="search-bar">
        <input id="s-print" type="text" placeholder="🔍 Filtrer par fichier, utilisateur ou imprimante…" oninput="renderPrints()">
      </div>
      <div class="card-dark" style="padding:1.5rem">
        <div class="table-wrap">
          <!-- Tableau rempli dynamiquement par renderPrints() -->
          <table id="t-prints">
            <thead><tr><th>Date</th><th>Fichier</th><th>Utilisateur</th><th>Imprimante</th><th>Matière</th><th>Durée</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         ONGLET CAMÉRAS
         Liste de toutes les caméras avec statut et détection
         de mouvement. Lien vers la page de vue live.
         ════════════════════════════════════════════════════ -->
    <div id="tab-cameras" class="tab-panel">
      <!-- Actions : ajouter une caméra et accéder à la vue live -->
      <div class="export-row">
        <button class="btn btn-success btn-sm" onclick="showAddCamModal()">➕ Ajouter caméra</button>
        <a href="cameras.html" class="btn btn-accent btn-sm">📷 Vue live</a>
      </div>
      <div class="card-dark" style="padding:1.5rem">
        <div class="table-wrap">
          <!-- Tableau rempli dynamiquement par renderCamerasTbl() -->
          <table id="t-cameras">
            <thead><tr><th>ID</th><th>Nom</th><th>Emplacement</th><th>Statut</th><th>Détection mvt</th><th>Ajoutée le</th><th>Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         ONGLET ALERTES
         Toutes les alertes (actives et résolues).
         Bouton "Résoudre" visible uniquement sur les alertes actives.
         Lien vers la page de gestion complète des alertes.
         ════════════════════════════════════════════════════ -->
    <div id="tab-alerts" class="tab-panel">
      <!-- Actions : créer une alerte, exporter CSV, accéder à la page alertes -->
      <div class="export-row">
        <button class="btn btn-danger btn-sm" onclick="showAddAlertModal()">➕ Créer alerte</button>
        <button class="btn btn-accent btn-sm" onclick="SGI3D_DB.exportCSV('alerts')">📊 Export CSV</button>
        <a href="alerts.html" class="btn btn-warning btn-sm">🔔 Gérer alertes</a>
      </div>
      <div class="card-dark" style="padding:1.5rem">
        <div class="table-wrap">
          <!-- Tableau rempli dynamiquement par renderAlertsTbl() -->
          <table id="t-alerts">
            <thead><tr><th>Date</th><th>Type</th><th>Titre</th><th>Message</th><th>Source</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- ════════════════════════════════════════════════════════
     MODALS
     Formulaires superposés déclenchés par les boutons "Ajouter".
     Chaque modal est affiché/masqué via la classe CSS "open".
     ════════════════════════════════════════════════════════ -->

<!-- Modal : ajout d'un utilisateur -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('addUserModal')">✕</button>
    <h2>➕ Ajouter un utilisateur</h2>
    <div class="form-group"><label>Nom</label><input id="au-name" class="form-control" placeholder="Jean Dupont"></div>
    <div class="form-group"><label>Email</label><input id="au-email" class="form-control" type="email" placeholder="jean@sgi3d.fr"></div>
    <div class="form-group"><label>Mot de passe</label><input id="au-pass" class="form-control" type="password" placeholder="••••••••"></div>
    <div class="form-group"><label>Rôle</label>
      <!-- Rôles disponibles : opérateur (accès standard) ou administrateur (accès complet) -->
      <select id="au-role" class="form-control"><option value="operator">Opérateur</option><option value="admin">Administrateur</option></select>
    </div>
    <div style="display:flex;gap:1rem;margin-top:1rem">
      <button class="btn btn-success" onclick="doAddUser()">✅ Créer</button>
      <button class="btn btn-ghost" onclick="closeModal('addUserModal')">Annuler</button>
    </div>
  </div>
</div>

<!-- Modal : création manuelle d'un travail d'impression -->
<div class="modal-overlay" id="addJobModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('addJobModal')">✕</button>
    <h2>🖨️ Nouveau travail d'impression</h2>
    <div class="form-group"><label>Nom du fichier</label><input id="aj-file" class="form-control" placeholder="pièce.stl"></div>
    <div class="form-group"><label>Imprimante</label>
      <!-- Liste des imprimantes disponibles dans l'atelier -->
      <select id="aj-printer" class="form-control"><option>Ultimaker 2+</option><option>Geeetech A20T</option></select>
    </div>
    <div class="form-group"><label>Matière</label>
      <!-- Types de filaments disponibles -->
      <select id="aj-mat" class="form-control"><option>PLA</option><option>ABS</option><option>TPU</option><option>Nylon</option></select>
    </div>
    <div class="form-group"><label>Durée estimée</label><input id="aj-dur" class="form-control" placeholder="90min"></div>
    <div style="display:flex;gap:1rem;margin-top:1rem">
      <button class="btn btn-success" onclick="doAddJob()">▶️ Lancer</button>
      <button class="btn btn-ghost" onclick="closeModal('addJobModal')">Annuler</button>
    </div>
  </div>
</div>

<!-- Modal : ajout d'une caméra de surveillance -->
<div class="modal-overlay" id="addCamModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('addCamModal')">✕</button>
    <h2>📷 Ajouter une caméra</h2>
    <div class="form-group"><label>Nom</label><input id="ac-name" class="form-control" placeholder="Caméra Atelier 3"></div>
    <div class="form-group"><label>Emplacement</label><input id="ac-loc" class="form-control" placeholder="Zone impression"></div>
    <!-- URL du flux vidéo (optionnel – peut être laissé vide) -->
    <div class="form-group"><label>URL flux (optionnel)</label><input id="ac-url" class="form-control" placeholder="http://192.168.1.100/stream"></div>
    <div style="display:flex;gap:1rem;margin-top:1rem">
      <button class="btn btn-success" onclick="doAddCam()">✅ Ajouter</button>
      <button class="btn btn-ghost" onclick="closeModal('addCamModal')">Annuler</button>
    </div>
  </div>
</div>

<!-- Modal : création manuelle d'une alerte système -->
<div class="modal-overlay" id="addAlertModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('addAlertModal')">✕</button>
    <h2>🔔 Créer une alerte</h2>
    <div class="form-group"><label>Type</label>
      <!-- Trois niveaux de gravité : info (bleu), avertissement (jaune), erreur (rouge) -->
      <select id="aa-type" class="form-control"><option value="info">ℹ️ Info</option><option value="warning">⚠️ Avertissement</option><option value="error">🔴 Erreur</option></select>
    </div>
    <div class="form-group"><label>Titre</label><input id="aa-title" class="form-control" placeholder="Titre de l'alerte"></div>
    <div class="form-group"><label>Message</label><textarea id="aa-msg" class="form-control" rows="3" placeholder="Description détaillée…"></textarea></div>
    <div style="display:flex;gap:1rem;margin-top:1rem">
      <button class="btn btn-danger" onclick="doAddAlert()">✅ Créer</button>
      <button class="btn btn-ghost" onclick="closeModal('addAlertModal')">Annuler</button>
    </div>
  </div>
</div>

<!-- Champ fichier caché pour l'import JSON (déclenché par importFile()) -->
<input type="file" id="importInput" accept=".json" style="display:none" onchange="handleImport(event)">

<!-- Conteneur des notifications toast (injectées dynamiquement par showToast()) -->
<div id="toast-container"></div>

<!-- ════════════════════════════════════════════════════════
     SCRIPTS
     db.js expose l'objet global SGI3D_DB avec toutes les
     méthodes d'accès et de manipulation des données.
     ════════════════════════════════════════════════════════ -->
<script src="db.js"></script>
<script>
// ── GARDE D'AUTHENTIFICATION ─────────────────────────────
// Redirige vers login.html si l'utilisateur n'est pas connecté
if(!SGI3D_DB.requireAuth()){}

// Déconnexion : enregistre l'événement puis redirige vers la page de connexion
function doLogout(){ SGI3D_DB.logout(); window.location.href='login.html'; }

// ── HELPERS MODALS ───────────────────────────────────────
// Ferme un modal en retirant la classe "open" (qui le rend visible)
function closeModal(id){ document.getElementById(id).classList.remove('open'); }

// Ouvre chaque modal en ajoutant la classe "open"
function showAddUserModal(){ document.getElementById('addUserModal').classList.add('open'); }
function showAddJobModal(){ document.getElementById('addJobModal').classList.add('open'); }
function showAddCamModal(){ document.getElementById('addCamModal').classList.add('open'); }
function showAddAlertModal(){ document.getElementById('addAlertModal').classList.add('open'); }

// ── GESTION DES ONGLETS ──────────────────────────────────
// Masque tous les panneaux et désactive tous les boutons,
// puis active uniquement l'onglet et le panneau ciblé,
// et déclenche le rendu du tableau correspondant.
function switchTab(name){
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  event.target.classList.add('active');
  renderTab(name);
}

// Aiguille vers la fonction de rendu appropriée selon l'onglet actif
function renderTab(name){
  if(name==='users')   renderUsers();
  if(name==='logins')  renderLogins();
  if(name==='prints')  renderPrints();
  if(name==='cameras') renderCamerasTbl();
  if(name==='alerts')  renderAlertsTbl();
}

// ════════════════════════════════════════════════════════
//  ONGLET UTILISATEURS
// ════════════════════════════════════════════════════════

// Génère le tableau HTML de tous les utilisateurs.
// Affiche : id, avatar (initiales), nom, email, rôle (badge),
// date de création, statut actif/inactif, boutons actions.
function renderUsers(){
  const users=SGI3D_DB.getUsers();
  document.querySelector('#t-users tbody').innerHTML=users.map(u=>`
    <tr>
      <td><code>${u.id}</code></td>
      <td><div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#4a4a4a,#2a2a2a);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700">${u.avatar||u.name[0]}</div></td>
      <td>${u.name}</td><td>${u.email}</td>
      <td><span class="badge badge-${u.role==='admin'?'info':'neutral'}">${u.role}</span></td>
      <td>${SGI3D_DB.formatDate(u.createdAt)}</td>
      <td><span class="badge badge-${u.active?'success':'danger'}">${u.active?'Actif':'Inactif'}</span></td>
      <td>
        <button class="btn btn-warning btn-xs" onclick="toggleUser(${u.id})">${u.active?'Désactiver':'Activer'}</button>
        <button class="btn btn-danger btn-xs" onclick="deleteUser(${u.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

// Valide le formulaire, vérifie l'unicité de l'email,
// crée l'utilisateur avec les initiales comme avatar et rafraîchit le tableau.
function doAddUser(){
  const n=document.getElementById('au-name').value.trim(),e=document.getElementById('au-email').value.trim(),p=document.getElementById('au-pass').value,r=document.getElementById('au-role').value;
  if(!n||!e||!p){showToast('Remplissez tous les champs','warning');return;}
  if(SGI3D_DB.getUserByEmail(e)){showToast('Email déjà utilisé','warning');return;}
  SGI3D_DB.createUser({name:n,email:e,password:p,role:r,avatar:n.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()});
  closeModal('addUserModal'); renderUsers(); showToast('✅ Utilisateur créé','success');
}

// Bascule l'état actif/inactif d'un utilisateur (true ↔ false)
function toggleUser(id){const u=SGI3D_DB.getUserById(id);if(u)SGI3D_DB.updateUser(id,{active:!u.active});renderUsers();}

// Demande confirmation puis supprime définitivement un utilisateur par son id
function deleteUser(id){if(confirm('Supprimer ?')){SGI3D_DB.deleteUser(id);renderUsers();showToast('Supprimé','warning');}}

// ════════════════════════════════════════════════════════
//  ONGLET CONNEXIONS
// ════════════════════════════════════════════════════════

// Filtre les journaux par le texte saisi (nom ou email),
// puis génère le tableau HTML (max 200 lignes).
// Badges colorés : ✅ succès (vert) | 🚪 déconnexion (gris) | ❌ échec (rouge)
function renderLogins(){
  const q=(document.getElementById('s-login').value||'').toLowerCase();
  const logs=SGI3D_DB.getLoginLogs().filter(l=>!q||l.userName.toLowerCase().includes(q)||l.email.toLowerCase().includes(q));
  const icon=s=>s===true?'<span class="badge badge-success">✅ Succès</span>':s==='logout'?'<span class="badge badge-neutral">🚪 Déco.</span>':'<span class="badge badge-danger">❌ Échec</span>';
  document.querySelector('#t-logins tbody').innerHTML=logs.slice(0,200).map(l=>`
    <tr>
      <td>${SGI3D_DB.formatDate(l.timestamp)}</td>
      <td>${l.userName||'—'}</td><td>${l.email}</td>
      <td>${icon(l.success)}</td><td><code>${l.ip||'—'}</code></td>
    </tr>`).join('')||'<tr><td colspan="5" style="text-align:center;color:rgba(255,255,255,.4)">Aucune connexion</td></tr>';
}

// ════════════════════════════════════════════════════════
//  ONGLET IMPRESSIONS
// ════════════════════════════════════════════════════════

// Filtre les travaux par fichier, utilisateur ou imprimante,
// puis génère le tableau HTML (max 200 lignes).
// Les travaux "en_cours" ont deux boutons supplémentaires :
// ✅ Terminer (succès) et ❌ Erreur (échec).
function renderPrints(){
  const q=(document.getElementById('s-print').value||'').toLowerCase();
  const jobs=SGI3D_DB.getPrintJobs().filter(j=>!q||(j.fileName||'').toLowerCase().includes(q)||(j.userName||'').toLowerCase().includes(q)||(j.printer||'').toLowerCase().includes(q));
  // Badge de statut coloré selon l'état du travail
  const sBadge=s=>`<span class="badge badge-${s==='terminé'?'success':s==='erreur'?'danger':'info'}">${s}</span>`;
  document.querySelector('#t-prints tbody').innerHTML=jobs.slice(0,200).map(j=>`
    <tr>
      <td>${SGI3D_DB.formatDate(j.startedAt)}</td>
      <td><strong>${j.fileName||'—'}</strong></td>
      <td>${j.userName||'—'}</td><td>${j.printer||'—'}</td>
      <td>${j.material||'—'}</td><td>${j.duration||'—'}</td>
      <td>${sBadge(j.status)}</td>
      <td>
        ${j.status==='en_cours'?`<button class="btn btn-success btn-xs" onclick="finJob(${j.id},true)">✅ Terminer</button><button class="btn btn-danger btn-xs" onclick="finJob(${j.id},false)">❌ Erreur</button>`:''}
        <button class="btn btn-danger btn-xs" onclick="delJob(${j.id})">🗑️</button>
      </td>
    </tr>`).join('')||'<tr><td colspan="8" style="text-align:center;color:rgba(255,255,255,.4)">Aucun travail</td></tr>';
}

// Crée un nouveau travail d'impression avec le statut "en_cours"
function doAddJob(){
  const f=document.getElementById('aj-file').value.trim(),p=document.getElementById('aj-printer').value,m=document.getElementById('aj-mat').value,d=document.getElementById('aj-dur').value;
  if(!f){showToast('Nom de fichier requis','warning');return;}
  SGI3D_DB.createPrintJob({fileName:f,printer:p,material:m,duration:d});
  closeModal('addJobModal');renderPrints();showToast('🖨️ Travail créé','success');
}

// Clôture un travail : ok=true → "terminé", ok=false → "erreur"
function finJob(id,ok){SGI3D_DB.finishPrintJob(id,ok);renderPrints();}

// Supprime un travail directement depuis le store local (sans passer par l'API)
function delJob(id){if(confirm('Supprimer ?')){const jobs=SGI3D_DB.getPrintJobs().filter(j=>j.id!==id);SGI3D_DB.set('print_jobs',jobs);renderPrints();}}

// ════════════════════════════════════════════════════════
//  ONGLET CAMÉRAS
// ════════════════════════════════════════════════════════

// Génère le tableau HTML de toutes les caméras.
// Badge statut : 🟢 En ligne (vert) | 🔴 Hors ligne (rouge)
// Badge détection mouvement : ✅ Actif (bleu) | ❌ (gris)
function renderCamerasTbl(){
  const cams=SGI3D_DB.getCameras();
  document.querySelector('#t-cameras tbody').innerHTML=cams.map(c=>`
    <tr>
      <td><code>${c.id}</code></td><td>${c.name}</td><td>${c.location}</td>
      <td><span class="badge badge-${c.status==='online'?'success':'danger'}">${c.status==='online'?'🟢 En ligne':'🔴 Hors ligne'}</span></td>
      <td>${c.motionDetect?'<span class="badge badge-info">✅ Actif</span>':'<span class="badge badge-neutral">❌</span>'}</td>
      <td>${SGI3D_DB.formatDate(c.addedAt)}</td>
      <td>
        <button class="btn btn-warning btn-xs" onclick="toggleCam(${c.id})">${c.status==='online'?'Désactiver':'Activer'}</button>
        <button class="btn btn-danger btn-xs" onclick="deleteCam(${c.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

// Valide les champs obligatoires (nom + emplacement) et crée la caméra
// avec la détection de mouvement activée par défaut.
function doAddCam(){
  const n=document.getElementById('ac-name').value.trim(),l=document.getElementById('ac-loc').value.trim(),u=document.getElementById('ac-url').value.trim();
  if(!n||!l){showToast('Nom et emplacement requis','warning');return;}
  SGI3D_DB.addCamera({name:n,location:l,url:u,motionDetect:true});
  closeModal('addCamModal');renderCamerasTbl();showToast('📷 Caméra ajoutée','success');
}

// Bascule le statut d'une caméra entre "online" et "offline"
function toggleCam(id){const c=SGI3D_DB.getCameras().find(c=>c.id===id);if(c)SGI3D_DB.updateCamera(id,{status:c.status==='online'?'offline':'online'});renderCamerasTbl();}

// Supprime définitivement une caméra après confirmation
function deleteCam(id){if(confirm('Supprimer ?')){SGI3D_DB.deleteCamera(id);renderCamerasTbl();}}

// ════════════════════════════════════════════════════════
//  ONGLET ALERTES
// ════════════════════════════════════════════════════════

// Génère le tableau HTML de toutes les alertes (actives et résolues).
// Met aussi à jour le badge d'alertes dans la sidebar.
// Icône par type : 🔴 erreur | 🟡 avertissement | 🔵 info
// Le bouton "Résoudre" n'est affiché que pour les alertes non résolues.
function renderAlertsTbl(){
  const alerts=SGI3D_DB.getAlerts();
  // Mise à jour du badge rouge (nombre d'alertes actives)
  document.getElementById('sb-alerts').textContent=SGI3D_DB.getAlerts(false).length;
  const icon=t=>t==='error'?'🔴':t==='warning'?'🟡':'🔵';
  document.querySelector('#t-alerts tbody').innerHTML=alerts.map(a=>`
    <tr>
      <td>${SGI3D_DB.formatDate(a.createdAt)}</td>
      <td>${icon(a.type)} ${a.type}</td>
      <td><strong>${a.title}</strong></td><td>${a.message.slice(0,50)}…</td>
      <td>${a.source||'—'}</td>
      <td><span class="badge badge-${a.resolved?'success':'danger'}">${a.resolved?'✅ Résolu':'⚠️ Actif'}</span></td>
      <td>
        ${!a.resolved?`<button class="btn btn-success btn-xs" onclick="resolveAlert(${a.id})">✅ Résoudre</button>`:''}
        <button class="btn btn-danger btn-xs" onclick="deleteAlert(${a.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

// Valide les champs obligatoires (titre + message) et crée une alerte.
// La source est automatiquement définie au nom de l'utilisateur connecté.
function doAddAlert(){
  const t=document.getElementById('aa-type').value,ti=document.getElementById('aa-title').value.trim(),m=document.getElementById('aa-msg').value.trim();
  if(!ti||!m){showToast('Titre et message requis','warning');return;}
  SGI3D_DB.addAlert({type:t,title:ti,message:m,source:SGI3D_DB.getSession()?.name||'Admin'});
  closeModal('addAlertModal');renderAlertsTbl();showToast('🔔 Alerte créée','success');
}

// Marque une alerte comme résolue et rafraîchit le tableau
function resolveAlert(id){SGI3D_DB.resolveAlert(id);renderAlertsTbl();}

// Supprime définitivement une alerte après confirmation
function deleteAlert(id){if(confirm('Supprimer ?')){SGI3D_DB.deleteAlert(id);renderAlertsTbl();}}

// ════════════════════════════════════════════════════════
//  UTILITAIRES
// ════════════════════════════════════════════════════════

// Vide entièrement une table de logs après confirmation utilisateur
function clearLogs(table){if(confirm('Vider les logs '+table+' ?')){SGI3D_DB.set(table,[]);renderLogins();showToast('Logs vidés','warning');}}

// Ouvre le sélecteur de fichier natif (input type="file" caché)
function importFile(){document.getElementById('importInput').click();}

// Lit le fichier JSON sélectionné, tente l'import via SGI3D_DB
// et rafraîchit le tableau utilisateurs si l'import est un succès.
function handleImport(e){
  const f=e.target.files[0];if(!f)return;
  const r=new FileReader();r.onload=ev=>{const ok=SGI3D_DB.importJSON(ev.target.result);showToast(ok?'✅ Import réussi':'❌ Erreur',ok?'success':'error');if(ok)renderUsers();};r.readAsText(f);
}

// Crée une notification toast temporaire (disparaît après 3,5 s).
// Types : 'info' | 'success' | 'warning' | 'error'
function showToast(msg,type='info'){
  const c=document.getElementById('toast-container');const t=document.createElement('div');
  t.className='toast '+type;t.textContent=msg;c.appendChild(t);setTimeout(()=>t.remove(),3500);
}

// ── INITIALISATION ───────────────────────────────────────
// Affiche l'onglet Utilisateurs au chargement de la page
renderUsers();
// Initialise le badge d'alertes dans la sidebar
document.getElementById('sb-alerts').textContent=SGI3D_DB.getAlerts(false).length;
</script>
</body>
</html>
