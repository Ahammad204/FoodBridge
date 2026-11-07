<?php
// FoodBridge — logout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data and the session cookie, then destroy the session.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

// Fresh session only for the goodbye flash message.
session_start();
$_SESSION['flash'] = ['type' => 'success', 'message' => 'You have been logged out.'];

header('Location: /FoodBridge/auth/login.php');
exit;
