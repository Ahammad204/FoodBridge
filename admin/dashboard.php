<?php
// FoodBridge — admin dashboard (placeholder; management pages come next)
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3">Admin Dashboard</h1>
<?php flash_show(); ?>
<div class="card">
    <div class="card-body">
        <p class="mb-1">Welcome, <strong><?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>
        <p class="text-muted mb-0">User management, donation oversight and reports will live here.</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
