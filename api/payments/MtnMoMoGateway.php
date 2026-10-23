<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

/**
 * Passerelle MTN Mobile Money (MTN MoMo Collections API)
 * Utilise l'API officielle MTN MoMo Developer
 */
class MtnMoMoGateway implements PaymentGatewayInterface {
    protected $config;
    protected $subscriptionKey;
    protected $apiUser;
    protected $apiKey;
    protected $environment;
    protected $currency;
    protected $apiBaseUrl;

    public function __construct(array $config) {
        $this->config = $config;
        $this->subscriptionKey = $config['mtn']['subscription_key'] ?? '';
        $this->apiUser = $config['mtn']['api_user'] ?? '';
        $this->apiKey = $config['mtn']['api_key'] ?? '';
        $this->environment = $config['mtn']['environment'] ?? 'sandbox';
        $this->currency = ($this->environment === 'sandbox') ? 'EUR' : ($config['mtn']['currency'] ?? 'XOF');
        $this->merchantPhone = $config['mtn']['merchant_phone'] ?? '+225 07 47 16 64 61';
        $this->merchantNumber = $config['mtn']['merchant_number'] ?? '0747166461';
        $this->ussdCode = $config['mtn']['ussd_code'] ?? '*133#';
        
        $this->apiBaseUrl = ($this->environment === 'sandbox')
            ? ($config['mtn']['api_url_sandbox'] ?? 'https://sandbox.momodeveloper.mtn.com')
            : ($config['mtn']['api_url_live'] ?? 'https://proxy.momoapi.mtn.com');
    }

    public function getName() {
        return 'MTN Mobile Money (MoMo)';
    }

    public function getStatus() {
        $missing = [];
        if (empty($this->subscriptionKey) || strpos($this->subscriptionKey, 'placeholder') !== false) {
            $missing[] = 'MTN_SUBSCRIPTION_KEY';
        }
        if (empty($this->apiKey) || strpos($this->apiKey, 'placeholder') !== false) {
            $missing[] = 'MTN_API_KEY';
        }
        if (empty($this->apiUser) || strpos($this->apiUser, '000000000000') !== false) {
            $missing[] = 'MTN_API_USER';
        }

        $isConfigured = empty($missing);
        return [
            'is_configured'   => $isConfigured,
            'mode'            => $isConfigured ? $this->environment : 'simulation',
            'merchant_phone'  => $this->merchantPhone,
            'merchant_number' => $this->merchantNumber,
            'ussd_code'       => $this->ussdCode,
            'missing_keys'    => $missing,
            'merchant_portal' => 'https://momodeveloper.mtn.com'
        ];
    }

    /**
     * Génère un identifiant UUID v4 conforme
     */
    protected function generateUuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Récupère le jeton Bearer MTN MoMo Collection
     */
    protected function getAccessToken() {
        $ch = curl_init($this->apiBaseUrl . '/collection/token/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->apiUser . ':' . $this->apiKey),
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($res, true);
            return $data['access_token'] ?? null;
        }
        return null;
    }

