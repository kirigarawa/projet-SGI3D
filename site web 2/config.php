<?php
// ============================================================
//  SGI3D - Configuration base de donnees
//  A modifier selon votre environnement (XAMPP, WAMP...)
// ============================================================

// ── OctoPrint : liste des imprimantes configurées ───────
// Ajoutez une entrée par imprimante avec son IP, port et clé API OctoPrint.
define('OCTOPRINT_PRINTERS', [
    ['id' => 1, 'name' => 'Ultimaker 2+',          'model' => 'Ultimaker', 'ip' => '192.168.0.32',  'port' => 5000, 'api_key' => 'GN2-MsGMr05YG0vUw-98MLiRZKFkXcYZrkvfeztDh-8'],
    ['id' => 2, 'name' => 'Elegoo Neptune 4 Pro',  'model' => 'Elegoo',    'ip' => '192.168.0.93', 'port' => 80, 'api_key' => '1cbf927f27024488bae6730c4fe4dd5b', 'type' => 'moonraker'],
]);

define('DB_HOST',     'localhost');
define('DB_NAME',     'sgi3d');
define('DB_USER',     'root');       // Votre utilisateur MySQL
define('DB_PASSWORD', 'phpmy_SGI3D');           // Votre mot de passe MySQL
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
