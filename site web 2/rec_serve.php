<?php
// rec_serve.php — Sert les images d'enregistrement depuis le système de fichiers
// (fonctionne que le dossier soit local ou sur un NAS/chemin réseau)
session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); exit; }

$camId    = intval($_GET['cam'] ?? 0);
$filename = basename($_GET['f']  ?? '');

// Validation stricte du nom de fichier
if (!$camId || !$filename || !preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.jpg$/i', $filename)) {
    http_response_code(400);
    exit('Paramètres invalides');
}

require_once __DIR__ . '/config.php';
$path = REC_BASE_DIR . '/cam_' . $camId . '/' . $filename;

if (!file_exists($path)) {
    http_response_code(404);
    exit('Fichier introuvable');
}

$size = filesize($path);
$etag = '"' . md5($path . $size) . '"';

// Cache côté navigateur (immutable — le nom de fichier contient la date)
header('Content-Type: image/jpeg');
header('Content-Length: ' . $size);
header('Cache-Control: private, max-age=86400');
header('ETag: ' . $etag);

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
    http_response_code(304);
    exit;
}

readfile($path);
