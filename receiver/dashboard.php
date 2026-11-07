<?php
// FoodBridge — receiver dashboard (placeholder; request pages come next)
require_once __DIR__ . '/../includes/auth_check.php';
require_role('receiver');

$pageTitle = 'Receiver Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3">Receiver Dashboard</h1>
<?php flash_show(); ?>
<div class="card">
    <div class="card-body">
        <p class="mb-1">Welcome, <strong><?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>
        <p class="text-muted mb-0">Available donations and your requests will live here.</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
