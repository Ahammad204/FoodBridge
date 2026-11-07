<?php
// FoodBridge — profile edit + change password (all roles)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_login();

$baseUrl = '/FoodBridge';
$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . $baseUrl . '/auth/logout.php');
    exit;
}

$profileErrors = [];
$passwordErrors = [];

// ---- Edit profile ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'profile') {
    csrf_verify();

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $org   = trim($_POST['organization_name'] ?? '');

    if ($name === '' || mb_strlen($name) < 3) {
        $profileErrors[] = 'Name is required (at least 3 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileErrors[] = 'A valid email address is required.';
    }
    if ($phone === '' || !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $profileErrors[] = 'A valid phone number is required.';
    }
    if ($address === '') {
        $profileErrors[] = 'Address is required.';
    }

    if (!$profileErrors && $email !== $user['email']) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            $profileErrors[] = 'This email is already used by another account.';
        }
    }

    if (!$profileErrors) {
        $upd = $pdo->prepare(
            'UPDATE users SET name = ?, email = ?, phone = ?, address = ?, organization_name = ?
             WHERE id = ?'
        );
        $upd->execute([$name, $email, $phone, $address, $org !== '' ? $org : null, $userId]);

        $_SESSION['name']  = $name;
        $_SESSION['email'] = $email;
        $user = array_merge($user, [
            'name' => $name, 'email' => $email, 'phone' => $phone,
            'address' => $address, 'organization_name' => $org,
        ]);

        flash_set('success', 'Profile updated.');
        header('Location: ' . $baseUrl . '/profile.php');
        exit;
    } else {
        $user = array_merge($user, [
            'name' => $name, 'email' => $email, 'phone' => $phone,
            'address' => $address, 'organization_name' => $org,
        ]);
    }
}

// ---- Change password ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'password') {
    csrf_verify();

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $passwordErrors[] = 'Current password is incorrect.';
    }
    if (strlen($new) < 8) {
        $passwordErrors[] = 'New password must be at least 8 characters.';
    }
    if ($new !== $confirm) {
        $passwordErrors[] = 'New password and confirm password do not match.';
    }
    if ($new !== '' && $current === $new) {
        $passwordErrors[] = 'New password must be different from the current one.';
    }

    if (!$passwordErrors) {
        $upd = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $upd->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);

        // Fresh session id after a credential change.
        session_regenerate_id(true);

        flash_set('success', 'Password changed successfully.');
        header('Location: ' . $baseUrl . '/profile.php');
        exit;
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-3">My Profile</h1>

<?php flash_show(); ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Edit profile</h2>

                <?php if ($profileErrors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($profileErrors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="profile">

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8') ?>" disabled>
                        <div class="form-text">Role cannot be changed. Verification status: <?= $user['is_verified'] ? 'Verified' : 'Not verified' ?>.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="name">Full name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" required
                                   value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="organization_name">Organization name <span class="text-muted">(optional)</span></label>
                            <input type="text" class="form-control" id="organization_name" name="organization_name"
                                   value="<?= htmlspecialchars($user['organization_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="address">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required
                               value="<?= htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <button type="submit" class="btn btn-fb-green">Save profile</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Change password</h2>

                <?php if ($passwordErrors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($passwordErrors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="password">

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_password">New password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">At least 8 characters, different from the current one.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirm_password">Confirm new password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-fb-orange">Change password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
