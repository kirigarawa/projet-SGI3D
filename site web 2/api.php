<?php
// ============================================================
//  SGI3D - API REST
//  Point d'entrée unique pour toutes les opérations en base.
//  Chaque fonctionnalité du site (alertes, imprimantes, caméras,
//  utilisateurs…) passe par ce fichier via le paramètre "action".
// ============================================================

// Chargement de la configuration (identifiants DB, fonction getDB())
require_once 'config.php';

// ── HEADERS HTTP ────────────────────────────────────────────
// On indique au navigateur que les réponses sont en JSON UTF-8.
// Les headers CORS permettent aux appels fetch() JavaScript
// provenant de n'importe quelle origine (*)  d'accéder à l'API.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Les requêtes OPTIONS sont des "pre-flight" CORS : on répond
// immédiatement avec un code 200 sans traiter d'action.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// ── LECTURE DES PARAMÈTRES D'ENTRÉE ─────────────────────────
// L'action peut arriver via :
//   - un paramètre GET  (?action=getUsers)
//   - un paramètre POST (formulaire)
//   - le corps JSON de la requête (fetch avec Content-Type: application/json)
// On fusionne les trois sources pour un accès uniforme via $data.
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// Fusion GET + POST + corps JSON (priorité au corps JSON en cas de doublon)
$data   = [...$_GET, ...$_POST, ...$body];
$action = $data['action'] ?? '';

