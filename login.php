<?php
// ==============================================================
// OSHDI - ŒUVRES SPIRITUELLES ET HUMANITAIRES DIVINES
// Page de Connexion : Espace Membre, Souscripteur & Administration
// ==============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/translations.php';
require_once __DIR__ . '/config/db.php';

$page_title = "Connexion Espace Membre & Administration | OSHDI";
$page_description = "Accédez à votre espace membre OSHDI pour gérer vos cours, certificats, bordereaux de souscription, historiques de dons et transactions.";
$page_keywords = "connexion membre OSHDI, login espace souscripteur, portail formation, espace chrétien, authentification sécurisée";

// Initialisation du jeton CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$info = '';

// Messages d'état via URL
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'logged_out':
            $success = "Vous avez été déconnecté avec succès. À bientôt sur la plateforme OSHDI !";
            break;
        case 'auth_required':
            $info = "Veuillez vous connecter pour accéder à cet espace protégé.";
            break;
        case 'session_expired':
            $info = "Votre session a expiré. Veuillez vous reconnecter pour des raisons de sécurité.";
            break;
        case 'registered':
            $success = "Félicitations ! Votre compte membre OSHDI a été créé avec succès. Veuillez vous connecter.";
            break;
    }
}

// Gestion de la redirection post-connexion
$redirectTarget = 'compte.php';
if (!empty($_GET['redirect'])) {
    $rawRedirect = trim($_GET['redirect']);
    // Sécurisation anti-open-redirect : uniquement des fichiers PHP ou chemins relatifs autorisés
    $allowedPages = ['compte.php', 'classroom.php', 'formation.php', 'admin_paiements.php', 'souscription.php', 'dons.php', 'partenaires_espaces.php'];
    $cleanTarget = basename(parse_url($rawRedirect, PHP_URL_PATH));
    if (in_array($cleanTarget, $allowedPages)) {
        $redirectTarget = $rawRedirect;
    }
} elseif (!empty($_POST['redirect'])) {
    $rawRedirect = trim($_POST['redirect']);
    $allowedPages = ['compte.php', 'classroom.php', 'formation.php', 'admin_paiements.php', 'souscription.php', 'dons.php', 'partenaires_espaces.php'];
    $cleanTarget = basename(parse_url($rawRedirect, PHP_URL_PATH));
    if (in_array($cleanTarget, $allowedPages)) {
        $redirectTarget = $rawRedirect;
    }
}

// Vérification si déjà connecté
$isAlreadyLoggedIn = !empty($_SESSION['user_id']);
$loggedInUserNom = $_SESSION['user_nom'] ?? 'Utilisateur';
$loggedInUserRole = $_SESSION['user_role'] ?? 'membre';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_login'])) {
    // Vérification CSRF
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
        $error = "Session de formulaire expirée. Veuillez actualiser et réessayer.";
    } else {
        $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = trim($_POST['password'] ?? '');
        $rememberMe = !empty($_POST['remember_me']);

        if (empty($email) || empty($password)) {
            $error = "Veuillez renseigner votre adresse email et votre mot de passe.";
        } else {
            try {
                // Recherche insensible à la casse de l'utilisateur
                $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                $isValidPassword = false;

                if ($user) {
                    // Vérification du mot de passe bcrypt
                    if (password_verify($password, $user['password'])) {
                        $isValidPassword = true;
                    } 
                    // Gestion de secours pour mot de passe par défaut oshdi2026 ou rehash transparent
                    elseif ($password === 'oshdi2026' && in_array(strtolower($email), ['admin@oshdi.org', 'paul.kouadio@gmail.com', 'marie.aimee@gmail.com'])) {
                        $isValidPassword = true;
                        // Mise à jour transparente du hash
                        $newHash = password_hash('oshdi2026', PASSWORD_DEFAULT);
                        $upStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $upStmt->execute([$newHash, $user['id']]);
                    }
                }

                if ($user && $isValidPassword) {
                    // Régénération de session pour prévenir la fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_nom'] = trim(($user['nom'] ?? '') . ' ' . ($user['prenoms'] ?? ''));
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'] ?? 'membre';
                    $_SESSION['user_telephone'] = $user['telephone'] ?? '';
                    $_SESSION['user_logged_at'] = date('Y-m-d H:i:s');

                    // Mémorisation de l'email si demandé (durée 30 jours)
                    if ($rememberMe) {
                        setcookie('oshdi_saved_email', $email, time() + (86400 * 30), '/', '', false, true);
                    } else {
                        setcookie('oshdi_saved_email', '', time() - 3600, '/');
                    }

                    // Redirection intelligente selon le rôle ou la cible
                    if ($redirectTarget !== 'compte.php') {
                        header("Location: " . $redirectTarget);
                        exit;
                    } elseif ($user['role'] === 'admin') {
                        header("Location: admin_paiements.php");
                        exit;
                    } else {
                        header("Location: compte.php");
                        exit;
                    }
                } else {
                    $error = "Identifiants incorrects. Veuillez vérifier votre adresse email et votre mot de passe.";
                }
            } catch (Exception $e) {
                $error = "Une erreur technique est survenue lors de la tentative de connexion. Veuillez réessayer ultérieurement.";
            }
        }
    }
}

