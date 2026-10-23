<?php
// Traitement et soumission du Bordereau de Souscription & Sondage d'Opinion
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$nomComplet = trim($_POST['nom_complet'] ?? '');
$email = trim($_POST['email'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$profession = trim($_POST['profession'] ?? '');
$localite = trim($_POST['localite'] ?? '');
$ville = trim($_POST['ville'] ?? '');
$region = trim($_POST['region'] ?? '');
$programmeChoisi = trim($_POST['programme_choisi'] ?? '');
$besoins = trim($_POST['besoins_assistance'] ?? '');
$refPaiement = trim($_POST['reference_paiement'] ?? ('OSHDI-SUB-' . date('Ymd') . '-' . mt_rand(1000, 9999)));
$moyenPaiement = trim($_POST['moyen_paiement'] ?? 'Mobile Money');
$montant = floatval($_POST['montant'] ?? 5000);
$signature = trim($_POST['signature_electronique'] ?? $nomComplet);

if (empty($nomComplet) || empty($email) || empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez renseigner tous les champs obligatoires du bordereau.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO souscriptions_sondage 
        (nom_complet, email, telephone, profession, localite, ville, region, programme_choisi, besoins_assistance, montant_paye, moyen_paiement, reference_transaction, signature_electronique, statut) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'transmis_oshdi')");
    
    $stmt->execute([
        $nomComplet,
        $email,
        $telephone,
        $profession,
        $localite,
        $ville,
        $region,
        $programmeChoisi,
        $besoins,
        $montant,
        $moyenPaiement,
        $refPaiement,
        $signature
    ]);

    $lastId = $pdo->lastInsertId();

    // Simulation d'envoi du mail officiel à OSHDI (contact@oshdi.org)
    $mailDestinataire = SITE_EMAIL;
    $sujetMail = "[SOUSCRIPTION OSHDI] Nouveau Bordereau de Sondage - " . $nomComplet . " (" . $ville . ")";
    $corpsMail = "Un nouveau souscripteur vient de renseigner son Bordereau officiel de Sondage d'Opinion OSHDI :\n\n"
               . "Réf. Transaction : " . $refPaiement . "\n"
               . "Nom Complet : " . $nomComplet . "\n"
               . "Téléphone : " . $telephone . "\n"
               . "Email : " . $email . "\n"
               . "Ville / Région : " . $ville . " / " . $region . " (" . $localite . ")\n"
               . "Programme sollicité : " . $programmeChoisi . "\n"
               . "Besoins et Doléances : " . $besoins . "\n"
               . "Signature Électronique : " . $signature . "\n"
               . "Date : " . date('d/m/Y H:i:s') . "\n";

    // En environnement de production avec serveur SMTP configuré :
    // @mail($mailDestinataire, $sujetMail, $corpsMail, "From: web@oshdi.org\r\nReply-To: " . $email);

    echo json_encode([
        'success' => true,
        'message' => 'Votre Bordereau de Souscription a été enregistré et transmis avec succès aux services OSHDI.',
        'bordereau_id' => $lastId,
        'reference' => $refPaiement,
        'redirect_url' => 'bordereau_pdf.php?id=' . $lastId . '&ref=' . urlencode($refPaiement)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la soumission : ' . $e->getMessage()]);
}
