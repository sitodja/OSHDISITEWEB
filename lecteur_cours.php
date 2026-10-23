<?php
$page_title = "Lecteur de Cours Sécurisé | Académie de Formation en Ligne";
$page_description = "Visionnez vos cours de délivrance spirituelle et masterclasses culinaires ABM en streaming haute définition avec supports de cours PDF officiels OSHDI.";
$page_keywords = "cours en ligne OSHDI, vidéo formation chrétienne, masterclass cuisine ABM, support cours PDF";
$extra_js = "assets/js/secure_viewer.js";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$courseId = intval($_GET['id'] ?? 1);
$stmt = $pdo->prepare("SELECT * FROM formations WHERE id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    echo "<div class='container' style='padding:60px 24px; text-align:center;'><h2>Formation introuvable.</h2><a href='formation.php' class='btn btn-gold'>Retour au catalogue</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$currentUser = $_SESSION['user_nom'] ?? 'Apprenant OSHDI (Session Invité)';
$currentEmail = $_SESSION['user_email'] ?? 'contact@oshdi.org';
?>

<script>
    window.OSHDI_USER_NAME = "<?= addslashes($currentUser) ?>";
    window.OSHDI_USER_EMAIL = "<?= addslashes($currentEmail) ?>";
</script>

<div class="container" style="padding:40px 24px;">
    <!-- En-tête du cours -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:20px; margin-bottom:28px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                <span class="program-badge" style="position:static; background:rgba(217,119,6,0.1); color:#D97706;"><?= htmlspecialchars($course['categorie']) ?></span>
                <span style="font-size:0.8rem; color:#64748B;"><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($course['duree']) ?></span>
            </div>
            <h1 style="font-size:2rem; color:#0A192F;"><?= htmlspecialchars($course['titre']) ?></h1>
            <p style="color:#64748B; font-size:0.95rem; margin-top:4px;">
                Formateur : <strong><?= htmlspecialchars($course['formateur']) ?></strong> • Espace d'apprentissage sécurisé certifié OSHDI
            </p>
        </div>

        <div style="display:flex; gap:12px;">
            <a href="classroom.php" class="btn btn-gold btn-sm">
                <i class="fa-solid fa-video"></i> Rejoindre la Classe en Direct
            </a>
            <a href="formation.php" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="course-reader-grid">
        
        <!-- ================= 1. LECTEUR VIDÉO PROTÉGÉ ================= -->
        <div>
            <div class="secure-viewer-container" style="margin-bottom:24px;">
                <div class="secure-viewer-header">
                    <div>
                        <span style="font-size:0.75rem; color:#F59E0B; text-transform:uppercase; font-weight:700;">Leçon Vidéo Streaming</span>
                        <h4 style="color:#FFFFFF; font-size:1.1rem; margin-top:2px;">Module 1 : Principes Fondamentaux de Délivrance</h4>
                    </div>
                    <span class="secure-badge"><i class="fa-solid fa-shield-halved"></i> Anti-Téléchargement</span>
                </div>

                <!-- Conteneur Vidéo Sécurisé -->
                <div style="position:relative; border-radius:12px; overflow:hidden; background:#000000; box-shadow:0 8px 24px rgba(0,0,0,0.6);">
                    <video 
                        id="secureVideoPlayer"
                        controls
                        controlsList="nodownload nofullscreen noplaybackrate"
                        disablePictureInPicture
                        oncontextmenu="return false;"
                        poster="assets/images/hero_banner.jpg"
                        style="width:100%; height:auto; display:block; outline:none;"
                    >
                        <!-- Source vidéo avec fallback -->
                        <source src="assets/videos/demo_formation.mp4" type="video/mp4">
                        Votre navigateur ne supporte pas la lecture vidéo sécurisée.
                    </video>

                    <!-- Filigrane dynamique sur la vidéo -->
                    <div style="position:absolute; top:16px; right:16px; background:rgba(0,0,0,0.6); padding:4px 10px; border-radius:6px; font-size:0.75rem; color:#F59E0B; pointer-events:none; border:1px solid rgba(245,158,11,0.3);">
                        <i class="fa-solid fa-lock"></i> OSHDI • <?= htmlspecialchars($currentUser) ?>
                    </div>
                </div>

                <div style="margin-top:16px; font-size:0.85rem; color:#94A3B8; line-height:1.6;">
                    <strong style="color:#FFFFFF;">Description du module :</strong><br>
                    <?= htmlspecialchars($course['description']) ?>
                </div>
            </div>

            <!-- Notes de cours interactives -->
            <div style="background:#FFFFFF; border:1px solid var(--border-color); border-radius:14px; padding:20px; box-shadow:var(--shadow-sm);">
                <h4 style="font-size:1.05rem; margin-bottom:10px;"><i class="fa-solid fa-pen" style="color:#D97706;"></i> Vos Notes Personnelles de Séance</h4>
                <textarea class="form-control" rows="4" placeholder="Prenez vos notes de méditation ou questions pour la prochaine session..." style="padding:10px;"></textarea>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                    <small style="color:#94A3B8;">Vos notes restent enregistrées dans votre espace souscripteur.</small>
                    <button type="button" class="btn btn-outline btn-sm" onclick="showToast('Notes sauvegardées dans votre profil !', 'success')">Enregistrer</button>
                </div>
            </div>
        </div>

        <!-- ================= 2. LECTEUR PDF PROTÉGÉ SUR CANVAS (NON TÉLÉCHARGEABLE) ================= -->
        <div>
            <div class="secure-viewer-container">
                <div class="secure-viewer-header">
                    <div>
                        <span style="font-size:0.75rem; color:#F59E0B; text-transform:uppercase; font-weight:700;">Manuel Numérique Officiel</span>
                        <h4 style="color:#FFFFFF; font-size:1.05rem; margin-top:2px;"><?= htmlspecialchars($course['pdf_title']) ?></h4>
                    </div>
                    <span class="secure-badge"><i class="fa-solid fa-ban"></i> Lecture Seule</span>
                </div>

                <!-- Barre d'outils de navigation du PDF -->
                <div style="display:flex; justify-content:space-between; align-items:center; background:#1E293B; padding:10px 14px; border-radius:8px; margin-bottom:14px;">
                    <div style="display:flex; gap:8px;">
                        <button type="button" id="btnPrevPage" class="btn btn-outline-white btn-sm">
                            <i class="fa-solid fa-chevron-left"></i> Précédent
                        </button>
                        <button type="button" id="btnNextPage" class="btn btn-outline-white btn-sm">
                            Suivant <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                    <div style="font-size:0.85rem; color:#CBD5E1;">
                        Page <strong id="securePageIndicator" style="color:#F59E0B;">1 / 5</strong>
                    </div>
                    <button type="button" class="btn btn-sm" style="background:rgba(239,68,68,0.2); color:#F87171; border:1px solid rgba(239,68,68,0.4);" onclick="showToast('Téléchargement et impression interdits par les protocoles OSHDI.', 'error')">
                        <i class="fa-solid fa-download"></i> Bloqué
                    </button>
                </div>

                <!-- Zone de Rendu Canvas PDF Sécurisé -->
                <div class="canvas-wrapper">
                    <canvas id="pdfSecureCanvas"></canvas>
                </div>

                <div style="margin-top:14px; text-align:center; font-size:0.75rem; color:#94A3B8;">
                    <i class="fa-solid fa-fingerprint"></i> Filigrane dynamique actif avec horodatage et adresse IP pour garantir l'intégrité intellectuelle.
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
