<?php
// API de soumission du Hash de transaction Cryptomonnaie (TxID)
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/payments/PaymentManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode POST requise.']);
    exit;
}

$ref = trim($_POST['reference'] ?? '');
$txHash = trim($_POST['tx_hash'] ?? '');
$coin = trim($_POST['crypto_coin'] ?? 'USDT_TRC20');

if (empty($ref) || empty($txHash)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Référence et Hash de transaction requis.']);
    exit;
}

try {
    global $pdo;
    $manager = PaymentManager::getInstance();

    // Mise à jour de la transaction avec le Hash Blockchain
    $stmt = $pdo->prepare("
        UPDATE transactions_paiements 
        SET crypto_tx_hash = ?, crypto_devise = ?, details_reponse = json_set(COALESCE(details_reponse, '{}'), '$.submitted_tx_hash', ?) 
        WHERE reference = ?
    ");
    
    // SQLite compatible fallback si json_set non dispo
    try {
        $stmt->execute([$txHash, $coin, $txHash, $ref]);
    } catch (Exception $e) {
        $stmt2 = $pdo->prepare("UPDATE transactions_paiements SET crypto_tx_hash = ?, crypto_devise = ? WHERE reference = ?");
        $stmt2->execute([$txHash, $coin, $ref]);
    }

    // Confirmation automatique de la transaction
    $manager->confirmTransaction($ref, 'CRYPTO-' . substr($txHash, 0, 16), [
        'tx_hash' => $txHash,
        'coin'    => $coin,
        'status'  => 'valide'
    ]);

    echo json_encode([
        'success'      => true,
        'message'      => 'Hash de transaction enregistré avec succès ! Votre don est validé.',
        'reference'    => $ref,
        'redirect_url' => 'recu_don.php?ref=' . urlencode($ref)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
