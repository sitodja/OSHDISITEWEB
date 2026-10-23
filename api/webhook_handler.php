<?php
// Point d'entrée universel pour les Webhooks (IPN) des 7 réseaux de paiement OSHDI
// Format des URLs : /api/webhook_handler.php?gateway={wave|orange|mtn|moov|card|paypal|crypto}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/payments/PaymentManager.php';

$gatewayName = strtolower(trim($_GET['gateway'] ?? ''));

if (empty($gatewayName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètre gateway manquant.']);
    exit;
}

// Récupération des données brutes
$rawInput = file_get_contents('php://input');
$jsonPayload = json_decode($rawInput, true);
$payload = is_array($jsonPayload) ? $jsonPayload : $_POST;

// Récupération des en-têtes HTTP
$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
}

// Journalisation de sécurité
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logEntry = "[" . date('Y-m-d H:i:s') . "] GATEWAY: $gatewayName\nIP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\nPAYLOAD: " . $rawInput . "\n\n";
@file_put_contents($logDir . '/webhooks.log', $logEntry, FILE_APPEND);

try {
    $manager = PaymentManager::getInstance();
    $gateway = $manager->getGateway($gatewayName);
    $result = $gateway->handleWebhook($payload, $headers);

    if (!empty($result['success']) && !empty($result['reference'])) {
        $confirmed = $manager->confirmTransaction(
            $result['reference'],
            $result['gateway_ref'] ?? null,
            ['webhook_result' => $result, 'raw' => $payload]
        );

        http_response_code(200);
        echo json_encode([
            'status'    => 'ok',
            'message'   => 'Webhook traité et transaction validée.',
            'reference' => $result['reference'],
            'confirmed' => $confirmed
        ]);
        exit;
    }

    // Réponse neutre HTTP 200 pour éviter que les passerelles ne renvoient la requête en boucle
    http_response_code(200);
    echo json_encode([
        'status'  => 'received',
        'message' => $result['message'] ?? 'Notification reçue mais non traitée.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Erreur traitement webhook : ' . $e->getMessage()
    ]);
}
