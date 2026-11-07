<?php
// FoodBridge — receiver dashboard: my request stats + recent requests
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('receiver');

$baseUrl = BASE_URL;
$receiverId = (int) $_SESSION['user_id'];

function receiver_count(PDO $pdo, int $receiverId, ?string $status): int
{
    if ($status === null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM food_requests WHERE receiver_id = ?');
        $stmt->execute([$receiverId]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM food_requests WHERE receiver_id = ? AND status = ?');
        $stmt->execute([$receiverId, $status]);
    }
    return (int) $stmt->fetchColumn();
}

$requestsSent = receiver_count($pdo, $receiverId, null);
$accepted     = receiver_count($pdo, $receiverId, 'accepted');
$completed    = receiver_count($pdo, $receiverId, 'completed');
$pending      = receiver_count($pdo, $receiverId, 'pending');

$stmt = $pdo->prepare(
    "SELECT fr.*, fd.title AS donation_title, u.name AS donor_name
     FROM food_requests fr
     JOIN food_donations fd ON fd.id = fr.donation_id
     JOIN users u ON u.id = fd.donor_id
     WHERE fr.receiver_id = ?
     ORDER BY fr.created_at DESC LIMIT 5"
);
$stmt->execute([$receiverId]);
$recentRequests = $stmt->fetchAll();

$reqBadge = [
    'pending'   => 'bg-warning text-dark',
    'accepted'  => 'bg-success',
    'rejected'  => 'bg-danger',
    'completed' => 'bg-primary',
];

$pageTitle = 'Receiver Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Receiver Dashboard</h1>
    <a class="btn btn-fb-orange" href="<?= $baseUrl ?>/receiver/browse_food.php">Browse available food</a>
</div>

<?php flash_show(); ?>

<div class="mb-4">
    <a class="btn btn-outline-success btn-sm" href="<?= $baseUrl ?>/receiver/browse_food.php">Browse food</a>
    <a class="btn btn-outline-success btn-sm" href="<?= $baseUrl ?>/receiver/my_requests.php">My requests</a>
    <a class="btn btn-outline-success btn-sm" href="<?= $baseUrl ?>/receiver/history.php">History</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="fb-stat p-4 text-center h-100">
            <div class="fb-stat-number"><?= number_format($requestsSent) ?></div>
            <div class="fb-stat-label">Requests sent</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="fb-stat p-4 text-center h-100" style="border-top-color: var(--fb-orange);">
            <div class="fb-stat-number"><?= number_format($pending) ?></div>
            <div class="fb-stat-label">Pending</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="fb-stat p-4 text-center h-100">
            <div class="fb-stat-number"><?= number_format($accepted) ?></div>
            <div class="fb-stat-label">Accepted</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="fb-stat p-4 text-center h-100">
            <div class="fb-stat-number"><?= number_format($completed) ?></div>
            <div class="fb-stat-label">Completed</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 fw-bold mb-0">Recent requests</h2>
            <a class="small" href="<?= $baseUrl ?>/receiver/my_requests.php">View all</a>
        </div>
        <?php if (!$recentRequests): ?>
            <p class="text-muted small mb-0">No requests yet. <a href="<?= $baseUrl ?>/receiver/browse_food.php">Browse available food</a> to send your first one.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentRequests as $r): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>
                            <?= htmlspecialchars($r['donation_title'], ENT_QUOTES, 'UTF-8') ?>
                            <small class="text-muted d-block">Donor: <?= htmlspecialchars($r['donor_name'], ENT_QUOTES, 'UTF-8') ?> &middot; <?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                        </span>
                        <span class="badge <?= $reqBadge[$r['status']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
