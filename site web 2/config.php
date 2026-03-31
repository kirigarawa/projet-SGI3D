<?php
// ============================================================
//  SGI3D - Configuration base de donnees
//  A modifier selon votre environnement (XAMPP, WAMP...)
// ============================================================

// ── OctoPrint : liste des imprimantes configurées ───────
// Ajoutez une entrée par imprimante avec son IP, port et clé API OctoPrint.
define('OCTOPRINT_PRINTERS', [
    // ['id' => 'printer1', 'name' => 'Ultimaker 2+',     'model' => 'Ultimaker',  'ip' => '192.168.0.100', 'port' => 5000, 'api_key' => 'VOTRE_CLE_API'],
    // ['id' => 'printer2', 'name' => 'Creality Ender V2', 'model' => 'Creality',   'ip' => '192.168.0.101', 'port' => 5000, 'api_key' => 'VOTRE_CLE_API'],
]);

define('DB_HOST',     'localhost');
define('DB_NAME',     'sgi3d');
define('DB_USER',     'sgi3d_user');       // Votre utilisateur MySQL
define('DB_PASSWORD', 'S@2026');           // Votre mot de passe MySQL
define('DB_CHARSET',  'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    }
    return $pdo;
}
