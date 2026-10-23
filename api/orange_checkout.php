<?php
/**
 * ====================================================================
 * OSHDI - API REST DÉDIÉE : ORANGE MONEY
 * ====================================================================
 * Compte marchand associé : +225 07 47 16 64 61 (0747166461)
 * Webhook URL : https://votresite.org/api/webhook_handler.php?gateway=orange
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
$gateway = $manager->getGateway('orange');
$config = $manager->getConfig();

// GET : Retourne l'état, les paramètres du compte marchand et les URLs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $gateway->getStatus();
    $baseUrl = rtrim($config['general']['base_url'], '/');

    echo json_encode([
        'network'          => 'Orange Money',
        'gateway_key'      => 'orange',
        'merchant_phone'   => '+225 07 47 16 64 61',
        'merchant_number'  => '0747166461',
        'ussd_code'        => '#144*82#',
        'currency'         => 'XOF',
        'is_ready'         => $status['is_configured'],
        'mode'             => $status['mode'],
        'webhook_url'      => $baseUrl . '/api/webhook_handler.php?gateway=orange',
        'merchant_portal'  => 'https://developer.orange.com',
        'usage'            => [
            'method' => 'POST',
            'endpoint' => $baseUrl . '/api/orange_checkout.php',
            'required_fields' => ['amount', 'payer_name', 'payer_phone'],
            'optional_fields' => ['payer_email', 'item_title', 'type']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// POST : Initie une demande de paiement Orange Money
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

    $input['payment_method'] = 'orange';

    try {
        $result = $manager->initiate($input);
        $result['merchant_phone'] = '+225 07 47 16 64 61';
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur API Orange Money : ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode HTTP non supportée.']);
