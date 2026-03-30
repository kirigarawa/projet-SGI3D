<?php
// octoprint_api.php - API complète pour interagir avec OctoPrint
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class OctoPrintManager {
    
    private $printers;
    
    public function __construct() {
        $this->printers = is_array(OCTOPRINT_PRINTERS) ? OCTOPRINT_PRINTERS : [];
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
        
        if (is_resource($ch) || $ch instanceof CurlHandle) {
            @curl_close($ch); // Suppression des avertissements pour les versions futures
        }
        
        if ($httpCode == 200 || $httpCode == 204) {
            return $response ? json_decode($response, true) : ['success' => true];
        }
        
        return ['error' => $error ?: 'Erreur HTTP ' . $httpCode];
    }
}

// Gestion des requêtes
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