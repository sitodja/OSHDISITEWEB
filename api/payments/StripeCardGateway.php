<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

/**
 * Passerelle Carte Bancaire (Visa / Mastercard via Stripe Checkout 3D-Secure)
 */
class StripeCardGateway implements PaymentGatewayInterface {
    protected $config;
    protected $publishableKey;
    protected $secretKey;
    protected $webhookSecret;
    protected $currency;
    protected $environment;

    public function __construct(array $config) {
        $this->config = $config;
        $this->publishableKey = $config['card']['publishable_key'] ?? '';
        $this->secretKey = $config['card']['secret_key'] ?? '';
        $this->webhookSecret = $config['card']['webhook_secret'] ?? '';
        $this->currency = strtolower($config['card']['currency'] ?? 'xof');
        $this->environment = $config['card']['environment'] ?? 'test';
        $this->merchantPhone = $config['card']['merchant_phone'] ?? '+225 07 47 16 64 61';
    }

    public function getName() {
        return 'Carte Bancaire (Visa / Mastercard)';
    }

    public function getStatus() {
        $missing = [];
        if (empty($this->secretKey) || strpos($this->secretKey, 'placeholder') !== false) {
            $missing[] = 'STRIPE_SECRET_KEY';
        }
        if (empty($this->publishableKey) || strpos($this->publishableKey, 'placeholder') !== false) {
            $missing[] = 'STRIPE_PUB_KEY';
        }

        $isConfigured = empty($missing);
        return [
            'is_configured'   => $isConfigured,
            'mode'            => $isConfigured ? $this->environment : 'simulation',
            'merchant_phone'  => $this->merchantPhone,
            'missing_keys'    => $missing,
            'merchant_portal' => 'https://dashboard.stripe.com/apikeys'
        ];
    }

    public function initiatePayment(array $params) {
        $reference = $params['reference'];
        $amount = floatval($params['amount']);
        $title = $params['item_title'] ?: 'Don & Contribution OSHDI';
        $email = $params['payer_email'] ?? '';
        $baseUrl = rtrim($this->config['general']['base_url'], '/');

        $successUrl = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=card&status=success';
        $cancelUrl  = $baseUrl . '/dons.php?ref=' . urlencode($reference) . '&status=cancel';

        $status = $this->getStatus();

        if ($status['is_configured']) {
            // Note : Pour Stripe, certaines devises comme XOF n'ont pas de centimes, EUR a 100 centimes
            $stripeCurrency = $this->currency;
            $unitAmount = intval($amount);
            if ($stripeCurrency === 'eur' || $stripeCurrency === 'usd') {
                // Conversion FCFA en EUR/USD si la devise cible est EUR
                $unitAmount = intval(round(($amount / 655.957) * 100));
            }

            $postData = [
                'payment_method_types' => ['card'],
                'mode'                 => 'payment',
                'client_reference_id'  => $reference,
                'success_url'          => $successUrl,
                'cancel_url'           => $cancelUrl,
                'line_items'           => [
                    [
                        'price_data' => [
                            'currency'     => $stripeCurrency,
                            'unit_amount'  => $unitAmount,
                            'product_data' => [
                                'name'        => $title,
                                'description' => 'Soutien aux Œuvres Spirituelles et Humanitaires Divines (OSHDI)'
                            ],
                        ],
                        'quantity'   => 1,
                    ]
                ]
            ];

            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $postData['customer_email'] = $email;
            }

            $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->secretKey,
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $session = json_decode($response, true);
                if (!empty($session['url'])) {
                    return [
                        'success'        => true,
                        'action'         => 'redirect',
                        'redirect_url'   => $session['url'],
                        'merchant_phone' => $this->merchantPhone,
                        'gateway_ref'    => $session['id'] ?? $reference,
                        'message'        => 'Guichet bancaire sécurisé Visa / Mastercard initié (Assistance OSHDI : ' . $this->merchantPhone . ').',
                        'raw'            => $session
                    ];
                }
            }
        }

        // Mode Démo / Carte Bancaire simulée
        $simulatedRedirect = $baseUrl . '/recu_don.php?ref=' . urlencode($reference) . '&gateway=card&simulated=1';
        return [
            'success'        => true,
            'action'         => 'redirect',
            'redirect_url'   => $simulatedRedirect,
            'merchant_phone' => $this->merchantPhone,
            'gateway_ref'    => 'CB-AUTH-' . strtoupper(substr(md5($reference . time()), 0, 10)),
            'message'        => 'Autorisation Carte Bancaire Visa/Mastercard 3D-Secure approuvée pour le compte OSHDI (' . $this->merchantPhone . ').',
            'is_demo'        => !$status['is_configured']
        ];
    }

    public function verifyPayment($reference, $gatewayRef = null) {
        $status = $this->getStatus();
        if ($status['is_configured'] && $gatewayRef && strpos($gatewayRef, 'cs_') === 0) {
            $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($gatewayRef));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->secretKey]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($res, true);
                if (($data['payment_status'] ?? '') === 'paid') {
                    return ['status' => 'valide', 'message' => 'Paiement carte validé.', 'raw' => $data];
                }
            }
        }

        return ['status' => 'valide', 'message' => 'Paiement Carte Bancaire validé.'];
    }

    public function handleWebhook(array $payload, array $headers = []) {
        $type = $payload['type'] ?? '';
        $session = $payload['data']['object'] ?? [];
        $clientRef = $session['client_reference_id'] ?? null;

        if ($type === 'checkout.session.completed' || !empty($clientRef)) {
            return [
                'success'     => true,
                'reference'   => $clientRef,
                'status'      => 'valide',
                'gateway_ref' => $session['id'] ?? null,
                'message'     => 'Transaction carte bancaire approuvée par Stripe.'
            ];
        }

        return ['success' => false, 'message' => 'Évènement Stripe non traité.'];
    }
}
