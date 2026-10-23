<?php
// Redirection transparente vers la page d'inscription principale
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ../register.php' . $queryString, true, 302);
exit;
