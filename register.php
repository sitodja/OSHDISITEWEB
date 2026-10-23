<?php
// ==============================================================
// OSHDI - ŒUVRES SPIRITUELLES ET HUMANITAIRES DIVINES
// Création de Compte Membre & Souscripteur
// ==============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/translations.php';
require_once __DIR__ . '/config/db.php';

$page_title = "Créer un Compte Membre Gratuit | Rejoindre la Communauté OSHDI";
$page_description = "Inscrivez-vous gratuitement sur la plateforme officielle OSHDI pour accéder aux formations en ligne, webinaires, programmes d'aide et suivi des souscriptions.";
$page_keywords = "inscription OSHDI, créer compte membre, rejoindre ONG spirituelle, formations gratuites et certifiantes, membre communauté chrétienne";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenoms = trim($_POST['prenoms'] ?? '');
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $telephone = trim($_POST['telephone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (!empty($nom) && !empty($prenoms) && !empty($email) && !empty($password)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Veuillez saisir une adresse email valide.";
        } elseif (strlen($password) < 6) {
            $error = "Le mot de passe doit comporter au moins 6 caractères.";
        } elseif (!empty($confirm_password) && $password !== $confirm_password) {
            $error = "Les deux mots de passe ne correspondent pas.";
        } else {
            // Vérification email unique
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?)");
            $stmtCheck->execute([$email]);
            if ($stmtCheck->fetch()) {
                $error = "Cette adresse email est déjà associée à un compte membre OSHDI. Veuillez vous connecter.";
            } else {
                try {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (nom, prenoms, email, telephone, password, role) VALUES (?, ?, ?, ?, ?, 'membre')");
                    $stmt->execute([$nom, $prenoms, $email, $telephone, $hashed]);

                    $newUserId = $pdo->lastInsertId();

                    // Connecter automatiquement l'utilisateur
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$newUserId;
                    $_SESSION['user_nom'] = $nom . ' ' . $prenoms;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'membre';
                    $_SESSION['user_telephone'] = $telephone;
                    $_SESSION['user_logged_at'] = date('Y-m-d H:i:s');

                    header('Location: compte.php?welcome=1');
                    exit;
                } catch (Exception $e) {
                    $error = "Une erreur est survenue lors de la création du compte. Veuillez réessayer.";
                }
            }
        }
    } else {
        $error = "Veuillez renseigner tous les champs obligatoires (*).";
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main style="background: radial-gradient(circle at 50% 20%, rgba(37,99,235,0.06) 0%, rgba(10,25,47,0.02) 60%), #F8FAFC; min-height:85vh; padding:50px 20px 80px; display:flex; align-items:center; justify-content:center;">
    <div style="width:100%; max-width:560px;">
        <div style="background:#FFFFFF; border-radius:24px; padding:38px 34px; box-shadow:0 20px 40px -15px rgba(10,25,47,0.12), 0 0 1px 1px rgba(226,232,240,0.8); border:1px solid #E2E8F0; border-top:5px solid; border-image:var(--oshdi-ribbon) 1;">
            
            <div style="display:flex; justify-content:center; margin-bottom:18px;">
                <span style="display:inline-flex; align-items:center; gap:8px; background:rgba(217,119,6,0.1); color:#D97706; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; padding:5px 14px; border-radius:9999px; border:1px solid rgba(217,119,6,0.25);">
                    <i class="fa-solid fa-users"></i> Communauté Internationale
                </span>
            </div>

            <div style="text-align:center; margin-bottom:28px;">
                <a href="index.php">
                    <img src="assets/images/logo.png" alt="Logo OSHDI" style="width:64px; height:64px; border-radius:50%; margin:0 auto 12px; border:2.5px solid var(--oshdi-green); box-shadow:0 0 16px rgba(34,197,94,0.35); background:#FFFFFF; object-fit:cover;">
                </a>
                <h1 style="font-size:1.65rem; color:#0A192F; font-family:'Outfit', sans-serif; font-weight:800; margin-bottom:6px;">
                    Rejoindre OSHDI
                </h1>
                <p style="font-size:0.88rem; color:#64748B;">
                    Créez votre compte membre pour accéder aux formations, visioconférences et sondages.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background:#FEF2F2; border:1px solid #FECACA; border-left:4px solid #EF4444; color:#991B1B; padding:14px; border-radius:12px; font-size:0.88rem; margin-bottom:22px; display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size:1.2rem; flex-shrink:0;"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;">
                    <div>
                        <label style="display:block; font-size:0.86rem; font-weight:700; color:#334155; margin-bottom:6px;">Nom de famille *</label>
                        <input type="text" name="nom" required placeholder="Ex: Kouamé" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" style="width:100%; padding:12px 14px; border:1.5px solid #CBD5E1; border-radius:12px; font-size:0.95rem; outline:none;" onfocus="this.style.borderColor='var(--oshdi-gold)'" onblur="this.style.borderColor='#CBD5E1'">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.86rem; font-weight:700; color:#334155; margin-bottom:6px;">Prénoms *</label>
                        <input type="text" name="prenoms" required placeholder="Ex: Eric Paul" value="<?= htmlspecialchars($_POST['prenoms'] ?? '') ?>" style="width:100%; padding:12px 14px; border:1.5px solid #CBD5E1; border-radius:12px; font-size:0.95rem; outline:none;" onfocus="this.style.borderColor='var(--oshdi-gold)'" onblur="this.style.borderColor='#CBD5E1'">
                    </div>
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.86rem; font-weight:700; color:#334155; margin-bottom:6px;">Numéro de Téléphone *</label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <i class="fa-solid fa-phone" style="position:absolute; left:14px; color:#94A3B8;"></i>
                        <input type="tel" name="telephone" required placeholder="+225 07 00 00 00 00" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>" style="width:100%; padding:12px 14px 12px 42px; border:1.5px solid #CBD5E1; border-radius:12px; font-size:0.95rem; outline:none;" onfocus="this.style.borderColor='var(--oshdi-gold)'" onblur="this.style.borderColor='#CBD5E1'">
                    </div>
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.86rem; font-weight:700; color:#334155; margin-bottom:6px;">Adresse Email *</label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <i class="fa-solid fa-envelope" style="position:absolute; left:14px; color:#94A3B8;"></i>
                        <input type="email" name="email" required placeholder="exemple@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" style="width:100%; padding:12px 14px 12px 42px; border:1.5px solid #CBD5E1; border-radius:12px; font-size:0.95rem; outline:none;" onfocus="this.style.borderColor='var(--oshdi-gold)'" onblur="this.style.borderColor='#CBD5E1'">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
                    <div>
                        <label style="display:block; font-size:0.86rem; font-weight:700; color:#334155; margin-bottom:6px;">Mot de passe *</label>
                        <input type="password" name="password" required placeholder="Min 6 caractères" style="width:100%; padding:12px 14px; border:1.5px solid #CBD5E1; border-radius:12px; font-size:0.95rem; outline:none;" onfocus="this.style.borderColor='var(--oshdi-gold)'" onblur="this.style.borderColor='#CBD5E1'">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.86rem; font-weight:700; color:#334155; margin-bottom:6px;">Confirmation *</label>
                        <input type="password" name="confirm_password" required placeholder="Répéter le mot de passe" style="width:100%; padding:12px 14px; border:1.5px solid #CBD5E1; border-radius:12px; font-size:0.95rem; outline:none;" onfocus="this.style.borderColor='var(--oshdi-gold)'" onblur="this.style.borderColor='#CBD5E1'">
                    </div>
                </div>

                <button type="submit" class="btn btn-gold" style="width:100%; padding:14px; font-size:1.02rem; font-weight:700; border-radius:12px; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow:0 10px 20px -5px rgba(217,119,6,0.4);">
                    <i class="fa-solid fa-user-plus"></i> Finaliser l'Inscription
                </button>
            </form>

            <div style="margin-top:26px; padding-top:20px; border-top:1px solid #E2E8F0; text-align:center;">
                <p style="font-size:0.88rem; color:#64748B; margin-bottom:8px;">
                    Vous avez déjà un compte ? 
                    <a href="login.php" style="color:#D97706; font-weight:700;">Se connecter</a>
                </p>
                <a href="index.php" style="display:inline-flex; align-items:center; gap:6px; font-size:0.8rem; color:#94A3B8;">
                    <i class="fa-solid fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
