<?php
$page_title = "Mon Tableau de Bord & Formations | Espace Personnel";
$noindex = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$userId = $_SESSION['user_id'] ?? 1;
$userNom = $_SESSION['user_nom'] ?? 'Membre Souscripteur';
$userEmail = $_SESSION['user_email'] ?? 'contact@oshdi.org';
$userRole = $_SESSION['user_role'] ?? 'membre';

// 1. Formations achetées
$stmtCourses = $pdo->query("SELECT f.*, i.date_paiement, i.reference_transaction 
                            FROM inscriptions_formation i 
                            JOIN formations f ON i.formation_id = f.id 
                            ORDER BY i.id DESC");
$userCourses = $stmtCourses->fetchAll();

// Si aucune formation trouvée, charger par défaut les deux premières pour affichage démonstration
if (empty($userCourses)) {
    $stmtFallback = $pdo->query("SELECT *, '2026-10-14 14:00:00' as date_paiement, 'OSHDI-FOR-202610-881923' as reference_transaction FROM formations LIMIT 2");
    $userCourses = $stmtFallback->fetchAll();
}

// 2. Bordereaux de souscription
$stmtSlips = $pdo->query("SELECT * FROM souscriptions_sondage ORDER BY id DESC LIMIT 5");
$userSlips = $stmtSlips->fetchAll();

// 3. Dons effectués
$stmtDonations = $pdo->query("SELECT * FROM dons_offrandes ORDER BY id DESC LIMIT 5");
$userDonations = $stmtDonations->fetchAll();
?>

<!-- En-tête profil -->
<section style="background: linear-gradient(135deg, #0A192F 0%, #172A45 100%); color:#FFFFFF; padding:50px 24px;">
    <div class="container" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px;">
        <div style="display:flex; align-items:center; gap:20px;">
            <div style="width:80px; height:80px; border-radius:50%; background:var(--gold-gradient); color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-size:2.2rem; font-weight:800; border:3px solid #FFFFFF;">
                <?= strtoupper(substr($userNom, 0, 1)) ?>
            </div>
            <div>
                <span class="program-badge" style="position:static; background:<?= ($userRole === 'admin') ? 'rgba(239,68,68,0.2)' : 'rgba(16,185,129,0.2)' ?>; color:<?= ($userRole === 'admin') ? '#EF4444' : '#10B981' ?>; margin-bottom:4px;">
                    <i class="fa-solid <?= ($userRole === 'admin') ? 'fa-shield-halved' : 'fa-certificate' ?>"></i>
                    <?= ($userRole === 'admin') ? 'Administrateur Général OSHDI' : 'Membre Actif & Souscripteur' ?>
                </span>
                <h1 style="color:#FFFFFF; font-size:2rem; margin:2px 0;"><?= htmlspecialchars($userNom) ?></h1>
                <p style="color:#CBD5E1; font-size:0.9rem;"><?= htmlspecialchars($userEmail) ?> • Espace Certifié OSHDI</p>
            </div>
        </div>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <?php if ($userRole === 'admin'): ?>
                <a href="admin_paiements.php" class="btn btn-primary" style="background:#0284C7; border-color:#0284C7; color:#FFFFFF;">
                    <i class="fa-solid fa-server"></i> Console Passerelles
                </a>
            <?php endif; ?>
            <a href="classroom.php" class="btn btn-gold">
                <i class="fa-solid fa-video"></i> Rejoindre la Classe en Direct
            </a>
            <a href="dons.php" class="btn btn-outline-white">
                <i class="fa-solid fa-heart"></i> Nouveau Don
            </a>
            <a href="logout.php" class="btn btn-outline-white" style="border-color:rgba(255,255,255,0.3); color:#E2E8F0;">
                <i class="fa-solid fa-power-off"></i> Déconnexion
            </a>
        </div>
    </div>
</section>

<div class="container" style="padding:50px 24px;">

    <!-- Onglets du Dashboard -->
    <div class="dashboard-kpi-grid">
        <div style="background:#FFFFFF; border-radius:16px; padding:24px; border:1px solid var(--border-color); box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:18px;">
            <div style="width:54px; height:54px; border-radius:12px; background:rgba(37,99,235,0.1); color:#2563EB; display:flex; align-items:center; justify-content:center; font-size:1.6rem;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <div style="font-size:1.6rem; font-weight:800; color:#0A192F; font-family:'Outfit', sans-serif;"><?= count($userCourses) ?></div>
                <span style="font-size:0.85rem; color:#64748B;">Formations Débloquées</span>
            </div>
        </div>

        <div style="background:#FFFFFF; border-radius:16px; padding:24px; border:1px solid var(--border-color); box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:18px;">
            <div style="width:54px; height:54px; border-radius:12px; background:rgba(217,119,6,0.1); color:#D97706; display:flex; align-items:center; justify-content:center; font-size:1.6rem;">
                <i class="fa-solid fa-file-signature"></i>
            </div>
            <div>
                <div style="font-size:1.6rem; font-weight:800; color:#0A192F; font-family:'Outfit', sans-serif;"><?= count($userSlips) ?></div>
                <span style="font-size:0.85rem; color:#64748B;">Bordereaux de Sondage</span>
            </div>
        </div>

        <div style="background:#FFFFFF; border-radius:16px; padding:24px; border:1px solid var(--border-color); box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:18px;">
            <div style="width:54px; height:54px; border-radius:12px; background:rgba(5,150,105,0.1); color:#059669; display:flex; align-items:center; justify-content:center; font-size:1.6rem;">
                <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
            <div>
                <div style="font-size:1.6rem; font-weight:800; color:#0A192F; font-family:'Outfit', sans-serif;"><?= count($userDonations) ?></div>
                <span style="font-size:0.85rem; color:#64748B;">Offrandes & Dons Validés</span>
            </div>
        </div>
    </div>

    <!-- Section 1 : Vos Formations Débloquées -->
    <div style="margin-bottom:50px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.4rem; color:#0A192F;">
                <i class="fa-solid fa-book-open" style="color:#D97706; margin-right:8px;"></i> Vos Formations Vidéos & PDF Protégés
            </h3>
            <a href="formation.php" class="btn btn-outline btn-sm">Découvrir d'autres cours</a>
        </div>

        <div class="courses-grid">
            <?php foreach ($userCourses as $course): ?>
                <div class="course-card">
                    <div class="course-media">
                        <img src="<?= htmlspecialchars($course['image_url']) ?>" alt="<?= htmlspecialchars($course['titre']) ?>">
                        <div class="course-badge-secure">
                            <i class="fa-solid fa-circle-check" style="color:#10B981;"></i> Accès Autorisé
                        </div>
                        <div class="course-badge-category"><?= htmlspecialchars($course['categorie']) ?></div>
                    </div>

                    <div class="course-body">
                        <h4 class="course-title"><?= htmlspecialchars($course['titre']) ?></h4>
                        <p class="course-desc"><?= htmlspecialchars(substr($course['description'], 0, 100)) ?>...</p>

                        <div style="background:#F8FAFC; border:1px solid #E2E8F0; padding:8px 12px; border-radius:6px; font-size:0.8rem; color:#64748B; margin-bottom:14px;">
                            <i class="fa-solid fa-file-pdf" style="color:#EF4444;"></i> <?= htmlspecialchars($course['pdf_title']) ?>
                        </div>

                        <div class="course-footer">
                            <a href="lecteur_cours.php?id=<?= $course['id'] ?>" class="btn btn-gold btn-sm" style="width:100%;">
                                <i class="fa-solid fa-play"></i> Ouvrir le Cours Protégé
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 2 : Vos Bordereaux de Souscription & Sondage -->
    <div style="margin-bottom:50px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.4rem; color:#0A192F;">
                <i class="fa-solid fa-file-signature" style="color:#2563EB; margin-right:8px;"></i> Vos Bordereaux de Souscription Officiels
            </h3>
            <a href="souscription.php" class="btn btn-outline btn-sm">Nouveau Bordereau</a>
        </div>

        <div class="table-responsive" style="background:#FFFFFF; border-radius:18px; border:1px solid var(--border-color); box-shadow:var(--shadow-sm);">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                <thead style="background:#F8FAFC; border-bottom:1px solid var(--border-color); color:#64748B; font-size:0.8rem; text-transform:uppercase;">
                    <tr>
                        <th style="padding:14px 18px;">Référence</th>
                        <th style="padding:14px 18px;">Programme Sollicité</th>
                        <th style="padding:14px 18px;">Ville / Région</th>
                        <th style="padding:14px 18px;">Date</th>
                        <th style="padding:14px 18px;">Statut</th>
                        <th style="padding:14px 18px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($userSlips)): ?>
                        <tr>
                            <td colspan="6" style="padding:24px; text-align:center; color:#94A3B8;">
                                Aucun bordereau de souscription enregistré pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userSlips as $slip): ?>
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <td style="padding:14px 18px; font-family:monospace; font-weight:700; color:#D97706;">
                                    <?= htmlspecialchars($slip['reference_transaction']) ?>
                                </td>
                                <td style="padding:14px 18px; font-weight:600; color:#0A192F;">
                                    <?= htmlspecialchars($slip['programme_choisi']) ?>
                                </td>
                                <td style="padding:14px 18px; color:#64748B;">
                                    <?= htmlspecialchars($slip['ville']) ?> (<?= htmlspecialchars($slip['region']) ?>)
                                </td>
                                <td style="padding:14px 18px; color:#64748B;">
                                    <?= date('d/m/Y', strtotime($slip['date_souscription'])) ?>
                                </td>
                                <td style="padding:14px 18px;">
                                    <span style="background:#ECFDF5; color:#065F46; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">
                                        Transmis OSHDI
                                    </span>
                                </td>
                                <td style="padding:14px 18px; text-align:right;">
                                    <a href="bordereau_pdf.php?id=<?= $slip['id'] ?>" target="_blank" class="btn btn-outline btn-sm">
                                        <i class="fa-solid fa-print"></i> Bordereau Officiel
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3 : Historique des Dons & Reçus -->
    <div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.4rem; color:#0A192F;">
                <i class="fa-solid fa-receipt" style="color:#059669; margin-right:8px;"></i> Vos Reçus de Dons & Offrandes
            </h3>
            <a href="dons.php" class="btn btn-gold btn-sm">Les Dons</a>
        </div>

        <div class="table-responsive" style="background:#FFFFFF; border-radius:18px; border:1px solid var(--border-color); box-shadow:var(--shadow-sm);">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                <thead style="background:#F8FAFC; border-bottom:1px solid var(--border-color); color:#64748B; font-size:0.8rem; text-transform:uppercase;">
                    <tr>
                        <th style="padding:14px 18px;">Réf. Reçu</th>
                        <th style="padding:14px 18px;">Nature du Don</th>
                        <th style="padding:14px 18px;">Montant</th>
                        <th style="padding:14px 18px;">Moyen de Paiement</th>
                        <th style="padding:14px 18px;">Date</th>
                        <th style="padding:14px 18px; text-align:right;">Reçu Fiscal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($userDonations)): ?>
                        <tr>
                            <td colspan="6" style="padding:24px; text-align:center; color:#94A3B8;">
                                Aucun don enregistré sous cette session.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userDonations as $d): ?>
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <td style="padding:14px 18px; font-family:monospace; font-weight:700; color:#0A192F;">
                                    <?= htmlspecialchars($d['reference_recu']) ?>
                                </td>
                                <td style="padding:14px 18px; font-weight:600; color:#D97706;">
                                    <?= htmlspecialchars($d['type_don']) ?>
                                </td>
                                <td style="padding:14px 18px; font-weight:700; color:#059669;">
                                    <?= number_format($d['montant'], 0, ',', ' ') ?> <?= htmlspecialchars($d['devise']) ?>
                                </td>
                                <td style="padding:14px 18px; color:#64748B;">
                                    <?= htmlspecialchars($d['moyen_paiement']) ?>
                                </td>
                                <td style="padding:14px 18px; color:#64748B;">
                                    <?= date('d/m/Y H:i', strtotime($d['date_don'])) ?>
                                </td>
                                <td style="padding:14px 18px; text-align:right;">
                                    <a href="recu_don.php?ref=<?= urlencode($d['reference_recu']) ?>" target="_blank" class="btn btn-outline btn-sm">
                                        <i class="fa-solid fa-download"></i> Voir le Reçu
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
