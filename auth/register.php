<?php
// FoodBridge — registration (Donor or Receiver)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$baseUrl = '/FoodBridge';

// Already logged in? Send to the right dashboard.
if (!empty($_SESSION['user_id'])) {
    redirect_to_dashboard($baseUrl);
}

$errors = [];
$old = [
    'name'              => '',
    'email'             => '',
    'phone'             => '',
    'address'           => '',
    'organization_name' => '',
    'role'              => 'donor',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['name']              = trim($_POST['name'] ?? '');
    $old['email']             = trim($_POST['email'] ?? '');
    $old['phone']             = trim($_POST['phone'] ?? '');
    $old['address']           = trim($_POST['address'] ?? '');
    $old['organization_name'] = trim($_POST['organization_name'] ?? '');
    $old['role']              = $_POST['role'] ?? 'donor';
    $password                 = $_POST['password'] ?? '';
    $confirmPassword          = $_POST['confirm_password'] ?? '';

    // ---- Server-side validation ----
    if ($old['name'] === '' || mb_strlen($old['name']) < 3) {
        $errors[] = 'Name is required (at least 3 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($old['phone'] === '' || !preg_match('/^[0-9+\-\s]{7,20}$/', $old['phone'])) {
        $errors[] = 'A valid phone number is required.';
    }
    if ($old['address'] === '') {
        $errors[] = 'Address is required.';
    }
    if (!in_array($old['role'], ['donor', 'receiver'], true)) {
        $errors[] = 'Please choose a valid role (Donor or Receiver).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirm password do not match.';
    }

    // Email must be unique
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered. Please login instead.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password, phone, address, role, organization_name)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $old['name'],
            $old['email'],
            $hash,
            $old['phone'],
            $old['address'],
            $old['role'],
            $old['organization_name'] !== '' ? $old['organization_name'] : null,
        ]);

        flash_set('success', 'Registration successful. Please login with your email and password.');
        header('Location: ' . $baseUrl . '/auth/login.php');
        exit;
    }
}

$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center">Create your FoodBridge account</h1>

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

                <form method="post" action="" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="role">I am a</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="donor" <?= $old['role'] === 'donor' ? 'selected' : '' ?>>Donor — I want to donate food</option>
                            <option value="receiver" <?= $old['role'] === 'receiver' ? 'selected' : '' ?>>Receiver — I need food support</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="name">Full name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?= htmlspecialchars($old['name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" required
                                   value="<?= htmlspecialchars($old['phone'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="organization_name">Organization name <span class="text-muted">(optional)</span></label>
                            <input type="text" class="form-control" id="organization_name" name="organization_name"
                                   value="<?= htmlspecialchars($old['organization_name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="address">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required
                               value="<?= htmlspecialchars($old['address'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">At least 8 characters.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="confirm_password">Confirm password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100">Register</button>
                </form>

                <p class="text-center text-muted mt-3 mb-0">
                    Already have an account? <a href="<?= $baseUrl ?>/auth/login.php">Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
