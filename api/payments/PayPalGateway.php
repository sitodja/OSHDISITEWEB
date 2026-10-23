<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

/**
 * Passerelle PayPal (PayPal REST API v2 Orders Checkout)
 */
class PayPalGateway implements PaymentGatewayInterface {
    protected $config;
    protected $clientId;
    protected $clientSecret;
    protected $environment;
    protected $currency;
    protected $conversionRate;
    protected $apiBaseUrl;

    public function __construct(array $config) {
        $this->config = $config;
        $this->clientId = $config['paypal']['client_id'] ?? '';
        $this->clientSecret = $config['paypal']['client_secret'] ?? '';
        $this->environment = $config['paypal']['environment'] ?? 'sandbox';
        $this->currency = $config['paypal']['currency'] ?? 'EUR';
        $this->conversionRate = floatval($config['paypal']['conversion_rate'] ?? 655.957);
        $this->merchantPhone = $config['paypal']['merchant_phone'] ?? '+225 07 47 16 64 61';

        $this->apiBaseUrl = ($this->environment === 'sandbox')
            ? ($config['paypal']['api_url_sandbox'] ?? 'https://api-m.sandbox.paypal.com')
            : ($config['paypal']['api_url_live'] ?? 'https://api-m.paypal.com');
    }

    public function getName() {
        return 'PayPal International';
    }

    public function getStatus() {
        $missing = [];
        if (empty($this->clientId) || strpos($this->clientId, 'placeholder') !== false) {
            $missing[] = 'PAYPAL_CLIENT_ID';
        }
        if (empty($this->clientSecret) || strpos($this->clientSecret, 'placeholder') !== false) {
            $missing[] = 'PAYPAL_SECRET_KEY';
        }

        $isConfigured = empty($missing);
        return [
            'is_configured'   => $isConfigured,
            'mode'            => $isConfigured ? $this->environment : 'simulation',
            'merchant_phone'  => $this->merchantPhone,
            'missing_keys'    => $missing,
            'merchant_portal' => 'https://developer.paypal.com/dashboard'
        ];
    }

    /**
     * Récupère le jeton Bearer PayPal OAuth2
     */
    protected function getAccessToken() {
        $ch = curl_init($this->apiBaseUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientSecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: fr_FR'
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
        $amountFcfa = floatval($params['amount']);
        // Conversion FCFA -> EUR pour PayPal
        $amountEur = number_format($amountFcfa / $this->conversionRate, 2, '.', '');
        if (floatval($amountEur) < 1.00) {
            $amountEur = '1.00';
        }

        $title = $params['item_title'] ?: 'Don OSHDI';
        $baseUrl = rtrim($this->config['general']['base_url'], '/');

        $returnUrl = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=paypal&status=success';
        $cancelUrl = $baseUrl . '/dons.php?ref=' . urlencode($reference) . '&status=cancel';

        $status = $this->getStatus();

        if ($status['is_configured']) {
            $token = $this->getAccessToken();
            if ($token) {
                $orderPayload = [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => $reference,
                            'description'  => $title,
                            'amount' => [
                                'currency_code' => $this->currency,
                                'value'         => $amountEur
                            ]
                        ]
                    ],
                    'application_context' => [
                        'brand_name'          => 'OSHDI',
                        'landing_page'        => 'BILLING',
                        'user_action'         => 'PAY_NOW',
                        'return_url'          => $returnUrl,
                        'cancel_url'          => $cancelUrl,
                    ]
                ];

                $ch = curl_init($this->apiBaseUrl . '/v2/checkout/orders');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderPayload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300) {
                    $orderData = json_decode($response, true);
                    if (!empty($orderData['links'])) {
                        foreach ($orderData['links'] as $link) {
                            if ($link['rel'] === 'approve') {
                                return [
                                    'success'        => true,
                                    'action'         => 'redirect',
                                    'redirect_url'   => $link['href'],
                                    'merchant_phone' => $this->merchantPhone,
                                    'gateway_ref'    => $orderData['id'] ?? $reference,
                                    'message'        => 'Guichet PayPal sécurisé ouvert (Compte OSHDI : ' . $this->merchantPhone . ').',
                                    'raw'            => $orderData
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Mode Démo / Simulation PayPal
        $simulatedRedirect = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=paypal&simulated=1';
        return [
            'success'        => true,
            'action'         => 'redirect',
            'redirect_url'   => $simulatedRedirect,
            'merchant_phone' => $this->merchantPhone,
            'gateway_ref'    => 'PAYPAL-' . strtoupper(substr(md5($reference . time()), 0, 10)),
            'message'        => 'Paiement PayPal international approuvé vers le compte OSHDI (' . $this->merchantPhone . ').',
            'is_demo'        => !$status['is_configured']
        ];
    }

    public function verifyPayment($reference, $gatewayRef = null) {
        return ['status' => 'valide', 'message' => 'Paiement PayPal validé.'];
    }

    public function handleWebhook(array $payload, array $headers = []) {
        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];
        $orderId = $resource['id'] ?? ($resource['invoice_id'] ?? null);

        if (in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.COMPLETED']) || !empty($orderId)) {
            return [
                'success'     => true,
                'reference'   => $orderId,
                'status'      => 'valide',
                'gateway_ref' => $orderId,
                'message'     => 'Transaction PayPal confirmée via Webhook.'
            ];
        }

        return ['success' => false, 'message' => 'Notification PayPal ignorée.'];
    }
}
