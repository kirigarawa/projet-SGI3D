<?php
// ============================================================
//  SGI3D - API REST
//  Point d'entree unique pour toutes les operations DB
// ============================================================

require_once 'config.php';
require_once 'octoprint_sync.php';

// Headers CORS + JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// Recuperer l'action
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// Fusionner GET + POST + body JSON
$data = array_merge($_GET, $_POST, $body);
$action = $data['action'] ?? '';

try {
    $db = getDB();
    $result = null;

    switch ($action) {

        // ── UTILISATEURS ────────────────────────────────────
        case 'getUsers':
            $result = $db->query('SELECT * FROM utilisateurs ORDER BY id')->fetchAll();
            break;

        case 'getUserById':
            $st = $db->prepare('SELECT * FROM utilisateurs WHERE id = ?');
            $st->execute([$data['id']]);
            $result = $st->fetch() ?: null;
            break;

        case 'getUserByEmail':
            $st = $db->prepare('SELECT * FROM utilisateurs WHERE email = ?');
            $st->execute([$data['email']]);
            $result = $st->fetch() ?: null;
            break;

        case 'createUser':
            $st = $db->prepare('INSERT INTO utilisateurs (nom, email, mot_de_passe, role, avatar, actif, cree_le)
                                VALUES (?, ?, ?, ?, ?, 1, NOW())');
            $st->execute([
                $data['nom'],
                $data['email'],
                $data['mot_de_passe'],
                $data['role'] ?? 'operateur',
                $data['avatar'] ?? strtoupper(substr($data['nom'], 0, 2))
            ]);
            $id = $db->lastInsertId();
            $st2 = $db->prepare('SELECT * FROM utilisateurs WHERE id = ?');
            $st2->execute([$id]);
            $result = $st2->fetch();
            break;

        case 'updateUser':
            $allowed = ['nom','email','mot_de_passe','role','avatar','actif'];
            $fields  = [];
            $values  = [];
            foreach ($allowed as $f) {
                if (isset($data[$f])) { $fields[] = "$f = ?"; $values[] = $data[$f]; }
            }
            if ($fields) {
                $values[] = $data['id'];
                $db->prepare('UPDATE utilisateurs SET ' . implode(', ', $fields) . ' WHERE id = ?')
                   ->execute($values);
            }
            $result = ['ok' => true];
            break;

        case 'deleteUser':
            $db->prepare('DELETE FROM utilisateurs WHERE id = ?')->execute([$data['id']]);
            $result = ['ok' => true];
            break;

        // ── AUTHENTIFICATION ─────────────────────────────────
        case 'login':
            $st = $db->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1');
            $st->execute([$data['email']]);
            $user = $st->fetch();
            if ($user && $user['mot_de_passe'] === $data['mot_de_passe']) {
                // Enregistrer le journal
                $st2 = $db->prepare('INSERT INTO journaux_connexion
                    (utilisateur_id, email, nom_utilisateur, succes, ip, navigateur, horodatage)
                    VALUES (?, ?, ?, "oui", ?, ?, NOW())');
                $st2->execute([
                    $user['id'], $user['email'], $user['nom'],
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)
                ]);
                unset($user['mot_de_passe']); // Ne jamais renvoyer le mdp
                $result = ['success' => true, 'user' => $user];
            } else {
                // Echec : journaliser quand meme
                $st3 = $db->prepare('INSERT INTO journaux_connexion
                    (utilisateur_id, email, nom_utilisateur, succes, ip, navigateur, horodatage)
                    VALUES (NULL, ?, "Inconnu", "non", ?, ?, NOW())');
                $st3->execute([
                    $data['email'],
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)
                ]);
                $result = ['success' => false];
            }
            break;

        case 'logout':
            $st = $db->prepare('INSERT INTO journaux_connexion
                (utilisateur_id, email, nom_utilisateur, succes, ip, navigateur, horodatage)
                VALUES (?, ?, ?, "deconnexion", ?, ?, NOW())');
            $st->execute([
                $data['userId'] ?? null,
                $data['email']  ?? '',
                $data['nom']    ?? '',
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)
            ]);
            $result = ['ok' => true];
            break;

        // ── JOURNAUX DE CONNEXION ────────────────────────────
        case 'getLoginLogs':
            $limit = intval($data['limit'] ?? 500);
            $st = $db->prepare('SELECT * FROM journaux_connexion ORDER BY horodatage DESC LIMIT ?');
            $st->execute([$limit]);
            $result = $st->fetchAll();
            break;

        // ── IMPRIMANTES ──────────────────────────────────────
        case 'getImprimantes':
            $result = $db->query('SELECT * FROM imprimantes ORDER BY id')->fetchAll();
            break;

        // ── TRAVAUX D'IMPRESSION ─────────────────────────────
        case 'getPrintJobs':
            $limit = intval($data['limit'] ?? 500);
            $st = $db->prepare('SELECT * FROM travaux_impression ORDER BY demarre_le DESC LIMIT ?');
            $st->execute([$limit]);
            $result = $st->fetchAll();
            break;

        case 'createPrintJob':
            $st = $db->prepare('INSERT INTO travaux_impression
                (imprimante_id, utilisateur_id, nom_utilisateur, nom_fichier, statut, materiau, duree_estimee, demarre_le)
                VALUES (?, ?, ?, ?, "en_cours", ?, ?, NOW())');
            $st->execute([
                $data['imprimante_id'] ?? null,
                $data['utilisateur_id'] ?? null,
                $data['nom_utilisateur'] ?? 'Inconnu',
                $data['nom_fichier'],
                $data['materiau'] ?? null,
                $data['duree_estimee'] ?? null
            ]);
            $id = $db->lastInsertId();
            $st2 = $db->prepare('SELECT * FROM travaux_impression WHERE id = ?');
            $st2->execute([$id]);
            $result = $st2->fetch();
            break;

        case 'finishPrintJob':
            $statut = ($data['success'] ?? false) ? 'termine' : 'erreur';
            $st = $db->prepare('UPDATE travaux_impression
                SET statut = ?, termine_le = NOW(),
                    duree_reelle = TIMESTAMPDIFF(SECOND, demarre_le, NOW())
                WHERE id = ?');
            $st->execute([$statut, $data['id']]);
            $result = ['ok' => true];
            break;

        // ── CAMERAS ──────────────────────────────────────────
        case 'getCameras':
            $result = $db->query('SELECT * FROM cameras ORDER BY id')->fetchAll();
            break;

        case 'addCamera':
            $st = $db->prepare('INSERT INTO cameras (nom, localisation, statut, url_flux, detection_mvt, ajoute_le)
                                VALUES (?, ?, "en_ligne", ?, ?, NOW())');
            $st->execute([
                $data['nom'],
                $data['localisation'],
                $data['url_flux']      ?? null,
                $data['detection_mvt'] ?? 1
            ]);
            $id = $db->lastInsertId();
            $st2 = $db->prepare('SELECT * FROM cameras WHERE id = ?');
            $st2->execute([$id]);
            $result = $st2->fetch();
            break;

        case 'updateCamera':
            $allowed = ['nom','localisation','statut','url_flux','detection_mvt'];
            $fields  = [];
            $values  = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) { $fields[] = "$f = ?"; $values[] = $data[$f]; }
            }
            if ($fields) {
                $values[] = $data['id'];
                $db->prepare('UPDATE cameras SET ' . implode(', ', $fields) . ' WHERE id = ?')
                   ->execute($values);
            }
            $result = ['ok' => true];
            break;

        case 'deleteCamera':
            $db->prepare('DELETE FROM cameras WHERE id = ?')->execute([$data['id']]);
            $result = ['ok' => true];
            break;

        // ── ALERTES ──────────────────────────────────────────
        case 'getAlerts':
            if (isset($data['resolved'])) {
                $resolved = $data['resolved'] === 'true' || $data['resolved'] === '1' ? 1 : 0;
                $st = $db->prepare('SELECT * FROM alertes WHERE resolue = ? ORDER BY cree_le DESC');
                $st->execute([$resolved]);
            } else {
                $st = $db->query('SELECT * FROM alertes ORDER BY cree_le DESC');
            }
            $result = $st->fetchAll();
            break;

        case 'addAlert':
            $st = $db->prepare('INSERT INTO alertes (type, titre, message, source, resolue, cree_le)
                                VALUES (?, ?, ?, ?, 0, NOW())');
            $st->execute([
                $data['type'],
                $data['titre'],
                $data['message'],
                $data['source'] ?? null
            ]);
            $id = $db->lastInsertId();
            $st2 = $db->prepare('SELECT * FROM alertes WHERE id = ?');
            $st2->execute([$id]);
            $result = $st2->fetch();
            break;

        case 'resolveAlert':
            $db->prepare('UPDATE alertes SET resolue = 1, resolue_le = NOW() WHERE id = ? AND resolue = 0')
               ->execute([$data['id']]);
            $result = ['ok' => true];
            break;

        case 'deleteAlert':
            $db->prepare('DELETE FROM alertes WHERE id = ?')->execute([$data['id']]);
            $result = ['ok' => true];
            break;

        case 'resolveAllAlerts':
            $db->exec('UPDATE alertes SET resolue = 1, resolue_le = NOW() WHERE resolue = 0');
            $result = ['ok' => true];
            break;

        case 'deleteResolvedAlerts':
            $db->exec('DELETE FROM alertes WHERE resolue = 1');
            $result = ['ok' => true];
            break;

        // ── STATISTIQUES ─────────────────────────────────────
        case 'getStats':
            $result = $db->query('SELECT * FROM vue_statistiques')->fetch();
            // Ajouter total utilisateurs et cameras
            $result['total_utilisateurs'] = $db->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
            $result['total_cameras']      = $db->query('SELECT COUNT(*) FROM cameras')->fetchColumn();
            $result['total_alertes']      = $db->query('SELECT COUNT(*) FROM alertes')->fetchColumn();
            $result['total_print_jobs']   = $db->query('SELECT COUNT(*) FROM travaux_impression')->fetchColumn();
            break;

        // ── EXPORT JSON ──────────────────────────────────────
        case 'exportJSON':
            $result = [
                'version'    => '3.0',
                'exportDate' => date('c'),
                'users'      => $db->query('SELECT * FROM utilisateurs')->fetchAll(),
                'login_logs' => $db->query('SELECT * FROM journaux_connexion ORDER BY horodatage DESC')->fetchAll(),
                'print_jobs' => $db->query('SELECT * FROM travaux_impression ORDER BY demarre_le DESC')->fetchAll(),
                'cameras'    => $db->query('SELECT * FROM cameras')->fetchAll(),
                'alerts'     => $db->query('SELECT * FROM alertes ORDER BY cree_le DESC')->fetchAll(),
            ];
            break;

        default:
            http_response_code(400);
            $result = ['error' => 'Action inconnue : ' . htmlspecialchars($action)];
    }

    echo json_encode(['ok' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur base de donnees : ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}