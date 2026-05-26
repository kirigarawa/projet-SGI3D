<?php
// moonraker_api.php — Adaptateur Moonraker/Fluidd avec interface compatible OctoPrint
require_once 'config.php';

class MoonrakerManager {

    private $printers;

    public function __construct() {
        $this->printers = array_values(array_filter(
            is_array(OCTOPRINT_PRINTERS) ? OCTOPRINT_PRINTERS : [],
            function($p) { return ($p['type'] ?? 'octoprint') === 'moonraker'; }
        ));
    }

    // Statut imprimante — retourne format compatible OctoPrint
    public function getPrinterStatus($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];

        $url  = "http://{$printer['ip']}:{$printer['port']}/printer/objects/query?extruder&heater_bed&print_stats";
        $resp = $this->makeRequest($url, $printer['api_key']);
        if (isset($resp['error'])) return $resp;

        $status     = $resp['result']['status'] ?? [];
        $extruder   = $status['extruder']    ?? [];
        $bed        = $status['heater_bed']  ?? [];
        $printStats = $status['print_stats'] ?? [];

        $stateMap = [
            'standby'   => 'Operational',
            'printing'  => 'Printing',
            'paused'    => 'Paused',
            'complete'  => 'Operational',
            'cancelled' => 'Operational',
            'error'     => 'Error',
        ];
        $rawState = $printStats['state'] ?? 'standby';

