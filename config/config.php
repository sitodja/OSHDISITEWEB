<?php
// Configuration générale de OSHDI
// Œuvres Spirituelles et Humanitaires Divines

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration du Système Multi-langues International
// Français, Anglais, Allemand, Espagnol, Chinois (mandarin), Arabe
$AVAILABLE_LANGUAGES = [
    'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'label' => 'FR', 'dir' => 'ltr'],
    'en' => ['name' => 'English', 'flag' => '🇬🇧', 'label' => 'EN', 'dir' => 'ltr'],
    'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪', 'label' => 'DE', 'dir' => 'ltr'],
    'es' => ['name' => 'Español', 'flag' => '🇪🇸', 'label' => 'ES', 'dir' => 'ltr'],
    'zh' => ['name' => '中文 (Mandarin)', 'flag' => '🇨🇳', 'label' => '中文', 'dir' => 'ltr'],
    'ar' => ['name' => 'العربية (Arabe)', 'flag' => '🇸🇦', 'label' => 'عربي', 'dir' => 'rtl'],
];

if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $AVAILABLE_LANGUAGES)) {
    $_SESSION['lang'] = $_GET['lang'];
    if (!headers_sent()) {
        setcookie('oshdi_lang', $_GET['lang'], time() + (86400 * 365), '/', '', false, false);
    }
} elseif (!isset($_SESSION['lang']) && isset($_COOKIE['oshdi_lang']) && array_key_exists($_COOKIE['oshdi_lang'], $AVAILABLE_LANGUAGES)) {
    $_SESSION['lang'] = $_COOKIE['oshdi_lang'];
}

$current_lang = $_SESSION['lang'] ?? 'fr';
if (!array_key_exists($current_lang, $AVAILABLE_LANGUAGES)) {
    $current_lang = 'fr';
}
$is_rtl = ($AVAILABLE_LANGUAGES[$current_lang]['dir'] === 'rtl');

// Helper pour conserver les paramètres de l'URL lors du changement de langue
if (!function_exists('buildLangUrl')) {
    function buildLangUrl($langCode) {
        $params = $_GET;
        $params['lang'] = $langCode;
        $script = basename($_SERVER['PHP_SELF'] ?? 'index.php');
        return $script . '?' . http_build_query($params);
    }
}

// Données institutionnelles
define('SITE_NAME', 'OSHDI');
define('SITE_URL', 'https://oshdi.org');
define('SITE_FULL_NAME', 'Œuvres Spirituelles et Humanitaires Divines');
define('SITE_SLOGAN_FR', 'Reconstruire les vies brisées par la prière, la compassion et l\'action humanitaire');
define('SITE_EMAIL', 'contact@oshdi.org');
define('SITE_PHONE', '+225 07 47 16 64 61');
define('PAYMENT_PHONE', '+225 07 47 16 64 61');
define('PAYMENT_PHONE_RAW', '2250747166461');
define('PAYMENT_PHONE_LOCAL', '0747166461');
define('SITE_ADDRESS', 'Siège Social OSHDI, Yamoussoukro, Côte d\'Ivoire & Représentations Internationales');

// Paramètres de paiement acceptés
$AVAILABLE_PAYMENTS = [
    'wave' => ['name' => 'Wave Mobile Money', 'icon' => 'fa-solid fa-water', 'badge' => 'Wave CI / SN'],
    'orange' => ['name' => 'Orange Money', 'icon' => 'fa-solid fa-mobile-screen', 'badge' => 'OM'],
    'mtn' => ['name' => 'MTN Mobile Money', 'icon' => 'fa-solid fa-phone-volume', 'badge' => 'MoMo'],
    'moov' => ['name' => 'Moov Money', 'icon' => 'fa-solid fa-tower-cell', 'badge' => 'Moov'],
    'card' => ['name' => 'Carte Bancaire (Visa / Mastercard)', 'icon' => 'fa-solid fa-credit-card', 'badge' => 'CB / Visa'],
    'paypal' => ['name' => 'PayPal', 'icon' => 'fa-brands fa-paypal', 'badge' => 'PayPal'],
    'crypto' => ['name' => 'Cryptomonnaies (USDT, BTC, ETH)', 'icon' => 'fa-brands fa-bitcoin', 'badge' => 'Crypto'],
];

// Helper pour formater les prix
function formatMoney($amount, $currency = 'FCFA') {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}
