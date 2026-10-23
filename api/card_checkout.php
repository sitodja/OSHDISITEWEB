<?php
/**
 * ====================================================================
 * OSHDI - API REST DÉDIÉE : CARTE BANCAIRE (VISA / MASTERCARD)
 * ====================================================================
 * Support & Contact marchand : +225 07 47 16 64 61
 * Protocole : Stripe Checkout 3D-Secure
 * Webhook URL : https://votresite.org/api/webhook_handler.php?gateway=card
 * ====================================================================
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/payments/PaymentManager.php';

$manager = PaymentManager::getInstance();
$gateway = $manager->getGateway('card');
$config = $manager->getConfig();

// GET : Retourne l'état, les paramètres du compte marchand et les URLs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $gateway->getStatus();
    $baseUrl = rtrim($config['general']['base_url'], '/');

    echo json_encode([
        'network'          => 'Carte Bancaire (Visa / Mastercard)',
        'gateway_key'      => 'card',
        'merchant_phone'   => '+225 07 47 16 64 61',
        'statement_name'   => 'OSHDI DIVINE',
        'currency'         => 'XOF',
        'is_ready'         => $status['is_configured'],
        'mode'             => $status['mode'],
        'webhook_url'      => $baseUrl . '/api/webhook_handler.php?gateway=card',
        'merchant_portal'  => 'https://dashboard.stripe.com/apikeys',
        'usage'            => [
            'method' => 'POST',
            'endpoint' => $baseUrl . '/api/card_checkout.php',
            'required_fields' => ['amount', 'payer_name', 'payer_email'],
            'optional_fields' => ['payer_phone', 'item_title', 'type']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// POST : Initie une session de paiement par Carte Bancaire 3D-Secure
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $input = json_decode($raw, true) ?: [];
        }
    }

    $amount = floatval($input['amount'] ?? 0);
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Le montant doit être supérieur à 0 FCFA.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input['payment_method'] = 'card';
    if (empty($input['payer_phone'])) {
        $input['payer_phone'] = '+225 07 47 16 64 61';
    }

    try {
        $result = $manager->initiate($input);
        $result['merchant_phone'] = '+225 07 47 16 64 61';
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur API Carte Bancaire : ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode HTTP non supportée.']);
