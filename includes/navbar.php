<?php
// FoodBridge — responsive navbar; links change by login state and role.
$baseUrl    = $baseUrl ?? BASE_URL;
$isLoggedIn = !empty($_SESSION['user_id']);
$role       = $_SESSION['role'] ?? null;
$userName   = $_SESSION['name'] ?? '';
$current    = basename($_SERVER['PHP_SELF'] ?? '');

$dashboardUrl = match ($role) {
    'admin'    => $baseUrl . '/admin/dashboard.php',
    'donor'    => $baseUrl . '/donor/dashboard.php',
    'receiver' => $baseUrl . '/receiver/dashboard.php',
    default    => $baseUrl . '/index.php',
};
?>
<nav class="navbar navbar-expand-lg navbar-dark fb-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $baseUrl ?>/index.php">Food<span class="brand-accent">Bridge</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>" href="<?= $baseUrl ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'about.php' ? 'active' : '' ?>" href="<?= $baseUrl ?>/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'contact.php' ? 'active' : '' ?>" href="<?= $baseUrl ?>/contact.php">Contact</a>
                </li>
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="<?= $dashboardUrl ?>">
                            <?= $role === 'admin' ? 'Admin Dashboard' : ($role === 'donor' ? 'Donor Dashboard' : 'Receiver Dashboard') ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                <?php if ($isLoggedIn): ?>
                    <?php
                    // Notification bell (only where a DB connection exists).
                    $notifCount = 0;
                    $notifLatest = [];
                    if (isset($pdo)) {
                        require_once __DIR__ . '/notify.php';
                        try {
                            $notifCount  = unread_notification_count($pdo, (int) $_SESSION['user_id']);
                            $notifLatest = latest_notifications($pdo, (int) $_SESSION['user_id'], 5);
                        } catch (Throwable $e) {
                            $notifCount = 0;
                            $notifLatest = [];
                        }
                    }
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                            &#128276;
                            <?php if ($notifCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notifCount ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 320px;">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if (!$notifLatest): ?>
                                <li><span class="dropdown-item-text text-muted small">No notifications yet.</span></li>
                            <?php endif; ?>
                            <?php foreach ($notifLatest as $n): ?>
                                <li>
                                    <a class="dropdown-item small <?= $n['is_read'] ? '' : 'fw-bold' ?>"
                                       href="<?= $baseUrl ?>/notifications.php?go=<?= (int) $n['id'] ?>">
                                        <?= htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8') ?>
                                        <span class="d-block text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($n['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small text-center" href="<?= $baseUrl ?>/notifications.php">View all notifications</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-white-50">Hello, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                            <span class="badge bg-warning text-dark text-capitalize"><?= htmlspecialchars((string) $role, ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $baseUrl ?>/profile.php">Profile</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-light btn-sm" href="<?= $baseUrl ?>/auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $baseUrl ?>/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-fb-orange btn-sm px-3" href="<?= $baseUrl ?>/auth/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
