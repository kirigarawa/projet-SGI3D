<?php
// octoprint_api.php - API complète pour interagir avec OctoPrint
require_once 'config.php';

if (basename($_SERVER['SCRIPT_FILENAME']) === 'octoprint_api.php') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
}

class OctoPrintManager {
    
    private $printers;
    
    public function __construct() {
        $this->printers = array_values(array_filter(
            is_array(OCTOPRINT_PRINTERS) ? OCTOPRINT_PRINTERS : [],
            function($p) { return ($p['type'] ?? 'octoprint') !== 'moonraker'; }
        ));
    }
    
    // Récupérer le statut d'une imprimante
    public function getPrinterStatus($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) {
            return ['error' => 'Imprimante introuvable'];
        }
        
        $url = "http://{$printer['ip']}:{$printer['port']}/api/printer";
        return $this->makeRequest($url, $printer['api_key']);
    }
    
    // Récupérer l'état d'impression
    public function getJobStatus($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) {
            return ['error' => 'Imprimante introuvable'];
        }
        
        $url = "http://{$printer['ip']}:{$printer['port']}/api/job";
        return $this->makeRequest($url, $printer['api_key']);
    }
    
    // Lancer une impression
    public function startPrint($printerId, $filename) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) {
            return ['error' => 'Imprimante introuvable'];
        }
        
        $url = "http://{$printer['ip']}:{$printer['port']}/api/job";
        $data = json_encode(['command' => 'start']);
        
        return $this->makeRequest($url, $printer['api_key'], 'POST', $data);
    }
    
    // Arrêter une impression
    public function stopPrint($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) {
            return ['error' => 'Imprimante introuvable'];
        }

        $url = "http://{$printer['ip']}:{$printer['port']}/api/job";
        $data = json_encode(['command' => 'cancel']);

        return $this->makeRequest($url, $printer['api_key'], 'POST', $data);
    }

    // Pause / reprise d'une impression
    public function pauseResume($printerId, $action = 'pause') {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $url  = "http://{$printer['ip']}:{$printer['port']}/api/job";
        $body = json_encode(['command' => 'pause', 'action' => $action]);
        return $this->makeRequest($url, $printer['api_key'], 'POST', $body);
    }
    
    // Obtenir la température
    public function getTemperature($printerId) {
        $status = $this->getPrinterStatus($printerId);
        
        if (isset($status['temperature'])) {
            return [
                'tool0' => $status['temperature']['tool0'] ?? null,
                'bed' => $status['temperature']['bed'] ?? null
            ];
        }
        
        return ['error' => 'Impossible de récupérer la température'];
    }
    
    // Obtenir tous les statuts
    public function getAllStatuses() {
        $results = [];
        
        foreach ($this->printers as $printer) {
            $status = $this->getPrinterStatus($printer['id']);
            $job = $this->getJobStatus($printer['id']);
            
            $results[] = [
                'id' => $printer['id'],
                'name' => $printer['name'],
                'model' => $printer['model'],
                'ip' => $printer['ip'],
                'status' => $status,
                'job' => $job,
                'online' => !isset($status['error']),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return $results;
    }
    
    // Régler la température (tool0, bed, etc.)
    public function setTemperature($printerId, $heater, $temp) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        if ($heater === 'bed') {
            $url  = "http://{$printer['ip']}:{$printer['port']}/api/printer/bed";
            $body = json_encode(['command' => 'target', 'target' => (float)$temp]);
        } else {
            $url  = "http://{$printer['ip']}:{$printer['port']}/api/printer/tool";
            $body = json_encode(['command' => 'target', 'targets' => [$heater => (float)$temp]]);
        }
        return $this->makeRequest($url, $printer['api_key'], 'POST', $body);
    }

    // Connexion / déconnexion de l'imprimante
    public function setConnection($printerId, $action) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $url  = "http://{$printer['ip']}:{$printer['port']}/api/connection";
        $body = json_encode(['command' => $action]);
        return $this->makeRequest($url, $printer['api_key'], 'POST', $body);
    }

    // Déplacement de la tête (jog)
    public function jog($printerId, $axes) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $url  = "http://{$printer['ip']}:{$printer['port']}/api/printer/printhead";
        $body = json_encode(array_merge(['command' => 'jog', 'absolute' => false, 'speed' => 3000], $axes));
        return $this->makeRequest($url, $printer['api_key'], 'POST', $body);
    }

    // Homing des axes
    public function home($printerId, $axes) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $url  = "http://{$printer['ip']}:{$printer['port']}/api/printer/printhead";
        $body = json_encode(['command' => 'home', 'axes' => array_values($axes)]);
        return $this->makeRequest($url, $printer['api_key'], 'POST', $body);
    }

    // Contrôle du ventilateur via G-code (M106/M107)
    public function setFan($printerId, $speed) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $url   = "http://{$printer['ip']}:{$printer['port']}/api/printer/command";
        $s255  = min(255, max(0, (int)round($speed * 2.55)));
        $gcode = $speed > 0 ? "M106 S{$s255}" : "M107";
        $body  = json_encode(['command' => $gcode]);
        return $this->makeRequest($url, $printer['api_key'], 'POST', $body);
    }

    // Liste des fichiers G-code
    public function getFiles($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $url  = "http://{$printer['ip']}:{$printer['port']}/api/files?recursive=true";
        $resp = $this->makeRequest($url, $printer['api_key']);
        if (isset($resp['error'])) return $resp;
        $files = [];
        $this->flattenFiles($resp['files'] ?? [], $files);
        return ['ok' => true, 'files' => $files];
    }

    private function flattenFiles($items, &$result, $prefix = '') {
        foreach ($items as $item) {
            if (($item['type'] ?? '') === 'folder') {
                $this->flattenFiles($item['children'] ?? [], $result, $prefix . $item['name'] . '/');
            } else {
                $item['name'] = $prefix . $item['name'];
                $result[] = $item;
            }
        }
    }

    // Téléchargement du G-code du fichier chargé (aperçu 512 Ko)
    public function getGcode($printerId) {
        $printer = $this->findPrinter($printerId);
        if (!$printer) return ['error' => 'Imprimante introuvable'];
        $job = $this->getJobStatus($printerId);
        if (isset($job['error'])) return $job;
        $filename = $job['job']['file']['name'] ?? null;
        $origin   = $job['job']['file']['origin'] ?? 'local';
        if (!$filename) return ['error' => 'Aucun fichier chargé'];
        $url = "http://{$printer['ip']}:{$printer['port']}/downloads/files/{$origin}/" . rawurlencode($filename);
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
        if ($error || $httpCode < 200 || $httpCode >= 300) return ['error' => $error ?: "HTTP {$httpCode}"];
        return ['ok' => true, 'content' => $content, 'filename' => $filename, 'truncated' => strlen($content) >= 524287];
    }

    // Méthode privée : trouver une imprimante par ID
    private function findPrinter($printerId) {
        foreach ($this->printers as $printer) {
            if ($printer['id'] === $printerId) {
                return $printer;
            }
        }
        return null;
    }
    
    // Méthode privée : effectuer une requête HTTP
    private function makeRequest($url, $apiKey, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Api-Key: ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        if ($httpCode == 200 || $httpCode == 204) {
            return $response ? json_decode($response, true) : ['success' => true];
        }

        if ($httpCode == 409) {
            $body = $response ? json_decode($response, true) : [];
            $msg  = $body['error'] ?? 'Imprimante non connectée au port série';
            return ['error' => $msg, 'not_operational' => true];
        }

        return ['error' => $error ?: 'Erreur HTTP ' . $httpCode];
    }
}

// Gestion des requêtes (seulement si ce fichier est appelé directement)
if (basename($_SERVER['SCRIPT_FILENAME']) !== 'octoprint_api.php') { return; }

$action = $_GET['action'] ?? 'all';
$printerId = $_GET['printer'] ?? null;

$manager = new OctoPrintManager();

switch ($action) {
    case 'all':
        // Vérification que la réponse est bien un JSON valide
        try {
            $response = json_encode($manager->getAllStatuses());
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Erreur JSON : ' . json_last_error_msg());
            }
            echo $response;
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'status':
        echo json_encode($manager->getPrinterStatus($printerId));
        break;
        
    case 'job':
        echo json_encode($manager->getJobStatus($printerId));
        break;
        
    case 'temperature':
        echo json_encode($manager->getTemperature($printerId));
        break;
        
    case 'start':
        echo json_encode($manager->startPrint($printerId, $_POST['file'] ?? null));
        break;
        
    case 'stop':
        echo json_encode($manager->stopPrint($printerId));
        break;
        
    default:
        echo json_encode(['error' => 'Action inconnue']);
}
?>