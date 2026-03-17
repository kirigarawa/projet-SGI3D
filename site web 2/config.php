<?php
// ============================================================
//  SGI3D - Configuration base de donnees
//  A modifier selon votre environnement (XAMPP, WAMP...)
// ============================================================

define('DB_HOST',     'localhost');
define('DB_NAME',     'sgi3d');
define('DB_USER',     'root');       // Votre utilisateur MySQL
define('DB_PASSWORD', '');           // Votre mot de passe MySQL
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
