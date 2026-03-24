<?php
// ============================================================
//  SGI3D - Synchronisation OctoPrint
//  Interroge l'API OctoPrint de chaque imprimante et
//  génère des alertes automatiques dans la base de données.
// ============================================================
require_once 'config.php';

// ── CONFIGURATION DES IMPRIMANTES OCTOPRINT ─────────────────
// Remplissez pour chaque imprimante :
//   host    : adresse IP ou nom d'hôte (ex: 192.168.1.100)
//   port    : port OctoPrint (défaut 5000)
//   api_key : clé API OctoPrint (Settings > API > API Key)
//   name    : nom affiché dans les alertes
//   id      : id de la table `imprimantes` dans votre base
define('OCTOPRINT_PRINTERS', json_encode([
    [
        'id'      => 1,
        'name'    => 'Ultimaker 2+',
        'host'    => '192.168.0.19',
        'port'    => 5000,
        'api_key' => 'GN2-MsGMr05YG0vUw-98MLiRZKFkXcYZrkvfeztDh-8',  // À remplacer
    ],
    [
        'id'      => 2,
        'name'    => 'Creality Ender V2 Neo',
        'host'    => '192.168.1.101',
        'port'    => 5000,
        'api_key' => '',  // À remplacer
    ],
]));

// ── SEUILS D'ALERTE ──────────────────────────────────────────
define('TEMP_BUSE_MAX',    270);   // °C — alerte si température buse > seuil
define('TEMP_LIT_MAX',     120);   // °C — alerte si température lit > seuil
define('TEMP_BUSE_MIN',     15);   // °C — alerte si buse froide pendant impression
define('FILAMENT_MIN',       5);   // %  — alerte si filament < seuil (si capteur dispo)

// Classe principale de synchronisation
class OctoPrintSync {

    private PDO $db;
    private array $printers;

    public function __construct(PDO $db) {
        $this->db       = $db;
        $this->printers = json_decode(OCTOPRINT_PRINTERS, true);
    }


    // Retourne la liste des imprimantes configurées
    public function getPrinters(): array { return $this->printers; }

    // Trouve une imprimante par son id
    public function getPrinterById(int $id): ?array {
        foreach ($this->printers as $p) {
            if ((int)$p['id'] === $id) return $p;
        }
        return null;
    }

