<?php
// FoodBridge — admin sidebar. Set $adminPage before including
// ('dashboard', 'users', 'verify', 'donations', 'requests', 'reports').
// Expects $pdo (for the pending-verification badge) and $baseUrl.
$baseUrl   = $baseUrl ?? '/FoodBridge';
$adminPage = $adminPage ?? '';

$pendingVerify = 0;
if (isset($pdo)) {
    $pendingVerify = (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'donor' AND is_verified = 0 AND status = 'active'"
    )->fetchColumn();
}

$adminLinks = [
    'dashboard' => ['Dashboard', '/admin/dashboard.php'],
    'users'     => ['Users', '/admin/users.php'],
    'verify'    => ['Verify Donors', '/admin/verify_donors.php'],
    'donations' => ['Donations', '/admin/donations.php'],
    'requests'  => ['Requests', '/admin/requests.php'],
    'reports'   => ['Reports', '/admin/reports.php'],
];
?>
<div class="list-group admin-sidebar">
    <span class="list-group-item fw-bold text-white" style="background-color: var(--fb-green-dark);">Admin Panel</span>
    <?php foreach ($adminLinks as $key => [$label, $path]): ?>
        <a href="<?= $baseUrl . $path ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $adminPage === $key ? 'active' : '' ?>">
            <?= $label ?>
            <?php if ($key === 'verify' && $pendingVerify > 0): ?>
                <span class="badge bg-danger rounded-pill"><?= $pendingVerify ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
    <a href="<?= $baseUrl ?>/index.php" class="list-group-item list-group-item-action">Back to site</a>
    <a href="<?= $baseUrl ?>/auth/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
</div>
