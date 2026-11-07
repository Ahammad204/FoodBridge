<?php
// FoodBridge — donor: my donations (list, edit link, delete, status)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('donor');

$baseUrl = BASE_URL;
$donorId = (int) $_SESSION['user_id'];

// ---- Delete (POST, own donations only; image file removed too) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT image FROM food_donations WHERE id = ? AND donor_id = ?');
    $stmt->execute([$id, $donorId]);
    $row = $stmt->fetch();

    if ($row) {
        $del = $pdo->prepare('DELETE FROM food_donations WHERE id = ? AND donor_id = ?');
        $del->execute([$id, $donorId]);
        if (!empty($row['image'])) {
            $path = __DIR__ . '/../assets/uploads/' . $row['image'];
            if (is_file($path)) {
                unlink($path);
            }
        }
        flash_set('success', 'Donation deleted.');
    } else {
        flash_set('error', 'Donation not found.');
    }
    header('Location: ' . $baseUrl . '/donor/my_donations.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT * FROM food_donations WHERE donor_id = ? ORDER BY created_at DESC'
);
$stmt->execute([$donorId]);
$donations = $stmt->fetchAll();

$statusBadge = [
    'available' => 'bg-success',
    'requested' => 'bg-warning text-dark',
    'completed' => 'bg-primary',
    'expired'   => 'bg-secondary',
];

$pageTitle = 'My Donations';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">My donations</h1>
    <a class="btn btn-fb-orange" href="<?= $baseUrl ?>/donor/add_food.php">+ Add food</a>
</div>

<?php flash_show(); ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Pickup</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$donations): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No donations yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($donations as $d): ?>
                        <tr>
                            <td>
                                <?php if (!empty($d['image'])): ?>
                                    <img src="<?= $baseUrl ?>/assets/uploads/<?= htmlspecialchars($d['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:56px;height:42px;object-fit:cover;border-radius:6px;">
                                <?php else: ?>
                                    <span class="text-muted">&#127858;</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($d['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['pickup_address'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['expiry_time'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $statusBadge[$d['status']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($d['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="<?= $baseUrl ?>/donor/edit_food.php?id=<?= (int) $d['id'] ?>">Edit</a>
                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Delete this donation? This cannot be undone.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