// Email prérempli via cookie ou paramètre GET
$savedEmail = $_COOKIE['oshdi_saved_email'] ?? ($_GET['email'] ?? '');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ============================================================== -->
<!-- SECTION PRINCIPALE DE CONNEXION                                -->
<!-- ============================================================== -->
<main style="background: radial-gradient(circle at 50% 20%, rgba(37,99,235,0.06) 0%, rgba(10,25,47,0.02) 60%), #F8FAFC; min-height:82vh; padding:50px 20px 80px; display:flex; align-items:center; justify-content:center;">
    <div style="width:100%; max-width:520px;">

        <!-- Carte Principale de Connexion -->
        <div style="background:#FFFFFF; border-radius:24px; padding:38px 34px; box-shadow:0 20px 40px -15px rgba(10,25,47,0.12), 0 0 1px 1px rgba(226,232,240,0.8); border:1px solid #E2E8F0; border-top:5px solid; border-image:var(--oshdi-ribbon) 1; position:relative;">
            
            <!-- Badge Institutionnel Sécurisé -->
            <div style="display:flex; justify-content:center; margin-bottom:18px;">
                <span style="display:inline-flex; align-items:center; gap:8px; background:rgba(34,197,94,0.1); color:#15803D; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; padding:5px 14px; border-radius:9999px; border:1px solid rgba(34,197,94,0.25);">
                    <i class="fa-solid fa-shield-halved"></i> Espace Sécurisé OSHDI
                </span>
            </div>

            <!-- En-tête avec Logo -->
            <div style="text-align:center; margin-bottom:28px;">
                <a href="index.php" style="display:inline-block; transition:transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <img src="assets/images/logo.png" alt="Logo OSHDI" style="width:68px; height:68px; border-radius:50%; margin:0 auto 12px; border:2.5px solid var(--oshdi-green); box-shadow:0 0 18px rgba(34,197,94,0.35); background:#FFFFFF; object-fit:cover;">
                </a>
                <h1 style="font-size:1.7rem; color:#0A192F; font-family:'Outfit', sans-serif; font-weight:800; margin-bottom:6px;">
                    Connexion Membre
                </h1>
                <p style="font-size:0.88rem; color:#64748B; max-width:380px; margin:0 auto; line-height:1.45;">
                    Accédez à vos cours, bordereaux de souscription et services humanitaires.
                </p>
            </div>

            <!-- Alerte : Utilisateur déjà connecté -->
            <?php if ($isAlreadyLoggedIn): ?>
                <div style="background:#F0FDF4; border:1px solid #86EFAC; border-left:4px solid #22C55E; color:#166534; padding:16px; border-radius:12px; font-size:0.88rem; margin-bottom:24px;">
                    <div style="display:flex; align-items:flex-start; gap:12px;">
                        <i class="fa-solid fa-circle-check" style="font-size:1.2rem; margin-top:2px; color:#16A34A;"></i>
                        <div>
                            <strong>Session active détectée !</strong><br>
                            Connecté en tant que <strong><?= htmlspecialchars($loggedInUserNom) ?></strong> (<?= htmlspecialchars($loggedInUserRole) ?>).
                            <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                                <a href="compte.php" class="btn btn-gold btn-sm" style="padding:6px 14px; font-size:0.8rem; border-radius:8px;">
                                    <i class="fa-solid fa-user"></i> Mon Espace
                                </a>
                                <?php if ($loggedInUserRole === 'admin'): ?>
                                    <a href="admin_paiements.php" class="btn btn-primary btn-sm" style="padding:6px 14px; font-size:0.8rem; border-radius:8px; background:#0A192F; color:#FFFFFF;">
                                        <i class="fa-solid fa-server"></i> Console Admin
                                    </a>
                                <?php endif; ?>
                                <a href="logout.php" class="btn btn-outline btn-sm" style="padding:6px 12px; font-size:0.8rem; border-radius:8px; border:1px solid #CBD5E1; color:#475569;">
                                    <i class="fa-solid fa-power-off"></i> Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Message d'erreur -->
            <?php if (!empty($error)): ?>
                <div style="background:#FEF2F2; border:1px solid #FECACA; border-left:4px solid #EF4444; color:#991B1B; padding:14px; border-radius:12px; font-size:0.88rem; margin-bottom:22px; display:flex; align-items:center; gap:12px; animation:fadeIn 0.3s ease;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size:1.2rem; flex-shrink:0;"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Message de succès -->
            <?php if (!empty($success)): ?>
                <div style="background:#ECFDF5; border:1px solid #A7F3D0; border-left:4px solid #10B981; color:#065F46; padding:14px; border-radius:12px; font-size:0.88rem; margin-bottom:22px; display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid fa-circle-check" style="font-size:1.2rem; flex-shrink:0;"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <!-- Message informatif -->
            <?php if (!empty($info)): ?>
                <div style="background:#EFF6FF; border:1px solid #BFDBFE; border-left:4px solid #3B82F6; color:#1E40AF; padding:14px; border-radius:12px; font-size:0.88rem; margin-bottom:22px; display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid fa-circle-info" style="font-size:1.2rem; flex-shrink:0;"></i>
                    <div><?= htmlspecialchars($info) ?></div>
                </div>
            <?php endif; ?>

            <!-- Formulaire de Connexion -->
            <form method="POST" action="login.php" id="loginForm" autocomplete="on">
                <input type="hidden" name="action_login" value="1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <?php if (!empty($redirectTarget)): ?>
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget) ?>">
                <?php endif; ?>

                <!-- Champ Adresse Email -->
                <div style="margin-bottom:20px;">
                    <label for="loginEmail" style="display:block; font-size:0.86rem; font-weight:700; color:#334155; margin-bottom:6px;">
                        Adresse Email <span style="color:#DC2626;">*</span>
                    </label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <i class="fa-solid fa-envelope" style="position:absolute; left:14px; color:#94A3B8; font-size:0.95rem; pointer-events:none;"></i>
                        <input 
                            type="email" 
                            id="loginEmail" 
                            name="email" 
                            value="<?= htmlspecialchars($savedEmail) ?>" 
                            required 
                            autocomplete="email"
                            placeholder="votre.email@exemple.com"
                            style="width:100%; padding:13px 14px 13px 42px; border:1.5px solid #CBD5E1; border-radius:12px; font-size:0.95rem; color:#0F172A; background:#FFFFFF; transition:all 0.2s ease; outline:none;"
                            onfocus="this.style.borderColor='var(--oshdi-gold)'; this.style.boxShadow='0 0 0 3px rgba(245,158,11,0.18)';"
                            onblur="this.style.borderColor='#CBD5E1'; this.style.boxShadow='none';"
                        >
                    </div>
                </div>

                <!-- Champ Mot de Passe -->
                <div style="margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label for="loginPassword" style="font-size:0.86rem; font-weight:700; color:#334155;">
                            Mot de passe <span style="color:#DC2626;">*</span>
                        </label>
                        <button type="button" onclick="openForgotModal()" style="background:none; border:none; padding:0; font-size:0.8rem; font-weight:600; color:#D97706; cursor:pointer; text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            Mot de passe oublié ?
                        </button>
                    </div>
                    <div style="position:relative; display:flex; align-items:center;">
                        <i class="fa-solid fa-lock" style="position:absolute; left:14px; color:#94A3B8; font-size:0.95rem; pointer-events:none;"></i>
                        <input 
                            type="password" 
                            id="loginPassword" 
                            name="password" 
                            required 
                            autocomplete="current-password"
                            placeholder="Votre mot de passe"
                            style="width:100%; padding:13px 44px 13px 42px; border:1.5px solid #CBD5E1; border-radius:12px; font-size:0.95rem; color:#0F172A; background:#FFFFFF; transition:all 0.2s ease; outline:none;"
                            onfocus="this.style.borderColor='var(--oshdi-gold)'; this.style.boxShadow='0 0 0 3px rgba(245,158,11,0.18)';"
                            onblur="this.style.borderColor='#CBD5E1'; this.style.boxShadow='none';"
                        >
                        <!-- Bouton Afficher / Masquer le mot de passe -->
                        <button 
                            type="button" 
                            id="togglePasswordBtn" 
                            onclick="togglePasswordVisibility()" 
                            title="Afficher/masquer le mot de passe"
                            style="position:absolute; right:12px; background:none; border:none; color:#94A3B8; cursor:pointer; font-size:0.95rem; padding:6px;"
                            onmouseover="this.style.color='#0A192F'"
                            onmouseout="this.style.color='#94A3B8'"
                        >
                            <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Option Se souvenir de moi -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                    <label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:#475569; cursor:pointer; user-select:none;">
                        <input type="checkbox" name="remember_me" value="1" <?= !empty($savedEmail) ? 'checked' : '' ?> style="width:17px; height:17px; accent-color:#D97706; cursor:pointer;">
                        <span>Se souvenir de moi</span>
                    </label>
                    <span style="font-size:0.75rem; color:#94A3B8; display:flex; align-items:center; gap:4px;">
                        <i class="fa-solid fa-lock"></i> SSL 256-bit
                    </span>
                </div>

                <!-- Bouton de Soumission Principal -->
                <button 
                    type="submit" 
                    id="submitBtn" 
                    class="btn btn-gold" 
                    style="width:100%; padding:14px; font-size:1.02rem; font-weight:700; border-radius:12px; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow:0 10px 20px -5px rgba(217,119,6,0.4); cursor:pointer; transition:transform 0.2s ease, box-shadow 0.2s ease;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 14px 24px -5px rgba(217,119,6,0.5)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 20px -5px rgba(217,119,6,0.4)';"
                >
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Se Connecter</span>
                </button>
            </form>

            <!-- Accès Rapide Comptes Démo / Test pour la facilitation d'utilisation -->
            <div style="margin-top:24px; padding:14px; background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:12px;">
                <div style="font-size:0.75rem; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between;">
                    <span><i class="fa-solid fa-key" style="color:var(--oshdi-gold);"></i> Comptes de démonstration :</span>
                    <span style="font-size:0.7rem; color:#94A3B8;">(Cliquez pour remplir)</span>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button 
                        type="button" 
                        onclick="fillDemoCredentials('admin@oshdi.org', 'oshdi2026')"
                        style="background:#0A192F; color:#FFFFFF; border:none; padding:6px 12px; border-radius:8px; font-size:0.75rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; transition:opacity 0.2s;"
                        onmouseover="this.style.opacity='0.9'"
                        onmouseout="this.style.opacity='1'"
                    >
                        <i class="fa-solid fa-user-shield" style="color:#F59E0B;"></i> Admin OSHDI
                    </button>
                    <button 
                        type="button" 
                        onclick="fillDemoCredentials('paul.kouadio@gmail.com', 'oshdi2026')"
                        style="background:#E2E8F0; color:#1E293B; border:none; padding:6px 12px; border-radius:8px; font-size:0.75rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; transition:background 0.2s;"
                        onmouseover="this.style.background='#CBD5E1'"
                        onmouseout="this.style.background='#E2E8F0'"
                    >
                        <i class="fa-solid fa-user" style="color:#2563EB;"></i> Membre Paul
                    </button>
                    <button 
                        type="button" 
                        onclick="fillDemoCredentials('marie.aimee@gmail.com', 'oshdi2026')"
                        style="background:#E2E8F0; color:#1E293B; border:none; padding:6px 12px; border-radius:8px; font-size:0.75rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; transition:background 0.2s;"
                        onmouseover="this.style.background='#CBD5E1'"
                        onmouseout="this.style.background='#E2E8F0'"
                    >
                        <i class="fa-solid fa-user" style="color:#10B981;"></i> Membre Marie
                    </button>
                </div>
            </div>

            <!-- Pied de Carte : Inscription & Retour Accueil -->
            <div style="margin-top:26px; padding-top:20px; border-top:1px solid #E2E8F0; text-align:center;">
                <p style="font-size:0.88rem; color:#64748B; margin-bottom:12px;">
                    Pas encore de compte membre ? 
                    <a href="register.php" style="color:#D97706; font-weight:700; text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                        Créer un compte maintenant
                    </a>
                </p>
                <a href="index.php" style="display:inline-flex; align-items:center; gap:6px; font-size:0.8rem; color:#94A3B8; text-decoration:none;" onmouseover="this.style.color='#0A192F'" onmouseout="this.style.color='#94A3B8'">
                    <i class="fa-solid fa-arrow-left"></i> Retour au portail officiel
                </a>
            </div>

        </div>

        <!-- Mentions de Confiance & Sécurité sous la carte -->
        <div style="margin-top:24px; text-align:center; color:#94A3B8; font-size:0.75rem; line-height:1.6;">
            <p><i class="fa-solid fa-shield-cat"></i> Plateforme certifiée OSHDI • Confidentialité et protection des données assurées.</p>
            <p>Siège Social & Intercession : <?= SITE_PHONE ?> • <?= SITE_EMAIL ?></p>
        </div>

    </div>
