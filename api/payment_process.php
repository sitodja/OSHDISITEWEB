<?php
// API de traitement universel des paiements (Dons, Formations, Souscriptions, Espaces)
// Connectée au gestionnaire modulaire PaymentManager (Wave, OM, MTN, Moov, Carte, PayPal, Crypto)
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/payments/PaymentManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

$type = $_POST['type'] ?? 'don';
$amount = floatval($_POST['amount'] ?? 0);
$method = trim($_POST['payment_method'] ?? 'wave');

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Montant invalide.']);
    exit;
}

try {
    $manager = PaymentManager::getInstance();
    $result = $manager->initiate($_POST);

    // Compatibilité descendante pour les flux existants
    if (empty($result['redirect_url'])) {
        if ($type === 'formation') {
            $itemId = intval($_POST['item_id'] ?? 0);
            $result['redirect_url'] = 'lecteur_cours.php?id=' . $itemId . '&ref=' . urlencode($result['reference']);
        } elseif ($type === 'souscription') {
            $result['redirect_url'] = 'souscription.php?step=form&ref=' . urlencode($result['reference']);
        } elseif ($type === 'espace_partenaire') {
            $result['redirect_url'] = 'partenaires_espaces.php?success=1&ref=' . urlencode($result['reference']);
        } else {
            $result['redirect_url'] = 'recu_don.php?ref=' . urlencode($result['reference']);
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du traitement : ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
