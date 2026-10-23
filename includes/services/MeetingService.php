<?php
/**
 * ====================================================================
 * OSHDI VISIOPRO - SERVICE DE GESTION DES RÉUNIONS (MeetingService)
 * ====================================================================
 * Gestion complète du cycle de vie des visioconférences :
 * - Création de réunions instantanées ou planifiées avec codes uniques
 * - Sécurisation par mot de passe et contrôle des droits d'accès
 * - Génération d'invitations sécurisées par e-mail ou lien direct
 * - Clôture, verrouillage, modération et statistiques de fréquentation
 * ====================================================================
 */

require_once __DIR__ . '/MeetingTokenService.php';
require_once __DIR__ . '/MeetingAuditService.php';

class MeetingService {
    private $pdo;
    private $auditService;
    private $dbDriver;

    public function __construct(PDO $pdo, $dbDriver = 'mysql') {
        $this->pdo = $pdo;
        $this->dbDriver = $dbDriver;
        $this->auditService = new MeetingAuditService($pdo);
        $this->initSchema();
    }

    /**
     * Initialise automatiquement les tables nécessaires si absentes
     */
    private function initSchema() {
        try {
            $idType = ($this->dbDriver === 'mysql') ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

            // 1. Table des réunions complètes
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS classroom_meetings (
                id $idType,
                meeting_code VARCHAR(64) UNIQUE NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                host_user_id INT DEFAULT NULL,
                host_name VARCHAR(100) NOT NULL,
                password_hash VARCHAR(255) DEFAULT NULL,
                is_locked TINYINT(1) DEFAULT 0,
                require_waiting_room TINYINT(1) DEFAULT 1,
                allow_screen_share TINYINT(1) DEFAULT 1,
                allow_chat TINYINT(1) DEFAULT 1,
                max_participants INT DEFAULT 50,
                scheduled_start DATETIME DEFAULT NULL,
                scheduled_end DATETIME DEFAULT NULL,
                recurrence VARCHAR(20) DEFAULT 'none',
                timezone VARCHAR(50) DEFAULT 'Africa/Abidjan',
                reminder_minutes INT DEFAULT 15,
                status VARCHAR(20) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Migrations colonnes Module F
            try { $this->pdo->exec("ALTER TABLE classroom_meetings ADD COLUMN recurrence VARCHAR(20) DEFAULT 'none'"); } catch(Exception $e) {}
            try { $this->pdo->exec("ALTER TABLE classroom_meetings ADD COLUMN timezone VARCHAR(50) DEFAULT 'Africa/Abidjan'"); } catch(Exception $e) {}
            try { $this->pdo->exec("ALTER TABLE classroom_meetings ADD COLUMN reminder_minutes INT DEFAULT 15"); } catch(Exception $e) {}

            // 2. Table de présence et historique des participants
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS classroom_meeting_participants (
                id $idType,
                meeting_code VARCHAR(64) NOT NULL,
                user_id INT DEFAULT NULL,
                user_name VARCHAR(100) NOT NULL,
                role VARCHAR(20) DEFAULT 'participant',
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                left_at DATETIME DEFAULT NULL,
                duration_seconds INT DEFAULT 0,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL
            )");

            // 3. Table des invitations sécurisées
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS classroom_invitations (
                id $idType,
                meeting_code VARCHAR(64) NOT NULL,
                invitation_token VARCHAR(64) UNIQUE NOT NULL,
                recipient_email VARCHAR(150) DEFAULT NULL,
                recipient_name VARCHAR(100) DEFAULT NULL,
                role VARCHAR(20) DEFAULT 'participant',
                status VARCHAR(20) DEFAULT 'pending',
                expires_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // 4. Table du journal d'audit
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS classroom_audit_logs (
                id $idType,
                meeting_code VARCHAR(64) NOT NULL,
                user_name VARCHAR(100) NOT NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // S'assurer que le salon de base par défaut existe
            $checkDef = $this->pdo->prepare("SELECT id FROM classroom_meetings WHERE meeting_code = 'OSHDI-ROOM-702'");
            $checkDef->execute();
            if (!$checkDef->fetch()) {
                $insDef = $this->pdo->prepare("INSERT INTO classroom_meetings 
                    (meeting_code, title, description, host_name, is_locked, require_waiting_room, status, timezone, recurrence) 
                    VALUES ('OSHDI-ROOM-702', 'Session Interactive : Restauration Spirituelle & Autonomie Sociale', 'Visioconférence pastorale et formation communautaire OSHDI en direct', 'Dr. K. N\'Guessan', 0, 1, 'active', 'Africa/Abidjan', 'weekly')");
                $insDef->execute();
            }
        } catch (Exception $e) {
            // Ignorer si déjà existant
        }
    }

    /**
     * Crée une nouvelle visioconférence (instantanée ou programmée)
     */
    public function createMeeting(array $data, array $hostUser = []) {
        $title = trim($data['title'] ?? 'Nouvelle Visioconférence OSHDI');
        $desc = trim($data['description'] ?? '');
        $hostName = trim($hostUser['name'] ?? ($data['host_name'] ?? 'Formateur OSHDI'));
        $hostUserId = !empty($hostUser['id']) ? intval($hostUser['id']) : null;

        // Génération d'un code unique de réunion propre et mémorisable (ex: OSHDI-A7B2-941)
        $customCode = trim($data['meeting_code'] ?? ($data['custom_code'] ?? ''));
        if (!empty($customCode)) {
            $meetingCode = strtoupper(preg_replace('/[^a-zA-Z0-9_\-]/', '', $customCode));
        } else {
            $meetingCode = 'OSHDI-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)) . '-' . rand(100, 999);
        }

        // Mot de passe optionnel
        $passwordHash = null;
        if (!empty($data['password'])) {
            $passwordHash = password_hash(trim($data['password']), PASSWORD_DEFAULT);
        }

        $requireWaitingRoom = isset($data['require_waiting_room']) ? (int)$data['require_waiting_room'] : 1;
        $allowScreenShare   = isset($data['allow_screen_share']) ? (int)$data['allow_screen_share'] : 1;
        $allowChat          = isset($data['allow_chat']) ? (int)$data['allow_chat'] : 1;
        $maxParticipants    = !empty($data['max_participants']) ? intval($data['max_participants']) : 50;
        $scheduledStart     = !empty($data['scheduled_start']) ? $data['scheduled_start'] : null;
        $scheduledEnd       = !empty($data['scheduled_end']) ? $data['scheduled_end'] : null;
        $recurrence         = trim($data['recurrence'] ?? 'none');
        $timezone           = trim($data['timezone'] ?? 'Africa/Abidjan');
        $reminderMinutes    = !empty($data['reminder_minutes']) ? intval($data['reminder_minutes']) : 15;
        $status             = (!empty($scheduledStart) && strtotime($scheduledStart) > time()) ? 'scheduled' : 'active';

        $stmt = $this->pdo->prepare("INSERT INTO classroom_meetings 
            (meeting_code, title, description, host_user_id, host_name, password_hash, is_locked, 
             require_waiting_room, allow_screen_share, allow_chat, max_participants, 
             scheduled_start, scheduled_end, recurrence, timezone, reminder_minutes, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->execute([
            $meetingCode, $title, $desc, $hostUserId, $hostName, $passwordHash,
            $requireWaitingRoom, $allowScreenShare, $allowChat, $maxParticipants,
            $scheduledStart, $scheduledEnd, $recurrence, $timezone, $reminderMinutes, $status
        ]);

        $this->auditService->logAuditEvent($meetingCode, $hostName, 'create_meeting', [
            'title' => $title,
            'status' => $status,
            'scheduled_start' => $scheduledStart,
            'recurrence' => $recurrence,
            'timezone' => $timezone,
            'has_password' => ($passwordHash !== null)
        ]);

        // Générer le jeton hôte pour l'organisateur
        $hostToken = MeetingTokenService::generateAccessToken($meetingCode, $hostName, 'host', $hostUserId);

        return [
            'success'          => true,
            'meeting_code'     => $meetingCode,
            'title'            => $title,
            'url'              => 'classroom.php?room=' . urlencode($meetingCode) . '&role=host&token=' . urlencode($hostToken),
            'token'            => $hostToken,
            'scheduled_start'  => $scheduledStart,
            'recurrence'       => $recurrence,
            'timezone'         => $timezone,
            'reminder_minutes' => $reminderMinutes,
            'has_password'     => ($passwordHash !== null),
            'status'           => $status
        ];
    }

    /**
     * Récupère les détails d'une réunion
     */
    public function getMeeting($meetingCode) {
        $stmt = $this->pdo->prepare("SELECT * FROM classroom_meetings WHERE meeting_code = ?");
        $stmt->execute([$meetingCode]);
        $meeting = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($meeting) {
            // Ne jamais exposer le hash de mot de passe directement
            $meeting['has_password'] = !empty($meeting['password_hash']);
            unset($meeting['password_hash']);
        }
        return $meeting;
    }

    /**
     * Vérifie les droits d'accès à une réunion et délivre un jeton d'accès
     */
    public function verifyMeetingAccess($meetingCode, $userName, $password = null, $token = null, $role = 'participant', $userId = null) {
        $stmt = $this->pdo->prepare("SELECT * FROM classroom_meetings WHERE meeting_code = ?");
        $stmt->execute([$meetingCode]);
        $meeting = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$meeting) {
            return ['allowed' => false, 'error' => 'Réunion introuvable ou code inexistant.'];
        }

        if ($meeting['status'] === 'ended') {
            return ['allowed' => false, 'error' => 'Cette réunion est déjà terminée par l\'organisateur.'];
        }

        // Si la réunion est verrouillée et que l'utilisateur n'est pas déjà un hôte
        if (!empty($meeting['is_locked']) && $role !== 'host') {
            return ['allowed' => false, 'error' => 'Cette salle a été verrouillée par l\'organisateur. Accès refusé.'];
        }

        // Si un jeton d'accès préalable est fourni, le valider
        if (!empty($token)) {
            $payload = MeetingTokenService::verifyAccessToken($token, $meetingCode);
            if ($payload) {
                return [
                    'allowed'      => true,
                    'meeting'      => $meeting,
                    'role'         => $payload['role'] ?? $role,
                    'access_token' => $token
                ];
            }
        }

        // Vérification du mot de passe s'il est requis
        if (!empty($meeting['password_hash'])) {
            if (empty($password) || !password_verify($password, $meeting['password_hash'])) {
                return [
                    'allowed'        => false,
                    'require_pwd'    => true,
                    'error'          => 'Mot de passe requis ou incorrect pour rejoindre cette réunion.'
                ];
            }
        }

        // Générer un jeton d'accès sécurisé
        $accessToken = MeetingTokenService::generateAccessToken($meetingCode, $userName, $role, $userId);

        return [
            'allowed'      => true,
            'meeting'      => $meeting,
            'role'         => $role,
            'access_token' => $accessToken
        ];
    }

    /**
     * Crée une invitation sécurisée à une réunion
     */
    public function createInvitation($meetingCode, $recipientEmail = null, $recipientName = null, $role = 'participant', $expiresInHours = 72) {
        $token = bin2hex(random_bytes(24));
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInHours * 3600));

        $stmt = $this->pdo->prepare("INSERT INTO classroom_invitations 
            (meeting_code, invitation_token, recipient_email, recipient_name, role, status, expires_at, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$meetingCode, $token, $recipientEmail, $recipientName, $role, $expiresAt]);

        $inviteUrl = "classroom.php?room=" . urlencode($meetingCode) . "&invite=" . urlencode($token);

        return [
            'success'     => true,
            'token'       => $token,
            'invite_url'  => $inviteUrl,
            'expires_at'  => $expiresAt
        ];
    }

    /**
     * Liste des réunions disponibles et récentes (pour le tableau de bord)
     */
    public function listMeetings($limit = 20, $status = null) {
        try {
            $sql = "SELECT m.*, 
                (SELECT COUNT(*) FROM classroom_peers p WHERE p.room_id = m.meeting_code) as active_peers_count 
                FROM classroom_meetings m";
            $params = [];

            if ($status) {
                $sql .= " WHERE m.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY m.created_at DESC LIMIT ?";
            $params[] = (int)$limit;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rooms as &$r) {
                $r['has_password'] = !empty($r['password_hash']);
                unset($r['password_hash']);
            }
            return $rooms;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Termine une réunion pour tout le monde (Organisateur)
     */
    public function endMeeting($meetingCode, $byUser = 'Hôte') {
        try {
            $up = $this->pdo->prepare("UPDATE classroom_meetings SET status = 'ended', updated_at = CURRENT_TIMESTAMP WHERE meeting_code = ?");
            $up->execute([$meetingCode]);

            // Clôturer la présence de tous les participants encore connectés
            $upP = $this->pdo->prepare("UPDATE classroom_meeting_participants SET left_at = CURRENT_TIMESTAMP WHERE meeting_code = ? AND left_at IS NULL");
            $upP->execute([$meetingCode]);

            $this->auditService->logAuditEvent($meetingCode, $byUser, 'end_meeting', 'La réunion a été clôturée par l\'hôte.');

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Modifie les paramètres d'une réunion planifiée (Module F)
     */
    public function updateMeeting($meetingCode, array $data, $byUser = 'Hôte') {
        try {
            $fields = [];
            $params = [];

            if (isset($data['title'])) {
                $fields[] = "title = ?";
                $params[] = trim($data['title']);
            }
            if (isset($data['description'])) {
                $fields[] = "description = ?";
                $params[] = trim($data['description']);
            }
            if (isset($data['scheduled_start'])) {
                $fields[] = "scheduled_start = ?";
                $params[] = !empty($data['scheduled_start']) ? $data['scheduled_start'] : null;
            }
            if (isset($data['scheduled_end'])) {
                $fields[] = "scheduled_end = ?";
                $params[] = !empty($data['scheduled_end']) ? $data['scheduled_end'] : null;
            }
            if (isset($data['recurrence'])) {
                $fields[] = "recurrence = ?";
                $params[] = trim($data['recurrence']);
            }
            if (isset($data['timezone'])) {
                $fields[] = "timezone = ?";
                $params[] = trim($data['timezone']);
            }
            if (isset($data['reminder_minutes'])) {
                $fields[] = "reminder_minutes = ?";
                $params[] = intval($data['reminder_minutes']);
            }
            if (!empty($data['password'])) {
                $fields[] = "password_hash = ?";
                $params[] = password_hash(trim($data['password']), PASSWORD_DEFAULT);
            }
            if (isset($data['require_waiting_room'])) {
                $fields[] = "require_waiting_room = ?";
                $params[] = intval($data['require_waiting_room']);
            }

            if (empty($fields)) {
                return ['success' => false, 'error' => 'Aucune donnée à modifier.'];
            }

            $fields[] = "updated_at = CURRENT_TIMESTAMP";
            $sql = "UPDATE classroom_meetings SET " . implode(", ", $fields) . " WHERE meeting_code = ?";
            $params[] = $meetingCode;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $this->auditService->logAuditEvent($meetingCode, $byUser, 'update_meeting', 'Paramètres de la réunion modifiés');

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Annule une réunion planifiée (Module F)
     */
    public function cancelMeeting($meetingCode, $byUser = 'Hôte') {
        try {
            $stmt = $this->pdo->prepare("UPDATE classroom_meetings SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE meeting_code = ?");
            $stmt->execute([$meetingCode]);

            $this->auditService->logAuditEvent($meetingCode, $byUser, 'cancel_meeting', 'La réunion a été annulée par l\'organisateur');

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Récupère la liste des invitations et confirmations RSVP (Module F)
     */
    public function getMeetingInvitations($meetingCode) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM classroom_invitations WHERE meeting_code = ? ORDER BY created_at DESC");
            $stmt->execute([$meetingCode]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Met à jour la confirmation de présence RSVP d'un invité (Module F)
     */
    public function updateRsvpStatus($token, $status, $guestName = null) {
        try {
            $allowed = ['accepted', 'declined', 'attended', 'pending'];
            if (!in_array($status, $allowed)) {
                return ['success' => false, 'error' => 'Statut RSVP invalide.'];
            }

            $sql = "UPDATE classroom_invitations SET status = ?";
            $params = [$status];

            if ($guestName !== null && trim($guestName) !== '') {
                $sql .= ", recipient_name = ?";
                $params[] = trim($guestName);
            }

            $sql .= " WHERE invitation_token = ?";
            $params[] = $token;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'status' => $status];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Envoie une invitation par e-mail avec modèle HTML stylisé (Module F)
     */
    public function sendEmailInvitation($meetingCode, $email, $name, $role = 'participant', $expiresInHours = 72) {
        $inv = $this->createInvitation($meetingCode, $email, $name, $role, $expiresInHours);
        if (!$inv['success']) {
            return $inv;
        }

        $meeting = $this->getMeeting($meetingCode);
        $title = $meeting['title'] ?? 'Visioconférence OSHDI';
        $scheduledStart = $meeting['scheduled_start'] ? date('d/m/Y à H:i', strtotime($meeting['scheduled_start'])) : 'Session en direct';
        $tz = $meeting['timezone'] ?? 'Africa/Abidjan';

        // URL absolue
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $fullInviteUrl = rtrim($baseUrl, '/') . '/' . ltrim($inv['invite_url'], '/');

        // Préparation du courriel
        $subject = "Invitation à la visioconférence : {$title} (OSHDI VisioPro)";
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: OSHDI VisioPro <visioconference@oshdi.org>\r\n";

        $htmlBody = "
        <div style='font-family:Arial, sans-serif; background:#0B1120; color:#E2E8F0; padding:24px; border-radius:12px;'>
            <h2 style='color:#F59E0B;'>Invitation Officielle OSHDI VisioPro</h2>
            <p>Bonjour <strong>" . htmlspecialchars($name ?: 'Cher Invité') . "</strong>,</p>
            <p>Vous êtes invité(e) en tant que <strong>" . ucfirst($role) . "</strong> à participer à la réunion suivante :</p>
            <div style='background:#1E293B; border-left:4px solid #F59E0B; padding:12px 16px; margin:16px 0; border-radius:4px;'>
                <h3 style='color:#FFF; margin:0 0 6px;'>" . htmlspecialchars($title) . "</h3>
                <p style='margin:4px 0; font-size:0.9rem; color:#94A3B8;'><strong>Code Réunion :</strong> <code style='color:#F59E0B;'>" . htmlspecialchars($meetingCode) . "</code></p>
                <p style='margin:4px 0; font-size:0.9rem; color:#94A3B8;'><strong>Date & Heure :</strong> " . htmlspecialchars($scheduledStart) . " (" . htmlspecialchars($tz) . ")</p>
            </div>
            <p style='margin:20px 0;'>
                <a href='" . htmlspecialchars($fullInviteUrl) . "' style='background:#D97706; color:#FFF; padding:10px 20px; text-decoration:none; border-radius:6px; font-weight:bold; display:inline-block;'>
                    Rejoindre la Réunion
                </a>
            </p>
            <p style='font-size:0.8rem; color:#64748B;'>Ce lien sécurisé expire le " . htmlspecialchars($inv['expires_at']) . ".</p>
        </div>";

        $mailSent = false;
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mailSent = @mail($email, $subject, $htmlBody, $headers);
        }

        $inv['email_sent'] = $mailSent;
        $inv['recipient_email'] = $email;

        $this->auditService->logAuditEvent($meetingCode, 'Système', 'send_invitation', [
            'email' => $email,
            'name' => $name,
            'role' => $role,
            'sent' => $mailSent
        ]);

        return $inv;
    }
}