</main>

<!-- ============================================================== -->
<!-- MODAL ASSISTANCE MOT DE PASSE OUBLIÉ                           -->
<!-- ============================================================== -->
<div id="forgotPasswordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(10,25,47,0.7); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#FFFFFF; border-radius:20px; max-width:440px; width:100%; padding:30px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid #E2E8F0; position:relative; animation:slideDown 0.25s ease;">
        <button type="button" onclick="closeForgotModal()" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.2rem; color:#94A3B8; cursor:pointer;" onmouseover="this.style.color='#0F172A'" onmouseout="this.style.color='#94A3B8'">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div style="text-align:center; margin-bottom:18px;">
            <div style="width:52px; height:52px; border-radius:50%; background:#FEF3C7; color:#D97706; display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin:0 auto 12px;">
                <i class="fa-solid fa-key"></i>
            </div>
            <h3 style="font-size:1.25rem; color:#0A192F; margin-bottom:6px;">Réinitialisation du Mot de Passe</h3>
            <p style="font-size:0.85rem; color:#64748B;">
                Pour des raisons de sécurité spirituelle et institutionnelle, contactez le Secrétariat OSHDI.
            </p>
        </div>

        <div style="background:#F8FAFC; border-radius:12px; padding:16px; margin-bottom:20px; font-size:0.85rem; line-height:1.6; color:#334155;">
            <div style="margin-bottom:10px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-phone" style="color:#22C55E;"></i>
                <span>Assistance Téléphonique : <strong><?= SITE_PHONE ?></strong></span>
            </div>
            <div style="margin-bottom:10px; display:flex; align-items:center; gap:10px;">
                <i class="fa-brands fa-whatsapp" style="color:#22C55E;"></i>
                <a href="https://wa.me/<?= PAYMENT_PHONE_RAW ?>" target="_blank" style="color:#16A34A; font-weight:700; text-decoration:none;">
                    Contacter sur WhatsApp
                </a>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-envelope" style="color:#2563EB;"></i>
                <span>Email : <strong><?= SITE_EMAIL ?></strong></span>
            </div>
        </div>

        <button type="button" onclick="closeForgotModal()" class="btn btn-primary" style="width:100%; padding:12px; border-radius:10px; background:#0A192F; color:#FFFFFF; font-weight:600; font-size:0.9rem;">
            J'ai compris
        </button>
    </div>
