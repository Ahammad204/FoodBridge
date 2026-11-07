<?php
// FoodBridge — donor dashboard: stats + recent activity
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('donor');

$baseUrl = '/FoodBridge';
$donorId = (int) $_SESSION['user_id'];

// ---- Stats for this donor ----
$stmt = $pdo->prepare('SELECT COUNT(*) FROM food_donations WHERE donor_id = ?');
$stmt->execute([$donorId]);
$totalDonations = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM food_requests fr
     JOIN food_donations fd ON fd.id = fr.donation_id
     WHERE fd.donor_id = ? AND fr.status = 'pending'"
);
$stmt->execute([$donorId]);
$pendingRequests = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM food_donations WHERE donor_id = ? AND status = 'completed'"
);
$stmt->execute([$donorId]);
$completedDonations = (int) $stmt->fetchColumn();

// ---- Recent activity: my latest donations + latest incoming requests ----
$stmt = $pdo->prepare(
    'SELECT * FROM food_donations WHERE donor_id = ? ORDER BY created_at DESC LIMIT 5'
);
$stmt->execute([$donorId]);
$recentDonations = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT fr.*, fd.title AS donation_title, u.name AS receiver_name
     FROM food_requests fr
     JOIN food_donations fd ON fd.id = fr.donation_id
     JOIN users u ON u.id = fr.receiver_id
     WHERE fd.donor_id = ?
     ORDER BY fr.created_at DESC LIMIT 5"
);
$stmt->execute([$donorId]);
$recentRequests = $stmt->fetchAll();

$statusBadge = [
    'available' => 'bg-success',
    'requested' => 'bg-warning text-dark',
    'completed' => 'bg-primary',
    'expired'   => 'bg-secondary',
];
$reqBadge = [
    'pending'   => 'bg-warning text-dark',
    'accepted'  => 'bg-success',
    'rejected'  => 'bg-danger',
    'completed' => 'bg-primary',
];

$pageTitle = 'Donor Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Donor Dashboard</h1>
    <a class="btn btn-fb-orange" href="<?= $baseUrl ?>/donor/add_food.php">+ Add food donation</a>
</div>

<?php flash_show(); ?>

<div class="mb-4">
    <a class="btn btn-outline-success btn-sm" href="<?= $baseUrl ?>/donor/my_donations.php">My donations</a>
    <a class="btn btn-outline-success btn-sm" href="<?= $baseUrl ?>/donor/requests.php">Incoming requests</a>
    <a class="btn btn-outline-success btn-sm" href="<?= $baseUrl ?>/donor/history.php">History</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="fb-stat p-4 text-center h-100">
            <div class="fb-stat-number"><?= number_format($totalDonations) ?></div>
            <div class="fb-stat-label">Total donations</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="fb-stat p-4 text-center h-100" style="border-top-color: var(--fb-orange);">
            <div class="fb-stat-number"><?= number_format($pendingRequests) ?></div>
            <div class="fb-stat-label">Pending requests</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="fb-stat p-4 text-center h-100">
            <div class="fb-stat-number"><?= number_format($completedDonations) ?></div>
            <div class="fb-stat-label">Completed</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 fw-bold mb-0">Recent donations</h2>
                    <a class="small" href="<?= $baseUrl ?>/donor/my_donations.php">View all</a>
                </div>
                <?php if (!$recentDonations): ?>
                    <p class="text-muted small mb-0">No donations yet. <a href="<?= $baseUrl ?>/donor/add_food.php">Post your first one</a>.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentDonations as $d): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>
                                    <?= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($d['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                                </span>
                                <span class="badge <?= $statusBadge[$d['status']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($d['status'], ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 fw-bold mb-0">Recent incoming requests</h2>
                    <a class="small" href="<?= $baseUrl ?>/donor/requests.php">View all</a>
                </div>
                <?php if (!$recentRequests): ?>
                    <p class="text-muted small mb-0">No requests yet.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentRequests as $r): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>
                                    <?= htmlspecialchars($r['receiver_name'], ENT_QUOTES, 'UTF-8') ?>
                                    requested <em><?= htmlspecialchars($r['donation_title'], ENT_QUOTES, 'UTF-8') ?></em>
                                    <small class="text-muted d-block"><?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                                </span>
                                <span class="badge <?= $reqBadge[$r['status']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
