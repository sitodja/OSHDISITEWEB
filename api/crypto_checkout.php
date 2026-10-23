<?php
/**
 * ====================================================================
 * OSHDI - API REST DÉDIÉE : CRYPTOMONNAIES (USDT, BTC, ETH)
 * ====================================================================
 * Ligne WhatsApp & Support marchand : +225 07 47 16 64 61
 * Portefeuilles : Tether USDT TRC-20, Bitcoin BTC, Ethereum ETH
 * Webhook URL : https://votresite.org/api/webhook_handler.php?gateway=crypto
 * Soumission TxID : https://votresite.org/api/crypto_submit_hash.php
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
$gateway = $manager->getGateway('crypto');
$config = $manager->getConfig();

// GET : Retourne l'état des portefeuilles, adresses officielles et cours
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $gateway->getStatus();
    $baseUrl = rtrim($config['general']['base_url'], '/');

    echo json_encode([
        'network'            => 'Cryptomonnaies (USDT, BTC, ETH)',
        'gateway_key'        => 'crypto',
        'merchant_phone'     => '+225 07 47 16 64 61',
        'whatsapp_proof_url' => 'https://wa.me/2250747166461',
        'is_ready'           => true,
        'mode'               => $status['mode'],
        'official_wallets'   => $config['crypto']['official_wallets'] ?? [],
        'webhook_url'        => $baseUrl . '/api/webhook_handler.php?gateway=crypto',
        'tx_submit_url'      => $baseUrl . '/api/crypto_submit_hash.php',
        'usage'              => [
            'method' => 'POST',
            'endpoint' => $baseUrl . '/api/crypto_checkout.php',
            'required_fields' => ['amount', 'payer_name', 'crypto_coin'],
            'crypto_coins' => ['USDT_TRC20', 'BTC', 'ETH'],
            'optional_fields' => ['payer_phone', 'payer_email', 'item_title']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// POST : Génère les coordonnées de règlement crypto (QR Code, montant converti, adresse)
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

    $input['payment_method'] = 'crypto';
    if (empty($input['crypto_coin'])) {
        $input['crypto_coin'] = 'USDT_TRC20';
    }
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
            'message' => 'Erreur API Cryptomonnaie : ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Méthode HTTP non supportée.']);
