<?php
if (!isset($_SESSION)) session_start();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user = getUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'SGI3D'; ?> - Système de Gestion d'Impression 3D</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="site-nav">
        <a href="index.php" class="nav-logo">SGI3D</a>
        <div class="nav-links">
            <a href="index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                🏠 <span>Accueil</span>
            </a>
            <a href="printers.php" class="<?php echo $current_page === 'printers' ? 'active' : ''; ?>">
                🖨️ <span>Imprimantes</span>
            </a>
            <a href="cameras.php" class="<?php echo $current_page === 'cameras' ? 'active' : ''; ?>">
                📷 <span>Caméras</span>
            </a>
            <a href="alerts.php" class="<?php echo $current_page === 'alerts' ? 'active' : ''; ?>">
                🔔 <span>Alertes</span>
            </a>
            
            <?php if ($user): ?>
                <a href="dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    📊 <span>Dashboard</span>
                </a>
                <a href="logout.php" class="btn-nav">
                    👤 <span><?php echo htmlspecialchars(explode(' ', $user['nom'])[0]); ?></span>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-nav <?php echo $current_page === 'login' ? 'active' : ''; ?>">
                    🔐 <span>Connexion</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <div id="toast-container"></div>