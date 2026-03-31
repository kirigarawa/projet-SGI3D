<?php
// ══════════════════════════════════════════════════════════════
//  stream_proxy.php — Proxy RTSP → MJPEG pour SGI3D
//
//  Principe :
//    1. PHP charge l'URL RTSP depuis la table `cameras` (DB)
//    2. FFmpeg lit le flux RTSP et sort des frames JPEG sur stdout
//    3. PHP retransmet en multipart/x-mixed-replace (MJPEG over HTTP)
//    4. Le navigateur affiche dans une <img src="stream_proxy.php?cam=ID">
//
//  Prérequis :
//    - FFmpeg installé : sudo apt install ffmpeg
//    - config.php dans le même répertoire
// ══════════════════════════════════════════════════════════════

session_start();

// ── Auth ──────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: text/plain');
    exit('Non autorisé');
}

require_once __DIR__ . '/config.php';

// ── Résolution de l'URL depuis la DB ─────────────────────────
$camId   = isset($_GET['cam']) ? (int)$_GET['cam'] : 0;
$rtspUrl = '';

if ($camId > 0) {
    try {
        $db = getDB();
        $st = $db->prepare('SELECT url_flux, statut FROM cameras WHERE id = ?');
        $st->execute([$camId]);
        $cam = $st->fetch();

        if (!$cam) {
            http_response_code(404);
            exit('Caméra introuvable (id=' . $camId . ')');
        }
        if ($cam['statut'] !== 'en_ligne') {
            http_response_code(503);
            exit('Caméra hors ligne');
        }
        if (empty($cam['url_flux'])) {
            http_response_code(404);
            exit('Aucun flux configuré pour cette caméra');
        }
        $rtspUrl = $cam['url_flux'];

    } catch (PDOException $e) {
        http_response_code(500);
        exit('Erreur DB : ' . $e->getMessage());
    }

} elseif (!empty($_GET['url'])) {
    // URL directe — réservée aux admins
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Accès refusé');
    }
    $rtspUrl = trim($_GET['url']);
    if (!preg_match('#^(rtsp|rtsps|http|https)://#i', $rtspUrl)) {
        http_response_code(400);
        exit('URL invalide (rtsp:// ou http:// requis)');
    }

} else {
    http_response_code(400);
    exit('Paramètre requis : ?cam=ID');
}

// ── Si URL HTTP/HTTPS : proxy direct sans FFmpeg ─────────────
if (preg_match('#^https?://#i', $rtspUrl)) {
    if (ob_get_level()) ob_end_clean();
    set_time_limit(0);
    ignore_user_abort(false);

    $ch = curl_init($rtspUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT      => 'SGI3D-Proxy/1.0',
        CURLOPT_HEADERFUNCTION => function($ch, $header) {
            $h = trim($header);
            if (stripos($h, 'Content-Type:') === 0) {
                header($h, true);
            }
            return strlen($header);
        },
        CURLOPT_WRITEFUNCTION  => function($ch, $data) {
            if (connection_aborted()) return -1;
            echo $data;
            if (ob_get_level()) ob_flush();
            flush();
            return strlen($data);
        },
    ]);

    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('X-Accel-Buffering: no');

    curl_exec($ch);
    curl_close($ch);
    exit;
}

// ── Vérification FFmpeg ───────────────────────────────────────
$ffmpegBin = '';
foreach ([
    trim((string)shell_exec('which ffmpeg 2>/dev/null')),
    '/usr/bin/ffmpeg',
    '/usr/local/bin/ffmpeg',
    '/opt/ffmpeg/bin/ffmpeg',
] as $p) {
    if ($p !== '' && file_exists($p) && is_executable($p)) {
        $ffmpegBin = $p;
        break;
    }
}
if ($ffmpegBin === '') {
    http_response_code(503);
    header('Content-Type: text/plain');
    exit("FFmpeg non trouvé. Installez-le : sudo apt install ffmpeg");
}

// ── Commande FFmpeg ───────────────────────────────────────────
$cmd = sprintf(
    '%s -loglevel quiet -rtsp_transport tcp -i %s -vf scale=640:360 -q:v 5 -r 15 -f mjpeg pipe:1',
    escapeshellcmd($ffmpegBin),
    escapeshellarg($rtspUrl)
);

// ── Lancement FFmpeg ──────────────────────────────────────────
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['file', '/dev/null', 'a'],
];
$process = proc_open($cmd, $descriptors, $pipes);

if (!is_resource($process)) {
    http_response_code(503);
    exit('Impossible de démarrer FFmpeg');
}

fclose($pipes[0]);
$stdout = $pipes[1];

// ── Headers MJPEG ─────────────────────────────────────────────
$boundary = 'SGI3Dframe';
header('Content-Type: multipart/x-mixed-replace; boundary=' . $boundary);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Accel-Buffering: no');

if (ob_get_level()) ob_end_clean();
set_time_limit(0);
ignore_user_abort(false);

// ── Boucle de streaming ───────────────────────────────────────
// Lit stdout de FFmpeg, extrait les frames JPEG (SOI→EOI)
// et les envoie au navigateur en multipart.
$buffer = '';
$maxBuf = 512 * 1024;

while (!feof($stdout)) {
    if (connection_aborted()) break;

    $chunk = fread($stdout, 8192);
    if ($chunk === false || $chunk === '') { usleep(5000); continue; }
    $buffer .= $chunk;

    if (strlen($buffer) > $maxBuf) {
        $soi    = strpos($buffer, "\xFF\xD8", 1);
        $buffer = ($soi !== false) ? substr($buffer, $soi) : '';
        continue;
    }

    $start = strpos($buffer, "\xFF\xD8");
    if ($start === false) { $buffer = substr($buffer, -2); continue; }

    $end = strpos($buffer, "\xFF\xD9", $start + 2);
    if ($end === false) { if ($start > 0) $buffer = substr($buffer, $start); continue; }

    $frame = substr($buffer, $start, $end - $start + 2);

    echo '--' . $boundary . "\r\n";
    echo "Content-Type: image/jpeg\r\n";
    echo "Content-Length: " . strlen($frame) . "\r\n\r\n";
    echo $frame . "\r\n";

    if (ob_get_level()) ob_flush();
    flush();

    $buffer = substr($buffer, $end + 2);
}

fclose($stdout);
proc_terminate($process);
proc_close($process);