<?php
/**
 * ====================================================================
 * OSHDI VISIOPRO - SERVEUR DE SIGNALISATION WEBRTC & GESTION DE SALLE
 * ====================================================================
 * Gère l'échange de signalisation temps réel (SDP Offer, SDP Answer,
 * ICE Candidates) entre pairs WebRTC, l'état des micros/caméras/écrans,
 * la présence des participants et les commandes de modération hôte.
 * Compatible MySQL & SQLite avec nettoyage automatique des pairs inactifs.
 * ====================================================================
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Initialisation transparente des tables nécessaires si absentes
if ($pdo) {
    try {
        $idType = ($db_driver === 'mysql') ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

        // Table des Salles
        $pdo->exec("CREATE TABLE IF NOT EXISTS classroom_rooms (
            id $idType,
            room_id VARCHAR(64) UNIQUE NOT NULL,
            title VARCHAR(255) NOT NULL,
            host_peer_id VARCHAR(64) DEFAULT NULL,
            host_name VARCHAR(100) DEFAULT NULL,
            is_locked TINYINT(1) DEFAULT 0,
            require_waiting_room TINYINT(1) DEFAULT 1,
            password_hash VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Table des Pairs connectés (Présence & États médias WebRTC)
        $pdo->exec("CREATE TABLE IF NOT EXISTS classroom_peers (
            id $idType,
            room_id VARCHAR(64) NOT NULL,
            peer_id VARCHAR(64) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            user_role VARCHAR(20) DEFAULT 'participant',
            user_avatar VARCHAR(255) DEFAULT '',
            is_audio_muted TINYINT(1) DEFAULT 0,
            is_video_muted TINYINT(1) DEFAULT 0,
            is_screen_sharing TINYINT(1) DEFAULT 0,
            is_hand_raised TINYINT(1) DEFAULT 0,
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(room_id, peer_id)
        )");

        // Table des Signaux WebRTC (Offres SDP, Réponses SDP, ICE Candidates, Commandes)
        $pdo->exec("CREATE TABLE IF NOT EXISTS classroom_signals (
            id $idType,
            room_id VARCHAR(64) NOT NULL,
            from_peer VARCHAR(64) NOT NULL,
            to_peer VARCHAR(64) NOT NULL,
            signal_type VARCHAR(32) NOT NULL,
            payload TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Tables des Salles de Sous-Groupes (Breakout Rooms)
        $pdo->exec("CREATE TABLE IF NOT EXISTS classroom_breakout_rooms (
            id $idType,
            meeting_code VARCHAR(64) NOT NULL,
            room_index INT NOT NULL,
            room_title VARCHAR(150) NOT NULL,
            duration_minutes INT DEFAULT 15,
            status VARCHAR(20) DEFAULT 'active',
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            closed_at DATETIME DEFAULT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS classroom_breakout_assignments (
            id $idType,
            meeting_code VARCHAR(64) NOT NULL,
            room_index INT NOT NULL,
            peer_id VARCHAR(64) NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Index d'optimisation
        if ($db_driver === 'mysql') {
            @$pdo->exec("CREATE INDEX idx_sig_room_to ON classroom_signals (room_id, to_peer, id)");
            @$pdo->exec("CREATE INDEX idx_peer_room ON classroom_peers (room_id, last_seen)");
            @$pdo->exec("CREATE INDEX idx_brk_room ON classroom_breakout_rooms (meeting_code, status)");
        }
    } catch (Exception $e) {
        // Ignorer si déjà existant
    }
}

// Récupération de l'action et des paramètres
$action = $_REQUEST['action'] ?? 'heartbeat';
$rawBody = file_get_contents('php://input');
$jsonInput = json_decode($rawBody, true) ?: [];

$roomId   = trim($_REQUEST['room_id'] ?? ($jsonInput['room_id'] ?? 'OSHDI-ROOM-702'));
$peerId   = trim($_REQUEST['peer_id'] ?? ($jsonInput['peer_id'] ?? ''));
$userName = trim($_REQUEST['user_name'] ?? ($jsonInput['user_name'] ?? 'Participant'));
$userRole = trim($_REQUEST['user_role'] ?? ($jsonInput['user_role'] ?? 'participant'));

// Fonction utilitaire de nettoyage des pairs obsolètes (> 20s sans battement de cœur)
function cleanupStalePeersAndSignals($pdo, $roomId) {
    if (!$pdo) return;
    try {
        $threshold = date('Y-m-d H:i:s', time() - 22);
        
        // Détecter les pairs expirés pour envoyer un signal peer_left
        $staleStmt = $pdo->prepare("SELECT peer_id FROM classroom_peers WHERE room_id = ? AND last_seen < ?");
        $staleStmt->execute([$roomId, $threshold]);
        $stalePeers = $staleStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($stalePeers)) {
            $delStmt = $pdo->prepare("DELETE FROM classroom_peers WHERE room_id = ? AND last_seen < ?");
            $delStmt->execute([$roomId, $threshold]);

            // Informer les autres pairs du départ
            foreach ($stalePeers as $sPeer) {
                $insSig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'peer_left', ?)");
                $insSig->execute([$roomId, $sPeer, json_encode(['peer_id' => $sPeer])]);
            }
        }

        // Nettoyer les signaux de plus de 90 secondes
        $sigThreshold = date('Y-m-d H:i:s', time() - 90);
        $delSig = $pdo->prepare("DELETE FROM classroom_signals WHERE room_id = ? AND created_at < ?");
        $delSig->execute([$roomId, $sigThreshold]);
    } catch (Exception $e) {
        // Log silencieux
    }
}

// -------------------------------------------------------------
// 1. REJOINDRE LA SALLE (JOIN)
// -------------------------------------------------------------
if ($action === 'join') {
    if (empty($peerId)) {
        echo json_encode(['success' => false, 'error' => 'peer_id requis']);
        exit;
    }

    try {
        cleanupStalePeersAndSignals($pdo, $roomId);

        $now = date('Y-m-d H:i:s');
        $isAudioMuted = !empty($jsonInput['is_audio_muted']) ? 1 : 0;
        $isVideoMuted = !empty($jsonInput['is_video_muted']) ? 1 : 0;

        if ($pdo) {
            // S'assurer que la salle existe
            $checkRoom = $pdo->prepare("SELECT * FROM classroom_rooms WHERE room_id = ?");
            $checkRoom->execute([$roomId]);
            $room = $checkRoom->fetch();

            if (!$room) {
                $insRoom = $pdo->prepare("INSERT INTO classroom_rooms (room_id, title, host_peer_id, host_name, is_locked) VALUES (?, ?, ?, ?, 0)");
                $insRoom->execute([$roomId, "Visioconférence OSHDI : Enseignement & Restauration", ($userRole === 'host' ? $peerId : null), ($userRole === 'host' ? $userName : null)]);
            }

            // Récupérer les autres pairs connectés AVANT d'insérer le nouveau
            $getPeers = $pdo->prepare("SELECT peer_id, user_name, user_role, is_audio_muted, is_video_muted, is_screen_sharing, is_hand_raised FROM classroom_peers WHERE room_id = ? AND peer_id != ?");
            $getPeers->execute([$roomId, $peerId]);
            $existingPeers = $getPeers->fetchAll();

            // Enregistrer ou mettre à jour le pair
            $upsert = $pdo->prepare("INSERT INTO classroom_peers (room_id, peer_id, user_name, user_role, is_audio_muted, is_video_muted, joined_at, last_seen) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), user_role = VALUES(user_role), is_audio_muted = VALUES(is_audio_muted), is_video_muted = VALUES(is_video_muted), last_seen = VALUES(last_seen)");
            
            // Pour SQLite compatibilité si ON DUPLICATE KEY non supporté
            if ($db_driver === 'sqlite') {
                $checkPeer = $pdo->prepare("SELECT id FROM classroom_peers WHERE room_id = ? AND peer_id = ?");
                $checkPeer->execute([$roomId, $peerId]);
                if ($checkPeer->fetch()) {
                    $up = $pdo->prepare("UPDATE classroom_peers SET user_name = ?, user_role = ?, is_audio_muted = ?, is_video_muted = ?, last_seen = ? WHERE room_id = ? AND peer_id = ?");
                    $up->execute([$userName, $userRole, $isAudioMuted, $isVideoMuted, $now, $roomId, $peerId]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO classroom_peers (room_id, peer_id, user_name, user_role, is_audio_muted, is_video_muted, joined_at, last_seen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$roomId, $peerId, $userName, $userRole, $isAudioMuted, $isVideoMuted, $now, $now]);
                }
            } else {
                $upsert->execute([$roomId, $peerId, $userName, $userRole, $isAudioMuted, $isVideoMuted, $now, $now]);
            }

            // Émettre un signal broadcast de nouveau participant
            $peerJoinedPayload = json_encode([
                'peer_id' => $peerId,
                'user_name' => $userName,
                'user_role' => $userRole,
                'is_audio_muted' => $isAudioMuted,
                'is_video_muted' => $isVideoMuted
            ], JSON_UNESCAPED_UNICODE);

            $insSig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'peer_joined', ?)");
            $insSig->execute([$roomId, $peerId, $peerJoinedPayload]);

            // Ajouter message système dans le chat
            $sysMsg = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, 'Système VisioPro', ?, 'system')");
            $sysMsg->execute([$roomId, "{$userName} a rejoint la visioconférence."]);
        } else {
            $existingPeers = [];
        }

        echo json_encode([
            'success' => true,
            'room_id' => $roomId,
            'peer_id' => $peerId,
            'existing_peers' => $existingPeers
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// 2. BATTEMENT DE CŒUR ET ÉTAT DE PRÉSENCE (HEARTBEAT)
// -------------------------------------------------------------
if ($action === 'heartbeat') {
    if (empty($peerId)) {
        echo json_encode(['success' => false, 'error' => 'peer_id requis']);
        exit;
    }

    try {
        $now = date('Y-m-d H:i:s');
        $isAudioMuted     = isset($jsonInput['is_audio_muted']) ? intval($jsonInput['is_audio_muted']) : 0;
        $isVideoMuted     = isset($jsonInput['is_video_muted']) ? intval($jsonInput['is_video_muted']) : 0;
        $isScreenSharing  = isset($jsonInput['is_screen_sharing']) ? intval($jsonInput['is_screen_sharing']) : 0;
        $isHandRaised     = isset($jsonInput['is_hand_raised']) ? intval($jsonInput['is_hand_raised']) : 0;

        if ($pdo) {
            $upPeer = $pdo->prepare("UPDATE classroom_peers SET last_seen = ?, is_audio_muted = ?, is_video_muted = ?, is_screen_sharing = ?, is_hand_raised = ? WHERE room_id = ? AND peer_id = ?");
            $upPeer->execute([$now, $isAudioMuted, $isVideoMuted, $isScreenSharing, $isHandRaised, $roomId, $peerId]);

            // Nettoyage régulier
            cleanupStalePeersAndSignals($pdo, $roomId);

            // Liste actuelle des pairs actifs
            $listStmt = $pdo->prepare("SELECT peer_id, user_name, user_role, is_audio_muted, is_video_muted, is_screen_sharing, is_hand_raised FROM classroom_peers WHERE room_id = ? ORDER BY joined_at ASC");
            $listStmt->execute([$roomId]);
            $activePeers = $listStmt->fetchAll();

            // Vérifier statut de la salle
            $rStmt = $pdo->prepare("SELECT is_locked, title, host_name FROM classroom_rooms WHERE room_id = ?");
            $rStmt->execute([$roomId]);
            $roomData = $rStmt->fetch();
        } else {
            $activePeers = [];
            $roomData = null;
        }

        echo json_encode([
            'success' => true,
            'active_peers' => $activePeers,
            'room' => $roomData
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// 3. ENVOI D'UN SIGNAL WEBRTC (OFFER, ANSWER, CANDIDATE, COMMAND)
// -------------------------------------------------------------
if ($action === 'signal_send') {
    $fromPeer   = trim($jsonInput['from_peer'] ?? $peerId);
    $toPeer     = trim($jsonInput['to_peer'] ?? '');
    $signalType = trim($jsonInput['signal_type'] ?? '');
    $payload    = is_array($jsonInput['payload'] ?? null) ? json_encode($jsonInput['payload'], JSON_UNESCAPED_UNICODE) : strval($jsonInput['payload'] ?? '');

    if (empty($fromPeer) || empty($toPeer) || empty($signalType) || empty($payload)) {
        echo json_encode(['success' => false, 'error' => 'Paramètres de signalisation incomplets']);
        exit;
    }

    try {
        if ($pdo) {
            $stmt = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$roomId, $fromPeer, $toPeer, $signalType, $payload]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// 4. RÉCUPÉRATION DES SIGNAUX DESTINÉS À CE PAIR (SIGNAL_POLL)
// -------------------------------------------------------------
if ($action === 'signal_poll') {
    $lastSignalId = intval($_GET['last_signal_id'] ?? ($jsonInput['last_signal_id'] ?? 0));

    if (empty($peerId)) {
        echo json_encode(['success' => false, 'error' => 'peer_id requis']);
        exit;
    }

    try {
        if ($pdo) {
            // Récupère les signaux adressés directement à ce pair OU diffusés en broadcast
            $stmt = $pdo->prepare("SELECT id, from_peer, to_peer, signal_type, payload, created_at 
                FROM classroom_signals 
                WHERE room_id = ? AND id > ? AND (to_peer = ? OR to_peer = 'broadcast') AND from_peer != ?
                ORDER BY id ASC LIMIT 50");
            $stmt->execute([$roomId, $lastSignalId, $peerId, $peerId]);
            $signals = $stmt->fetchAll();

            $maxId = $lastSignalId;
            foreach ($signals as &$sig) {
                if ($sig['id'] > $maxId) {
                    $maxId = $sig['id'];
                }
                // Décoder le JSON payload pour faciliter la manipulation côté client
                $decoded = json_decode($sig['payload'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $sig['payload'] = $decoded;
                }
            }
        } else {
            $signals = [];
            $maxId = $lastSignalId;
        }

        echo json_encode([
            'success' => true,
            'signals' => $signals,
            'last_signal_id' => $maxId
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// 5. SYNCHRONISATION DE L'ÉTAT MÉDIA (AUDIO, VIDÉO, ÉCRAN, MAIN)
// -------------------------------------------------------------
if ($action === 'media_state') {
    $isAudioMuted    = isset($jsonInput['is_audio_muted']) ? intval($jsonInput['is_audio_muted']) : null;
    $isVideoMuted    = isset($jsonInput['is_video_muted']) ? intval($jsonInput['is_video_muted']) : null;
    $isScreenSharing = isset($jsonInput['is_screen_sharing']) ? intval($jsonInput['is_screen_sharing']) : null;
    $isHandRaised    = isset($jsonInput['is_hand_raised']) ? intval($jsonInput['is_hand_raised']) : null;

    try {
        if ($pdo && !empty($peerId)) {
            $fields = [];
            $params = [];

            if ($isAudioMuted !== null) { $fields[] = "is_audio_muted = ?"; $params[] = $isAudioMuted; }
            if ($isVideoMuted !== null) { $fields[] = "is_video_muted = ?"; $params[] = $isVideoMuted; }
            if ($isScreenSharing !== null) { $fields[] = "is_screen_sharing = ?"; $params[] = $isScreenSharing; }
            if ($isHandRaised !== null) { $fields[] = "is_hand_raised = ?"; $params[] = $isHandRaised; }

            if (!empty($fields)) {
                $fields[] = "last_seen = CURRENT_TIMESTAMP";
                $sql = "UPDATE classroom_peers SET " . implode(", ", $fields) . " WHERE room_id = ? AND peer_id = ?";
                $params[] = $roomId;
                $params[] = $peerId;
                $up = $pdo->prepare($sql);
                $up->execute($params);

                // Diffuser l'état à tous les autres pairs
                $statePayload = json_encode([
                    'peer_id' => $peerId,
                    'is_audio_muted' => $isAudioMuted,
                    'is_video_muted' => $isVideoMuted,
                    'is_screen_sharing' => $isScreenSharing,
                    'is_hand_raised' => $isHandRaised
                ], JSON_UNESCAPED_UNICODE);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'peer_media_state', ?)");
                $sig->execute([$roomId, $peerId, $statePayload]);
            }
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// 6. QUITTER LA SALLE (LEAVE)
// -------------------------------------------------------------
if ($action === 'leave') {
    if (!empty($peerId) && $pdo) {
        try {
            $del = $pdo->prepare("DELETE FROM classroom_peers WHERE room_id = ? AND peer_id = ?");
            $del->execute([$roomId, $peerId]);

            // Signal de départ
            $leavePayload = json_encode(['peer_id' => $peerId, 'user_name' => $userName]);
            $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'peer_left', ?)");
            $sig->execute([$roomId, $peerId, $leavePayload]);

            // Message système dans le chat
            $sysMsg = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, 'Système VisioPro', ?, 'system')");
            $sysMsg->execute([$roomId, "{$userName} a quitté la visioconférence."]);
        } catch (Exception $e) {}
    }
    echo json_encode(['success' => true]);
    exit;
}

// -------------------------------------------------------------
// 7. ACTIONS DE MODÉRATION HÔTE (MUTE ALL, KICK, LOCK ROOM)
// -------------------------------------------------------------
if ($action === 'moderate') {
    $command   = trim($jsonInput['command'] ?? '');
    $targetPeer = trim($jsonInput['target_peer'] ?? '');

    try {
        if ($pdo) {
            if ($command === 'mute_all') {
                $up = $pdo->prepare("UPDATE classroom_peers SET is_audio_muted = 1 WHERE room_id = ? AND user_role != 'host'");
                $up->execute([$roomId]);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'host_mute_all', ?)");
                $sig->execute([$roomId, $peerId, json_encode(['by' => $userName])]);

                $sysMsg = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, 'Modérateur Hôte', 'Tous les micros ont été coupés par l\'hôte.', 'system')");
                $sysMsg->execute([$roomId]);
            } elseif ($command === 'mute_peer' && !empty($targetPeer)) {
                $up = $pdo->prepare("UPDATE classroom_peers SET is_audio_muted = 1 WHERE room_id = ? AND peer_id = ?");
                $up->execute([$roomId, $targetPeer]);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, ?, 'host_mute_peer', ?)");
                $sig->execute([$roomId, $peerId, $targetPeer, json_encode(['by' => $userName])]);
            } elseif ($command === 'kick_peer' && !empty($targetPeer)) {
                $del = $pdo->prepare("DELETE FROM classroom_peers WHERE room_id = ? AND peer_id = ?");
                $del->execute([$roomId, $targetPeer]);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, ?, 'host_kick', ?)");
                $sig->execute([$roomId, $peerId, $targetPeer, json_encode(['reason' => 'Retiré par le modérateur'])]);
            } elseif ($command === 'toggle_lock') {
                $lockState = !empty($jsonInput['is_locked']) ? 1 : 0;
                $up = $pdo->prepare("UPDATE classroom_rooms SET is_locked = ? WHERE room_id = ?");
                $up->execute([$lockState, $roomId]);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'room_lock_changed', ?)");
                $sig->execute([$roomId, $peerId, json_encode(['is_locked' => $lockState])]);
            } elseif ($command === 'toggle_screen_perm') {
                $allowScreen = !empty($jsonInput['allow_screen']) ? 1 : 0;
                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'screen_perm_changed', ?)");
                $sig->execute([$roomId, $peerId, json_encode(['allow_screen' => $allowScreen])]);
            } elseif ($command === 'toggle_chat_perm') {
                $allowChat = !empty($jsonInput['allow_chat']) ? 1 : 0;
                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'chat_perm_changed', ?)");
                $sig->execute([$roomId, $peerId, json_encode(['allow_chat' => $allowChat])]);
            } elseif ($command === 'toggle_file_perm') {
                $allowFiles = !empty($jsonInput['allow_files']) ? 1 : 0;
                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'file_perm_changed', ?)");
                $sig->execute([$roomId, $peerId, json_encode(['allow_files' => $allowFiles])]);
            } elseif ($command === 'admit_guest' && !empty($targetPeer)) {
                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, ?, 'guest_admitted', ?)");
                $sig->execute([$roomId, $peerId, $targetPeer, json_encode(['by' => $userName])]);
            } elseif ($command === 'reject_guest' && !empty($targetPeer)) {
                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, ?, 'guest_rejected', ?)");
                $sig->execute([$roomId, $peerId, $targetPeer, json_encode(['by' => $userName])]);
            } elseif ($command === 'set_role' && !empty($targetPeer)) {
                $newRole = trim($jsonInput['role'] ?? 'presentateur');
                $up = $pdo->prepare("UPDATE classroom_peers SET user_role = ? WHERE room_id = ? AND peer_id = ?");
                $up->execute([$newRole, $roomId, $targetPeer]);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'peer_role_changed', ?)");
                $sig->execute([$roomId, $peerId, json_encode(['target_peer' => $targetPeer, 'role' => $newRole])]);
            } elseif ($command === 'breakout_start') {
                $rooms = is_array($jsonInput['rooms'] ?? null) ? $jsonInput['rooms'] : [];
                $assignments = is_array($jsonInput['assignments'] ?? null) ? $jsonInput['assignments'] : [];
                $duration = intval($jsonInput['duration_minutes'] ?? 15);
                if ($duration <= 0) $duration = 15;

                // Clôturer les précédentes
                $pdo->prepare("UPDATE classroom_breakout_rooms SET status = 'closed', closed_at = NOW() WHERE meeting_code = ? AND status = 'active'")->execute([$roomId]);
                $pdo->prepare("DELETE FROM classroom_breakout_assignments WHERE meeting_code = ?")->execute([$roomId]);

                // Insérer les nouvelles salles
                $insRoom = $pdo->prepare("INSERT INTO classroom_breakout_rooms (meeting_code, room_index, room_title, duration_minutes, status, started_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                foreach ($rooms as $r) {
                    $rIdx = intval($r['index'] ?? 1);
                    $rTitle = trim($r['title'] ?? ("Sous-groupe " . $rIdx));
                    $insRoom->execute([$roomId, $rIdx, $rTitle, $duration]);
                }

                // Insérer les affectations
                $insAssign = $pdo->prepare("INSERT INTO classroom_breakout_assignments (meeting_code, room_index, peer_id, user_name) VALUES (?, ?, ?, ?)");
                foreach ($assignments as $pId => $item) {
                    $rIdx = is_array($item) ? intval($item['room_index'] ?? 1) : intval($item);
                    $pName = is_array($item) ? trim($item['user_name'] ?? 'Participant') : 'Participant';
                    $insAssign->execute([$roomId, $rIdx, $pId, $pName]);
                }

                // Signal broadcast de démarrage
                $breakoutPayload = json_encode([
                    'rooms' => $rooms,
                    'assignments' => $assignments,
                    'duration_minutes' => $duration,
                    'started_at' => date('Y-m-d H:i:s'),
                    'started_by' => $userName
                ], JSON_UNESCAPED_UNICODE);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'breakout_started', ?)");
                $sig->execute([$roomId, $peerId, $breakoutPayload]);

                $sysMsg = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, 'Système VisioPro', ?, 'system')");
                $sysMsg->execute([$roomId, "🚀 Les salles de sous-groupes ({$duration} min) ont été lancées par l'hôte."]);

            } elseif ($command === 'breakout_move_peer') {
                $targetPeer = trim($jsonInput['target_peer'] ?? '');
                $targetName = trim($jsonInput['target_name'] ?? 'Participant');
                $newRoomIdx = intval($jsonInput['room_index'] ?? 1);
                $newRoomTitle = trim($jsonInput['room_title'] ?? ("Sous-groupe " . $newRoomIdx));

                if (!empty($targetPeer)) {
                    $del = $pdo->prepare("DELETE FROM classroom_breakout_assignments WHERE meeting_code = ? AND peer_id = ?");
                    $del->execute([$roomId, $targetPeer]);
                    $ins = $pdo->prepare("INSERT INTO classroom_breakout_assignments (meeting_code, room_index, peer_id, user_name) VALUES (?, ?, ?, ?)");
                    $ins->execute([$roomId, $newRoomIdx, $targetPeer, $targetName]);

                    $payload = json_encode([
                        'target_peer' => $targetPeer,
                        'target_name' => $targetName,
                        'room_index' => $newRoomIdx,
                        'room_title' => $newRoomTitle,
                        'moved_by' => $userName
                    ], JSON_UNESCAPED_UNICODE);

                    $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'breakout_peer_moved', ?)");
                    $sig->execute([$roomId, $peerId, $payload]);
                }

            } elseif ($command === 'breakout_broadcast_msg') {
                $broadcastMsg = trim($jsonInput['message'] ?? '');
                if (!empty($broadcastMsg)) {
                    $payload = json_encode(['message' => $broadcastMsg, 'from' => $userName], JSON_UNESCAPED_UNICODE);
                    $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'breakout_broadcast_msg', ?)");
                    $sig->execute([$roomId, $peerId, $payload]);

                    $sysMsg = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, 'Annonce Hôte (Sous-Groupes)', ?, 'system')");
                    $sysMsg->execute([$roomId, "📢 Annonce générale aux sous-groupes : {$broadcastMsg}"]);
                }

            } elseif ($command === 'breakout_close_warning') {
                $countdownSec = intval($jsonInput['countdown_seconds'] ?? 60);
                $payload = json_encode(['countdown_seconds' => $countdownSec, 'by' => $userName]);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'breakout_close_warning', ?)");
                $sig->execute([$roomId, $peerId, $payload]);

                $sysMsg = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, 'Système VisioPro', ?, 'system')");
                $sysMsg->execute([$roomId, "⏳ Les sous-groupes vont fermer dans {$countdownSec} secondes. Préparez-vous à revenir dans la salle principale."]);

            } elseif ($command === 'breakout_end') {
                $pdo->prepare("UPDATE classroom_breakout_rooms SET status = 'closed', closed_at = NOW() WHERE meeting_code = ? AND status = 'active'")->execute([$roomId]);
                $pdo->prepare("DELETE FROM classroom_breakout_assignments WHERE meeting_code = ?")->execute([$roomId]);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'breakout_ended', ?)");
                $sig->execute([$roomId, $peerId, json_encode(['by' => $userName])]);

                $sysMsg = $pdo->prepare("INSERT INTO classroom_messages (room_id, user_name, message, msg_type) VALUES (?, 'Système VisioPro', 'Toutes les salles de sous-groupes sont fermées. Bienvenue à nouveau dans la salle principale.', 'system')");
                $sysMsg->execute([$roomId]);

            } elseif ($command === 'breakout_ask_help') {
                $rIdx = intval($jsonInput['room_index'] ?? 1);
                $rTitle = trim($jsonInput['room_title'] ?? ("Sous-groupe " . $rIdx));
                $payload = json_encode([
                    'from_peer' => $peerId,
                    'user_name' => $userName,
                    'room_index' => $rIdx,
                    'room_title' => $rTitle
                ], JSON_UNESCAPED_UNICODE);

                $sig = $pdo->prepare("INSERT INTO classroom_signals (room_id, from_peer, to_peer, signal_type, payload) VALUES (?, ?, 'broadcast', 'breakout_help_requested', ?)");
                $sig->execute([$roomId, $peerId, $payload]);
            }
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// 8. INFOS SALLE
// -------------------------------------------------------------
if ($action === 'get_room_info') {
    try {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM classroom_rooms WHERE room_id = ?");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch();

            $pStmt = $pdo->prepare("SELECT COUNT(*) as count FROM classroom_peers WHERE room_id = ?");
            $pStmt->execute([$roomId]);
            $count = $pStmt->fetch()['count'] ?? 0;
        } else {
            $room = null;
            $count = 0;
        }

        echo json_encode([
            'success' => true,
            'room' => $room,
            'participants_count' => $count
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// 9. CRÉATION D'UNE NOUVELLE RÉUNION
// -------------------------------------------------------------
if ($action === 'create_room') {
    $title      = trim($jsonInput['title'] ?? ($_REQUEST['title'] ?? 'Nouvelle Visioconférence OSHDI'));
    $customCode = trim($jsonInput['custom_code'] ?? ($_REQUEST['custom_code'] ?? ''));
    $hostName   = trim($jsonInput['host_name'] ?? ($_REQUEST['host_name'] ?? $userName));

    if (empty($customCode)) {
        $customCode = 'OSHDI-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)) . '-' . rand(100, 999);
    } else {
        $customCode = preg_replace('/[^a-zA-Z0-9_\-]/', '', $customCode);
    }

    try {
        if ($pdo) {
            $stmt = $pdo->prepare("INSERT INTO classroom_rooms (room_id, title, host_name, is_locked, created_at) VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP)");
            $stmt->execute([$customCode, $title, $hostName]);
        }

        echo json_encode([
            'success' => true,
            'room_id' => $customCode,
            'title' => $title,
            'url' => 'classroom.php?room=' . urlencode($customCode)
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// -------------------------------------------------------------
// 10. LISTE DES RÉUNIONS DISPONIBLES & STATISTIQUES (TABLEAU DE BORD)
// -------------------------------------------------------------
if ($action === 'list_rooms') {
    try {
        if ($pdo) {
            // Nettoyage préalable des pairs inactifs
            cleanupStalePeersAndSignals($pdo, $roomId);

            $stmt = $pdo->query("SELECT r.*, 
                (SELECT COUNT(*) FROM classroom_peers p WHERE p.room_id = r.room_id) as active_peers_count 
                FROM classroom_rooms r 
                ORDER BY r.created_at DESC LIMIT 20");
            $rooms = $stmt->fetchAll();
        } else {
            $rooms = [];
        }

        echo json_encode([
            'success' => true,
            'rooms' => $rooms
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Action inconnue']);