// ── TRAITEMENT PRINCIPAL ─────────────────────────────────────
// Toutes les opérations DB sont encapsulées dans un try/catch :
// - PDOException : erreur SQL (connexion, requête, contrainte…)
// - Exception    : toute autre erreur applicative
try {
    $db     = getDB();   // Connexion PDO à la base de données
    $result = null;      // Contiendra la réponse JSON à retourner

    switch ($action) {

        // ════════════════════════════════════════════════════
        //  UTILISATEURS
        //  CRUD complet sur la table `utilisateurs`.
        // ════════════════════════════════════════════════════

        // Retourne tous les utilisateurs triés par id
        case 'getUsers':
            $result = $db->query('SELECT * FROM utilisateurs ORDER BY id')->fetchAll();
            break;

        // Retourne un utilisateur par son id (null si introuvable)
        case 'getUserById':
            $st = $db->prepare('SELECT * FROM utilisateurs WHERE id = ?');
            $st->execute([$data['id']]);
            $result = $st->fetch() ?: null;
            break;

        // Retourne un utilisateur par son adresse e-mail (null si introuvable)
        case 'getUserByEmail':
            $st = $db->prepare('SELECT * FROM utilisateurs WHERE email = ?');
            $st->execute([$data['email']]);
            $result = $st->fetch() ?: null;
            break;

        // Crée un nouvel utilisateur.
        // L'avatar par défaut est formé des 2 premières lettres du nom (ex: "MA").
        // Le compte est activé (actif = 1) et la date de création est fixée à NOW().
        // Retourne l'enregistrement complet une fois inséré.
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
            // Récupération de l'enregistrement fraîchement créé
            $id = $db->lastInsertId();
            $st2 = $db->prepare('SELECT * FROM utilisateurs WHERE id = ?');
            $st2->execute([$id]);
            $result = $st2->fetch();
            break;

        // Met à jour un ou plusieurs champs d'un utilisateur existant.
        // Seuls les champs listés dans $allowed peuvent être modifiés
        // (protection contre l'injection de colonnes arbitraires).
        // Construction dynamique de la requête UPDATE avec uniquement
        // les champs présents dans $data.
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

        // Supprime définitivement un utilisateur par son id
        case 'deleteUser':
            $db->prepare('DELETE FROM utilisateurs WHERE id = ?')->execute([$data['id']]);
            $result = ['ok' => true];
            break;

        // ════════════════════════════════════════════════════
        //  AUTHENTIFICATION
        //  Connexion et déconnexion avec journalisation systématique.
        // ════════════════════════════════════════════════════

        // Vérifie l'email + mot de passe et journalise la tentative.
        // En cas de succès : retourne les infos user (sans le mot de passe).
        // En cas d'échec   : journalise quand même pour audit de sécurité.
        case 'login':
            $st = $db->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1');
            $st->execute([$data['email']]);
            $user = $st->fetch();
            if ($user && $user['mot_de_passe'] === $data['mot_de_passe']) {
                // Succès : enregistrement dans le journal (IP + navigateur)
                $st2 = $db->prepare('INSERT INTO journaux_connexion
                    (utilisateur_id, email, nom_utilisateur, succes, ip, navigateur, horodatage)
                    VALUES (?, ?, ?, "oui", ?, ?, NOW())');
                $st2->execute([
                    $user['id'], $user['email'], $user['nom'],
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)
                ]);
                unset($user['mot_de_passe']); // Sécurité : ne jamais renvoyer le mot de passe au client
                $result = ['success' => true, 'user' => $user];
            } else {
                // Échec : journalisation pour détecter les tentatives d'intrusion
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

        // Enregistre une entrée "déconnexion" dans le journal.
        // Permet de tracer les sessions et durées de connexion.
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

        // ════════════════════════════════════════════════════
        //  JOURNAUX DE CONNEXION
        //  Historique de toutes les tentatives de connexion/déconnexion.
        // ════════════════════════════════════════════════════

        // Retourne les N dernières entrées du journal (défaut 500),
        // triées de la plus récente à la plus ancienne.
        case 'getLoginLogs':
            $limit = intval($data['limit'] ?? 500);
            $st = $db->prepare('SELECT * FROM journaux_connexion ORDER BY horodatage DESC LIMIT ?');
            $st->execute([$limit]);
            $result = $st->fetchAll();
            break;

        // ════════════════════════════════════════════════════
        //  IMPRIMANTES
        //  Lecture de la liste des imprimantes 3D.
        // ════════════════════════════════════════════════════

        // Retourne toutes les imprimantes enregistrées, triées par id
        case 'getImprimantes':
            $result = $db->query('SELECT * FROM imprimantes ORDER BY id')->fetchAll();
            break;

        // ════════════════════════════════════════════════════
        //  TRAVAUX D'IMPRESSION
        //  Suivi des impressions 3D (début, fin, statut).
        // ════════════════════════════════════════════════════

        // Retourne les N derniers travaux (défaut 500), du plus récent au plus ancien
        case 'getPrintJobs':
            $limit = intval($data['limit'] ?? 500);
            $st = $db->prepare('SELECT * FROM travaux_impression ORDER BY demarre_le DESC LIMIT ?');
            $st->execute([$limit]);
            $result = $st->fetchAll();
            break;

        // Crée un nouveau travail d'impression avec le statut "en_cours".
        // La durée réelle sera calculée automatiquement à la fin (finishPrintJob).
        case 'createPrintJob':
            $st = $db->prepare('INSERT INTO travaux_impression
                (imprimante_id, utilisateur_id, nom_utilisateur, nom_fichier, statut, materiau, duree_estimee, demarre_le)
                VALUES (?, ?, ?, ?, "en_cours", ?, ?, NOW())');
            $st->execute([
                $data['imprimante_id']  ?? null,
                $data['utilisateur_id'] ?? null,
                $data['nom_utilisateur'] ?? 'Inconnu',
                $data['nom_fichier'],
                $data['materiau']       ?? null,
                $data['duree_estimee']  ?? null
            ]);
            // Retourne l'enregistrement complet du travail créé
            $id = $db->lastInsertId();
            $st2 = $db->prepare('SELECT * FROM travaux_impression WHERE id = ?');
            $st2->execute([$id]);
            $result = $st2->fetch();
            break;

        // Clôture un travail d'impression.
        // Le statut passe à "termine" (succès) ou "erreur" selon $data['success'].
        // La durée réelle est calculée en secondes via TIMESTAMPDIFF.
        case 'finishPrintJob':
            $statut = ($data['success'] ?? false) ? 'termine' : 'erreur';
            $st = $db->prepare('UPDATE travaux_impression
                SET statut = ?, termine_le = NOW(),
                    duree_reelle = TIMESTAMPDIFF(SECOND, demarre_le, NOW())
                WHERE id = ?');
            $st->execute([$statut, $data['id']]);
            $result = ['ok' => true];
            break;

        // ════════════════════════════════════════════════════
        //  CAMÉRAS
        //  CRUD complet sur la table `cameras`.
        // ════════════════════════════════════════════════════

        // Retourne toutes les caméras enregistrées, triées par id
        case 'getCameras':
            $result = $db->query('SELECT * FROM cameras ORDER BY id')->fetchAll();
            break;

        // Ajoute une nouvelle caméra avec le statut "en_ligne" par défaut.
        // La détection de mouvement est activée (1) si non précisée.
        // Retourne l'enregistrement complet une fois inséré.
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

        // Met à jour un ou plusieurs champs d'une caméra existante.
        // Même protection par liste blanche ($allowed) que pour updateUser.
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

        // Supprime définitivement une caméra par son id
        case 'deleteCamera':
            $db->prepare('DELETE FROM cameras WHERE id = ?')->execute([$data['id']]);
            $result = ['ok' => true];
            break;

        // ════════════════════════════════════════════════════
        //  ALERTES
        //  Gestion complète du système de notifications.
        // ════════════════════════════════════════════════════

        // Retourne les alertes selon leur état :
        // - Si $data['resolved'] est fourni : filtre par resolue = 0 ou 1
        // - Sinon : retourne toutes les alertes, de la plus récente à la plus ancienne
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

        // Crée une nouvelle alerte (non résolue par défaut, resolue = 0).
        // Retourne l'enregistrement complet une fois inséré.
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

        // Marque une alerte comme résolue (resolue = 1) et enregistre l'heure de résolution.
        // La clause AND resolue = 0 évite d'écraser une date de résolution déjà présente.
        case 'resolveAlert':
            $db->prepare('UPDATE alertes SET resolue = 1, resolue_le = NOW() WHERE id = ? AND resolue = 0')
               ->execute([$data['id']]);
            $result = ['ok' => true];
            break;

        // Supprime définitivement une alerte par son id
        case 'deleteAlert':
            $db->prepare('DELETE FROM alertes WHERE id = ?')->execute([$data['id']]);
            $result = ['ok' => true];
            break;

        // Résout d'un coup toutes les alertes encore actives (resolue = 0)
        case 'resolveAllAlerts':
            $db->exec('UPDATE alertes SET resolue = 1, resolue_le = NOW() WHERE resolue = 0');
            $result = ['ok' => true];
            break;

        // Supprime en masse toutes les alertes déjà résolues (nettoyage de l'historique)
        case 'deleteResolvedAlerts':
            $db->exec('DELETE FROM alertes WHERE resolue = 1');
            $result = ['ok' => true];
            break;

        // ════════════════════════════════════════════════════
        //  STATISTIQUES
        //  Agrégats globaux utilisés par le dashboard.
        // ════════════════════════════════════════════════════

        // Récupère les statistiques depuis la vue SQL `vue_statistiques`,
        // puis y ajoute les compteurs totaux (utilisateurs, caméras, alertes, travaux)
        // que la vue ne calcule pas forcément.
        case 'getStats':
            $result = $db->query('SELECT * FROM vue_statistiques')->fetch();
            $result['total_utilisateurs'] = $db->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
            $result['total_cameras']      = $db->query('SELECT COUNT(*) FROM cameras')->fetchColumn();
            $result['total_alertes']      = $db->query('SELECT COUNT(*) FROM alertes')->fetchColumn();
            $result['total_print_jobs']   = $db->query('SELECT COUNT(*) FROM travaux_impression')->fetchColumn();
            break;

        // ════════════════════════════════════════════════════
        //  EXPORT JSON
        //  Dump complet de toutes les tables pour sauvegarde.
        // ════════════════════════════════════════════════════

        // Retourne l'intégralité des données du site dans un objet JSON structuré :
        // utilisateurs, journaux, travaux d'impression, caméras et alertes.
        // Utilisé par le bouton "Exporter JSON" de la sidebar.
        case 'exportJSON':
            $result = [
                'version'    => '3.0',
                'exportDate' => date('c'),  // Format ISO 8601 (ex: 2026-03-16T14:30:00+00:00)
                'users'      => $db->query('SELECT * FROM utilisateurs')->fetchAll(),
                'login_logs' => $db->query('SELECT * FROM journaux_connexion ORDER BY horodatage DESC')->fetchAll(),
                'print_jobs' => $db->query('SELECT * FROM travaux_impression ORDER BY demarre_le DESC')->fetchAll(),
                'cameras'    => $db->query('SELECT * FROM cameras')->fetchAll(),
                'alerts'     => $db->query('SELECT * FROM alertes ORDER BY cree_le DESC')->fetchAll(),
            ];
            break;

        // ── Action inconnue : réponse 400 Bad Request ────────
        default:
            http_response_code(400);
            $result = ['error' => 'Action inconnue : ' . htmlspecialchars($action)];
    }

    // ── RÉPONSE JSON DE SUCCÈS ───────────────────────────────
    // Enveloppe standard : { "ok": true, "data": <résultat> }
    // JSON_UNESCAPED_UNICODE conserve les accents lisibles (é, à, ç…)
    echo json_encode(['ok' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Erreur SQL (requête invalide, contrainte d'intégrité, connexion perdue…)
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur base de données : ' . $e->getMessage()]);
} catch (Exception $e) {
    // Toute autre erreur applicative non prévue
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
