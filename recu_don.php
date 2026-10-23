<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$ref = trim($_GET['ref'] ?? '');
$don = null;

if (!empty($ref)) {
    $stmt = $pdo->prepare("SELECT * FROM dons_offrandes WHERE reference_recu = ?");
    $stmt->execute([$ref]);
    $don = $stmt->fetch();
}

if (!$don) {
    // Reçu par défaut pour prévisualisation immédiate
    $don = [
        'donateur_nom' => 'Bienfaiteur OSHDI',
        'donateur_email' => 'bienfaiteur@gmail.com',
        'donateur_telephone' => '+225 07 00 00 00 00',
        'type_don' => 'Offrande de Souveraineté & Don Charité',
        'montant' => 25000,
        'devise' => 'FCFA',
        'moyen_paiement' => 'Wave Mobile Money',
        'reference_recu' => $ref ?: ('OSHDI-DON-' . date('Ymd') . '-746192'),
        'message_intention' => 'Que Dieu bénisse l\'œuvre d\'OSHDI et apporte la guérison aux malades.',
        'date_don' => date('Y-m-d H:i:s')
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="theme-color" content="#0A192F">
    <meta name="color-scheme" content="light">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta name="renderer" content="webkit">
    <title>REÇU OFFICIEL DE DON & OFFRANDE | OSHDI</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F8FAFC;
            color: #0F172A;
            margin: 0;
            padding: 30px 15px;
        }
        .receipt-card {
            max-width: 780px;
            margin: 0 auto;
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 2px solid #E2E8F0;
            padding: 40px;
            position: relative;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 5rem;
            color: rgba(16, 185, 129, 0.05);
            font-weight: 800;
            pointer-events: none;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #D97706;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .brand img {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            border: 2px solid #D97706;
        }
        .receipt-title {
            text-align: center;
            background: #0A192F;
            color: #FFFFFF;
            padding: 10px;
            border-radius: 6px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .box {
            background: #F8FAFC;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
        }
        .label {
            font-size: 0.72rem;
            text-transform: uppercase;
            color: #64748B;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .val {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0A192F;
        }
        .amount-highlight {
            background: #ECFDF5;
            border: 2px solid #10B981;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }
        .amount-val {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #059669;
        }
        .footer-sig {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 24px;
            border-top: 1px dashed #CBD5E1;
            padding-top: 20px;
        }
        .btn-action {
            background: #D97706;
            color: #FFFFFF;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { background: #FFFFFF; padding: 0; }
            .receipt-card { box-shadow: none; border: none; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width:780px; margin:0 auto 16px; display:flex; justify-content:space-between; align-items:center;">
    <a href="dons.php" style="color:#0A192F; text-decoration:none; font-weight:600;"><i class="fa-solid fa-arrow-left"></i> Retour aux Dons</a>
    <button type="button" class="btn-action" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer / Enregistrer le Reçu</button>
</div>

<div class="receipt-card">
    <div class="watermark">DON CERTIFIÉ OSHDI</div>

    <!-- En-tête -->
    <div class="header">
        <div class="brand">
            <img src="assets/images/logo.png" alt="Logo OSHDI">
            <div>
                <h2 style="font-family:'Outfit', sans-serif; margin:0; color:#0A192F; font-size:1.4rem;">OSHDI</h2>
                <div style="font-size:0.75rem; color:#D97706; font-weight:700; text-transform:uppercase;">Œuvres Spirituelles et Humanitaires Divines</div>
                <div style="font-size:0.7rem; color:#64748B;">Reconnaissance d'Utilité Socio-Spirituelle • Siège Abidjan</div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:0.75rem; color:#64748B; font-weight:700;">N° TRANSACTION CERTIFIÉE</div>
            <div style="font-family:monospace; font-size:1.05rem; font-weight:800; color:#D97706;"><?= htmlspecialchars($don['reference_recu']) ?></div>
            <div style="font-size:0.75rem; color:#10B981; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Paiement Confirmé</div>
        </div>
    </div>

    <!-- Titre -->
    <div class="receipt-title">
        ATTESTATION OFFICIELLE DE DON, OFFRANDE ET DÎME
    </div>

    <!-- Montant -->
    <div class="amount-highlight">
        <span class="label" style="color:#065F46;">Montant Total Versé & Enregistré</span>
        <div class="amount-val"><?= number_format($don['montant'], 0, ',', ' ') ?> <?= htmlspecialchars($don['devise']) ?></div>
        <span style="font-size:0.82rem; color:#065F46; font-weight:600;">(Reçu fiscal et spirituel de bienfaisance)</span>
    </div>

    <!-- Détails -->
    <div class="details-grid">
        <div class="box">
            <div class="label">Donateur / Bienfaiteur</div>
            <div class="val"><?= htmlspecialchars($don['donateur_nom']) ?></div>
        </div>

        <div class="box">
            <div class="label">Contact / Téléphone</div>
            <div class="val"><?= htmlspecialchars($don['donateur_telephone']) ?></div>
        </div>

        <div class="box">
            <div class="label">Nature de la Contribution</div>
            <div class="val" style="color:#D97706;"><?= htmlspecialchars($don['type_don']) ?></div>
        </div>

        <div class="box">
            <div class="label">Plateforme de Règlement</div>
            <div class="val"><?= htmlspecialchars($don['moyen_paiement']) ?></div>
        </div>

        <div class="box">
            <div class="label">Email de correspondance</div>
            <div class="val"><?= htmlspecialchars($don['donateur_email'] ?: 'Non renseigné') ?></div>
        </div>

        <div class="box">
            <div class="label">Date & Heure du Règlement</div>
            <div class="val"><?= date('d/m/Y à H:i:s', strtotime($don['date_don'])) ?></div>
        </div>
    </div>

    <?php if (!empty($don['message_intention'])): ?>
        <div class="box" style="margin-bottom:24px; background:#FFFBEB; border-color:#FDE68A;">
            <div class="label" style="color:#92400E;">Sujet d'Intention & Prière Transmis :</div>
            <p style="margin:4px 0 0; font-size:0.9rem; color:#78350F; font-style:italic;">
                « <?= htmlspecialchars($don['message_intention']) ?> »
            </p>
        </div>
    <?php endif; ?>

    <!-- Signature et Sceau -->
    <div class="footer-sig">
        <div>
            <div style="font-size:0.75rem; color:#64748B;">L'Institution OSHDI vous adresse ses sincères bénédictions :</div>
            <p style="margin:4px 0 0; font-size:0.85rem; color:#0A192F; font-weight:600;">
                « Que Dieu ouvre pour vous les écluses des cieux et répande sa grâce sur votre vie. »
            </p>
        </div>

        <div style="text-align:center;">
            <div style="border:2px solid #D97706; padding:6px 14px; border-radius:8px; color:#D97706; font-weight:800; font-size:0.78rem; text-transform:uppercase;">
                SECRÉTARIAT GÉNÉRAL OSHDI • VISA OFFICIEL
            </div>
            <div style="font-size:0.7rem; color:#64748B; margin-top:4px;">Vérifié électroniquement</div>
        </div>
    </div>
</div>

</body>
</html>