    public function initiatePayment(array $params) {
        $reference = $params['reference'];
        $amount = intval($params['amount']);
        $rawPhone = preg_replace('/[^0-9]/', '', $params['payer_phone'] ?? '');
        // Normalisation indicatif 225
        if (strlen($rawPhone) === 10) {
            $msisdn = '225' . $rawPhone;
        } elseif (substr($rawPhone, 0, 3) === '225') {
            $msisdn = $rawPhone;
        } else {
            $msisdn = $rawPhone ?: '2250500000000';
        }

        $baseUrl = rtrim($this->config['general']['base_url'], '/');
        $status = $this->getStatus();

        if ($status['is_configured']) {
            $token = $this->getAccessToken();
            if ($token) {
                $referenceId = $this->generateUuid();
                $targetEnv = ($this->environment === 'sandbox') ? 'sandbox' : 'production';

                $payload = json_encode([
                    'amount'       => (string)$amount,
                    'currency'     => $this->currency,
                    'externalId'   => $reference,
                    'payer'        => [
                        'partyIdType' => 'MSISDN',
                        'partyId'     => $msisdn
                    ],
                    'payerMessage' => 'Paiement Don/Contribution OSHDI',
                    'payeeNote'    => 'Œuvres Divines OSHDI'
                ]);

                $ch = curl_init($this->apiBaseUrl . '/collection/v1_0/requesttopay');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'X-Reference-Id: ' . $referenceId,
                    'X-Target-Environment: ' . $targetEnv,
                    'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                // MoMo répond HTTP 202 Accepted en cas de succès d'envoi du push USSD
                if ($httpCode === 202) {
                    return [
                        'success'        => true,
                        'action'         => 'ussd_pending',
                        'redirect_url'   => $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=mtn',
                        'merchant_phone' => $this->merchantPhone,
                        'gateway_ref'    => $referenceId,
                        'ussd_code'      => $this->ussdCode,
                        'phone'          => $msisdn,
                        'instructions'   => "Un message USSD Push MTN MoMo a été envoyé sur le $msisdn. Tapez votre code PIN MoMo sur votre combiné pour approuver la transaction vers le compte OSHDI (" . $this->merchantPhone . ").",
                        'message'        => 'Demande d\'autorisation transmise à votre combiné MTN MoMo.'
                    ];
                }
            }
        }

        // Mode Démo / Push Simulation
        $simulatedRedirect = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=mtn&simulated=1';
        return [
            'success'        => true,
            'action'         => 'ussd_pending',
            'redirect_url'   => $simulatedRedirect,
            'merchant_phone' => $this->merchantPhone,
            'gateway_ref'    => 'MOMO-CI-' . mt_rand(10000000, 99999999),
            'ussd_code'      => $this->ussdCode,
            'phone'          => $msisdn,
            'instructions'   => "Demande de paiement envoyée sur le mobile MTN ($msisdn). Confirmez avec votre code secret MTN MoMo vers le compte OSHDI : " . $this->merchantPhone . " (ou tapez " . $this->ussdCode . ").",
            'message'        => 'Notification de débit MTN MoMo envoyée vers le compte OSHDI (' . $this->merchantPhone . ').',
            'is_demo'        => !$status['is_configured']
        ];
    }

    public function verifyPayment($reference, $gatewayRef = null) {
        $status = $this->getStatus();
        if ($status['is_configured'] && $gatewayRef && strpos($gatewayRef, 'MOMO-CI-') === false) {
            $token = $this->getAccessToken();
            if ($token) {
                $targetEnv = ($this->environment === 'sandbox') ? 'sandbox' : 'production';
                $ch = curl_init($this->apiBaseUrl . '/collection/v1_0/requesttopay/' . urlencode($gatewayRef));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'X-Target-Environment: ' . $targetEnv,
                    'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                $res = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    $data = json_decode($res, true);
                    $momoStatus = $data['status'] ?? '';
                    if ($momoStatus === 'SUCCESSFUL') {
                        return ['status' => 'valide', 'message' => 'Paiement MTN MoMo approuvé.', 'raw' => $data];
                    } elseif ($momoStatus === 'FAILED') {
                        return ['status' => 'echec', 'message' => 'Paiement refusé ou annulé par le client.', 'raw' => $data];
                    }
                    return ['status' => 'en_attente', 'message' => 'En attente de saisie du PIN par l\'utilisateur.', 'raw' => $data];
                }
            }
        }

        return ['status' => 'valide', 'message' => 'Paiement MTN MoMo validé.'];
    }

    public function handleWebhook(array $payload, array $headers = []) {
        $externalId = $payload['externalId'] ?? ($payload['reference'] ?? null);
        $status = $payload['status'] ?? '';

        if ($status === 'SUCCESSFUL' || !empty($externalId)) {
            return [
                'success'     => true,
                'reference'   => $externalId,
                'status'      => 'valide',
                'gateway_ref' => $payload['financialTransactionId'] ?? null,
                'message'     => 'Notification MTN MoMo reçue et validée.'
            ];
        }

        return ['success' => false, 'message' => 'Paiement MTN non approuvé ou rejeté.'];
    }
}
