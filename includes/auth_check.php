<?php
// FoodBridge — auth guard + CSRF helpers.
// Usage at the top of a protected page:
//   require_once __DIR__ . '/../includes/auth_check.php';
//   require_login();            // any logged-in user
//   require_role('admin');      // only a specific role

if (session_status() === PHP_SESSION_NONE) {
    // Hardened session cookie: not readable by JavaScript, Lax same-site.
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
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

// Where each role lands after login.
function dashboard_url(string $role, string $baseUrl = '/FoodBridge'): string
{
    return match ($role) {
        'admin'    => $baseUrl . '/admin/dashboard.php',
        'donor'    => $baseUrl . '/donor/dashboard.php',
        'receiver' => $baseUrl . '/receiver/dashboard.php',
        default    => $baseUrl . '/index.php',
    };
}

function redirect_to_dashboard(string $baseUrl = '/FoodBridge'): void
{
    header('Location: ' . dashboard_url($_SESSION['role'] ?? '', $baseUrl));
    exit;
}

// ---- CSRF protection ----

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Invalid CSRF token. Please go back and try again.');
    }
}

// ---- Flash messages (one-time success/error notes across redirects) ----

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_show(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $class = $flash['type'] === 'success' ? 'alert-success' : 'alert-danger';
    echo '<div class="alert ' . $class . '" role="alert">'
        . htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8')
        . '</div>';
}
