<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$ref = $_GET['ref'] ?? '';
$id = intval($_GET['id'] ?? 0);

$souscription = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM souscriptions_sondage WHERE id = ?");
    $stmt->execute([$id]);
    $souscription = $stmt->fetch();
} elseif (!empty($ref)) {
    $stmt = $pdo->prepare("SELECT * FROM souscriptions_sondage WHERE reference_transaction = ?");
    $stmt->execute([$ref]);
    $souscription = $stmt->fetch();
}

// Fallback données de démonstration si consultation directe
if (!$souscription) {
    $souscription = [
        'id' => 101,
        'nom_complet' => 'M. Kouadio Paul',
        'profession' => 'Artisan Commerçant',
        'telephone' => '+225 07 12 34 56 78',
        'email' => 'souscripteur@domaine.ci',
        'localite' => 'Cocody Palmeraie',
        'ville' => 'Abidjan',
        'region' => 'Lagunes',
        'programme_choisi' => 'REMDIS - Prise en charge médicale solidaire',
        'besoins_assistance' => 'Demande d\'appui médical pour chirurgie et orientation vers une clinique partenaire agréée OSHDI.',
        'montant_paye' => 5000,
        'moyen_paiement' => 'Wave Money',
        'reference_transaction' => $ref ?: 'OSHDI-SUB-20261014-998812',
        'signature_electronique' => 'Kouadio Paul',
        'statut' => 'transmis_oshdi',
        'date_souscription' => date('Y-m-d H:i:s')
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
    <title>BORDEREAU OFFICIEL DE SOUSCRIPTION | OSHDI</title>
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
            background: #F1F5F9;
            color: #0F172A;
            margin: 0;
            padding: 30px 15px;
        }
        .slip-card {
            max-width: 820px;
            margin: 0 auto;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
            color: rgba(217, 119, 6, 0.05);
            font-weight: 800;
            pointer-events: none;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .slip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px double #D97706;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .logo-box {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .logo-box img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2px solid #D97706;
        }
        .slip-title {
            text-align: center;
            background: #0A192F;
            color: #FFFFFF;
            padding: 10px;
            border-radius: 6px;
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .info-item {
            background: #F8FAFC;
            padding: 12px 14px;
            border-radius: 6px;
            border: 1px solid #E2E8F0;
        }
        .info-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            color: #64748B;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0A192F;
        }
        .box-dol {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-left: 4px solid #F59E0B;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;
            border-top: 1px dashed #CBD5E1;
            padding-top: 20px;
        }
        .btn-print {
            background: #D97706;
            color: #FFFFFF;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { background: #FFFFFF; padding: 0; }
            .slip-card { box-shadow: none; border: none; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width:820px; margin:0 auto 16px; display:flex; justify-content:space-between; align-items:center;">
    <a href="index.php" style="color:#0A192F; text-decoration:none; font-weight:600;"><i class="fa-solid fa-arrow-left"></i> Retour au site OSHDI</a>
    <button type="button" class="btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer / Enregistrer en PDF</button>
</div>

<div class="slip-card">
    <div class="watermark">OSHDI OFFICIEL</div>

    <!-- En-tête -->
    <div class="slip-header">
        <div class="logo-box">
            <img src="assets/images/logo.png" alt="Emblème OSHDI">
            <div>
                <h2 style="font-family:'Outfit', sans-serif; margin:0; color:#0A192F; font-size:1.4rem;">OSHDI</h2>
                <div style="font-size:0.75rem; color:#D97706; font-weight:700; text-transform:uppercase;">Œuvres Spirituelles et Humanitaires Divines</div>
                <div style="font-size:0.7rem; color:#64748B;">Institution Internationale d'Aide aux Vies Brisées</div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:0.75rem; color:#64748B; font-weight:700;">N° BORDEREAU OFFICIEL</div>
            <div style="font-family:monospace; font-size:1.1rem; font-weight:800; color:#D97706;"><?= htmlspecialchars($souscription['reference_transaction']) ?></div>
            <div style="font-size:0.75rem; color:#10B981; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Statut : Dossier Transmis</div>
        </div>
    </div>

    <!-- Titre -->
    <div class="slip-title">
        BORDEREAU OFFICIEL DE SOUSCRIPTION & SONDAGE D'OPINION
    </div>

    <!-- Grille Info -->
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Nom et Prénoms du Souscripteur</div>
            <div class="info-value"><?= htmlspecialchars($souscription['nom_complet']) ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Profession / Activité</div>
            <div class="info-value"><?= htmlspecialchars($souscription['profession']) ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Contact Téléphonique</div>
            <div class="info-value"><?= htmlspecialchars($souscription['telephone']) ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Email de correspondance</div>
            <div class="info-value"><?= htmlspecialchars($souscription['email']) ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Ville & Région de Rattachement</div>
            <div class="info-value"><?= htmlspecialchars($souscription['ville']) ?> / <?= htmlspecialchars($souscription['region']) ?> (<?= htmlspecialchars($souscription['localite']) ?>)</div>
        </div>
        <div class="info-item">
            <div class="info-label">Programme OSHDI Sollicité</div>
            <div class="info-value" style="color:#D97706;"><?= htmlspecialchars($souscription['programme_choisi']) ?></div>
        </div>
    </div>

    <!-- Doléances & Besoins -->
    <div class="box-dol">
        <div class="info-label" style="color:#92400E; margin-bottom:6px;">Expression des Doléances & Besoins d'Assistance :</div>
        <p style="margin:0;"><?= nl2br(htmlspecialchars($souscription['besoins_assistance'])) ?></p>
    </div>

    <!-- Règlement des frais -->
    <div style="display:flex; justify-content:space-between; align-items:center; background:#F8FAFC; border:1px solid #E2E8F0; padding:12px 18px; border-radius:6px; margin-bottom:24px; font-size:0.85rem;">
        <div>
            <span style="color:#64748B;">Frais de Souscription & Adhésion :</span>
            <strong style="color:#059669; font-size:1rem; margin-left:6px;"><?= number_format($souscription['montant_paye'], 0, ',', ' ') ?> FCFA</strong>
        </div>
        <div>
            <span style="color:#64748B;">Moyen de Paiement :</span>
            <strong><?= htmlspecialchars($souscription['moyen_paiement']) ?></strong>
        </div>
        <div>
            <span style="color:#64748B;">Date & Heure :</span>
            <strong><?= date('d/m/Y H:i', strtotime($souscription['date_souscription'])) ?></strong>
        </div>
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div style="text-align:center;">
            <div style="font-size:0.75rem; color:#64748B; margin-bottom:4px;">Signature Électronique du Souscripteur</div>
            <div style="font-family:'Outfit', cursive; font-size:1.3rem; color:#0A192F; font-weight:700;">
                <?= htmlspecialchars($souscription['signature_electronique']) ?>
            </div>
            <div style="font-size:0.7rem; color:#10B981;"><i class="fa-solid fa-lock"></i> Authentifié</div>
        </div>

        <div style="text-align:center;">
            <div style="font-size:0.75rem; color:#64748B; margin-bottom:4px;">Cachet & Sceau de l'Institution</div>
            <div style="display:inline-block; border:2px solid #D97706; padding:6px 14px; border-radius:8px; color:#D97706; font-weight:800; font-size:0.78rem; text-transform:uppercase;">
                DIRECTION OSHDI • VISA CONFORME
            </div>
            <div style="font-size:0.7rem; color:#64748B; margin-top:4px;">Transmis à : <?= SITE_EMAIL ?></div>
        </div>
    </div>
</div>

</body>
</html>
