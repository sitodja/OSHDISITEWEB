<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/translations.php';
require_once __DIR__ . '/../config/db.php';

// Variables SEO dynamiques avancées (Google Top Ranking)
$currentScript = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$canonical_url = $canonical_url ?? (SITE_URL . '/' . $currentScript);
$page_title_full = !empty($page_title) ? (htmlspecialchars($page_title) . ' | OSHDI') : (SITE_FULL_NAME . ' | OSHDI');
$page_description = $page_description ?? 'Institution chrétienne et humanitaire OSHDI : restauration des vies brisées, soutien aux orphelins, veuves, malades, prières de délivrance et concours Africa Best Menu.';
$page_keywords = $page_keywords ?? 'OSHDI, œuvres spirituelles, humanitaire divine, prière de délivrance, souveraineté de dieu, aide orphelins veuves, REMDIS santé, concours ABM';
$og_image = $og_image ?? (SITE_URL . '/assets/images/logo.png');
$og_type = $og_type ?? 'website';

$og_locales = [
    'fr' => 'fr_FR',
    'en' => 'en_US',
    'de' => 'de_DE',
    'es' => 'es_ES',
    'zh' => 'zh_CN',
    'ar' => 'ar_AR',
];
$current_og_locale = $og_locales[$current_lang] ?? 'fr_FR';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>" dir="<?= !empty($is_rtl) ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    
    <!-- ==================== RÉFÉRENCEMENT NATUREL (SEO GOOGLE #1) ==================== -->
    <title><?= $page_title_full ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <meta name="author" content="OSHDI - Œuvres Spirituelles et Humanitaires Divines">
    
    <!-- URL Canonique Officielle (Évite le contenu dupliqué) -->
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">

    <!-- Directives d'Indexation pour Googlebot & Moteurs de Recherche -->
    <?php if (!empty($noindex)): ?>
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    <?php else: ?>
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <?php endif; ?>

    <!-- Balises Hreflang Multi-Langues Internationales pour Googlebot -->
    <?php foreach ($AVAILABLE_LANGUAGES as $code => $info): ?>
    <link rel="alternate" hreflang="<?= $code ?>" href="<?= htmlspecialchars(SITE_URL . '/' . $currentScript . ($code === 'fr' ? '' : '?lang=' . $code)) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars(SITE_URL . '/' . $currentScript) ?>">

    <!-- Open Graph Protocol (Facebook, WhatsApp, LinkedIn, Telegram) -->
    <meta property="og:locale" content="<?= $current_og_locale ?>">
    <meta property="og:type" content="<?= htmlspecialchars($og_type) ?>">
    <meta property="og:title" content="<?= $page_title_full ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta property="og:site_name" content="OSHDI - Œuvres Spirituelles et Humanitaires Divines">
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
    <meta property="og:image:secure_url" content="<?= htmlspecialchars($og_image) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Logo Officiel OSHDI">

    <!-- Twitter Cards (X / Twitter Rich Snippets) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@OSHDI_Official">
    <meta name="twitter:creator" content="@OSHDI_Official">
    <meta name="twitter:title" content="<?= $page_title_full ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">

    <!-- Données Structurées Schema.org JSON-LD (Rich Snippets Google) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "NGO",
          "@id": "https://oshdi.org/#organization",
          "name": "OSHDI",
          "legalName": "Œuvres Spirituelles et Humanitaires Divines",
          "alternateName": ["Organisation OSHDI", "Institution OSHDI", "OSHDI International"],
          "url": "https://oshdi.org/",
          "logo": {
            "@type": "ImageObject",
            "@id": "https://oshdi.org/#logo",
            "url": "https://oshdi.org/assets/images/logo.png",
            "contentUrl": "https://oshdi.org/assets/images/logo.png",
            "caption": "Logo OSHDI"
          },
          "image": "https://oshdi.org/assets/images/logo.png",
          "description": "Institution chrétienne et humanitaire pour la restauration des vies brisées : malades, orphelins, veuves, prisonniers et démunis.",
          "telephone": "+2250747166461",
          "email": "contact@oshdi.org",
          "address": {
            "@type": "PostalAddress",
            "addressLocality": "Abidjan / Yamoussoukro",
            "addressCountry": "CI"
          },
          "sameAs": [
            "https://www.facebook.com/oshdi",
            "https://www.youtube.com/oshdi"
          ]
        },
        {
          "@type": "WebSite",
          "@id": "https://oshdi.org/#website",
          "url": "https://oshdi.org/",
          "name": "OSHDI",
          "description": "Portail Officiel des Œuvres Spirituelles et Humanitaires Divines",
          "publisher": {
            "@id": "https://oshdi.org/#organization"
          },
          "inLanguage": "<?= htmlspecialchars($current_lang) ?>",
          "potentialAction": {
            "@type": "SearchAction",
            "target": {
              "@type": "EntryPoint",
              "urlTemplate": "https://oshdi.org/recherche_activites.php?q={search_term_string}"
            },
            "query-input": "required name=search_term_string"
          }
        }
        <?php if (!empty($extra_schema_json)): ?>,
        <?= $extra_schema_json ?>
        <?php endif; ?>
      ]
    }
    </script>
    
    <!-- Favicon OSHDI -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($is_rtl)): ?>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Main Style (avec cache buster automatique) -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <?php if (isset($extra_css)): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($extra_css) ?>?v=<?= file_exists(__DIR__ . '/../' . $extra_css) ? filemtime(__DIR__ . '/../' . $extra_css) : time() ?>">
    <?php endif; ?>

    <?php if (!empty($is_rtl)): ?>
    <style>
        body {
            direction: rtl;
            text-align: right;
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
        }
        .topbar-info {
            direction: rtl;
        }
        .topbar-info span {
            margin-left: 18px;
            margin-right: 0;
        }
        .nav-menu {
            direction: rtl;
        }
        .footer-col {
            text-align: right;
        }
        .footer-links li a i {
            transform: rotate(180deg);
            margin-left: 8px;
            margin-right: 0;
        }
        .lang-dropdown-menu {
            right: auto !important;
            left: 0 !important;
        }
    </style>
    <?php endif; ?>
