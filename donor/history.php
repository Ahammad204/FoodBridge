<?php
// FoodBridge — donor: history.
// Top: donations with an accepted request, waiting for pickup — Mark as Completed.
// Below: completed donations history.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/notify.php';
require_role('donor');

$baseUrl = BASE_URL;
$donorId = (int) $_SESSION['user_id'];

// ---- Mark as Completed (donation must be mine and currently 'requested') ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete') {
    csrf_verify();
    $donationId = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT id FROM food_donations WHERE id = ? AND donor_id = ? AND status = 'requested'"
    );
    $stmt->execute([$donationId, $donorId]);

    if ($stmt->fetch()) {
        // Who receives it? (for the completion notification)
        $infoStmt = $pdo->prepare(
            "SELECT fd.title, fr.receiver_id
             FROM food_donations fd
             JOIN food_requests fr ON fr.donation_id = fd.id AND fr.status = 'accepted'
             WHERE fd.id = ?"
        );
        $infoStmt->execute([$donationId]);
        $info = $infoStmt->fetch();

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE food_donations SET status = 'completed' WHERE id = ?")
                ->execute([$donationId]);
            $pdo->prepare(
                "UPDATE food_requests SET status = 'completed'
                 WHERE donation_id = ? AND status = 'accepted'"
            )->execute([$donationId]);
            $pdo->commit();

            if ($info) {
                notify_user(
                    $pdo,
                    (int) $info['receiver_id'],
                    'request_completed',
                    'Your pickup of "' . $info['title'] . '" was marked as completed. Thank you!',
                    '/receiver/history.php'
                );
            }

            flash_set('success', 'Marked as completed. Thank you for sharing!');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Something went wrong. Please try again.');
        }
    } else {
        flash_set('error', 'Donation not found or not ready to complete.');
    }

    header('Location: ' . $baseUrl . '/donor/history.php');
    exit;
}

// Donations waiting for pickup completion (status 'requested', with accepted receiver)
$stmt = $pdo->prepare(
    "SELECT fd.*, u.name AS receiver_name
     FROM food_donations fd
     JOIN food_requests fr ON fr.donation_id = fd.id AND fr.status = 'accepted'
     JOIN users u ON u.id = fr.receiver_id
     WHERE fd.donor_id = ? AND fd.status = 'requested'
     ORDER BY fd.created_at DESC"
);
$stmt->execute([$donorId]);
$readyToComplete = $stmt->fetchAll();

// Completed donations
$stmt = $pdo->prepare(
    "SELECT fd.*, u.name AS receiver_name
     FROM food_donations fd
     LEFT JOIN food_requests fr ON fr.donation_id = fd.id AND fr.status = 'completed'
     LEFT JOIN users u ON u.id = fr.receiver_id
     WHERE fd.donor_id = ? AND fd.status = 'completed'
     ORDER BY fd.created_at DESC"
);
$stmt->execute([$donorId]);
$completed = $stmt->fetchAll();

$pageTitle = 'Donation History';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3">History</h1>

<?php flash_show(); ?>

<h2 class="h6 fw-bold">Waiting for pickup — mark completed after handover</h2>
<div class="card mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Quantity</th>
                        <th>Receiver</th>
                        <th>Pickup</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$readyToComplete): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nothing waiting. Accept a request first.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($readyToComplete as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($d['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['receiver_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['pickup_address'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Confirm the food was picked up?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-fb-green">Mark as Completed</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<h2 class="h6 fw-bold">Completed donations</h2>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Receiver</th>
                        <th>Posted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$completed): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No completed donations yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($completed as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($d['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['receiver_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($d['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge bg-primary">Completed</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
