<?php
// ── Auth ────────────────────────────────────────────────────
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$ALLOWED_EXT = ['stl', 'gcode', 'gco'];
$MAX_SIZE    = 50 * 1024 * 1024; // 50 Mo
require_once __DIR__ . '/config.php';
$UPLOAD_DIR = UPLOAD_GCODE_DIR;

if (!is_dir($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Aucun fichier reçu']);
    exit;
}

$file = $_FILES['file'];

// Erreurs PHP upload
$UPLOAD_ERRORS = [
    UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux (limite serveur)',
    UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux',
    UPLOAD_ERR_PARTIAL    => 'Upload incomplet — réessayez',
    UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu',
    UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
    UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire sur le disque',
];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => $UPLOAD_ERRORS[$file['error']] ?? 'Erreur inconnue']);
    exit;
}

// Taille
if ($file['size'] > $MAX_SIZE) {
    echo json_encode(['ok' => false, 'error' => 'Fichier trop volumineux (max 50 Mo)']);
    exit;
}

// Extension
$original = basename($file['name']);
$ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
if (!in_array($ext, $ALLOWED_EXT, true)) {
    echo json_encode(['ok' => false, 'error' => 'Format non autorisé. Acceptés : ' . implode(', ', $ALLOWED_EXT)]);
    exit;
}

// Nom de fichier sécurisé + unique
$stem     = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($original, PATHINFO_FILENAME));
$stem     = substr($stem, 0, 80);
$filename = $stem . '_' . date('YmdHis') . '_' . substr(uniqid(), -4) . '.' . $ext;
$dest     = $UPLOAD_DIR . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['ok' => false, 'error' => 'Impossible de sauvegarder le fichier']);
    exit;
}

echo json_encode([
    'ok'       => true,
    'filename' => $filename,
    'original' => $original,
    'size'     => $file['size'],
]);
