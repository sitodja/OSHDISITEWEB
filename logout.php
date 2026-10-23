<?php
// ==============================================================
// OSHDI - Déconnexion Sécurisée
// ==============================================================
require_once __DIR__ . '/config/config.php';

// Effacer toutes les variables de session
$_SESSION = [];

// Détruire le cookie de session si présent
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Redirection vers la page de connexion avec message
header('Location: login.php?msg=logged_out');
exit;
