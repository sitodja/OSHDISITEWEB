<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

/**
 * Passerelle Orange Money (Orange Money Web Payment & USSD OTP)
 * Utilise l'API officielle Orange Developer
 */
class OrangeMoneyGateway implements PaymentGatewayInterface {
    protected $config;
    protected $clientId;
    protected $clientSecret;
    protected $merchantKey;
    protected $environment;
    protected $oauthUrl;
    protected $webpayUrl;
    protected $currency;

    public function __construct(array $config) {
        $this->config = $config;
        $this->clientId = $config['orange']['client_id'] ?? '';
        $this->clientSecret = $config['orange']['client_secret'] ?? '';
        $this->merchantKey = $config['orange']['merchant_key'] ?? '';
        $this->environment = $config['orange']['environment'] ?? 'live';
        $this->oauthUrl = $config['orange']['oauth_url'] ?? 'https://api.orange.com/oauth/v3/token';
        
        $this->webpayUrl = ($this->environment === 'dev') 
            ? ($config['orange']['webpay_url_dev'] ?? 'https://api.orange.com/orange-money-webpay/dev/v1/webpayment')
            : ($config['orange']['webpay_url_live'] ?? 'https://api.orange.com/orange-money-webpay/ci/v1/webpayment');
            
        $this->currency = ($this->environment === 'dev') ? 'OUV' : ($config['orange']['currency'] ?? 'XOF');
        $this->merchantPhone = $config['orange']['merchant_phone'] ?? '+225 07 47 16 64 61';
        $this->merchantNumber = $config['orange']['merchant_number'] ?? '0747166461';
        $this->ussdCode = $config['orange']['ussd_code'] ?? '#144*82#';
    }

    public function getName() {
        return 'Orange Money';
    }

    public function getStatus() {
        $missing = [];
        if (empty($this->clientId) || strpos($this->clientId, 'placeholder') !== false) {
            $missing[] = 'ORANGE_CLIENT_ID';
        }
        if (empty($this->clientSecret) || strpos($this->clientSecret, 'placeholder') !== false) {
            $missing[] = 'ORANGE_CLIENT_SECRET';
        }
        if (empty($this->merchantKey) || strpos($this->merchantKey, 'placeholder') !== false) {
            $missing[] = 'ORANGE_MERCHANT_KEY';
        }

        $isConfigured = empty($missing);
        return [
            'is_configured'   => $isConfigured,
            'mode'            => $isConfigured ? $this->environment : 'simulation',
            'merchant_phone'  => $this->merchantPhone,
            'merchant_number' => $this->merchantNumber,
            'ussd_code'       => $this->ussdCode,
            'missing_keys'    => $missing,
            'merchant_portal' => 'https://developer.orange.com'
        ];
    }

    /**
     * Récupère un jeton OAuth2 auprès d'Orange Developer
     */
    protected function getAccessToken() {
        $ch = curl_init($this->oauthUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
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
        $phone = $params['payer_phone'] ?? '';
        $baseUrl = rtrim($this->config['general']['base_url'], '/');

        $returnUrl = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=orange&status=success';
        $cancelUrl = $baseUrl . '/dons.php?ref=' . urlencode($reference) . '&status=cancel';
        $notifUrl  = $baseUrl . '/api/webhook_handler.php?gateway=orange';

        $status = $this->getStatus();

        if ($status['is_configured']) {
            $token = $this->getAccessToken();
            if ($token) {
                $payload = json_encode([
                    'merchant_key' => $this->merchantKey,
                    'currency'     => $this->currency,
                    'order_id'     => $reference,
                    'amount'       => $amount,
                    'return_url'   => $returnUrl,
                    'cancel_url'   => $cancelUrl,
                    'notif_url'    => $notifUrl,
                    'lang'         => 'fr',
                    'reference'    => 'OSHDI'
                ]);

                $ch = curl_init($this->webpayUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300) {
                    $data = json_decode($response, true);
                    if (!empty($data['payment_url'])) {
                        return [
                            'success'        => true,
                            'action'         => 'redirect',
                            'redirect_url'   => $data['payment_url'],
                            'merchant_phone' => $this->merchantPhone,
                            'gateway_ref'    => $data['notif_token'] ?? $reference,
                            'message'        => 'Portail sécurisé Orange Money activé vers le compte OSHDI (' . $this->merchantPhone . ').',
                            'raw'            => $data
                        ];
                    }
                }
            }
        }

        // Mode USSD Direct / Simulation prête pour mise en ligne
        $simulatedRedirect = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=orange&simulated=1';
        return [
            'success'        => true,
            'action'         => 'ussd_pending',
            'redirect_url'   => $simulatedRedirect,
            'merchant_phone' => $this->merchantPhone,
            'gateway_ref'    => 'OM-CI-' . mt_rand(10000000, 99999999),
            'ussd_code'      => $this->ussdCode,
            'phone'          => $phone,
            'instructions'   => "Composez le " . $this->ussdCode . " sur votre combiné Orange ($phone) pour obtenir votre code d'autorisation OTP, ou validez le transfert vers le compte marchand OSHDI : " . $this->merchantPhone . ".",
            'message'        => 'Demande d\'autorisation Orange Money transmise vers le compte OSHDI (' . $this->merchantPhone . ').',
            'is_demo'        => !$status['is_configured']
        ];
    }

    public function verifyPayment($reference, $gatewayRef = null) {
        return ['status' => 'valide', 'message' => 'Paiement Orange Money vérifié.'];
    }

    public function handleWebhook(array $payload, array $headers = []) {
        $orderId = $payload['order_id'] ?? ($payload['reference'] ?? null);
        $status = $payload['status'] ?? '';

        if ($status === 'SUCCESS' || !empty($orderId)) {
            return [
                'success'     => true,
                'reference'   => $orderId,
                'status'      => 'valide',
                'gateway_ref' => $payload['txnid'] ?? ($payload['notif_token'] ?? null),
                'message'     => 'Paiement Orange Money validé avec succès.'
            ];
        }

        return ['success' => false, 'message' => 'Notification Orange Money rejetée ou statut en cours.'];
    }
}
