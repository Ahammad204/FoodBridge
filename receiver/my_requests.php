<?php
// FoodBridge — receiver: my requests with status tracking + cancel.
// Cancel note: food_requests has no 'cancelled' status in the schema,
// so cancelling a pending request removes the row (own + pending only).
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('receiver');

$baseUrl = '/FoodBridge';
$receiverId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    csrf_verify();
    $requestId = (int) ($_POST['id'] ?? 0);

    $del = $pdo->prepare(
        "DELETE FROM food_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'"
    );
    $del->execute([$requestId, $receiverId]);

    if ($del->rowCount() > 0) {
        flash_set('success', 'Request cancelled.');
    } else {
        flash_set('error', 'Only your own pending requests can be cancelled.');
    }
    header('Location: ' . $baseUrl . '/receiver/my_requests.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT fr.*, fd.title AS donation_title, fd.pickup_address, fd.expiry_time,
            fd.quantity, fd.unit, u.name AS donor_name
     FROM food_requests fr
     JOIN food_donations fd ON fd.id = fr.donation_id
     JOIN users u ON u.id = fd.donor_id
     WHERE fr.receiver_id = ?
     ORDER BY fr.created_at DESC"
);
$stmt->execute([$receiverId]);
$requests = $stmt->fetchAll();

$reqBadge = [
    'pending'   => 'bg-warning text-dark',
    'accepted'  => 'bg-success',
    'rejected'  => 'bg-danger',
    'completed' => 'bg-primary',
];

$pageTitle = 'My Requests';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3">My requests</h1>

<?php flash_show(); ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Food</th>
                        <th>Quantity</th>
                        <th>Donor</th>
                        <th>Pickup</th>
                        <th>Requested at</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$requests): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            No requests yet. <a href="<?= $baseUrl ?>/receiver/browse_food.php">Browse available food</a>.
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= $baseUrl ?>/receiver/food_details.php?id=<?= (int) $r['donation_id'] ?>"><?= htmlspecialchars($r['donation_title'], ENT_QUOTES, 'UTF-8') ?></a>
                                <?php if (!empty($r['message'])): ?>
                                    <small class="text-muted d-block">&ldquo;<?= htmlspecialchars($r['message'], ENT_QUOTES, 'UTF-8') ?>&rdquo;</small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($r['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['donor_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['pickup_address'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $reqBadge[$r['status']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="text-end">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('Cancel this request?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<p class="text-muted small mt-3 mb-0">
    <span class="badge bg-warning text-dark">Pending</span> waiting for the donor &middot;
    <span class="badge bg-success">Accepted</span> ready for pickup &middot;
    <span class="badge bg-danger">Rejected</span> the donor gave it to another request &middot;
    <span class="badge bg-primary">Completed</span> food received.
    Cancelling removes a pending request.
</p>

<?php include __DIR__ . '/../includes/footer.php'; ?>
