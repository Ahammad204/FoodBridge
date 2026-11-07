<?php
// FoodBridge — auth guard.
// Usage at the top of a protected page:
//   require_once __DIR__ . '/../includes/auth_check.php';
//   require_login();            // any logged-in user
//   require_role('admin');      // only a specific role

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(string $baseUrl = '/FoodBridge'): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $baseUrl . '/auth/login.php');
        exit;
    }
}

function require_role(string $role, string $baseUrl = '/FoodBridge'): void
{
    require_login($baseUrl);
    if (($_SESSION['role'] ?? null) !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}
