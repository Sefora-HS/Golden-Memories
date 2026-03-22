<?php
require_once __DIR__ . '/../modele/config.php';

// On vide toutes les variables de session
$_SESSION = array();

// Si on veut détruire complètement la session, on efface aussi le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// On détruit la session côté serveur
session_destroy();

// Redirection vers la page de login
header("Location: " . BASE_URL . "/vue/pages/login.php");
exit;