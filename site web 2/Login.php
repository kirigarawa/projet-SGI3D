<?php
// ── Auth : si déjà connecté, on redirige directement ─────────
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/config.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ══════════════════════════════════════════════════════════
    //  CONNEXION
    //  Requête PDO directe — plus fiable que file_get_contents
    //  vers api.php (pas de dépendance à l'URL du serveur).
    // ══════════════════════════════════════════════════════════
    if ($_POST['action'] === 'login') {
        $email    = trim($_POST['email']    ?? '');
        $password =      $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Veuillez remplir tous les champs.';
        } else {
            try {
                $db = getDB();

                // Récupérer l'utilisateur actif
                $st = $db->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1');
                $st->execute([$email]);
                $user = $st->fetch();

                // Comparaison mot de passe (plain-text, cohérent avec api.php)
                if ($user && $user['mot_de_passe'] === $password) {
                    // Journaliser la connexion réussie
                    $db->prepare(
                        'INSERT INTO journaux_connexion
                         (utilisateur_id, email, nom_utilisateur, succes, ip, navigateur, horodatage)
                         VALUES (?, ?, ?, "oui", ?, ?, NOW())'
                    )->execute([
                        $user['id'],
                        $user['email'],
                        $user['nom'],
                        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
                    ]);

                    // Hydrater la session
                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['nom']    ?? 'Utilisateur';
                    $_SESSION['role']     = $user['role']   ?? 'operateur';
                    $_SESSION['email']    = $user['email']  ?? '';
                    $_SESSION['avatar']   = $user['avatar'] ?? strtoupper(substr($user['nom'], 0, 2));

                    header('Location: dashboard.php');
                    exit;

                } else {
                    // Journaliser l'échec
                    $db->prepare(
                        'INSERT INTO journaux_connexion
                         (utilisateur_id, email, nom_utilisateur, succes, ip, navigateur, horodatage)
                         VALUES (NULL, ?, "Inconnu", "non", ?, ?, NOW())'
                    )->execute([
                        $email,
                        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
                    ]);
                    $error = 'Email ou mot de passe incorrect.';
                }

            } catch (PDOException $e) {
                $error = 'Erreur base de données. Vérifiez la configuration.';
                // En développement, décommentez pour voir le détail :
                // $error .= ' (' . $e->getMessage() . ')';
            }
        }
    }

    // ══════════════════════════════════════════════════════════
    //  INSCRIPTION
    //  PDO direct — plus de file_get_contents vers api.php.
    // ══════════════════════════════════════════════════════════
    if ($_POST['action'] === 'register') {
        $name     = trim($_POST['reg_name']  ?? '');
        $email    = trim($_POST['reg_email'] ?? '');
        $password =      $_POST['reg_pass']  ?? '';
        $regError = '';

        if ($name === '' || $email === '' || $password === '') {
            $regError = 'Remplissez tous les champs.';
        } elseif (strlen($password) < 4) {
            $regError = 'Mot de passe trop court (min. 4 caractères).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $regError = 'Adresse email invalide.';
        } else {
            try {
                $db = getDB();

                // Vérifier l'unicité de l'email
                $stCheck = $db->prepare('SELECT id FROM utilisateurs WHERE email = ?');
                $stCheck->execute([$email]);
                if ($stCheck->fetch()) {
                    $regError = 'Cet email est déjà utilisé.';
                } else {
                    // Générer l'avatar à partir des initiales
                    $words  = array_filter(explode(' ', $name));
                    $avatar = strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1), $words)));
                    $avatar = mb_substr($avatar, 0, 2);

                    $db->prepare(
                        'INSERT INTO utilisateurs (nom, email, mot_de_passe, role, avatar, actif, cree_le)
                         VALUES (?, ?, ?, "operateur", ?, 1, NOW())'
                    )->execute([$name, $email, $password, $avatar]);

                    $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                }

            } catch (PDOException $e) {
                $regError = 'Erreur base de données lors de la création du compte.';
            }
        }

        if ($regError !== '') {
            $error = $regError;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SGI3D – Connexion</title>
<link rel="stylesheet" href="style.css">
<style>
  body{display:flex;flex-direction:column;min-height:100vh;padding-top:60px}
  .login-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem}
  .login-box{
    background:var(--card-bg);border-radius:var(--radius);
    box-shadow:var(--shadow);padding:3rem 2.5rem;width:100%;max-width:440px;color:#2c3e50;
  }
  .login-logo{text-align:center;margin-bottom:2rem}
  .login-logo .logo-text{
    font-size:3rem;font-weight:900;letter-spacing:5px;
    background:var(--btn-grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;
    background-clip:text;
  }
  .login-logo p{color:#666;font-size:.9rem;margin-top:.3rem}
  .login-box .form-group label{color:#444}
  .login-box input{
    background:#f8f9fa;border:1.5px solid #e0e0e0;color:#2c3e50;
    border-radius:12px;padding:.85rem 1rem;width:100%;
    font-family:var(--font);font-size:.95rem;transition:border-color .2s;
    box-sizing:border-box;
  }
  .login-box input:focus{outline:none;border-color:var(--accent);background:#fff}
  .btn-login{
    width:100%;padding:1rem;border-radius:25px;border:none;cursor:pointer;
    background:var(--btn-grad);color:#fff;font-size:1rem;font-weight:700;
    font-family:var(--font);transition:all .3s;letter-spacing:.5px;margin-top:.5rem;
  }
  .btn-login:hover{transform:translateY(-2px);box-shadow:0 10px 25px rgba(0,0,0,.25)}
  .btn-login:disabled{opacity:.6;cursor:not-allowed;transform:none}
  .divider{text-align:center;margin:1.5rem 0;color:#aaa;font-size:.85rem;position:relative}
  .divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:#e0e0e0}
  .divider::before{left:0}.divider::after{right:0}
  .login-links{text-align:center;margin-top:1.5rem;font-size:.85rem;color:#666}
  .login-links a{color:var(--accent);text-decoration:none;font-weight:600}
  .alert-box{padding:.75rem 1rem;border-radius:10px;font-size:.88rem;margin-bottom:1rem;border:1px solid}
  .alert-error  {background:#ffeaea;border-color:#f5c6cb;color:#721c24}
  .alert-success{background:#d4edda;border-color:#c3e6cb;color:#155724}
  footer.site-footer{text-align:center;padding:1.5rem;color:rgba(255,255,255,.4);font-size:.8rem}
  footer.site-footer a{color:rgba(255,255,255,.6);text-decoration:none}
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
    <a href="login.php" class="active btn-nav">🔐 <span>Connexion</span></a>
  </div>
</nav>

<div class="login-wrap">
  <div class="login-box">

    <div class="login-logo">
      <div class="logo-text">SGI3D</div>
      <p>Système de Gestion d'Impression 3D</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="alert-box alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <div class="alert-box alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Formulaire de connexion (POST natif PHP) -->
    <form method="POST" action="login.php" id="loginForm">
      <input type="hidden" name="action" value="login">

      <div class="form-group">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email"
               placeholder="admin@sgi3d.fr" required autocomplete="username"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn-login" id="btnLogin">Se connecter →</button>
    </form>

    <div class="divider">ou</div>

    <div class="login-links">
      <p>Pas encore de compte ? <a href="#" onclick="showRegister();return false">Créer un compte</a></p>
      <p style="margin-top:.5rem">
        <a href="printers.php">🖨️ Voir nos imprimantes</a> · <a href="index.php">🏠 Accueil</a>
      </p>
    </div>
  </div>
</div>

<!-- Modal Inscription -->
<div class="modal-overlay" id="registerModal">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <h2>✏️ Créer un compte</h2>

    <form method="POST" action="login.php" id="registerForm">
      <input type="hidden" name="action" value="register">
      <div class="form-group">
        <label>Nom complet</label>
        <input type="text" name="reg_name" id="regName" class="form-control"
               placeholder="Jean Dupont" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="reg_email" id="regEmail" class="form-control"
               placeholder="jean@sgi3d.fr" required>
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="reg_pass" id="regPass" class="form-control"
               placeholder="••••••••" required minlength="4">
      </div>
      <div style="display:flex;gap:1rem;margin-top:1.5rem">
        <button type="submit" class="btn btn-primary" id="btnRegister">✅ Créer</button>
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Annuler</button>
      </div>
    </form>

    <p id="regMsg" style="margin-top:1rem;font-size:.85rem"></p>
  </div>
</div>

<footer class="site-footer">
  <a href="sitemap.php">🗺️ Plan du site</a> · <a href="index.php">Accueil</a> · <a href="printers.php">Imprimantes</a>
</footer>

<div id="toast-container"></div>

<script>
function showRegister() { document.getElementById('registerModal').classList.add('open'); }
function closeModal()   { document.getElementById('registerModal').classList.remove('open'); }

// Spinner sur soumission connexion
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('btnLogin');
  btn.textContent = '⏳ Connexion…';
  btn.disabled = true;
});

// Spinner sur soumission inscription
document.getElementById('registerForm').addEventListener('submit', function() {
  const btn = document.getElementById('btnRegister');
  btn.textContent = '⏳ Vérification…';
  btn.disabled = true;
});

<?php if ($error !== '' && !empty($_POST['action']) && $_POST['action'] === 'register'): ?>
// Erreur inscription → réouvre la modal automatiquement
window.addEventListener('DOMContentLoaded', showRegister);
<?php endif; ?>

<?php if ($success !== ''): ?>
// Succès inscription → toast + modal fermée
window.addEventListener('DOMContentLoaded', function() {
  showToast('<?= addslashes($success) ?>', 'success');
});
<?php endif; ?>

function showToast(msg, type = 'info') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
</script>
</body>
</html>