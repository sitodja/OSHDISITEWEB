<?php
// API de vérification d'état des transactions en temps réel
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/payments/PaymentManager.php';

$ref = trim($_GET['ref'] ?? ($_POST['ref'] ?? ''));

if (empty($ref)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Référence de transaction requise.']);
    exit;
}

try {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM transactions_paiements WHERE reference = ?");
    $stmt->execute([$ref]);
    $tx = $stmt->fetch();

    if (!$tx) {
        // Vérifier si le don existe déjà directement dans dons_offrandes
        $donStmt = $pdo->prepare("SELECT * FROM dons_offrandes WHERE reference_recu = ?");
        $donStmt->execute([$ref]);
        $don = $donStmt->fetch();

        if ($don) {
            echo json_encode([
                'success'      => true,
                'status'       => 'valide',
                'reference'    => $ref,
                'redirect_url' => 'recu_don.php?ref=' . urlencode($ref),
                'message'      => 'Transaction validée avec succès.'
            ]);
            exit;
        }

        echo json_encode([
            'success' => false,
            'status'  => 'introuvable',
            'message' => 'Transaction non trouvée.'
        ]);
        exit;
    }

    if ($tx['statut'] === 'valide') {
        $baseUrl = rtrim($pdo ? '' : '', '/');
        $redirectUrl = 'recu_don.php?ref=' . urlencode($ref);
        if (($tx['type_commande'] ?? '') === 'formation') {
            $redirectUrl = 'lecteur_cours.php?id=' . intval($tx['item_id'] ?? 0) . '&ref=' . urlencode($ref);
        }

        echo json_encode([
            'success'      => true,
            'status'       => 'valide',
            'reference'    => $ref,
            'amount'       => $tx['montant'],
            'method'       => $tx['moyen_paiement'],
            'redirect_url' => $redirectUrl,
            'message'      => 'Transaction validée.'
        ]);
        exit;
    }

    // Interrogation de la passerelle si statut encore en_attente
    $manager = PaymentManager::getInstance();
    $gateway = $manager->getGateway($tx['moyen_paiement']);
    $verifyRes = $gateway->verifyPayment($ref, $tx['passerelle_ref']);

    if (($verifyRes['status'] ?? '') === 'valide') {
        $manager->confirmTransaction($ref, $tx['passerelle_ref'], $verifyRes);
        echo json_encode([
            'success'      => true,
            'status'       => 'valide',
            'reference'    => $ref,
            'redirect_url' => 'recu_don.php?ref=' . urlencode($ref),
            'message'      => 'Paiement confirmé par le réseau.'
        ]);
        exit;
    }

    echo json_encode([
        'success'   => true,
        'status'    => $tx['statut'],
        'reference' => $ref,
        'message'   => $verifyRes['message'] ?? 'En attente de confirmation sur le réseau de paiement.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la vérification : ' . $e->getMessage()
    ]);
}
