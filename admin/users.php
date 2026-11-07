<?php
// FoodBridge — admin: user management (search, activate/deactivate, delete)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$baseUrl = '/FoodBridge';
$myId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $targetId = (int) ($_POST['id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    if ($targetId === $myId) {
        flash_set('error', 'You cannot change or delete your own admin account.');
    } elseif ($action === 'toggle_status') {
        $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $user = $stmt->fetch();
        if ($user) {
            $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
            $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $targetId]);
            flash_set('success', 'User status set to ' . $newStatus . '.');
        }
    } elseif ($action === 'delete') {
        // FK ON DELETE CASCADE removes their donations and requests too.
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
        flash_set('success', 'User deleted (with their donations and requests).');
    }

    header('Location: ' . $baseUrl . '/admin/users.php?' . http_build_query(['q' => trim($_POST['q'] ?? ''), 'role' => $_POST['role_filter'] ?? '']));
    exit;
}

$q    = trim($_GET['q'] ?? '');
$role = trim($_GET['role'] ?? '');
if (!in_array($role, ['admin', 'donor', 'receiver'], true)) {
    $role = '';
}

$where  = [];
$params = [];
if ($q !== '') {
    $where[]  = '(name LIKE ? OR email LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($role !== '') {
    $where[]  = 'role = ?';
    $params[] = $role;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT * FROM users $whereSql ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$roleBadge = ['admin' => 'bg-dark', 'donor' => 'bg-success', 'receiver' => 'bg-primary'];
$statusBadge = ['active' => 'bg-success', 'inactive' => 'bg-secondary', 'suspended' => 'bg-danger'];

$adminPage = 'users';
$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-3">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    </div>
    <div class="col-lg-9">
        <h1 class="h4 mb-3">Users</h1>

        <?php flash_show(); ?>

        <form method="get" action="" class="card p-3 mb-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="q" placeholder="Search by name or email…"
                           value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">All roles</option>
                        <?php foreach (['admin', 'donor', 'receiver'] as $r): ?>
                            <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-fb-green">Search</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Verified</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$users): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($u['organization_name'])): ?>
                                            <small class="text-muted d-block"><?= htmlspecialchars($u['organization_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge <?= $roleBadge[$u['role']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= $u['is_verified'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning text-dark">No</span>' ?></td>
                                    <td><span class="badge <?= $statusBadge[$u['status']] ?? 'bg-secondary' ?> text-capitalize"><?= htmlspecialchars($u['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end text-nowrap">
                                        <?php if ((int) $u['id'] !== $myId): ?>
                                            <form method="post" action="" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="role_filter" value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                                                    <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                            <form method="post" action="" class="d-inline" onsubmit="return confirm('Delete this user and all their donations/requests?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="role_filter" value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">You</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <p class="text-muted small mt-2 mb-0">Deactivated users cannot login. Deleting a user also deletes their donations and requests (database cascade).</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
