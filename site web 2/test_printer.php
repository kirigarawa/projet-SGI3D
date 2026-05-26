<?php
// Script de diagnostic temporaire — à supprimer après tests
session_start();
if (empty($_SESSION['user_id'])) { die('Non autorisé'); }

$ip     = '192.168.0.130';
$port   = 80;
$apiKey = '1cbf927f27024488bae6730c4fe4dd5b';

$tests = [
    'Racine Fluidd'              => "http://{$ip}:{$port}/",
    'Moonraker /server/info'     => "http://{$ip}:{$port}/server/info",
    'Moonraker /printer/info'    => "http://{$ip}:{$port}/printer/info",
    'Moonraker objects query'    => "http://{$ip}:{$port}/printer/objects/query?print_stats",
];

echo '<pre style="font-family:monospace;padding:20px">';
echo "=== Diagnostic imprimante {$ip}:{$port} ===\n\n";

foreach ($tests as $label => $url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER     => ["X-Api-Key: {$apiKey}"],
    ]);
    $body     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    $status = $error ? "ERREUR: {$error}" : "HTTP {$httpCode}";
    $preview = $body ? substr(strip_tags($body), 0, 120) : '(vide)';
    echo "[{$label}]\n  URL    : {$url}\n  Status : {$status}\n  Réponse: {$preview}\n\n";
}
echo '</pre>';
