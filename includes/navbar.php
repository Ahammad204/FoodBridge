<?php
// FoodBridge — top navigation bar
$baseUrl = $baseUrl ?? '/FoodBridge';
$isLoggedIn = !empty($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $baseUrl ?>/index.php">FoodBridge</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/index.php">Home</a></li>
                <?php if ($isLoggedIn && $role === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/dashboard.php">Admin Dashboard</a></li>
                <?php elseif ($isLoggedIn && $role === 'donor'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/donor/dashboard.php">Donor Dashboard</a></li>
                <?php elseif ($isLoggedIn && $role === 'receiver'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/receiver/dashboard.php">Receiver Dashboard</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item"><span class="nav-link text-white-50"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/auth/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
