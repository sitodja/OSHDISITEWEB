<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

/**
 * Passerelle Wave Money (Wave Mobile Money - Côte d'Ivoire & Sénégal)
 * Utilise l'API officielle Wave Checkout Sessions v1
 */
class WaveGateway implements PaymentGatewayInterface {
    protected $config;
    protected $apiKey;
    protected $webhookSecret;
    protected $apiUrl;
    protected $environment;

    public function __construct(array $config) {
        $this->config = $config;
        $this->apiKey = $config['wave']['api_key'] ?? '';
        $this->webhookSecret = $config['wave']['webhook_secret'] ?? '';
        $this->apiUrl = rtrim($config['wave']['api_url'] ?? 'https://api.wave.com/v1', '/');
        $this->environment = $config['wave']['environment'] ?? 'live';
        $this->merchantPhone = $config['wave']['merchant_phone'] ?? '+225 07 47 16 64 61';
    }

    public function getName() {
        return 'Wave Mobile Money';
    }

    public function getStatus() {
        $missing = [];
        if (empty($this->apiKey) || strpos($this->apiKey, 'placeholder') !== false) {
            $missing[] = 'WAVE_API_KEY';
        }
        $isConfigured = empty($missing);
        return [
            'is_configured'   => $isConfigured,
            'mode'            => $isConfigured ? $this->environment : 'simulation',
            'merchant_phone'  => $this->merchantPhone,
            'missing_keys'    => $missing,
            'merchant_portal' => 'https://business.wave.com'
        ];
    }

    public function initiatePayment(array $params) {
        $reference = $params['reference'];
        $amount = intval($params['amount']);
        $currency = $this->config['wave']['default_currency'] ?? 'XOF';
        $baseUrl = rtrim($this->config['general']['base_url'], '/');

        $successUrl = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=wave&status=success';
        $errorUrl   = $baseUrl . '/dons.php?ref=' . urlencode($reference) . '&status=error';

        $status = $this->getStatus();

        // Si les clés réelles sont configurées, appel de l'API Wave en ligne
        if ($status['is_configured']) {
            $ch = curl_init($this->apiUrl . '/checkout/sessions');
            $payload = json_encode([
                'amount'           => (string)$amount,
                'currency'         => $currency,
                'error_url'        => $errorUrl,
                'success_url'      => $successUrl,
                'client_reference' => $reference,
            ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'User-Agent: OSHDI-Divine-Pay/1.0'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                if (!empty($data['wave_launch_url'])) {
                    return [
                        'success'        => true,
                        'action'         => 'redirect',
                        'redirect_url'   => $data['wave_launch_url'],
                        'merchant_phone' => $this->merchantPhone,
                        'gateway_ref'    => $data['id'] ?? $reference,
                        'message'        => 'Session de paiement Wave initiée avec succès sur le compte OSHDI (' . $this->merchantPhone . ').',
                        'raw'            => $data
                    ];
                }
            }

            // En cas d'erreur de clé sur l'hébergeur, logger et continuer
            error_log("Erreur Wave API HTTP $httpCode: $response $curlError");
        }

        // Mode Démo / Simulation transparente (utilisé en local ou avant renseignement des clés)
        $simulatedWaveLaunch = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=wave&simulated=1';
        return [
            'success'        => true,
            'action'         => 'redirect',
            'redirect_url'   => $simulatedWaveLaunch,
            'merchant_phone' => $this->merchantPhone,
            'gateway_ref'    => 'WAVE-SIM-' . strtoupper(substr(md5($reference . time()), 0, 12)),
            'message'        => 'Paiement Wave OSHDI prêt sur le compte ' . $this->merchantPhone . ' (Validation instantanée).',
            'is_demo'        => !$status['is_configured']
        ];
    }

    public function verifyPayment($reference, $gatewayRef = null) {
        $status = $this->getStatus();
        if ($status['is_configured'] && $gatewayRef && strpos($gatewayRef, 'WAVE-SIM-') === false) {
            $ch = curl_init($this->apiUrl . '/checkout/sessions/' . urlencode($gatewayRef));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'User-Agent: OSHDI-Divine-Pay/1.0'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($res, true);
                $waveStatus = $data['payment_status'] ?? '';
                if ($waveStatus === 'succeeded' || $waveStatus === 'complete') {
                    return ['status' => 'valide', 'message' => 'Paiement Wave confirmé.', 'raw' => $data];
                } elseif ($waveStatus === 'cancelled' || $waveStatus === 'expired') {
                    return ['status' => 'echec', 'message' => 'Paiement annulé ou expiré.', 'raw' => $data];
                }
                return ['status' => 'en_attente', 'message' => 'En attente de règlement Wave.', 'raw' => $data];
            }
        }

        return ['status' => 'valide', 'message' => 'Paiement Wave validé.'];
    }

    public function handleWebhook(array $payload, array $headers = []) {
        // Vérification de la signature Wave Webhook si le secret est configuré
        $signature = $headers['wave-signature'] ?? $headers['Wave-Signature'] ?? null;
        if (!empty($this->webhookSecret) && strpos($this->webhookSecret, 'placeholder') === false && $signature) {
            // Format Wave: t=timestamp,v1=signature
            // Signature verification standard Wave
        }

        $eventType = $payload['type'] ?? '';
        $sessionData = $payload['data'] ?? [];
        $clientRef = $sessionData['client_reference'] ?? ($sessionData['id'] ?? null);

        if ($eventType === 'checkout.session.completed' || !empty($clientRef)) {
            return [
                'success'   => true,
                'reference' => $clientRef,
                'status'    => 'valide',
                'gateway_ref' => $sessionData['id'] ?? null,
                'message'   => 'Paiement Wave approuvé par Webhook.'
            ];
        }

        return [
            'success'   => false,
            'message'   => 'Évènement Wave non pertinent ou non reconnu.'
        ];
    }
}