</head>
<body>

<!-- Topbar avec Infos & Sélecteur Multi-langues (FR, EN, DE, ES, ZH, AR) -->
<div class="topbar">
    <div class="topbar-container">
        <div class="topbar-info">
            <span><i class="fa-solid fa-phone"></i> <?= SITE_PHONE ?></span>
            <span><i class="fa-solid fa-envelope"></i> <?= SITE_EMAIL ?></span>
            <span><i class="fa-solid fa-location-dot"></i> Siège & Représentations Internationales</span>
        </div>
        <div class="topbar-actions" style="display:flex; align-items:center; gap:14px;">
            <!-- Sélecteur de langue interactif 6 Langues avec menu déroulant -->
            <div class="lang-dropdown-wrapper" style="position:relative;">
                <button type="button" class="lang-dropdown-btn" id="langDropdownTrigger" onclick="toggleLangMenu(event)" style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.22); color:#FFFFFF; border-radius:9999px; padding:4px 12px; font-size:0.8rem; font-weight:600; cursor:pointer; transition:all 0.2s ease;">
                    <span style="font-size:1.05rem; line-height:1;"><?= $AVAILABLE_LANGUAGES[$current_lang]['flag'] ?></span>
                    <span><?= htmlspecialchars($AVAILABLE_LANGUAGES[$current_lang]['label']) ?></span>
                    <i class="fa-solid fa-chevron-down" style="font-size:0.65rem; color:#F59E0B; margin-left:2px; transition:transform 0.2s;" id="langChevron"></i>
                </button>

                <div class="lang-dropdown-menu" id="langDropdownMenu" style="display:none; position:absolute; top:calc(100% + 8px); right:0; background:#0A192F; border:1px solid rgba(255,255,255,0.18); border-radius:14px; box-shadow:0 16px 36px rgba(0,0,0,0.45); padding:6px; min-width:190px; z-index:99999; -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px);">
                    <div style="font-size:0.68rem; color:#94A3B8; text-transform:uppercase; letter-spacing:0.8px; padding:6px 10px 4px; font-weight:700; border-bottom:1px solid rgba(255,255,255,0.08); margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-globe" style="color:#F59E0B;"></i> 
                        <span><?= $current_lang === 'ar' ? 'اختر اللغة' : ($current_lang === 'zh' ? '选择语言' : ($current_lang === 'es' ? 'Seleccionar idioma' : ($current_lang === 'de' ? 'Sprache wählen' : ($current_lang === 'en' ? 'Select Language' : 'Langue du site')))) ?></span>
                    </div>
                    <?php foreach ($AVAILABLE_LANGUAGES as $code => $info): ?>
                        <a href="<?= htmlspecialchars(buildLangUrl($code)) ?>" style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-radius:8px; font-size:0.84rem; color:<?= $code === $current_lang ? '#F59E0B' : '#E2E8F0' ?>; background:<?= $code === $current_lang ? 'rgba(245,158,11,0.16)' : 'transparent' ?>; text-decoration:none; font-weight:<?= $code === $current_lang ? '700' : '500' ?>; transition:all 0.15s ease;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='<?= $code === $current_lang ? 'rgba(245,158,11,0.16)' : 'transparent' ?>'">
                            <span style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:1.1rem; line-height:1;"><?= $info['flag'] ?></span>
                                <span><?= htmlspecialchars($info['name']) ?></span>
                            </span>
                            <?php if ($code === $current_lang): ?>
                                <i class="fa-solid fa-check" style="font-size:0.75rem; color:#F59E0B;"></i>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Espace Utilisateur -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                    <a href="admin_paiements.php" style="color:#38BDF8; font-weight:700;"><i class="fa-solid fa-shield-halved"></i> Admin</a>
                <?php endif; ?>
                <a href="compte.php" style="color:#F59E0B; font-weight:700;"><i class="fa-solid fa-user-circle"></i> <?= t('nav_account') ?></a>
                <a href="logout.php" style="color:#94A3B8;"><i class="fa-solid fa-power-off"></i> <?= t('nav_logout') ?></a>
            <?php else: ?>
                <a href="login.php" style="color:#CBD5E1;"><i class="fa-solid fa-user"></i> <?= t('nav_login') ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Gestion du menu déroulant de sélection de langue
function toggleLangMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('langDropdownMenu');
    const chevron = document.getElementById('langChevron');
    if (!menu) return;
    const isShown = menu.style.display === 'block';
    menu.style.display = isShown ? 'none' : 'block';
    if (chevron) {
        chevron.style.transform = isShown ? 'rotate(0deg)' : 'rotate(180deg)';
    }
}

document.addEventListener('click', function(e) {
    const menu = document.getElementById('langDropdownMenu');
    const btn = document.getElementById('langDropdownTrigger');
    const chevron = document.getElementById('langChevron');
    if (menu && menu.style.display === 'block') {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            menu.style.display = 'none';
            if (chevron) chevron.style.transform = 'rotate(0deg)';
        }
    }
});
</script>
