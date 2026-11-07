<?php
// FoodBridge — responsive navbar; links change by login state and role.
$baseUrl    = $baseUrl ?? '/FoodBridge';
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
                    <li class="nav-item">
                        <span class="nav-link text-white-50">Hello, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                            <span class="badge bg-warning text-dark text-capitalize"><?= htmlspecialchars((string) $role, ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
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
