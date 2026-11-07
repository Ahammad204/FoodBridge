<?php
// FoodBridge — receiver: received food history (completed requests)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('receiver');

$baseUrl = BASE_URL;
$receiverId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT fr.*, fd.title AS donation_title, fd.category, fd.quantity, fd.unit,
            fd.pickup_address, u.name AS donor_name, u.organization_name AS donor_org
     FROM food_requests fr
     JOIN food_donations fd ON fd.id = fr.donation_id
     JOIN users u ON u.id = fd.donor_id
     WHERE fr.receiver_id = ? AND fr.status = 'completed'
     ORDER BY fr.created_at DESC"
);
$stmt->execute([$receiverId]);
$history = $stmt->fetchAll();

$totalMeals = 0.0;
foreach ($history as $h) {
    $totalMeals += (float) $h['quantity'];
}

$pageTitle = 'Received Food History';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3">Received food history</h1>

<?php flash_show(); ?>

<?php if ($history): ?>
    <div class="alert alert-success">
        You have received <strong><?= count($history) ?></strong> donation(s) through FoodBridge —
        total quantity <strong><?= number_format($totalMeals) ?></strong> (mixed units: packs, pcs, kg…).
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Food</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Donor</th>
                        <th>Pickup location</th>
                        <th>Requested at</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$history): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            Nothing received yet. When a donor marks your pickup as completed, it will appear here.
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['donation_title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($h['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($h['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($h['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($h['donor_name'], ENT_QUOTES, 'UTF-8') ?><?= !empty($h['donor_org']) ? ' (' . htmlspecialchars($h['donor_org'], ENT_QUOTES, 'UTF-8') . ')' : '' ?></td>
                            <td><?= htmlspecialchars($h['pickup_address'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($h['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge bg-primary">Completed</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
