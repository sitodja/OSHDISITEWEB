<?php
$page_title = "Formations Certifiantes & Modules d'Enseignement";
$page_description = "Formations en ligne OSHDI certifiantes : délivrance spirituelle, leadership universel, autonomie culinaire et santé solidaire. Accédez aux cours vidéo et PDF.";
$page_keywords = "formation en ligne OSHDI, cours chrétien, certificat leadership, délivrance spirituelle, formation culinaire ABM, cours vidéo streaming";

$extra_schema_json = '{
  "@type": "ItemList",
  "name": "Catalogue des Formations OSHDI",
  "itemListElement": [
    {
      "@type": "Course",
      "position": 1,
      "name": "Délivrance Spirituelle & Fondements de la Souveraineté de Dieu",
      "description": "Comprendre les lois spirituelles pour vaincre l\'oppression et vivre dans la plénitude divine.",
      "provider": {
        "@type": "Organization",
        "name": "Académie OSHDI",
        "sameAs": "https://oshdi.org"
      }
    },
    {
      "@type": "Course",
      "position": 2,
      "name": "Masterclass Culinaire & Gestion de Restaurant (ABM)",
      "description": "Techniques professionnelles, conservation des épices et gestion financière d\'un établissement de restauration.",
      "provider": {
        "@type": "Organization",
        "name": "Académie OSHDI",
        "sameAs": "https://oshdi.org"
      }
    }
  ]
}';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Récupération de toutes les formations
$stmt = $pdo->query("SELECT * FROM formations ORDER BY id ASC");
$allFormations = $stmt->fetchAll();
?>

<!-- En-tête -->
<section style="background: linear-gradient(135deg, #0A192F 0%, #172A45 100%); color:#FFFFFF; padding:60px 24px; text-align:center;">
    <div class="container">
        <span class="section-tag" style="background:rgba(245,158,11,0.2); color:#F59E0B;">Académie & Enseignements OSHDI</span>
        <h1 style="color:#FFFFFF; font-size:2.8rem; margin:16px 0;"><?= t('train_title') ?></h1>
        <p style="color:#CBD5E1; max-width:780px; margin:0 auto; font-size:1.1rem;">
            <?= t('train_subtitle') ?>
        </p>

        <div style="margin-top:28px; display:flex; justify-content:center; gap:16px; flex-wrap:wrap;">
            <a href="classroom.php" class="btn btn-gold btn-lg">
                <i class="fa-solid fa-video"></i> <?= t('train_btn_join') ?>
            </a>
            <a href="compte.php" class="btn btn-outline-white btn-lg">
                <i class="fa-solid fa-user-lock"></i> Mes Formations Achetées
            </a>
        </div>
    </div>
</section>

<!-- Notice de Sécurité & Non-Téléchargement -->
<section style="background:#FEF3C7; border-bottom:1px solid #FDE68A; padding:14px 24px;">
    <div class="container" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; font-size:0.9rem; color:#92400E;">
        <div style="display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-shield-halved" style="font-size:1.4rem; color:#D97706;"></i>
            <span>
                <strong>Protection des Droits OSHDI :</strong> 
                Toutes les vidéos et manuels PDF sont strictement diffusés en <strong>lecture seule non téléchargeable</strong> avec filigrane nominatif dynamique pour protéger les enseignements sacrés.
            </span>
        </div>
        <span class="program-badge" style="background:#D97706; color:#FFFFFF; margin:0;">100% Anti-Copie</span>
    </div>
</section>