        return [
            'state' => ['text' => $stateMap[$rawState] ?? 'Operational'],
            'temperature' => [
                'tool0' => [
                    'actual' => round((float)($extruder['temperature'] ?? 0), 1),
                    'target' => round((float)($extruder['target']      ?? 0), 1),
                ],
                'bed' => [
                    'actual' => round((float)($bed['temperature'] ?? 0), 1),
                    'target' => round((float)($bed['target']      ?? 0), 1),
                ],
            ],
        ];
    }

    // Statut du travail — retourne format compatible OctoPrint
    public function getJobStatus($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];

        $url  = "http://{$printer['ip']}:{$printer['port']}/printer/objects/query?print_stats&display_status";
        $resp = $this->makeRequest($url, $printer['api_key']);
        if (isset($resp['error'])) return $resp;

        $status        = $resp['result']['status'] ?? [];
        $printStats    = $status['print_stats']    ?? [];
        $displayStatus = $status['display_status'] ?? [];

        $filename      = $printStats['filename']       ?? null;
        $printDuration = (float)($printStats['print_duration'] ?? 0);
        $progress      = (float)($displayStatus['progress']    ?? 0) * 100;

        $timeLeft = null;
        if ($printDuration > 0 && $progress > 0) {
            $estimated = $printDuration / ($progress / 100);
            $timeLeft  = max(0, (int)($estimated - $printDuration));
        }

        return [
            'job' => [
                'file' => ['name' => $filename, 'origin' => 'local'],
                'estimatedPrintTime' => $printDuration > 0 && $progress > 0
                    ? (int)($printDuration / ($progress / 100))
                    : null,
            ],
            'progress' => [
                'completion'    => round($progress, 1),
                'printTime'     => (int)$printDuration,
                'printTimeLeft' => $timeLeft,
            ],
        ];
    }

    // Lancer une impression
    public function startPrint($printerId, $filename) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $url = "http://{$printer['ip']}:{$printer['port']}/printer/print/start";
        return $this->makeRequest($url, $printer['api_key'], 'POST', json_encode(['filename' => $filename]));
    }

    // Arrêter une impression
    public function stopPrint($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $url = "http://{$printer['ip']}:{$printer['port']}/printer/print/cancel";
        return $this->makeRequest($url, $printer['api_key'], 'POST', '{}');
    }

    // Pause / Reprise
    public function pauseResume($printerId, $action = 'pause') {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $endpoint = $action === 'resume' ? 'resume' : 'pause';
        $url = "http://{$printer['ip']}:{$printer['port']}/printer/print/{$endpoint}";
        return $this->makeRequest($url, $printer['api_key'], 'POST', '{}');
    }

    // Température
    public function getTemperature($printerId) {
        $status = $this->getPrinterStatus($printerId);
        if (isset($status['error'])) return $status;
        return [
            'tool0' => $status['temperature']['tool0'] ?? null,
            'bed'   => $status['temperature']['bed']   ?? null,
        ];
    }

    // Définir température via G-code (M104 buse / M140 lit)
    public function setTemperature($printerId, $heater, $temp) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $gcode = $heater === 'bed' ? "M140 S{$temp}" : "M104 S{$temp}";
        return $this->runGcode($printer, $gcode);
    }

    // Connexion : Moonraker gère la liaison série automatiquement
    public function setConnection($printerId, $action) {
        return ['ok' => true];
    }

    // Déplacement (jog) via G91 relatif
    public function jog($printerId, $axes) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $moves = [];
        foreach ($axes as $axis => $dist) {
            $moves[] = strtoupper($axis) . $dist;
        }
        $gcode = "G91\nG1 " . implode(' ', $moves) . " F3000\nG90";
        return $this->runGcode($printer, $gcode);
    }

    // Homing des axes
    public function home($printerId, $axes) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $axesStr = implode(' ', array_map('strtoupper', $axes));
        $gcode   = count($axes) >= 3 ? 'G28' : "G28 {$axesStr}";
        return $this->runGcode($printer, $gcode);
    }

    // Ventilateur (M106 / M107)
    public function setFan($printerId, $speed) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $s255  = min(255, max(0, (int)round($speed * 2.55)));
        $gcode = $speed > 0 ? "M106 S{$s255}" : 'M107';
        return $this->runGcode($printer, $gcode);
    }

    // Liste des fichiers G-code — retourne format compatible OctoPrint
    public function getFiles($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $url  = "http://{$printer['ip']}:{$printer['port']}/server/files/list?root=gcodes";
        $resp = $this->makeRequest($url, $printer['api_key']);
        if (isset($resp['error'])) return $resp;
        $files = array_map(function($f) {
            return [
                'type'   => 'machinecode',
                'name'   => $f['path'] ?? ($f['filename'] ?? ''),
                'origin' => 'local',
                'size'   => $f['size'] ?? 0,
            ];
        }, $resp['result'] ?? []);
        return ['ok' => true, 'files' => $files];
    }

    // Téléchargement G-code du fichier en cours (aperçu 512 Ko)
    public function getGcode($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $job = $this->getJobStatus($printerId);
        if (isset($job['error'])) return $job;
        $filename = $job['job']['file']['name'] ?? null;
        if (!$filename) return ['error' => 'Aucun fichier chargé'];
        $url = "http://{$printer['ip']}:{$printer['port']}/server/files/gcodes/" . rawurlencode($filename);
        $ch  = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['X-Api-Key: ' . $printer['api_key']],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_RANGE          => '0-524287',
        ]);
        $content  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);
        if ($error || $httpCode < 200 || $httpCode >= 300) {
            return ['error' => $error ?: "HTTP {$httpCode}"];
        }
        return ['ok' => true, 'content' => $content, 'filename' => $filename, 'truncated' => strlen($content) >= 524287];
    }

    // Tous les statuts (pour syncOctoPrint)
    public function getAllStatuses() {
        $results = [];
        foreach ($this->printers as $printer) {
            $status    = $this->getPrinterStatus($printer['id']);
            $job       = $this->getJobStatus($printer['id']);
            $results[] = [
                'id'        => $printer['id'],
                'name'      => $printer['name'],
                'model'     => $printer['model'],
                'ip'        => $printer['ip'],
                'status'    => $status,
                'job'       => $job,
                'online'    => !isset($status['error']),
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }
        return $results;
    }

    private function runGcode(array $printer, string $script) {
        $url = "http://{$printer['ip']}:{$printer['port']}/printer/gcode/script";
        return $this->makeRequest($url, $printer['api_key'], 'POST', json_encode(['script' => $script]));
    }

    private function findPrinter($printerId) {
        foreach ($this->printers as $printer) {
            if ($printer['id'] === $printerId) return $printer;
        }
        return null;
    }

    private function makeRequest($url, $apiKey, $method = 'GET', $data = null) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'X-Api-Key: ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);
        if ($httpCode >= 200 && $httpCode < 300) {
            return $response ? json_decode($response, true) : ['ok' => true];
        }
        return ['error' => $error ?: 'Erreur HTTP ' . $httpCode];
    }
}
