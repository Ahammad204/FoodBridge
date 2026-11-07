<?php
// FoodBridge — admin: verify donors.
// Approve: is_verified = 1 (donor can now post food).
// Reject: account deactivated (status = inactive, still unverified).
// Rule enforced in donor/add_food.php: unverified donors cannot post food.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$baseUrl = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $targetId = (int) ($_POST['id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ? AND role = 'donor'")
            ->execute([$targetId]);
        flash_set('success', 'Donor approved. They can now post food.');
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'donor' AND is_verified = 0")
            ->execute([$targetId]);
        flash_set('success', 'Donor rejected and account deactivated. You can re-activate them from Users if needed.');
    }

    header('Location: ' . $baseUrl . '/admin/verify_donors.php');
    exit;
}

$stmt = $pdo->query(
    "SELECT * FROM users
     WHERE role = 'donor' AND is_verified = 0 AND status = 'active'
     ORDER BY created_at ASC"
);
$pending = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT * FROM users WHERE role = 'donor' AND is_verified = 1 ORDER BY name ASC"
);
$verified = $stmt->fetchAll();

$adminPage = 'verify';
$pageTitle = 'Verify Donors';
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-3">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    </div>
    <div class="col-lg-9">
        <h1 class="h4 mb-3">Verify donors</h1>

        <?php flash_show(); ?>

        <p class="text-muted">New donors register as <strong>unverified</strong> and cannot post food until approved here.</p>

        <div class="card mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Organization</th>
                                <th>Registered</th>
                                <th class="text-end">Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$pending): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No donors waiting for verification.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($pending as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($u['organization_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end text-nowrap">
                                        <form method="post" action="" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="post" action="" class="d-inline" onsubmit="return confirm('Reject this donor and deactivate the account?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <h2 class="h6 fw-bold">Verified donors (<?= count($verified) ?>)</h2>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Organization</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php if (!$verified): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No verified donors yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($verified as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($u['organization_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge bg-success text-capitalize"><?= htmlspecialchars($u['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
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
