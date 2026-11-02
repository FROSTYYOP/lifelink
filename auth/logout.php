<?php
/**
 * LifeLink - Authentication: Logout
 */
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session storage
session_destroy();

// Start fresh temporary session to deliver flash message
session_start();
set_flash('info', 'You have been successfully logged out. Stay safe!');

header('Location: ../index.php');
exit;