    // ── État complet d'une imprimante ────────────────────────
    public function getFullState(int $printerId): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable', 'online' => false];

        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);

        $state = [
            'printer_id'   => $printer['id'],
            'printer_name' => $printer['name'],
            'host'         => $printer['host'],
            'port'         => $printer['port'],
            'online'       => false,
            'connection'   => null,
            'temperatures' => null,
            'job'          => null,
        ];

        $conn = $this->apiGet("$baseUrl/api/connection", $headers);
        if ($conn === null) {
            $state['error'] = 'OctoPrint inaccessible — vérifiez ' . $printer['host'];
            return $state;
        }

        $state['online']     = true;
        $state['connection'] = $conn['current'] ?? [];

        $printerData = $this->apiGet("$baseUrl/api/printer", $headers);
        if ($printerData) {
            $state['temperatures']  = $printerData['temperature'] ?? [];
            $state['printer_state'] = $printerData['state']       ?? [];
        }

        $jobData = $this->apiGet("$baseUrl/api/job", $headers);
        if ($jobData) {
            $progress = $jobData['progress'] ?? [];
            $job      = $jobData['job']      ?? [];
            $state['job'] = [
                'state'          => $jobData['state']                   ?? 'Unknown',
                'file'           => $job['file']['name']                ?? null,
                'size'           => $job['file']['size']                ?? null,
                'progress'       => round($progress['completion']       ?? 0, 1),
                'time'           => $progress['printTime']              ?? null,
                'timeLeft'       => $progress['printTimeLeft']          ?? null,
                'estimatedTotal' => $job['estimatedPrintTime']          ?? null,
            ];
        }

        return $state;
    }

    // ── Contrôle du travail : pause / resume / cancel / start ─
    public function controlJob(int $printerId, string $command): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['ok' => false, 'error' => 'Imprimante introuvable'];

        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);

        $body = match($command) {
            'pause'  => ['command' => 'pause', 'action' => 'pause'],
            'resume' => ['command' => 'pause', 'action' => 'resume'],
            'cancel' => ['command' => 'cancel'],
            'start'  => ['command' => 'start'],
            default  => null,
        };

        if ($body === null) return ['ok' => false, 'error' => "Commande invalide : $command"];

        $this->apiPost("$baseUrl/api/job", $headers, $body);
        $labels = ['pause' => 'Pause', 'resume' => 'Reprise', 'cancel' => 'Annulation', 'start' => 'Démarrage'];
        $this->createAlertIfNew('info', "{$labels[$command]} impression – {$printer['name']}", "Commande '$command' envoyée depuis SGI3D.", $printer['name'], 5);

        return ['ok' => true, 'command' => $command];
    }

    // ── Température cible buse ou lit ────────────────────────
    public function setTemperature(int $printerId, string $heater, float $temp): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['ok' => false, 'error' => 'Imprimante introuvable'];

        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);

        if ($heater === 'tool0') {
            $this->apiPost("$baseUrl/api/printer/tool", $headers, ['command' => 'target', 'targets' => ['tool0' => (int)$temp]]);
        } elseif ($heater === 'bed') {
            $this->apiPost("$baseUrl/api/printer/bed", $headers, ['command' => 'target', 'target' => (int)$temp]);
        } else {
            return ['ok' => false, 'error' => "Heater inconnu : $heater"];
        }

        return ['ok' => true, 'heater' => $heater, 'target' => $temp];
    }

    // ── Déplacement de la buse (jog) ─────────────────────────
    public function jogPrinthead(int $printerId, array $axes): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['ok' => false, 'error' => 'Imprimante introuvable'];
        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);
        $body = array_merge(['command' => 'jog', 'absolute' => false], $axes);
        $this->apiPost("$baseUrl/api/printer/printhead", $headers, $body);
        return ['ok' => true];
    }

    // ── Mise à zéro des axes (home) ───────────────────────────
    public function homePrinthead(int $printerId, array $axes): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['ok' => false, 'error' => 'Imprimante introuvable'];
        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);
        $this->apiPost("$baseUrl/api/printer/printhead", $headers, ['command' => 'home', 'axes' => $axes]);
        return ['ok' => true];
    }

    // ── Vitesse du ventilateur (0-100 %) ─────────────────────
    public function setFanSpeed(int $printerId, int $speed): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['ok' => false, 'error' => 'Imprimante introuvable'];
        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);
        $gcode = $speed > 0 ? 'M106 S' . (int)round($speed * 2.55) : 'M107';
        $this->apiPost("$baseUrl/api/printer/command", $headers, ['command' => $gcode]);
        return ['ok' => true, 'speed' => $speed];
    }

    // ── Liste les fichiers G-code d'OctoPrint ────────────────
    public function getFiles(int $printerId): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['ok' => false, 'error' => 'Imprimante introuvable'];
        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);
        $data = $this->apiGet("$baseUrl/api/files?recursive=true", $headers);
        if ($data === null) return ['ok' => false, 'error' => 'OctoPrint inaccessible'];
        return ['ok' => true, 'files' => $data['files'] ?? []];
    }

    // ── Lance l'impression d'un fichier ──────────────────────
    public function startPrint(int $printerId, string $filename, string $location = 'local'): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['ok' => false, 'error' => 'Imprimante introuvable'];
        if (!$filename) return ['ok' => false, 'error' => 'Nom de fichier manquant'];
        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);
        $url = "$baseUrl/api/files/{$location}/" . rawurlencode($filename);
        $this->apiPost($url, $headers, ['command' => 'select', 'print' => true]);
        return ['ok' => true];
    }

    // ── Télécharge le G-code du fichier en cours (500 Ko max) ─
    public function getGcodeFile(int $printerId, string $filename = '', string $location = 'local'): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['ok' => false, 'error' => 'Imprimante introuvable'];
        if (!function_exists('curl_init')) return ['ok' => false, 'error' => 'cURL requis'];

        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);

        if (!$filename) {
            $job      = $this->apiGet("$baseUrl/api/job", $headers);
            $filename = $job['job']['file']['name']   ?? '';
            $location = $job['job']['file']['origin'] ?? 'local';
            if (!$filename) return ['ok' => false, 'error' => 'Aucun fichier en cours'];
        }

        $url      = "$baseUrl/downloads/files/{$location}/" . rawurlencode($filename);
        $maxBytes = 512000; // 500 Ko
        $received = '';
        $truncated = false;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT    => 20,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($_, $chunk) use (&$received, &$truncated, $maxBytes) {
            $free = $maxBytes - strlen($received);
            if ($free <= 0) { $truncated = true; return strlen($chunk); }
            $received .= substr($chunk, 0, $free);
            if (strlen($chunk) > $free) $truncated = true;
            return strlen($chunk);
        });
        curl_exec($ch);

        if (!$received) return ['ok' => false, 'error' => 'Téléchargement échoué : ' . curl_error($ch)];

        return ['ok' => true, 'filename' => $filename, 'content' => $received, 'truncated' => $truncated];
    }

    // ── Connexion / déconnexion imprimante ───────────────────
    public function setConnection(int $printerId, string $action): array {
        $printer = $this->getPrinterById($printerId);
        if (!$printer) return ['ok' => false, 'error' => 'Imprimante introuvable'];
        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);
        $this->apiPost("$baseUrl/api/connection", $headers, ['command' => $action]);
        return ['ok' => true, 'action' => $action];
    }

    // ── Construit le header X-Api-Key (CORRECTION BUG) ───────
    private function buildHeaders(string $apiKey): array {
        return [
            "X-Api-Key: $apiKey",
            "Content-Type: application/json",
        ];
    }

    // ── HTTP POST ─────────────────────────────────────────────
    private function apiPost(string $url, array $headers, array $body, int $timeout = 8): ?array {
        $json = json_encode($body);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_HTTPHEADER     => $headers,
            ]);
            $raw = curl_exec($ch);
            if ($raw === false || curl_error($ch)) return ['sent' => true];
        } else {
            $ctx = stream_context_create(['http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $json,
                'timeout'       => $timeout,
                'ignore_errors' => true,
            ]]);
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw === false || trim($raw) === '') return ['sent' => true];
        }
        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : ['sent' => true];
    }

    // ── Point d'entrée : synchronise toutes les imprimantes ──
    public function syncAll(): array {
        $results = [];
        foreach ($this->printers as $printer) {
            $results[$printer['name']] = $this->syncPrinter($printer);
        }
        return $results;
    }

    // ── Synchronise une imprimante ────────────────────────────
    private function syncPrinter(array $printer): array {
        $log     = [];
        $baseUrl = "http://{$printer['host']}:{$printer['port']}";
        $headers = $this->buildHeaders($printer['api_key']);

        // 1. Vérifier la connexion à OctoPrint
        $connection = $this->apiGet("$baseUrl/api/connection", $headers);
        if ($connection === null) {
            $this->createAlertIfNew(
                'erreur',
                "OctoPrint inaccessible – {$printer['name']}",
                "Impossible de joindre OctoPrint sur {$printer['host']}:{$printer['port']}. Vérifiez que le service est démarré.",
                $printer['name']
            );
            $log[] = "ERREUR: OctoPrint inaccessible";
            return $log;
        }

        $connState = $connection['current']['state'] ?? 'Unknown';

        // 2. Imprimante déconnectée d'OctoPrint
        if (!in_array($connState, ['Operational', 'Printing', 'Paused', 'Pausing', 'Cancelling', 'Finishing'])) {
            $this->createAlertIfNew(
                'avertissement',
                "Imprimante déconnectée – {$printer['name']}",
                "L'imprimante {$printer['name']} n'est pas connectée à OctoPrint. État : $connState",
                $printer['name']
            );
            $log[] = "AVERTISSEMENT: Imprimante déconnectée ($connState)";
            return $log;
        }

        $log[] = "Connexion OK ($connState)";

        // 3. Données de l'imprimante (températures, état)
        $printerData = $this->apiGet("$baseUrl/api/printer", $headers);
        if ($printerData) {
            $log = array_merge($log, $this->checkTemperatures($printerData, $printer, $connState));
        }

        // 4. Travail d'impression en cours
        $jobData = $this->apiGet("$baseUrl/api/job", $headers);
        if ($jobData) {
            $log = array_merge($log, $this->checkPrintJob($jobData, $printer));
        }

        return $log;
    }

    // ── Vérification des températures ─────────────────────────
    private function checkTemperatures(array $data, array $printer, string $state): array {
        $log   = [];
        $temps = $data['temperature'] ?? [];

        // Buse (tool0)
        if (isset($temps['tool0'])) {
            $actual  = round($temps['tool0']['actual'] ?? 0, 1);
            $target  = round($temps['tool0']['target'] ?? 0, 1);

            // Surchauffe
            if ($actual > TEMP_BUSE_MAX) {
                $this->createAlertIfNew(
                    'erreur',
                    "Surchauffe buse – {$printer['name']}",
                    "La buse de {$printer['name']} atteint {$actual}°C (seuil : " . TEMP_BUSE_MAX . "°C, cible : {$target}°C). Arrêt recommandé.",
                    $printer['name']
                );
                $log[] = "ERREUR: Surchauffe buse {$actual}°C";
            }

            // Buse froide pendant impression
            if (in_array($state, ['Printing']) && $target > 0 && $actual < TEMP_BUSE_MIN) {
                $this->createAlertIfNew(
                    'erreur',
                    "Buse froide pendant impression – {$printer['name']}",
                    "La buse de {$printer['name']} est à {$actual}°C pendant une impression (cible {$target}°C). Risque de bourrage.",
                    $printer['name']
                );
                $log[] = "ERREUR: Buse froide {$actual}°C pendant impression";
            }

            $log[] = "Buse: {$actual}°C / {$target}°C";
        }

        // Lit chauffant (bed)
        if (isset($temps['bed'])) {
            $actual = round($temps['bed']['actual'] ?? 0, 1);
            $target = round($temps['bed']['target'] ?? 0, 1);

            if ($actual > TEMP_LIT_MAX) {
                $this->createAlertIfNew(
                    'avertissement',
                    "Température lit élevée – {$printer['name']}",
                    "Le lit chauffant de {$printer['name']} atteint {$actual}°C (seuil : " . TEMP_LIT_MAX . "°C, cible : {$target}°C).",
                    $printer['name']
                );
                $log[] = "AVERTISSEMENT: Lit {$actual}°C";
            }

            $log[] = "Lit: {$actual}°C / {$target}°C";
        }

        return $log;
    }

    // ── Vérification du travail d'impression ──────────────────
    private function checkPrintJob(array $data, array $printer): array {
        $log     = [];
        $state   = $data['state'] ?? 'Unknown';
        $job     = $data['job']   ?? [];
        $prog    = $data['progress'] ?? [];
        $file    = $job['file']['name'] ?? 'fichier inconnu';
        $pct     = round($prog['completion'] ?? 0, 1);
        $timeLeft= $prog['printTimeLeft'] ?? null;

        // Impression annulée inopinément
        if ($state === 'Cancelled') {
            $this->createAlertIfNew(
                'avertissement',
                "Impression annulée – {$printer['name']}",
                "L'impression de « $file » sur {$printer['name']} a été annulée à {$pct}%.",
                $printer['name']
            );
            $log[] = "AVERTISSEMENT: Impression annulée ($file)";
        }

        // Erreur d'impression
        if ($state === 'Error' || stripos($state, 'error') !== false) {
            $this->createAlertIfNew(
                'erreur',
                "Erreur impression – {$printer['name']}",
                "Une erreur est survenue pendant l'impression de « $file » sur {$printer['name']}. État OctoPrint : $state",
                $printer['name']
            );
            $log[] = "ERREUR: Impression ($file) — $state";
        }

        // Impression terminée avec succès
        if ($state === 'Operational' && $pct >= 100 && !empty($file)) {
            $this->createAlertIfNew(
                'info',
                "Impression terminée – {$printer['name']}",
                "« $file » terminé avec succès sur {$printer['name']} (100%).",
                $printer['name'],
                60  // Ne duplique pas si une alerte similaire existe depuis < 60 min
            );
            $log[] = "INFO: Impression terminée ($file)";
        }

        // En cours : log informatif
        if ($state === 'Printing') {
            $eta = $timeLeft ? gmdate('H\h i\m', $timeLeft) : '—';
            $log[] = "Impression: $file – {$pct}% (ETA: $eta)";
            $this->syncPrintJobInDB($printer, $file, $pct, $state);
        }

        return $log;
    }

    // ── Synchronise le travail en cours dans la table travaux_impression ──
    private function syncPrintJobInDB(array $printer, string $file, float $pct, string $state): void {
        // Vérifier si un travail en_cours existe déjà pour cette imprimante
        $st = $this->db->prepare('SELECT id FROM travaux_impression WHERE imprimante_id = ? AND statut = "en_cours" ORDER BY demarre_le DESC LIMIT 1');
        $st->execute([$printer['id']]);
        $existing = $st->fetch();

        if (!$existing) {
            // Créer un nouveau travail
            $st2 = $this->db->prepare('INSERT INTO travaux_impression
                (imprimante_id, nom_utilisateur, nom_fichier, statut, demarre_le)
                VALUES (?, "OctoPrint", ?, "en_cours", NOW())');
            $st2->execute([$printer['id'], $file]);
        }
    }

    // ── Crée une alerte si aucune alerte identique n'existe déjà ──
    // $dedupMinutes : durée en minutes pendant laquelle on ne recrée pas la même alerte
    private function createAlertIfNew(string $type, string $titre, string $message, string $source, int $dedupMinutes = 30): bool {
        // Vérifier si une alerte similaire (même titre, non résolue) existe déjà récemment
        $mins = (int)$dedupMinutes;
        $st = $this->db->prepare("
            SELECT id FROM alertes
            WHERE titre = ?
              AND source = ?
              AND resolue = 0
              AND cree_le > NOW() - INTERVAL {$mins} MINUTE
            LIMIT 1
        ");
        $st->execute([$titre, $source]);

        if ($st->fetch()) {
            return false; // Alerte déjà présente, on ne duplique pas
        }

        // Créer la nouvelle alerte
        $st2 = $this->db->prepare('INSERT INTO alertes (type, titre, message, source, resolue, cree_le) VALUES (?, ?, ?, ?, 0, NOW())');
        $st2->execute([$type, $titre, $message, $source]);
        return true;
    }

    // ── Appel HTTP vers l'API OctoPrint ──────────────────────
    private function apiGet(string $url, array $headers, int $timeout = 5): ?array {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            if ($response === false || $err) return null;
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method'        => 'GET',
                    'header'        => implode("\r\n", $headers),
                    'timeout'       => $timeout,
                    'ignore_errors' => true,
                ]
            ]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response === false) return null;
        }

        $data = json_decode($response, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
    }
}

// ── POINT D'ENTRÉE (appelé depuis api.php ou en CLI) ─────────
// Retourne les logs de synchronisation au format JSON
if (isset($_GET['run']) || php_sapi_name() === 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $sync    = new OctoPrintSync(getDB());
        $results = $sync->syncAll();
        echo json_encode(['ok' => true, 'results' => $results, 'synced_at' => date('c')], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
