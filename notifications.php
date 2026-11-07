<?php
// FoodBridge — all notifications for the logged-in user (any role).
// ?go=<id>  marks one notification read and forwards to its link.
// POST      mark_all = mark everything read.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_login();

$baseUrl = '/FoodBridge';
$userId = (int) $_SESSION['user_id'];

// Open one notification: mark read, then go to its target page.
if (isset($_GET['go'])) {
    $id = (int) $_GET['go'];
    $stmt = $pdo->prepare('SELECT link FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $row = $stmt->fetch();

    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')
        ->execute([$id, $userId]);

    $link = $row['link'] ?? null;
    if (is_string($link) && str_starts_with($link, '/FoodBridge/')) {
        header('Location: ' . $link);
    } else {
        header('Location: ' . $baseUrl . '/notifications.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all') {
    csrf_verify();
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$userId]);
    flash_set('success', 'All notifications marked as read.');
    header('Location: ' . $baseUrl . '/notifications.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$typeBadge = [
    'new_request'       => 'bg-warning text-dark',
    'request_accepted'  => 'bg-success',
    'request_rejected'  => 'bg-danger',
    'request_completed' => 'bg-primary',
];

$pageTitle = 'Notifications';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Notifications</h1>
    <?php if ($notifications): ?>
        <form method="post" action="" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="mark_all">
            <button type="submit" class="btn btn-outline-success btn-sm">Mark all as read</button>
        </form>
    <?php endif; ?>
</div>

<?php flash_show(); ?>

<div class="list-group">
    <?php if (!$notifications): ?>
        <div class="list-group-item text-muted text-center py-4">No notifications yet.</div>
    <?php endif; ?>
    <?php foreach ($notifications as $n): ?>
        <a href="<?= $baseUrl ?>/notifications.php?go=<?= (int) $n['id'] ?>"
           class="list-group-item list-group-item-action <?= $n['is_read'] ? '' : 'fw-bold' ?>">
            <div class="d-flex w-100 justify-content-between align-items-start">
                <span>
                    <span class="badge <?= $typeBadge[$n['type']] ?? 'bg-secondary' ?> me-2"><?= htmlspecialchars(str_replace('_', ' ', $n['type']), ENT_QUOTES, 'UTF-8') ?></span>
                    <?= htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <small class="text-muted text-nowrap ms-3"><?= htmlspecialchars($n['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
