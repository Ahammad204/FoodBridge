<?php
// FoodBridge — landing page
$pageTitle = 'Home';
require_once __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';

// Latest available donations
$stmt = $pdo->query(
    "SELECT fd.*, u.name AS donor_name
     FROM food_donations fd
     JOIN users u ON u.id = fd.donor_id
     WHERE fd.status = 'available'
     ORDER BY fd.created_at DESC
     LIMIT 6"
);
$donations = $stmt->fetchAll();
?>

<div class="p-4 mb-4 bg-success text-white rounded-3">
    <h1 class="display-6 fw-bold">FoodBridge</h1>
    <p class="mb-0">Connecting food donors with people who need it — less waste, fewer empty plates.</p>
</div>

<h2 class="h4 mb-3">Available Donations</h2>
<div class="row g-3">
    <?php if (!$donations): ?>
        <p class="text-muted">No donations available right now. Please check back later.</p>
    <?php endif; ?>
    <?php foreach ($donations as $d): ?>
        <div class="col-md-4">
            <div class="card card-donation h-100">
                <?php if (!empty($d['image'])): ?>
                    <img src="assets/uploads/<?= htmlspecialchars($d['image']) ?>" class="card-img-top" alt="">
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($d['title']) ?></h5>
                    <p class="card-text text-muted mb-1"><?= htmlspecialchars($d['category']) ?> &middot;
                        <?= htmlspecialchars($d['quantity']) ?> <?= htmlspecialchars($d['unit']) ?></p>
                    <p class="card-text mb-1">Pickup: <?= htmlspecialchars($d['pickup_address']) ?></p>
                    <p class="card-text"><small class="text-muted">Expires: <?= htmlspecialchars($d['expiry_time']) ?> &middot; by <?= htmlspecialchars($d['donor_name']) ?></small></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
