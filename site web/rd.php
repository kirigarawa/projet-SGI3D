<?php
require_once 'includes/auth.php';
requireAdmin(); // Nécessite un compte admin

$page_title = 'Dashboard';
include 'includes/header.php';

// Récupérer les statistiques
try {
    $pdo = getDB();
    
    // Total utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
    $total_users = $stmt->fetch()['total'];
    
    // Total connexions aujourd'hui
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM connexions WHERE DATE(date_connexion) = CURDATE()");
    $total_connexions_today = $stmt->fetch()['total'];
    
    // Total impressions en cours
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM impressions WHERE statut = 'en cours'");
    $total_impressions_encours = $stmt->fetch()['total'];
    
    // Total alertes non résolues
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alertes WHERE type = 'error'");
    $total_alertes = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des statistiques";
}
?>

<style>
    body { padding-top: 60px; }
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 3rem 2rem;
    }
    .dashboard-title {
        text-align: center;
        margin-bottom: 3rem;
    }
    .dashboard-title h1 {
        font-size: 2.5rem;
        font-weight: 900;
        color: #fff;
        margin-bottom: 0.5rem;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }
    .stat-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        background: rgba(255,255,255,0.1);
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    .stat-value {
        font-size: 3rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }
    .stat-label {
        color: rgba(255,255,255,0.7);
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-title">
        <h1>📊 Dashboard Administrateur</h1>
        <p style="color:rgba(255,255,255,0.7)">Bienvenue, <?php echo htmlspecialchars($user['nom']); ?></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_users; ?></div>
            <div class="stat-label">👥 Utilisateurs</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_connexions_today; ?></div>
            <div class="stat-label">🔐 Connexions Aujourd'hui</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_impressions_encours; ?></div>
            <div class="stat-label">🖨️ Impressions en Cours</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_alertes; ?></div>
            <div class="stat-label">🔔 Alertes Actives</div>
        </div>
    </div>

    <div style="text-align:center; margin-top:3rem;">
        <a href="database.php" class="btn btn-primary" style="padding:1rem 2rem; font-size:1.1rem; border-radius:50px;">
            🗄️ Accéder à la Base de Données
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>