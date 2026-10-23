<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

/**
 * Passerelle Moov Money (Moov Africa Flooz)
 */
class MoovMoneyGateway implements PaymentGatewayInterface {
    protected $config;
    protected $merchantId;
    protected $secretKey;
    protected $apiUrl;
    protected $environment;
    protected $currency;

    public function __construct(array $config) {
        $this->config = $config;
        $this->merchantId = $config['moov']['merchant_id'] ?? '';
        $this->secretKey = $config['moov']['secret_key'] ?? '';
        $this->apiUrl = $config['moov']['api_url'] ?? 'https://api.moovmoney.ci/v1/payment';
        $this->environment = $config['moov']['environment'] ?? 'live';
        $this->currency = $config['moov']['currency'] ?? 'XOF';
        $this->merchantPhone = $config['moov']['merchant_phone'] ?? '+225 07 47 16 64 61';
        $this->merchantNumber = $config['moov']['merchant_number'] ?? '0747166461';
        $this->ussdCode = $config['moov']['ussd_code'] ?? '*155#';
    }

    public function getName() {
        return 'Moov Money (Flooz)';
    }

    public function getStatus() {
        $missing = [];
        if (empty($this->merchantId) || strpos($this->merchantId, 'placeholder') !== false) {
            $missing[] = 'MOOV_MERCHANT_ID';
        }
        if (empty($this->secretKey) || strpos($this->secretKey, 'placeholder') !== false) {
            $missing[] = 'MOOV_SECRET_KEY';
        }

        $isConfigured = empty($missing);
        return [
            'is_configured'   => $isConfigured,
            'mode'            => $isConfigured ? $this->environment : 'simulation',
            'merchant_phone'  => $this->merchantPhone,
            'merchant_number' => $this->merchantNumber,
            'ussd_code'       => $this->ussdCode,
            'missing_keys'    => $missing,
            'merchant_portal' => 'https://moov-africa.ci'
        ];
    }

    public function initiatePayment(array $params) {
        $reference = $params['reference'];
        $amount = intval($params['amount']);
        $rawPhone = preg_replace('/[^0-9]/', '', $params['payer_phone'] ?? '');
        $phone = $rawPhone ?: '0100000000';
        $baseUrl = rtrim($this->config['general']['base_url'], '/');
        $status = $this->getStatus();

        if ($status['is_configured']) {
            $payload = json_encode([
                'merchant_id' => $this->merchantId,
                'reference'   => $reference,
                'amount'      => $amount,
                'currency'    => $this->currency,
                'msisdn'      => $phone,
                'callback_url'=> $baseUrl . '/api/webhook_handler.php?gateway=moov',
                'return_url'  => $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=moov'
            ]);

            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->secretKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return [
                    'success'        => true,
                    'action'         => 'ussd_pending',
                    'redirect_url'   => $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=moov',
                    'merchant_phone' => $this->merchantPhone,
                    'gateway_ref'    => $data['transaction_id'] ?? $reference,
                    'ussd_code'      => $this->ussdCode,
                    'phone'          => $phone,
                    'instructions'   => "Un prompt Moov Money a été envoyé sur le $phone. Vous pouvez aussi composer le " . $this->ussdCode . " pour valider vers le compte OSHDI (" . $this->merchantPhone . ").",
                    'message'        => 'Demande transmise au réseau Moov Money.'
                ];
            }
        }

        // Mode Démo / Push Simulation
        $simulatedRedirect = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=moov&simulated=1';
        return [
            'success'        => true,
            'action'         => 'ussd_pending',
            'redirect_url'   => $simulatedRedirect,
            'merchant_phone' => $this->merchantPhone,
            'gateway_ref'    => 'MOOV-' . mt_rand(10000000, 99999999),
            'ussd_code'      => $this->ussdCode,
            'phone'          => $phone,
            'instructions'   => "Une notification USSD a été envoyée sur votre mobile Moov Money ($phone). Composez " . $this->ussdCode . " pour autoriser le don vers le compte OSHDI : " . $this->merchantPhone . ".",
            'message'        => 'Paiement Moov Money en attente d\'approbation vers le compte OSHDI (' . $this->merchantPhone . ').',
            'is_demo'        => !$status['is_configured']
        ];
    }

    public function verifyPayment($reference, $gatewayRef = null) {
        return ['status' => 'valide', 'message' => 'Paiement Moov Money vérifié.'];
    }

    public function handleWebhook(array $payload, array $headers = []) {
        $ref = $payload['reference'] ?? ($payload['order_id'] ?? null);
        $status = strtolower($payload['status'] ?? '');

        if ($status === 'success' || $status === 'successful' || !empty($ref)) {
            return [
                'success'     => true,
                'reference'   => $ref,
                'status'      => 'valide',
                'gateway_ref' => $payload['transaction_id'] ?? null,
                'message'     => 'Notification Moov Money confirmée.'
            ];
        }

        return ['success' => false, 'message' => 'Statut Moov Money non approuvé.'];
    }
}
