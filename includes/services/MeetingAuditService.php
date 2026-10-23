<?php
/**
 * ====================================================================
 * OSHDI VISIOPRO - SERVICE D'AUDIT ET D'HISTORIQUE (MeetingAuditService)
 * ====================================================================
 * Enregistrement de la présence des participants, des durées d'appel,
 * et du journal d'audit de sécurité des réunions (actions modérateur).
 * Compatible MySQL & SQLite avec transactions PDO.
 * ====================================================================
 */

class MeetingAuditService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Enregistre l'arrivée d'un participant dans la réunion
     */
    public function logParticipantJoin($meetingCode, $userName, $role = 'participant', $userId = null) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

            $stmt = $this->pdo->prepare("INSERT INTO classroom_meeting_participants 
                (meeting_code, user_id, user_name, role, joined_at, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)");
            $stmt->execute([$meetingCode, $userId, $userName, $role, $ip, $ua]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Enregistre le départ d'un participant et calcule sa durée de présence
     */
    public function logParticipantLeave($participantSessionId) {
        if (empty($participantSessionId)) return;
        try {
            $stmt = $this->pdo->prepare("UPDATE classroom_meeting_participants 
                SET left_at = CURRENT_TIMESTAMP,
                    duration_seconds = MAX(0, CAST((strftime('%s', 'now') - strftime('%s', joined_at)) AS INTEGER))
                WHERE id = ? AND left_at IS NULL");
            $stmt->execute([$participantSessionId]);
        } catch (Exception $e) {
            // Fallback MySQL si strftime non reconnu
            try {
                $upMy = $this->pdo->prepare("UPDATE classroom_meeting_participants 
                    SET left_at = CURRENT_TIMESTAMP,
                        duration_seconds = TIMESTAMPDIFF(SECOND, joined_at, NOW())
                    WHERE id = ? AND left_at IS NULL");
                $upMy->execute([$participantSessionId]);
            } catch (Exception $e2) {}
        }
    }

    /**
     * Enregistre un événement sensible dans le journal d'audit
     */
    public function logAuditEvent($meetingCode, $userName, $action, $details = '') {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $this->pdo->prepare("INSERT INTO classroom_audit_logs 
                (meeting_code, user_name, action, details, ip_address, created_at) 
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$meetingCode, $userName, $action, is_array($details) ? json_encode($details) : strval($details), $ip]);
        } catch (Exception $e) {}
    }

    /**
     * Récupère l'historique des participants d'une réunion
     */
    public function getMeetingParticipantsHistory($meetingCode, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM classroom_meeting_participants WHERE meeting_code = ? ORDER BY joined_at DESC LIMIT ?");
            $stmt->bindValue(1, $meetingCode, PDO::PARAM_STR);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupère le journal d'audit des actions pour les administrateurs
     */
    public function getMeetingAuditLogs($meetingCode = null, $limit = 50) {
        try {
            if ($meetingCode) {
                $stmt = $this->pdo->prepare("SELECT * FROM classroom_audit_logs WHERE meeting_code = ? ORDER BY created_at DESC LIMIT ?");
                $stmt->bindValue(1, $meetingCode, PDO::PARAM_STR);
                $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $stmt = $this->pdo->prepare("SELECT * FROM classroom_audit_logs ORDER BY created_at DESC LIMIT ?");
                $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
                $stmt->execute();
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
