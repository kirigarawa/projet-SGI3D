<?php
require_once 'config.php';
session_start();

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_avatar'] = $user['avatar'];
                
                // Log de connexion
                $stmt = $pdo->prepare("INSERT INTO connexions (utilisateur_id, ip_address) VALUES (?, ?)");
                $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données.';
        }
    }
    
    elseif ($_POST['action'] === 'register') {
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        if (strlen($password) < 4) {
            $error = 'Le mot de passe doit contenir au moins 4 caractères.';
        } else {
            try {
                $pdo = getDB();
                
                // Vérifier si l'email existe déjà
                $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Cet email est déjà utilisé.';
                } else {
                    // Créer le compte
                    $avatar = strtoupper(substr($nom, 0, 2));
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, avatar) VALUES (?, ?, ?, 'operateur', ?)");
                    $stmt->execute([$nom, $email, $hashed_password, $avatar]);
                    
                    $success = 'Compte créé avec succès ! Vous pouvez vous connecter.';
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de la création du compte.';
            }
        }
    }
}

$page_title = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SGI3D</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; padding-top: 60px; }
        .login-wrap { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .login-box {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 440px;
            color: #2c3e50;
        }
        .login-logo { text-align: center; margin-bottom: 2rem; }
        .login-logo .logo-text {
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: 5px;
            background: var(--btn-grad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .login-logo p { color: #666; font-size: 0.9rem; margin-top: 0.3rem; }
        .error-msg, .success-msg {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.88rem;
            margin-bottom: 1rem;
        }
        .error-msg {
            background: #ffeaea;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .success-msg {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .login-box input {
            background: #f8f9fa;
            border: 1.5px solid #e0e0e0;
            color: #2c3e50;
            border-radius: 12px;
            padding: 0.85rem 1rem;
            width: 100%;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        .login-box input:focus {
            outline: none;
            border-color: var(--accent);
            background: #fff;
        }
        .btn-login {
            width: 100%;
            padding: 1rem;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            background: var(--btn-grad);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }
        .login-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        .login-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
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

        <?php if ($error): ?>
            <div class="error-msg">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="success-msg">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" placeholder="admin@sgi3d.fr" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">Se connecter →</button>
        </form>

        <div class="login-links">
            <p>Pas encore de compte ? <a href="#" onclick="document.getElementById('registerForm').style.display='block'; return false;">Créer un compte</a></p>
            <p style="margin-top:0.5rem">
                <a href="printers.php">🖨️ Voir nos imprimantes</a> · 
                <a href="index.php">🏠 Accueil</a>
            </p>
        </div>
        
        <form method="POST" id="registerForm" style="display:none; margin-top:2rem; border-top:1px solid #e0e0e0; padding-top:2rem;">
            <input type="hidden" name="action" value="register">
            <h3 style="margin-bottom:1rem">Créer un compte</h3>
            
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" name="nom" placeholder="Jean Dupont" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="jean@sgi3d.fr" required>
            </div>
            
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn-login">✅ Créer le compte</button>
        </form>
    </div>
</div>

<footer class="site-footer">
    <a href="sitemap.php">🗺️ Plan du site</a> · 
    <a href="index.php">Accueil</a> · 
    <a href="printers.php">Imprimantes</a>
</footer>

</body>
</html>