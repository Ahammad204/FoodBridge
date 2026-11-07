<?php
// FoodBridge — admin: view all requests and their status (read-only)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$baseUrl = '/FoodBridge';

$status = trim($_GET['status'] ?? '');
if (!in_array($status, ['pending', 'accepted', 'rejected', 'completed'], true)) {
    $status = '';
}

$sql = "SELECT fr.*, fd.title AS donation_title,
               donor.name AS donor_name, receiver.name AS receiver_name
        FROM food_requests fr
        JOIN food_donations fd ON fd.id = fr.donation_id
        JOIN users donor ON donor.id = fd.donor_id
        JOIN users receiver ON receiver.id = fr.receiver_id";

if ($status !== '') {
    $stmt = $pdo->prepare($sql . ' WHERE fr.status = ? ORDER BY fr.created_at DESC');
    $stmt->execute([$status]);
} else {
    $stmt = $pdo->query($sql . ' ORDER BY fr.created_at DESC');
}
$requests = $stmt->fetchAll();

$reqBadge = [
    'pending'   => 'bg-warning text-dark',
    'accepted'  => 'bg-success',
    'rejected'  => 'bg-danger',
    'completed' => 'bg-primary',
];

$adminPage = 'requests';
$pageTitle = 'All Requests';
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-3">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    </div>
    <div class="col-lg-9">
        <h1 class="h4 mb-3">Requests</h1>

        <?php flash_show(); ?>

        <form method="get" action="" class="card p-3 mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <select class="form-select" name="status">
                        <option value="">All statuses</option>
                        <?php foreach (['pending', 'accepted', 'rejected', 'completed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-fb-green">Filter</button>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="text-muted"><?= count($requests) ?> request(s)</span>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Donation</th>
                                <th>Donor</th>
                                <th>Receiver</th>
                                <th>Message</th>
                                <th>Requested at</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$requests): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No requests found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['donation_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($r['donor_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($r['receiver_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($r['message'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge <?= $reqBadge[$r['status']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
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
