<?php
/**
 * ====================================================================
 * OSHDI - API DE COMMUNICATION & VISIOCONFÉRENCE EN TEMPS RÉEL
 * ====================================================================
 * Gère le chat en direct, l'envoi de notes vocales réelles, les levées
 * de main, les réactions et le sondage interactif de la classe.
 * ====================================================================
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Création automatique de la table si absente
if ($pdo) {
    try {
        $idType = ($db_driver === 'mysql') ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $pdo->exec("CREATE TABLE IF NOT EXISTS classroom_messages (
            id $idType,
            room_id VARCHAR(50) DEFAULT 'OSHDI-ROOM-702',
            user_name VARCHAR(100) NOT NULL,
            message TEXT,
            msg_type VARCHAR(20) DEFAULT 'text',
            audio_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS classroom_poll_votes (
            id $idType,
            room_id VARCHAR(50) DEFAULT 'OSHDI-ROOM-702',
            poll_id INT DEFAULT 1,
            user_identifier VARCHAR(100) NOT NULL,
            option_index INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS classroom_permissions (
            id $idType,
            room_id VARCHAR(50) DEFAULT 'OSHDI-ROOM-702',
            user_uid VARCHAR(100) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            user_location VARCHAR(100) DEFAULT '',
            status VARCHAR(20) DEFAULT 'pending',
            requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $e) {
        // Ignorer si déjà existant
    }
}

$action = $_REQUEST['action'] ?? 'fetch_messages';
$roomId = $_REQUEST['room_id'] ?? 'OSHDI-ROOM-702';

// 1. Récupération des messages récents
if ($action === 'fetch_messages') {
    $lastId = intval($_GET['last_id'] ?? 0);
    try {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM classroom_messages WHERE room_id = ? AND id > ? ORDER BY id ASC LIMIT 50");
            $stmt->execute([$roomId, $lastId]);
            $messages = $stmt->fetchAll();
        } else {
            $messages = [];
        }
        echo json_encode(['success' => true, 'messages' => $messages], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'messages' => []]);
    }
    exit;
}

// 2. Envoi d'un message texte ou d'une levée de main
if ($action === 'send_message') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;

    $userName = trim($input['user_name'] ?? 'Participant');
    $message  = trim($input['message'] ?? '');
    $msgType  = trim($input['msg_type'] ?? 'text');

    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message vide']);
        exit;
    }

    try {
        if ($pdo) {
            $stmt = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$roomId, $userName, $message, $msgType]);
            $insertedId = $pdo->lastInsertId();

            $fetch = $pdo->prepare("SELECT * FROM classroom_messages WHERE id = ?");
            $fetch->execute([$insertedId]);
            $newMsg = $fetch->fetch();
        } else {
            $newMsg = [
                'id' => time(),
                'room_id' => $roomId,
                'user_name' => $userName,
                'message' => $message,
                'msg_type' => $msgType,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        echo json_encode(['success' => true, 'message' => $newMsg], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 3. Envoi d'une note vocale réelle
if ($action === 'send_voice') {
    $userName = trim($_POST['user_name'] ?? 'Participant');
    $audioData = '';

    // Si envoyé en upload multipart file
    if (!empty($_FILES['voice_file']['tmp_name'])) {
        $uploadsDir = __DIR__ . '/../uploads/voice_notes';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }
        $filename = 'voice_' . time() . '_' . mt_rand(1000, 9999) . '.webm';
        $target = $uploadsDir . '/' . $filename;
        if (move_uploaded_file($_FILES['voice_file']['tmp_name'], $target)) {
            $audioData = 'uploads/voice_notes/' . $filename;
        }
    } elseif (!empty($_POST['audio_base64'])) {
        // Enregistrement base64
        $audioData = $_POST['audio_base64'];
    }

    if (empty($audioData)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Données audio introuvables']);
        exit;
    }

    try {
        if ($pdo) {
            $stmt = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type, audio_url) VALUES (?, ?, ?, 'voice', ?)");
            $stmt->execute([$roomId, $userName, 'Note vocale transmise', $audioData]);
            $insertedId = $pdo->lastInsertId();

            $fetch = $pdo->prepare("SELECT * FROM classroom_messages WHERE id = ?");
            $fetch->execute([$insertedId]);
            $newMsg = $fetch->fetch();
        } else {
            $newMsg = [
                'id' => time(),
                'room_id' => $roomId,
                'user_name' => $userName,
                'message' => 'Note vocale transmise',
                'msg_type' => 'voice',
                'audio_url' => $audioData,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        echo json_encode(['success' => true, 'message' => $newMsg], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 4. Sondage en direct (Vote & Récupération des statistiques)
if ($action === 'vote_poll') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;

    $pollId = intval($input['poll_id'] ?? 1);
    $optionIndex = intval($input['option_index'] ?? 0);
    $userUid = trim($input['user_uid'] ?? ('user_' . session_id()));

    try {
        if ($pdo) {
            // Empêche le double vote pour le même pollId
            $check = $pdo->prepare("SELECT id FROM classroom_poll_votes WHERE room_id = ? AND poll_id = ? AND user_identifier = ?");
            $check->execute([$roomId, $pollId, $userUid]);
            if ($check->fetch()) {
                $up = $pdo->prepare("UPDATE classroom_poll_votes SET option_index = ? WHERE room_id = ? AND poll_id = ? AND user_identifier = ?");
                $up->execute([$optionIndex, $roomId, $pollId, $userUid]);
            } else {
                $ins = $pdo->prepare("INSERT INTO classroom_poll_votes (room_id, poll_id, user_identifier, option_index) VALUES (?, ?, ?, ?)");
                $ins->execute([$roomId, $pollId, $userUid, $optionIndex]);
            }

            // Calcul des pourcentages
            $votes = $pdo->prepare("SELECT option_index, COUNT(*) as cnt FROM classroom_poll_votes WHERE room_id = ? AND poll_id = ? GROUP BY option_index");
            $votes->execute([$roomId, $pollId]);
            $results = $votes->fetchAll();
        } else {
            $results = [];
        }

        echo json_encode(['success' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 5. Récupération des résultats du sondage
if ($action === 'get_poll') {
    $pollId = intval($_GET['poll_id'] ?? 1);
    try {
        if ($pdo) {
            $votes = $pdo->prepare("SELECT option_index, COUNT(*) as cnt FROM classroom_poll_votes WHERE room_id = ? AND poll_id = ? GROUP BY option_index");
            $votes->execute([$roomId, $pollId]);
            $results = $votes->fetchAll();
        } else {
            $results = [];
        }
        echo json_encode(['success' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 6. Demande de permission d'accès (Participant en salle d'attente)
if ($action === 'request_permission') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];

    $userUid = trim($input['user_uid'] ?? ($_REQUEST['user_uid'] ?? ''));
    $userName = trim($input['user_name'] ?? ($_REQUEST['user_name'] ?? 'Participant'));
    $userLocation = trim($input['user_location'] ?? ($_REQUEST['user_location'] ?? 'En ligne'));

    if (empty($userUid)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Identifiant utilisateur requis']);
        exit;
    }

    try {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id, status FROM classroom_permissions WHERE room_id = ? AND user_uid = ?");
            $stmt->execute([$roomId, $userUid]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Si déjà accepté, conserver accepted
                if ($existing['status'] !== 'accepted') {
                    $up = $pdo->prepare("UPDATE classroom_permissions SET status = 'pending', user_name = ?, user_location = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $up->execute([$userName, $userLocation, $existing['id']]);
                }
                $status = $existing['status'] === 'accepted' ? 'accepted' : 'pending';
            } else {
                $ins = $pdo->prepare("INSERT INTO classroom_permissions (room_id, user_uid, user_name, user_location, status) VALUES (?, ?, ?, ?, 'pending')");
                $ins->execute([$roomId, $userUid, $userName, $userLocation]);
                $status = 'pending';
            }
        } else {
            $status = 'pending';
        }

        echo json_encode(['success' => true, 'status' => $status], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 7. Vérification du statut de la permission (Polling de la salle d'attente)
if ($action === 'check_permission') {
    $userUid = trim($_GET['user_uid'] ?? '');

    try {
        $status = 'pending';
        if ($pdo && !empty($userUid)) {
            $stmt = $pdo->prepare("SELECT status FROM classroom_permissions WHERE room_id = ? AND user_uid = ?");
            $stmt->execute([$roomId, $userUid]);
            $row = $stmt->fetch();
            if ($row) {
                $status = $row['status'];
            }
        }
        echo json_encode(['success' => true, 'status' => $status], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 8. Récupération des demandes en attente (Pour l'Hôte / Modérateur)
if ($action === 'get_pending_permissions') {
    try {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM classroom_permissions WHERE room_id = ? AND status = 'pending' ORDER BY requested_at ASC");
            $stmt->execute([$roomId]);
            $requests = $stmt->fetchAll();
        } else {
            $requests = [];
        }
        echo json_encode(['success' => true, 'requests' => $requests], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 9. Mise à jour de la permission (Accepter / Refuser un participant)
if ($action === 'update_permission') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];

    $userUid = trim($input['user_uid'] ?? ($_REQUEST['user_uid'] ?? ''));
    $newStatus = trim($input['status'] ?? ($_REQUEST['status'] ?? 'accepted')); // 'accepted' ou 'rejected'

    if (empty($userUid)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Identifiant requis']);
        exit;
    }

    try {
        if ($pdo) {
            if ($userUid === 'all') {
                $stmt = $pdo->prepare("UPDATE classroom_permissions SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE room_id = ? AND status = 'pending'");
                $stmt->execute([$newStatus, $roomId]);

                // Message système de bienvenue générale
                $sysMsg = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, 'Modérateur OSHDI', ?, 'system')");
                $sysMsg->execute([$roomId, 'Tous les participants en attente ont été admis dans la salle de conférence.']);
            } else {
                $stmt = $pdo->prepare("UPDATE classroom_permissions SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE room_id = ? AND user_uid = ?");
                $stmt->execute([$newStatus, $roomId, $userUid]);

                // Récupérer le nom
                $getN = $pdo->prepare("SELECT user_name FROM classroom_permissions WHERE room_id = ? AND user_uid = ?");
                $getN->execute([$roomId, $userUid]);
                $user = $getN->fetch();
                if ($user && $newStatus === 'accepted') {
                    $sysMsg = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, 'Modérateur OSHDI', ?, 'system')");
                    $sysMsg->execute([$roomId, "{$user['user_name']} a été admis(e) dans la salle de conférence."]);
                }
            }
        }
        echo json_encode(['success' => true, 'status' => $newStatus], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 10. Simulation d'une demande entrante (Outil pour tester l'interface hôte)
if ($action === 'simulate_permission_request') {
    $demoNames = [
        ['name' => 'Kouassi David', 'loc' => 'Bouaké, Côte d\'Ivoire'],
        ['name' => 'Sarah Traoré', 'loc' => 'Genève, Suisse'],
        ['name' => 'Michel Yao', 'loc' => 'Abidjan, Cocody'],
        ['name' => 'Esther Bahi', 'loc' => 'Paris, France'],
        ['name' => 'Pasteur Jonathan', 'loc' => 'San-Pédro, CI']
    ];
    $chosen = $demoNames[array_rand($demoNames)];
    $demoUid = 'sim_' . time() . '_' . mt_rand(100, 999);

    try {
        if ($pdo) {
            $stmt = $pdo->prepare("INSERT INTO classroom_permissions (room_id, user_uid, user_name, user_location, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$roomId, $demoUid, $chosen['name'], $chosen['loc']]);
        }
        echo json_encode(['success' => true, 'user_uid' => $demoUid, 'user_name' => $chosen['name'], 'user_location' => $chosen['loc']], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue']);
