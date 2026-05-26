<?php
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html><html><head><meta charset="UTF-8">
<title>Diagnostic NAS</title>
<style>body{font-family:monospace;background:#111;color:#eee;padding:2rem}.ok{color:#2ecc71}.err{color:#e74c3c}.warn{color:#f39c12}pre{background:#1a1a1a;padding:1rem;border-radius:6px}</style>
</head><body>
<h1>🔧 Diagnostic depuis le NAS</h1>

<h2>🖥️ Environnement PHP</h2>
<p>PHP version : <strong><?= PHP_VERSION ?></strong></p>
<p>cURL disponible : <?= function_exists('curl_init') ? "<span class='ok'>✅ Oui</span>" : "<span class='err'>❌ Non</span>" ?></p>
<p>shell_exec disponible : <?= function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions')))) ? "<span class='ok'>✅ Oui</span>" : "<span class='warn'>⚠️ Désactivé (normal sur Synology)</span>" ?></p>
<p>IP du serveur (NAS) : <strong><?= $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()) ?></strong></p>

<h2>🖨️ OctoPrint — Accès depuis le NAS</h2>
<?php foreach (OCTOPRINT_PRINTERS as $p): ?>
<h3><?= htmlspecialchars($p['name']) ?> — <?= $p['ip'] ?>:<?= $p['port'] ?></h3>
<?php
    $sock = @fsockopen($p['ip'], $p['port'], $errno, $errstr, 3);
    if ($sock) {
        echo "<p class='ok'>✅ Port TCP {$p['port']} accessible depuis le NAS</p>";
        fclose($sock);
        $ch = curl_init("http://{$p['ip']}:{$p['port']}/api/version");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['X-Api-Key: '.$p['api_key']], CURLOPT_TIMEOUT=>5]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err)         echo "<p class='err'>❌ cURL : $err</p>";
        elseif ($code==200) { $j=json_decode($resp,true); echo "<p class='ok'>✅ OctoPrint v".htmlspecialchars($j['server']??'?')."</p>"; }
        elseif ($code==403) echo "<p class='err'>❌ HTTP 403 — Clé API incorrecte</p>";
        else                echo "<p class='err'>❌ HTTP $code</p><pre>".htmlspecialchars($resp)."</pre>";
    } else {
        echo "<p class='err'>❌ Inaccessible depuis le NAS — $errstr (code $errno)</p>";
        echo "<p class='warn'>⚠️ Vérifiez le pare-feu Synology ou le routage inter-sous-réseau</p>";
    }
?>
<?php endforeach; ?>

<h2>📷 Caméras — URLs en base</h2>
<?php
try {
    $db = getDB();
    $cams = $db->query('SELECT id, nom, url_flux, statut FROM cameras')->fetchAll();
    if (!$cams) { echo "<p class='warn'>⚠️ Aucune caméra en base de données</p>"; }
    foreach ($cams as $c):
?>
<p><strong><?= htmlspecialchars($c['nom']) ?></strong> — Statut : <?= $c['statut'] ?><br>
URL flux : <code><?= htmlspecialchars($c['url_flux'] ?: '(vide)') ?></code><br>
<?php if ($c['url_flux']): ?>
<?php
        $ch = curl_init($c['url_flux']);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>4, CURLOPT_NOBODY=>true, CURLOPT_FOLLOWLOCATION=>true]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err)        echo "<span class='err'>❌ cURL depuis NAS : $err</span>";
        elseif ($code>0) echo "<span class='ok'>✅ URL accessible depuis le NAS (HTTP $code)</span>";
        else             echo "<span class='warn'>⚠️ Pas de réponse</span>";
?>
<?php else: echo "<span class='warn'>⚠️ Pas d'URL configurée</span>"; endif; ?>
</p>
<?php endforeach; } catch(Exception $e) { echo "<p class='err'>❌ BDD : ".htmlspecialchars($e->getMessage())."</p>"; } ?>

<hr style="border-color:#333;margin-top:2rem">
<p style="color:#555;font-size:.8rem">⚠️ Supprimer ce fichier après diagnostic : <code>octo_test.php</code></p>
</body></html>
