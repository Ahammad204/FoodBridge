<?php
// FoodBridge — donor: incoming requests with Accept / Reject.
// Accept rule: the accepted request wins the donation — the donation becomes
// 'requested' and every other pending request for it is rejected.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/notify.php';
require_role('donor');

$baseUrl = BASE_URL;
$donorId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $requestId = (int) ($_POST['id'] ?? 0);
    $action    = $_POST['action'] ?? '';

    // Load the request only if it targets one of my donations and is pending.
    $stmt = $pdo->prepare(
        "SELECT fr.*, fd.title AS donation_title
         FROM food_requests fr
         JOIN food_donations fd ON fd.id = fr.donation_id
         WHERE fr.id = ? AND fd.donor_id = ? AND fr.status = 'pending'"
    );
    $stmt->execute([$requestId, $donorId]);
    $request = $stmt->fetch();

    if (!$request) {
        flash_set('error', 'Request not found or already handled.');
    } elseif ($action === 'reject') {
        $upd = $pdo->prepare("UPDATE food_requests SET status = 'rejected' WHERE id = ?");
        $upd->execute([$requestId]);

        notify_user(
            $pdo,
            (int) $request['receiver_id'],
            'request_rejected',
            'Your request for "' . $request['donation_title'] . '" was rejected by the donor.',
            '/receiver/my_requests.php'
        );

        flash_set('success', 'Request rejected.');
    } elseif ($action === 'accept') {
        // Remember who else is pending — they get auto-rejected + notified.
        $othersStmt = $pdo->prepare(
            "SELECT receiver_id FROM food_requests
             WHERE donation_id = ? AND id <> ? AND status = 'pending'"
        );
        $othersStmt->execute([$request['donation_id'], $requestId]);
        $otherReceiverIds = $othersStmt->fetchAll(PDO::FETCH_COLUMN);

        $pdo->beginTransaction();
        try {
            // 1) Accept this request.
            $pdo->prepare("UPDATE food_requests SET status = 'accepted' WHERE id = ?")
                ->execute([$requestId]);

            // 2) Donation is now promised to this receiver.
            $pdo->prepare("UPDATE food_donations SET status = 'requested' WHERE id = ?")
                ->execute([$request['donation_id']]);

            // 3) Reject every other pending request for the same donation.
            $pdo->prepare(
                "UPDATE food_requests SET status = 'rejected'
                 WHERE donation_id = ? AND id <> ? AND status = 'pending'"
            )->execute([$request['donation_id'], $requestId]);

            $pdo->commit();

            // Notify the winner and everyone who was auto-rejected.
            notify_user(
                $pdo,
                (int) $request['receiver_id'],
                'request_accepted',
                'Your request for "' . $request['donation_title'] . '" was accepted. Please arrange the pickup.',
                '/receiver/my_requests.php'
            );
            foreach ($otherReceiverIds as $otherId) {
                notify_user(
                    $pdo,
                    (int) $otherId,
                    'request_rejected',
                    'Your request for "' . $request['donation_title'] . '" was rejected — the donor accepted another request.',
                    '/receiver/my_requests.php'
                );
            }

            flash_set('success', 'Request accepted. The donation is marked as requested, and other pending requests for it were rejected.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Something went wrong. Please try again.');
        }
    }

    header('Location: ' . $baseUrl . '/donor/requests.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT fr.*, fd.title AS donation_title, fd.status AS donation_status,
            u.name AS receiver_name, u.phone AS receiver_phone,
            u.organization_name AS receiver_org
     FROM food_requests fr
     JOIN food_donations fd ON fd.id = fr.donation_id
     JOIN users u ON u.id = fr.receiver_id
     WHERE fd.donor_id = ?
     ORDER BY (fr.status = 'pending') DESC, fr.created_at DESC"
);
$stmt->execute([$donorId]);
$requests = $stmt->fetchAll();

$reqBadge = [
    'pending'   => 'bg-warning text-dark',
    'accepted'  => 'bg-success',
    'rejected'  => 'bg-danger',
    'completed' => 'bg-primary',
];

$pageTitle = 'Incoming Requests';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3">Incoming requests</h1>

<?php flash_show(); ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Donation</th>
                        <th>Receiver</th>
                        <th>Message</th>
                        <th>Requested at</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$requests): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No requests yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['donation_title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars($r['receiver_name'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($r['receiver_org'])): ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($r['receiver_org'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                                <?php if ($r['status'] === 'accepted' && !empty($r['receiver_phone'])): ?>
                                    <small class="text-muted d-block">Phone: <?= htmlspecialchars($r['receiver_phone'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $reqBadge[$r['status']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="text-end text-nowrap">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="post" action="" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-sm btn-success">Accept</button>
                                    </form>
                                    <form method="post" action="" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
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
    Accepting a request marks the donation as <strong>requested</strong> and automatically rejects
    the other pending requests for that donation. Complete the handover from the
    <a href="<?= $baseUrl ?>/donor/history.php">History</a> page.
</p>

<?php include __DIR__ . '/../includes/footer.php'; ?>
