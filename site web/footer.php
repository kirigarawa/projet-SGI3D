footer class="site-footer">
        <a href="sitemap.php">🗺️ Plan du site</a> · 
        <a href="index.php">Accueil</a> · 
        <a href="printers.php">Imprimantes</a> · 
        <a href="dashboard.php">Dashboard</a>
        <p style="margin-top:.5rem">© 2025 SGI3D v3.0 – Système de Gestion d'Impression 3D</p>
    </footer>

    <script>
        // Toast notification system
        function showToast(msg, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast ' + type;
            toast.textContent = msg;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>
