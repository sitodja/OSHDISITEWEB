<?php
// API REST d'initialisation des paiements OSHDI
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/payments/PaymentManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode HTTP non autorisée. Utilisez POST.']);
    exit;
}

// Récupération des données POST (form-data ou json)
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $input = json_decode($raw, true) ?: [];
    }
}

$amount = floatval($input['amount'] ?? 0);
$method = trim($input['payment_method'] ?? 'wave');

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Montant de paiement invalide.']);
    exit;
}

try {
    $manager = PaymentManager::getInstance();
    $result = $manager->initiate($input);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'initialisation du paiement : ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
