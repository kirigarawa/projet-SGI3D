<?php
require_once 'includes/auth.php';
$page_title = 'Accueil';
include 'includes/header.php';
?>

<style>
    body { padding-top: 60px; }
    .hero-section {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 4rem 2rem;
    }
    .hero-content h1 {
        font-size: 4rem;
        font-weight: 900;
        background: linear-gradient(135deg, #fff, rgba(255,255,255,0.5));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 1.5rem;
    }
    .hero-content p {
        font-size: 1.5rem;
        color: rgba(255,255,255,0.8);
        margin-bottom: 2rem;
    }
    .cta-buttons {
        display: flex;
        gap: 1.5rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    .btn-cta {
        padding: 1rem 2.5rem;
        font-size: 1.1rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 700;
        transition: all 0.3s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
    }
    .btn-secondary {
        background: rgba(255,255,255,0.1);
        color: white;
        border: 2px solid rgba(255,255,255,0.3);
    }
    .btn-secondary:hover {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.5);
    }
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 4rem auto;
        padding: 0 2rem;
    }
    .feature-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 2.5rem;
        text-align: center;
        transition: all 0.3s;
    }
    .feature-card:hover {
        transform: translateY(-10px);
        background: rgba(255,255,255,0.1);
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    .feature-icon {
        font-size: 3.5rem;
        margin-bottom: 1.5rem;
    }
    .feature-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #fff;
    }
    .feature-description {
        color: rgba(255,255,255,0.7);
        line-height: 1.6;
    }
</style>

<div class="hero-section">
    <div class="hero-content">
        <h1>🖨️ SGI3D</h1>
        <p>Système de Gestion d'Impression 3D</p>
        <p style="font-size: 1.2rem; max-width: 600px; margin: 0 auto 2.5rem;">
            Supervisez, contrôlez et optimisez votre parc d'imprimantes 3D 
            avec une interface moderne et intuitive
        </p>
        
        <div class="cta-buttons">
            <a href="printers.php" class="btn-cta btn-primary">
                🖨️ Voir les Imprimantes
            </a>
            <?php if (!$user): ?>
                <a href="login.php" class="btn-cta btn-secondary">
                    🔐 Se Connecter
                </a>
            <?php else: ?>
                <a href="dashboard.php" class="btn-cta btn-secondary">
                    📊 Tableau de Bord
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="feature-icon">🖨️</div>
        <h3 class="feature-title">Gestion d'Imprimantes</h3>
        <p class="feature-description">
            Contrôlez vos Ultimaker 2+ et Ender V2 Neo en temps réel 
            avec connexion OctoPrint
        </p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon">📷</div>
        <h3 class="feature-title">Surveillance Caméras</h3>
        <p class="feature-description">
            Monitoring vidéo en direct avec détection de mouvement 
            et enregistrement automatique
        </p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon">🔔</div>
        <h3 class="feature-title">Système d'Alertes</h3>
        <p class="feature-description">
            Notifications instantanées pour température, fumée, 
            fin d'impression et erreurs
        </p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h3 class="feature-title">Dashboard Analytics</h3>
        <p class="feature-description">
            Statistiques complètes, graphiques de performance 
            et historique d'utilisation
        </p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon">🗄️</div>
        <h3 class="feature-title">Base de Données</h3>
        <p class="feature-description">
            Gestion complète avec MySQL, exports USB 
            et sauvegarde automatique
        </p>
    </div>
    
    <div class="feature-card">
        <div class="feature-icon">🔐</div>
        <h3 class="feature-title">Sécurité Renforcée</h3>
        <p class="feature-description">
            Authentification sécurisée, gestion des rôles 
            et logs d'activité complets
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>