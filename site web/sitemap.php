<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>SGI3D – Plan du site</title>
<link rel="stylesheet" href="style.css">
<style>
  body{padding-top:60px}
  .sitemap-wrap{max-width:1100px;margin:0 auto;padding:3rem 2rem}
  .sitemap-title{text-align:center;margin-bottom:3rem}
  .sitemap-title h1{font-size:2.5rem;font-weight:900;letter-spacing:3px;background:linear-gradient(135deg,#fff,rgba(255,255,255,.5));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
  .sitemap-title p{color:rgba(255,255,255,.6);margin-top:.5rem}
  /* ── Arbre du plan ── */
  .sitemap-tree{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.5rem}
  .site-node{
    background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);
    border-radius:var(--radius);overflow:hidden;transition:all .3s;
  }
  .site-node:hover{transform:translateY(-4px);background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);box-shadow:0 20px 40px rgba(0,0,0,.3)}
  .node-header{padding:1.2rem 1.5rem;background:rgba(0,0,0,.3);display:flex;align-items:center;gap:.9rem}
  .node-icon{font-size:1.8rem}
  .node-header h2{font-size:1rem;font-weight:700;margin:0}
  .node-header p{font-size:.78rem;color:rgba(255,255,255,.5);margin:.2rem 0 0}
  .node-links{padding:1rem 1.5rem;display:flex;flex-direction:column;gap:.5rem}
  .node-link{
    display:flex;align-items:center;gap:.6rem;color:rgba(255,255,255,.75);text-decoration:none;
    font-size:.88rem;padding:.4rem .6rem;border-radius:8px;transition:all .2s;
  }
  .node-link:hover{background:rgba(255,255,255,.1);color:#fff}
  .node-link .lk-icon{font-size:.95rem;width:20px;text-align:center}
  .node-link .badge-admin{font-size:.68rem;padding:.15rem .5rem;border-radius:8px;background:rgba(52,152,219,.3);color:#3498db;margin-left:auto}
  /* ── Tableau récapitulatif ── */
  .pages-table-wrap{margin-top:3rem}
  .pages-table-wrap h2{font-size:1.2rem;font-weight:700;margin-bottom:1rem;color:rgba(255,255,255,.85)}
  /* ── Légende ── */
  .legend{display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:2rem;font-size:.82rem;color:rgba(255,255,255,.6)}
  .legend span{display:flex;align-items:center;gap:.4rem}
  footer.site-footer{text-align:center;padding:2rem;color:rgba(255,255,255,.4);font-size:.8rem}
  footer.site-footer a{color:rgba(255,255,255,.6);text-decoration:none}
  footer.site-footer a:hover{color:#fff}
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
    <a href="dashboard.php">📊 <span>Dashboard</span></a>
    <?php if (isset($_SESSION['user'])): ?>
      <a href="dashboard.php" class="btn-nav">👤 <span><?= htmlspecialchars(explode(' ', $_SESSION['user']['nom'])[0]) ?></span></a>
    <?php else: ?>
      <a href="login.php" class="btn-nav">🔐 <span>Connexion</span></a>
    <?php endif; ?>
  </div>
</nav>

<div class="sitemap-wrap">
  <div class="sitemap-title">
    <h1>🗺️ PLAN DU SITE</h1>
    <p>Vue complète de toutes les pages et fonctionnalités de SGI3D v3.0</p>
  </div>

  <div class="sitemap-tree">

    <!-- Accueil -->
    <div class="site-node">
      <div class="node-header">
        <div class="node-icon">🏠</div>
        <div><h2>Accueil</h2><p>Page principale du site</p></div>
      </div>
      <div class="node-links">
        <a href="index.php" class="node-link"><span class="lk-icon">🏠</span> index.php</a>
        <a href="printers.php" class="node-link"><span class="lk-icon">→</span> Voir les imprimantes</a>
        <a href="login.php" class="node-link"><span class="lk-icon">→</span> Se connecter</a>
        <a href="cameras.php" class="node-link"><span class="lk-icon">→</span> Surveillance caméras</a>
        <a href="alerts.php" class="node-link"><span class="lk-icon">→</span> Alertes système</a>
        <a href="dashboard.php" class="node-link"><span class="lk-icon">→</span> Dashboard admin</a>
      </div>
    </div>

    <!-- Connexion -->
    <div class="site-node">
      <div class="node-header">
        <div class="node-icon">🔐</div>
        <div><h2>Connexion</h2><p>Authentification utilisateur</p></div>
      </div>
      <div class="node-links">
        <a href="login.php" class="node-link"><span class="lk-icon">🔐</span> login.php</a>
        <a href="#" class="node-link"><span class="lk-icon">✉️</span> Formulaire email/password</a>
        <a href="#" class="node-link"><span class="lk-icon">📝</span> Créer un compte</a>
        <a href="index.php" class="node-link"><span class="lk-icon">←</span> Retour accueil</a>
        <a href="printers.php" class="node-link"><span class="lk-icon">←</span> Voir imprimantes</a>
        <a href="dashboard.php" class="node-link"><span class="lk-icon">→</span> Dashboard (après login)</a>
      </div>
    </div>

    <!-- Imprimantes -->
    <div class="site-node">
      <div class="node-header">
        <div class="node-icon">🖨️</div>
        <div><h2>Imprimantes</h2><p>Parc machines 3D</p></div>
      </div>
      <div class="node-links">
        <a href="printers.php" class="node-link"><span class="lk-icon">🖨️</span> printers.php</a>
        <a href="#" class="node-link"><span class="lk-icon">📐</span> Ultimaker 2+ (modèle 3D)</a>
        <a href="#" class="node-link"><span class="lk-icon">📐</span> Geeetech A20T (modèle 3D)</a>
        <a href="#" class="node-link"><span class="lk-icon">▶️</span> Lancer une impression <span class="badge-admin">Login</span></a>
        <a href="index.php" class="node-link"><span class="lk-icon">←</span> Accueil</a>
        <a href="login.php" class="node-link"><span class="lk-icon">←</span> Connexion</a>
      </div>
    </div>

    <!-- Dashboard -->
    <div class="site-node">
      <div class="node-header">
        <div class="node-icon">📊</div>
        <div><h2>Dashboard</h2><p>Tableau de bord admin</p></div>
      </div>
      <div class="node-links">
        <a href="dashboard.php" class="node-link"><span class="lk-icon">📊</span> dashboard.php <span class="badge-admin">Admin</span></a>
        <a href="#" class="node-link"><span class="lk-icon">📈</span> Statistiques globales</a>
        <a href="#" class="node-link"><span class="lk-icon">🔐</span> Journal connexions</a>
        <a href="#" class="node-link"><span class="lk-icon">🖨️</span> Travaux impression</a>
        <a href="#" class="node-link"><span class="lk-icon">👥</span> Gestion utilisateurs</a>
        <a href="database.php" class="node-link"><span class="lk-icon">→</span> Base de données</a>
      </div>
    </div>

    <!-- Base de données -->
    <div class="site-node">
      <div class="node-header">
        <div class="node-icon">🗄️</div>
        <div><h2>Base de données</h2><p>Gestion données complète</p></div>
      </div>
      <div class="node-links">
        <a href="database.php" class="node-link"><span class="lk-icon">🗄️</span> database.php <span class="badge-admin">Admin</span></a>
        <a href="#" class="node-link"><span class="lk-icon">👥</span> Table Utilisateurs</a>
        <a href="#" class="node-link"><span class="lk-icon">🔐</span> Table Connexions (logs)</a>
        <a href="#" class="node-link"><span class="lk-icon">🖨️</span> Table Travaux impression</a>
        <a href="#" class="node-link"><span class="lk-icon">📷</span> Table Caméras</a>
        <a href="#" class="node-link"><span class="lk-icon">📤</span> Export JSON / CSV (USB)</a>
      </div>
    </div>

    <!-- Caméras -->
    <div class="site-node">
      <div class="node-header">
        <div class="node-icon">📷</div>
        <div><h2>Caméras</h2><p>Surveillance en temps réel</p></div>
      </div>
      <div class="node-links">
        <a href="cameras.php" class="node-link"><span class="lk-icon">📷</span> cameras.php</a>
        <a href="#" class="node-link"><span class="lk-icon">🎥</span> Flux caméras simulés</a>
        <a href="#" class="node-link"><span class="lk-icon">🎯</span> Détection de mouvement</a>
        <a href="#" class="node-link"><span class="lk-icon">⛶</span> Mode plein écran</a>
        <a href="#" class="node-link"><span class="lk-icon">➕</span> Ajouter une caméra</a>
        <a href="alerts.php" class="node-link"><span class="lk-icon">→</span> Alertes mouvement</a>
      </div>
    </div>

    <!-- Alertes -->
    <div class="site-node">
      <div class="node-header">
        <div class="node-icon">🔔</div>
        <div><h2>Alertes</h2><p>Notifications et gestion</p></div>
      </div>
      <div class="node-links">
        <a href="alerts.php" class="node-link"><span class="lk-icon">🔔</span> alerts.php</a>
        <a href="#" class="node-link"><span class="lk-icon">🔴</span> Erreurs critiques</a>
        <a href="#" class="node-link"><span class="lk-icon">🟡</span> Avertissements</a>
        <a href="#" class="node-link"><span class="lk-icon">🔵</span> Informations</a>
        <a href="#" class="node-link"><span class="lk-icon">🔊</span> Son d'alerte</a>
        <a href="#" class="node-link"><span class="lk-icon">➕</span> Créer une alerte</a>
      </div>
    </div>

    <!-- Export USB -->
    <div class="site-node">
      <div class="node-header">
        <div class="node-icon">💾</div>
        <div><h2>Export USB</h2><p>Portabilité des données</p></div>
      </div>
      <div class="node-links">
        <a href="database.php" class="node-link"><span class="lk-icon">📤</span> Exporter JSON (database.php)</a>
        <a href="dashboard.php" class="node-link"><span class="lk-icon">📤</span> Export via Dashboard</a>
        <a href="#" class="node-link"><span class="lk-icon">📊</span> Export CSV Connexions</a>
        <a href="#" class="node-link"><span class="lk-icon">📊</span> Export CSV Impressions</a>
        <a href="#" class="node-link"><span class="lk-icon">📥</span> Importer un backup JSON</a>
        <a href="#" class="node-link"><span class="lk-icon">📂</span> Copier 9 PHP + db.js</a>
      </div>
    </div>
  </div>

  <!-- Tableau récapitulatif -->
  <div class="pages-table-wrap">
    <h2>📋 Récapitulatif des pages</h2>
    <div class="card-dark" style="padding:1.5rem">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Fichier</th><th>Page</th><th>Accès</th><th>Description</th><th>Action</th></tr>
          </thead>
          <tbody>
            <tr><td><code>index.php</code></td><td>🏠 Accueil</td><td><span class="badge badge-success">Public</span></td><td>Page principale avec modèle 3D animé</td><td><a href="index.php" class="btn btn-ghost btn-xs">Ouvrir</a></td></tr>
            <tr><td><code>login.php</code></td><td>🔐 Connexion</td><td><span class="badge badge-success">Public</span></td><td>Authentification, création de compte</td><td><a href="login.php" class="btn btn-ghost btn-xs">Ouvrir</a></td></tr>
            <tr><td><code>printers.php</code></td><td>🖨️ Imprimantes</td><td><span class="badge badge-success">Public</span></td><td>Fiches techniques + lancer impression</td><td><a href="printers.php" class="btn btn-ghost btn-xs">Ouvrir</a></td></tr>
            <tr><td><code>cameras.php</code></td><td>📷 Caméras</td><td><span class="badge badge-success">Public</span></td><td>Flux vidéo simulés, détection mouvement</td><td><a href="cameras.php" class="btn btn-ghost btn-xs">Ouvrir</a></td></tr>
            <tr><td><code>alerts.php</code></td><td>🔔 Alertes</td><td><span class="badge badge-success">Public</span></td><td>Gestion alertes, son, auto-simulation</td><td><a href="alerts.php" class="btn btn-ghost btn-xs">Ouvrir</a></td></tr>
            <tr><td><code>dashboard.php</code></td><td>📊 Dashboard</td><td><span class="badge badge-info">Login</span></td><td>Stats, activité, gestion utilisateurs</td><td><a href="dashboard.php" class="btn btn-ghost btn-xs">Ouvrir</a></td></tr>
            <tr><td><code>database.php</code></td><td>🗄️ Base données</td><td><span class="badge badge-info">Login</span></td><td>CRUD complet sur toutes les tables</td><td><a href="database.php" class="btn btn-ghost btn-xs">Ouvrir</a></td></tr>
            <tr><td><code>sitemap.php</code></td><td>🗺️ Plan du site</td><td><span class="badge badge-success">Public</span></td><td>Navigation complète, cette page</td><td><a href="sitemap.php" class="btn btn-ghost btn-xs">Ici</a></td></tr>
            <tr><td><code>db.js</code></td><td>⚙️ Moteur DB</td><td><span class="badge badge-neutral">Fichier JS</span></td><td>localStorage – CRUD, auth, logs, export</td><td>—</td></tr>
            <tr><td><code>style.css</code></td><td>🎨 Styles</td><td><span class="badge badge-neutral">Fichier CSS</span></td><td>Design tokens, sidebar, composants</td><td>—</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Légende -->
  <div class="legend">
    <span>🟢 Public = accessible sans connexion</span>
    <span>🔵 Login = nécessite une session active</span>
    <span>💾 Toutes les données = localStorage (portable)</span>
  </div>

  <!-- Instructions USB -->
  <div class="card-dark" style="padding:2rem;margin-top:2rem">
    <h2 style="margin-bottom:1rem">💾 Comment transporter le site sur clé USB</h2>
    <ol style="line-height:2;color:rgba(255,255,255,.8);padding-left:1.5rem">
      <li>Copiez ces <strong>10 fichiers</strong> sur votre clé USB : <code>index.php, login.php, printers.php, cameras.php, alerts.php, dashboard.php, database.php, sitemap.php, db.js, style.css</code></li>
      <li>Sur le <strong>PC source</strong>, allez dans Dashboard → cliquez <strong>📤 Exporter JSON</strong> et copiez le fichier JSON sur la clé USB</li>
      <li>Sur le <strong>PC destination</strong>, ouvrez <code>index.php</code> depuis la clé USB dans Chrome ou Firefox</li>
      <li>Connectez-vous, allez dans Dashboard → <strong>📥 Importer JSON</strong> et sélectionnez le fichier de backup</li>
      <li>Toutes vos données (utilisateurs, connexions, impressions, alertes, caméras) sont restaurées ✅</li>
    </ol>
    <div style="margin-top:1.5rem;display:flex;gap:1rem;flex-wrap:wrap">
      <a href="dashboard.php" class="btn btn-primary">📊 Aller au Dashboard</a>
      <button class="btn btn-accent" onclick="SGI3D_DB.exportJSON()">📤 Exporter la DB maintenant</button>
    </div>
  </div>
</div>

<footer class="site-footer">
  <a href="index.php">🏠 Accueil</a> · <a href="login.php">🔐 Connexion</a> · <a href="printers.php">🖨️ Imprimantes</a> · <a href="dashboard.php">📊 Dashboard</a> · <a href="database.php">🗄️ Base de données</a>
  <p style="margin-top:.5rem">© 2025 SGI3D v3.0 – Système de Gestion d'Impression 3D</p>
</footer>

<script src="db.js"></script>
</body>
</html>
