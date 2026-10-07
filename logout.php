<?php
require_once __DIR__ . '/config/db.php';

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

// Destroy session
session_destroy();

session_start();
$_SESSION['flash_success'] = 'You have logged out successfully.';
header('Location: index.php');
exit;
