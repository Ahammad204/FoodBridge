<?php
// FoodBridge — receiver: food details + Send Request (no duplicate requests)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/notify.php';
require_role('receiver');

$baseUrl = BASE_URL;
$receiverId = (int) $_SESSION['user_id'];
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT fd.*, u.name AS donor_name, u.organization_name AS donor_org, u.phone AS donor_phone
     FROM food_donations fd
     JOIN users u ON u.id = fd.donor_id
     WHERE fd.id = ?"
);
$stmt->execute([$id]);
$donation = $stmt->fetch();

if (!$donation) {
    flash_set('error', 'Food item not found.');
    header('Location: ' . $baseUrl . '/receiver/browse_food.php');
    exit;
}

$isExpired  = strtotime($donation['expiry_time']) <= time();
$isOpen     = $donation['status'] === 'available' && !$isExpired;

// Does this receiver already have a live request for this donation?
$stmt = $pdo->prepare(
    "SELECT * FROM food_requests
     WHERE donation_id = ? AND receiver_id = ? AND status IN ('pending', 'accepted')
     ORDER BY created_at DESC LIMIT 1"
);
$stmt->execute([$id, $receiverId]);
$myLiveRequest = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $message = trim($_POST['message'] ?? '');

    if (!$isOpen) {
        $errors[] = 'This food is no longer available.';
    } elseif ($myLiveRequest) {
        $errors[] = 'You already have a ' . $myLiveRequest['status'] . ' request for this food.';
    } elseif (mb_strlen($message) > 500) {
        $errors[] = 'Message is too long (max 500 characters).';
    }

    if (!$errors) {
        // Re-check inside the insert path: one live request per receiver per donation.
        $stmt = $pdo->prepare(
            'INSERT INTO food_requests (donation_id, receiver_id, message, status)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$id, $receiverId, $message !== '' ? $message : null, 'pending']);

        // Notify the donor about the new request.
        notify_user(
            $pdo,
            (int) $donation['donor_id'],
            'new_request',
            ($_SESSION['name'] ?? 'A receiver') . ' requested your donation "' . $donation['title'] . '".',
            '/donor/requests.php'
        );

        flash_set('success', 'Request sent. The donor will review it — track the status in My Requests.');
        header('Location: ' . $baseUrl . '/receiver/my_requests.php');
        exit;
    }
}

$pageTitle = 'Food Details';
include __DIR__ . '/../includes/header.php';
?>

<a class="small" href="<?= $baseUrl ?>/receiver/browse_food.php">&larr; Back to browse</a>

<div class="row g-4 mt-1">
    <div class="col-md-6">
        <?php if (!empty($donation['image'])): ?>
            <img src="<?= $baseUrl ?>/assets/uploads/<?= htmlspecialchars($donation['image'], ENT_QUOTES, 'UTF-8') ?>"
                 class="img-fluid rounded w-100" style="max-height:380px;object-fit:cover;" alt="">
        <?php else: ?>
            <div class="fb-no-image rounded" style="height:320px;">&#127858;</div>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <div class="mb-2">
            <span class="badge badge-fb-category"><?= htmlspecialchars($donation['category'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($isOpen): ?>
                <span class="badge bg-success">Available</span>
            <?php elseif ($isExpired): ?>
                <span class="badge bg-secondary">Expired</span>
            <?php else: ?>
                <span class="badge bg-warning text-dark text-capitalize"><?= htmlspecialchars($donation['status'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
        <h1 class="h3"><?= htmlspecialchars($donation['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if (!empty($donation['description'])): ?>
            <p class="text-muted"><?= nl2br(htmlspecialchars($donation['description'], ENT_QUOTES, 'UTF-8')) ?></p>
        <?php endif; ?>

        <table class="table table-sm">
            <tbody>
                <tr><th style="width:35%;">Quantity</th><td><?= htmlspecialchars($donation['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($donation['unit'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Donor</th><td><?= htmlspecialchars($donation['donor_name'], ENT_QUOTES, 'UTF-8') ?><?= !empty($donation['donor_org']) ? ' (' . htmlspecialchars($donation['donor_org'], ENT_QUOTES, 'UTF-8') . ')' : '' ?></td></tr>
                <tr><th>Pickup location</th><td><?= htmlspecialchars($donation['pickup_address'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Expiry time</th><td><?= htmlspecialchars($donation['expiry_time'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Posted</th><td><?= htmlspecialchars($donation['created_at'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php if ($myLiveRequest && $myLiveRequest['status'] === 'accepted' && !empty($donation['donor_phone'])): ?>
                    <tr><th>Donor phone</th><td><?= htmlspecialchars($donation['donor_phone'], ENT_QUOTES, 'UTF-8') ?> <small class="text-muted">(shown because your request was accepted)</small></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php flash_show(); ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($myLiveRequest): ?>
            <div class="alert alert-info" role="alert">
                You already have a <strong><?= htmlspecialchars($myLiveRequest['status'], ENT_QUOTES, 'UTF-8') ?></strong>
                request for this food. Track it in <a href="<?= $baseUrl ?>/receiver/my_requests.php">My Requests</a>.
            </div>
        <?php elseif ($isOpen): ?>
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Send a request</h2>
                    <form method="post" action="" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="message">Message to the donor <span class="text-muted">(optional)</span></label>
                            <textarea class="form-control" id="message" name="message" rows="3" maxlength="500"
                                      placeholder="e.g. We serve about 40 people and can pick up within an hour."></textarea>
                        </div>
                        <button type="submit" class="btn btn-fb-orange">Send Request</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary" role="alert">This food can no longer be requested.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