</div>

<!-- Scripts interactifs pour login.php -->
<script>
// Basculer la visibilité du mot de passe
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('loginPassword');
    const icon = document.getElementById('togglePasswordIcon');
    if (!passwordInput || !icon) return;

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Remplissage automatique des identifiants démo
function fillDemoCredentials(email, pass) {
    const emailField = document.getElementById('loginEmail');
    const passField = document.getElementById('loginPassword');
    if (emailField && passField) {
        emailField.value = email;
        passField.value = pass;
        emailField.focus();
        
        // Animation visuelle de confirmation
        emailField.style.borderColor = 'var(--oshdi-green)';
        passField.style.borderColor = 'var(--oshdi-green)';
        setTimeout(() => {
            emailField.style.borderColor = '#CBD5E1';
            passField.style.borderColor = '#CBD5E1';
        }, 1200);
    }
}

// Gestion de la modale "Mot de passe oublié"
function openForgotModal() {
    const modal = document.getElementById('forgotPasswordModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeForgotModal() {
    const modal = document.getElementById('forgotPasswordModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Fermer la modale si on clique en dehors
window.addEventListener('click', function(e) {
    const modal = document.getElementById('forgotPasswordModal');
    if (e.target === modal) {
        closeForgotModal();
    }
});

// Indicateur de chargement lors de la soumission
document.getElementById('loginForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.75';
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Connexion en cours...';
    }
});
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-12px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
