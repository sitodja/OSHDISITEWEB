<?php
// Redirection transparente vers la page de connexion principale
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ../login.php' . $queryString, true, 302);
exit;
