<?php
// FoodBridge — admin: monitor all donations, delete any donation
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$baseUrl = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT image FROM food_donations WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        $pdo->prepare('DELETE FROM food_donations WHERE id = ?')->execute([$id]);
        if (!empty($row['image'])) {
            $path = __DIR__ . '/../assets/uploads/' . $row['image'];
            if (is_file($path)) {
                unlink($path);
            }
        }
        flash_set('success', 'Donation deleted (its requests were removed too).');
    } else {
        flash_set('error', 'Donation not found.');
    }

    header('Location: ' . $baseUrl . '/admin/donations.php?' . http_build_query(['status' => $_POST['status_filter'] ?? '']));
    exit;
}

$status = trim($_GET['status'] ?? '');
if (!in_array($status, ['available', 'requested', 'completed', 'expired'], true)) {
    $status = '';
}

if ($status !== '') {
    $stmt = $pdo->prepare(
        "SELECT fd.*, u.name AS donor_name
         FROM food_donations fd JOIN users u ON u.id = fd.donor_id
         WHERE fd.status = ? ORDER BY fd.created_at DESC"
    );
    $stmt->execute([$status]);
} else {
    $stmt = $pdo->query(
        "SELECT fd.*, u.name AS donor_name
         FROM food_donations fd JOIN users u ON u.id = fd.donor_id
         ORDER BY fd.created_at DESC"
    );
}
$donations = $stmt->fetchAll();

$statusBadge = [
    'available' => 'bg-success',
    'requested' => 'bg-warning text-dark',
    'completed' => 'bg-primary',
    'expired'   => 'bg-secondary',
];

$adminPage = 'donations';
$pageTitle = 'Monitor Donations';
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-3">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    </div>
    <div class="col-lg-9">
        <h1 class="h4 mb-3">Donations</h1>

        <?php flash_show(); ?>

        <form method="get" action="" class="card p-3 mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <select class="form-select" name="status">
                        <option value="">All statuses</option>
                        <?php foreach (['available', 'requested', 'completed', 'expired'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-fb-green">Filter</button>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="text-muted"><?= count($donations) ?> donation(s)</span>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Donor</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Pickup</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$donations): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No donations found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($donations as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d['donor_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d['category'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($d['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d['pickup_address'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d['expiry_time'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge <?= $statusBadge[$d['status']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($d['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="text-end">
                                        <form method="post" action="" class="d-inline" onsubmit="return confirm('Delete this donation and its requests?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
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
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
