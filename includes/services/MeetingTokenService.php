<?php
/**
 * ====================================================================
 * OSHDI VISIOPRO - SERVICE DE JETONS DE RÉUNION (MeetingTokenService)
 * ====================================================================
 * Génération et vérification cryptographique de jetons d'accès aux
 * visioconférences OSHDI (Signature HMAC-SHA256, expiration, permissions).
 * Compatible PHP 7.4 à PHP 8.x natif sans dépendance externe.
 * ====================================================================
 */

class MeetingTokenService {
    // Clé secrète de signature (peut être surchargée via .env ou constante)
    private static $secretKey = 'OSHDI_VISIOPRO_SECURE_HMAC_KEY_2026_@DEV!';

    /**
     * Génère un jeton d'accès sécurisé pour une réunion
     *
     * @param string $meetingCode Code de la réunion (ex: OSHDI-ROOM-702)
     * @param string $userName Nom du participant
     * @param string $role Rôle ('host', 'co-host', 'presenter', 'participant')
     * @param int|null $userId ID utilisateur s'il est connecté
     * @param int $durationSeconds Durée de validité du jeton (défaut : 8 heures)
     * @return string Jeton signé en base64url
     */
    public static function generateAccessToken(
        $meetingCode,
        $userName,
        $role = 'participant',
        $userId = null,
        $durationSeconds = 28800
    ) {
        $now = time();
        $payload = [
            'meeting_code' => $meetingCode,
            'user_name'    => $userName,
            'user_id'      => $userId,
            'role'         => $role,
            'iat'          => $now,
            'exp'          => $now + $durationSeconds,
            'nonce'        => bin2hex(random_bytes(8))
        ];

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $b64Header  = self::base64UrlEncode(json_encode($header));
        $b64Payload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $b64Header . '.' . $b64Payload, self::$secretKey, true);
        $b64Signature = self::base64UrlEncode($signature);

        return $b64Header . '.' . $b64Payload . '.' . $b64Signature;
    }

    /**
     * Vérifie et décode un jeton d'accès de réunion
     *
     * @param string $token
     * @param string|null $expectedMeetingCode
     * @return array|false Payload décodé si valide, false sinon
     */
    public static function verifyAccessToken($token, $expectedMeetingCode = null) {
        if (empty($token) || !is_string($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($b64Header, $b64Payload, $b64Signature) = $parts;

        // Vérification de la signature HMAC
        $expectedSignature = hash_hmac('sha256', $b64Header . '.' . $b64Payload, self::$secretKey, true);
        $actualSignature = self::base64UrlDecode($b64Signature);

        if (!hash_equals($expectedSignature, $actualSignature)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($b64Payload), true);
        if (!$payload || !is_array($payload)) {
            return false;
        }

        // Vérification de l'expiration
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return false; // Jeton expiré
        }

        // Vérification du code de réunion si spécifié
        if ($expectedMeetingCode !== null && isset($payload['meeting_code'])) {
            if (strcasecmp($payload['meeting_code'], $expectedMeetingCode) !== 0) {
                return false;
            }
        }

        return $payload;
    }

    /**
     * Encode en Base64 URL-safe (sans +, / ni =)
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Décode du Base64 URL-safe
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
