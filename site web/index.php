<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SGI3D – Accueil</title>
<!-- Feuille de style globale partagée par toutes les pages -->
<link rel="stylesheet" href="style.css">
<style>
  /* Cache le défilement horizontal causé par les particules animées */
  body{overflow-x:hidden}

  /* ── SECTION HERO ───────────────────────────────────────
     Occupe toute la hauteur de l'écran, centre le contenu
     verticalement et horizontalement. Le padding-top laisse
     de la place sous la barre de navigation fixe. */
  .hero{
    min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;
    text-align:center;padding:80px 2rem 2rem;position:relative;
  }

  /* Titre principal "SGI3D" avec dégradé appliqué sur le texte.
     -webkit-background-clip + -webkit-text-fill-color : compatibilité Safari/Chrome.
     background-clip:text : version standard CSS (Firefox, navigateurs modernes). */
  .hero-logo{
    font-size:5rem;font-weight:900;letter-spacing:8px;
    background:linear-gradient(135deg,#fff 30%,rgba(255,255,255,.4));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
    background-clip:text;
    margin-bottom:.5rem;
  }

  /* Sous-titre en lettres espacées et atténuées sous le logo */
  .hero-sub{font-size:1.1rem;color:rgba(255,255,255,.6);letter-spacing:3px;text-transform:uppercase;margin-bottom:3rem}

  /* Canvas Three.js : taille réactive (max 500×400 px),
     coins arrondis, ombre portée et bordure translucide */
  #hero-canvas{
    width:min(500px,90vw);height:min(400px,60vw);
    border-radius:var(--radius);box-shadow:0 30px 60px rgba(0,0,0,.5);
    border:1px solid rgba(255,255,255,.1);margin-bottom:3rem;
  }

  /* ── GRILLE DE CARTES D'ACTION ───────────────────────────
     Disposition auto-responsive : les cartes s'ajustent
     automatiquement en colonnes (minimum 240 px par carte). */
  .action-grid{
    display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:1.5rem;width:100%;max-width:900px;
  }

  /* Carte individuelle : fond semi-transparent avec effet de flou (glassmorphism) */
  .action-card{
    background:rgba(255,255,255,.07);backdrop-filter:blur(10px);
    border:1px solid rgba(255,255,255,.12);border-radius:var(--radius);
    padding:2rem;text-align:center;text-decoration:none;color:#fff;
    transition:all .3s;
  }
  /* Au survol : légère élévation, fond plus clair, bordure plus visible */
  .action-card:hover{transform:translateY(-8px);background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);box-shadow:0 20px 40px rgba(0,0,0,.3)}
  .action-card .ac-icon{font-size:2.5rem;margin-bottom:1rem}
  .action-card h3{font-size:1.1rem;font-weight:700;margin-bottom:.5rem}
  .action-card p{font-size:.85rem;color:rgba(255,255,255,.6)}

  /* ── PARTICULES DE FOND ─────────────────────────────────
     Couche fixe couvrant tout l'écran, derrière tout le contenu.
     pointer-events:none évite de bloquer les clics. */
  .particles{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0}

  /* Chaque particule est un minuscule point blanc semi-transparent */
  .particle{
    position:absolute;width:2px;height:2px;background:rgba(255,255,255,.5);border-radius:50%;
    animation:float linear infinite;
  }

  /* Animation de montée : la particule remonte du bas de l'écran vers le haut
     en apparaissant progressivement, puis disparaît en fin de course */
  @keyframes float{0%{transform:translateY(100vh) translateX(0);opacity:0}
    10%{opacity:1}90%{opacity:1}100%{transform:translateY(-100px) translateX(30px);opacity:0}}

  /* Passe le contenu principal au-dessus des particules (z-index:1) */
  .hero,.action-grid,.hero-logo,.hero-sub{position:relative;z-index:1}

  /* Pied de page centré, texte atténué, liens discrets */
  footer.site-footer{text-align:center;padding:2rem;color:rgba(255,255,255,.4);font-size:.8rem;z-index:1;position:relative}
  footer.site-footer a{color:rgba(255,255,255,.6);text-decoration:none}
  footer.site-footer a:hover{color:#fff}
</style>
</head>
<body>

<!-- ════════════════════════════════════════════════════════
     NAVIGATION PRINCIPALE (barre fixe en haut)
     Le lien "Connexion" sera remplacé par le prénom de
     l'utilisateur s'il est déjà connecté (voir script en bas).
     ════════════════════════════════════════════════════════ -->
<nav class="site-nav">
  <a href="index.html" class="nav-logo">SGI3D</a>
  <div class="nav-links">
    <a href="index.php" class="active">🏠 <span>Accueil</span></a>
    <a href="printers.php">🖨️ <span>Imprimantes</span></a>
    <a href="cameras.php">📷 <span>Caméras</span></a>
    <a href="alerts.html">🔔 <span>Alertes</span></a>
    <a href="dashboard.php">📊 <span>Dashboard</span></a>
    <a href="login.php" class="btn-nav">🔐 <span>Connexion</span></a>
  </div>
</nav>

<!-- ════════════════════════════════════════════════════════
     PARTICULES DE FOND
     Le conteneur est rempli dynamiquement par le script JS
     (40 points flottants avec positions et durées aléatoires).
     ════════════════════════════════════════════════════════ -->
<div class="particles" id="particles"></div>

<!-- ════════════════════════════════════════════════════════
     SECTION HERO
     Contient le titre animé, la scène 3D interactive et la
     grille des 6 cartes de navigation vers les modules.
     ════════════════════════════════════════════════════════ -->
<section class="hero">
  <!-- Logo texte principal avec dégradé CSS -->
  <div class="hero-logo">SGI3D</div>
  <p class="hero-sub">Système de Gestion d'Impression 3D</p>

  <!-- Canvas Three.js : scène 3D d'une imprimante animée -->
  <canvas id="hero-canvas"></canvas>

  <!-- Grille de 6 cartes d'accès rapide aux modules du site -->
  <div class="action-grid">
    <!-- Carte : accès à la page des imprimantes 3D -->
    <a href="printers.html" class="action-card">
      <div class="ac-icon">🖨️</div>
      <h3>Nos Imprimantes</h3>
      <p>Ultimaker 2+ &amp; Geeetech A20T – Spécifications et modèles 3D interactifs</p>
    </a>
    <!-- Carte : accès à la page de connexion -->
    <a href="login.html" class="action-card">
      <div class="ac-icon">🔐</div>
      <h3>Se Connecter</h3>
      <p>Accéder au tableau de bord, gérer les impressions et les utilisateurs</p>
    </a>
    <!-- Carte : accès à la surveillance par caméras -->
    <a href="cameras.html" class="action-card">
      <div class="ac-icon">📷</div>
      <h3>Surveillance</h3>
      <p>Flux caméras en temps réel, détection de mouvement, monitoring atelier</p>
    </a>
    <!-- Carte : accès aux alertes système -->
    <a href="alerts.html" class="action-card">
      <div class="ac-icon">🔔</div>
      <h3>Alertes</h3>
      <p>Notifications système, pannes, alertes filament et températures</p>
    </a>
    <!-- Carte : accès au tableau de bord admin -->
    <a href="dashboard.html" class="action-card">
      <div class="ac-icon">📊</div>
      <h3>Dashboard</h3>
      <p>Statistiques globales, journaux d'activité et gestion complète</p>
    </a>
    <!-- Carte : plan du site (vue d'ensemble de toutes les pages) -->
    <a href="sitemap.html" class="action-card">
      <div class="ac-icon">🗺️</div>
      <h3>Plan du site</h3>
      <p>Vue d'ensemble de toutes les pages et fonctionnalités disponibles</p>
    </a>
  </div>
</section>

<!-- Pied de page avec liens vers le plan du site et la base de données -->
<footer class="site-footer">
  <p>© 2025 SGI3D – <a href="sitemap.html">Plan du site</a> · <a href="database.html">Base de données</a> · v3.0</p>
</footer>

<!-- db.js : objet global SGI3D_DB pour accéder aux données (session, alertes…) -->
<script src="db.js"></script>
<!-- Three.js r128 : bibliothèque de rendu 3D WebGL -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
// ════════════════════════════════════════════════════════
//  PARTICULES DE FOND
//  Crée 40 éléments <div class="particle"> avec une position
//  horizontale, un délai et une durée d'animation aléatoires,
//  puis les injecte dans le conteneur #particles.
// ════════════════════════════════════════════════════════
(function(){
  const c = document.getElementById('particles');
  for(let i=0;i<40;i++){
    const p = document.createElement('div');
    p.className='particle';
    // left      : position horizontale aléatoire (0–100%)
    // delay     : décale le début de l'animation pour éviter que toutes montent ensemble
    // duration  : durée variable entre 6 et 14 secondes
    p.style.cssText=`left:${Math.random()*100}%;animation-delay:${Math.random()*8}s;animation-duration:${6+Math.random()*8}s`;
    c.appendChild(p);
  }
})();

// ════════════════════════════════════════════════════════
//  SCÈNE THREE.JS – IMPRIMANTE 3D ANIMÉE
//  Construit une imprimante 3D stylisée à partir de formes
//  géométriques simples (BoxGeometry, TorusGeometry…),
//  avec lumières, ombres portées, et animation en boucle.
// ════════════════════════════════════════════════════════
(function(){
  const canvas = document.getElementById('hero-canvas');
  const W = canvas.clientWidth, H = canvas.clientHeight;

  // Renderer WebGL avec antialiasing et fond transparent (alpha:true)
  const renderer = new THREE.WebGLRenderer({canvas,antialias:true,alpha:true});
  renderer.setSize(W,H);
  renderer.setPixelRatio(devicePixelRatio); // Rendu net sur écrans Retina
  renderer.shadowMap.enabled = true;        // Active les ombres portées

  const scene = new THREE.Scene();

  // Caméra perspective : angle 40°, ratio W/H, near 0.1, far 100
  // Positionnée légèrement en hauteur et en retrait pour une vue isométrique
  const camera = new THREE.PerspectiveCamera(40,W/H,0.1,100);
  camera.position.set(3,4,6); camera.lookAt(0,1,0);

  // ── Lumières ────────────────────────────────────────────
  // Lumière ambiante douce (éclaire toutes les surfaces uniformément)
  scene.add(new THREE.AmbientLight(0xffffff,0.4));
  // Lumière directionnelle principale (simule le soleil, génère des ombres)
  const dl = new THREE.DirectionalLight(0xffffff,0.8);
  dl.position.set(5,8,5); dl.castShadow=true; scene.add(dl);
  // Point de lumière bleutée pour accentuer le côté high-tech
  const pl = new THREE.PointLight(0x3498db,1,10);
  pl.position.set(-2,3,2); scene.add(pl);

  // ── Sol ─────────────────────────────────────────────────
  // Plan horizontal sombre qui reçoit les ombres portées
  const floor = new THREE.Mesh(new THREE.PlaneGeometry(8,8),new THREE.MeshLambertMaterial({color:0x1a1a2e}));
  floor.rotation.x=-Math.PI/2; floor.receiveShadow=true; scene.add(floor);

  // Helper : crée une boîte colorée à la position (x,y,z) et l'ajoute à la scène
  function box(w,h,d,color,x,y,z){
    const m=new THREE.Mesh(new THREE.BoxGeometry(w,h,d),new THREE.MeshPhongMaterial({color,shininess:60}));
    m.position.set(x,y,z); m.castShadow=true; scene.add(m); return m;
  }

  // ── Structure de l'imprimante ────────────────────────────
  const base     = box(2,0.2,2,   0x2c3e50, 0,   0.1,  0);      // Socle
  const col1     = box(0.12,3,0.12,0x4a4a4a,-0.85,1.6,-0.85);   // Colonne avant-gauche
  const col2     = box(0.12,3,0.12,0x4a4a4a, 0.85,1.6,-0.85);   // Colonne avant-droite
  const col3     = box(0.12,3,0.12,0x4a4a4a,-0.85,1.6, 0.85);   // Colonne arrière-gauche
  const col4     = box(0.12,3,0.12,0x4a4a4a, 0.85,1.6, 0.85);   // Colonne arrière-droite
  const topFrame = box(2,0.1,2,    0x3d5a80, 0,   3.1,  0);      // Cadre supérieur
  const plate    = box(1.6,0.06,1.6,0x546e7a,0,   0.5,  0);      // Plateau d'impression (animé)
  const carriageY= box(2.0,0.1,0.15,0x607d8b,0,   2.4,  0);      // Chariot axe X
  const head     = box(0.25,0.35,0.25,0x263238,0,  2.2,  0);     // Tête d'impression (animée)
  const nozzle   = box(0.06,0.15,0.06,0xffd700,0,  2.0,  0);     // Buse (or)
  const screen   = box(0.4,0.3,0.05,0x1565c0,0.7, 0.9,-0.96);   // Écran LCD

  // Bobine de filament (torus = forme torique) pivotante
  const spool    = new THREE.Mesh(new THREE.TorusGeometry(.3,.1,8,20),new THREE.MeshPhongMaterial({color:0xe74c3c}));
  spool.position.set(0,2.6,0); spool.rotation.x=Math.PI/2; spool.castShadow=true; scene.add(spool);

  // ── LED verte clignotante (indicateur d'état) ────────────
  const ledGeo = new THREE.SphereGeometry(0.05,8,8);
  const ledMat = new THREE.MeshPhongMaterial({color:0x00ff88,emissive:0x00ff88,emissiveIntensity:0.5});
  const led = new THREE.Mesh(ledGeo,ledMat);
  led.position.set(-0.7,0.9,-0.96); scene.add(led);

  // ── Filament (courbe tube rouge entre la bobine et la buse) ──
  const filPoints=[
    new THREE.Vector3(0,2.8,0),   // Sortie bobine
    new THREE.Vector3(0.05,2.6,0.05),
    new THREE.Vector3(0,2.3,0),
    new THREE.Vector3(0,2.1,0)    // Entrée tête d'impression
  ];
  const filCurve=new THREE.CatmullRomCurve3(filPoints);
  const filGeo=new THREE.TubeGeometry(filCurve,20,0.012,6,false);
  const fil=new THREE.Mesh(filGeo,new THREE.MeshPhongMaterial({color:0xe74c3c}));
  scene.add(fil);

  // ── Boucle d'animation ───────────────────────────────────
  let t=0;
  function animate(){
    requestAnimationFrame(animate);
    t+=0.015;

    // Rotation oscillante de toute la scène (effet de présentation)
    scene.rotation.y=Math.sin(t*.3)*.3+Math.PI*.15;
    // Déplacement latéral de la tête d'impression (simule l'axe X)
    head.position.x=Math.sin(t*1.5)*.7;
    carriageY.position.x=head.position.x;
    // Descente progressive du plateau (simule la montée en couches)
    plate.position.y=0.45+Math.sin(t*.4)*.08;
    // Rotation continue de la bobine
    spool.rotation.z+=0.03;
    // Pulsation de la LED verte (emissiveIntensity oscille entre 0.3 et 1.0)
    ledMat.emissiveIntensity=0.3+Math.abs(Math.sin(t*3))*.7;

    renderer.render(scene,camera);
  }
  animate();

  // ── Redimensionnement responsive ────────────────────────
  // Recalcule la taille du renderer et le ratio de la caméra
  // si la fenêtre change de taille.
  window.addEventListener('resize',()=>{
    const W2=canvas.clientWidth,H2=canvas.clientHeight;
    renderer.setSize(W2,H2); camera.aspect=W2/H2; camera.updateProjectionMatrix();
  });
})();

// ════════════════════════════════════════════════════════
//  PERSONNALISATION DE LA NAV SI CONNECTÉ
//  Si une session active est détectée, remplace le bouton
//  "Connexion" par le prénom de l'utilisateur connecté
//  et redirige vers le dashboard au clic.
// ════════════════════════════════════════════════════════
(function(){
  const s = SGI3D_DB.getSession();
  if(s){
    const nav = document.querySelector('.nav-links');
    const loginLink = nav.querySelector('a[href="login.html"]');
    if(loginLink){
      // Affiche uniquement le prénom (premier mot du nom complet)
      loginLink.innerHTML='👤 '+s.name.split(' ')[0];
      loginLink.href='dashboard.html';
    }
  }
})();
</script>
</body>
</html>