<!-- Catalogue des Formations -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Modules Disponibles</span>
            <h2 class="section-title">Parcours Certifiants & Ateliers Pratiques</h2>
            <p class="section-subtitle">
                Inscrivez-vous et accédez immédiatement aux leçons vidéo et documents d'étude interactifs.
            </p>
        </div>

        <div class="courses-grid">
            <?php foreach ($allFormations as $f): ?>
                <div class="course-card">
                    <div class="course-media">
                        <img src="<?= htmlspecialchars($f['image_url']) ?>" alt="<?= htmlspecialchars($f['titre']) ?>">
                        <div class="course-badge-secure">
                            <i class="fa-solid fa-lock"></i> PDF & Vidéo Non-Téléchargeable
                        </div>
                        <div class="course-badge-category"><?= htmlspecialchars($f['categorie']) ?></div>
                    </div>

                    <div class="course-body">
                        <h4 class="course-title"><?= htmlspecialchars($f['titre']) ?></h4>
                        <p class="course-desc"><?= htmlspecialchars($f['description']) ?></p>

                        <!-- Infos du document PDF protégé -->
                        <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:10px 12px; margin-bottom:16px; font-size:0.8rem; color:#475569;">
                            <div style="font-weight:700; color:#0A192F; margin-bottom:2px;">
                                <i class="fa-solid fa-file-pdf" style="color:#EF4444;"></i> <?= htmlspecialchars($f['pdf_title']) ?>
                            </div>
                            <span style="font-size:0.75rem; color:#64748B;">Support d'étude inclus en consultation streaming</span>
                        </div>

                        <div class="course-meta">
                            <span><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($f['duree']) ?></span>
                            <span><i class="fa-solid fa-chalkboard-user"></i> <?= htmlspecialchars($f['formateur']) ?></span>
                        </div>

                        <div class="course-footer">
                            <div class="course-price"><?= formatMoney($f['prix']) ?></div>
                            <div style="display:flex; gap:8px;">
                                <button type="button" class="btn btn-gold btn-sm" onclick="openPaymentModal({
                                    title: 'Inscription Formation OSHDI',
                                    itemName: '<?= addslashes($f['titre']) ?>',
                                    amount: <?= $f['prix'] ?>,
                                    amountFormatted: '<?= formatMoney($f['prix']) ?>',
                                    type: 'formation',
                                    itemId: <?= $f['id'] ?>
                                })">
                                    <i class="fa-solid fa-credit-card"></i> Payer l'accès
                                </button>
                                <a href="lecteur_cours.php?id=<?= $f['id'] ?>" class="btn btn-outline btn-sm" title="Aperçu du lecteur protégé">
                                    <i class="fa-solid fa-eye"></i> Aperçu
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Démonstration des moyens de paiement acceptés -->
<section style="background:#FFFFFF; border-top:1px solid var(--border-color); padding:40px 24px; text-align:center;">
    <div class="container">
        <h4 style="font-size:1.1rem; color:#64748B; margin-bottom:20px;">MOYENS DE PAIEMENT LOCAUX & INTERNATIONAUX SÉCURISÉS</h4>
        <div style="display:flex; justify-content:center; align-items:center; gap:28px; flex-wrap:wrap; font-size:1.1rem; font-weight:700; color:#0A192F;">
            <span style="display:flex; align-items:center; gap:8px; color:#00B2FE;"><i class="fa-solid fa-water"></i> Wave Money</span>
            <span style="display:flex; align-items:center; gap:8px; color:#FF7900;"><i class="fa-solid fa-mobile-screen"></i> Orange Money</span>
            <span style="display:flex; align-items:center; gap:8px; color:#005BAC;"><i class="fa-solid fa-tower-cell"></i> Moov Money</span>
            <span style="display:flex; align-items:center; gap:8px; color:#E5A900;"><i class="fa-solid fa-phone-volume"></i> MTN MoMo</span>
            <span style="display:flex; align-items:center; gap:8px; color:#2563EB;"><i class="fa-solid fa-credit-card"></i> Visa / Mastercard</span>
            <span style="display:flex; align-items:center; gap:8px; color:#003087;"><i class="fa-brands fa-paypal"></i> PayPal</span>
            <span style="display:flex; align-items:center; gap:8px; color:#F59E0B;"><i class="fa-brands fa-bitcoin"></i> Crypto (USDT / BTC)</span>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
