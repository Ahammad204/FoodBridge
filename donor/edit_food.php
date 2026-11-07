<?php
// FoodBridge — donor: edit one of my donations
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('donor');

$baseUrl = BASE_URL;
$donorId = (int) $_SESSION['user_id'];
$id = (int) ($_GET['id'] ?? 0);

// Load the donation — only if it belongs to this donor.
$stmt = $pdo->prepare('SELECT * FROM food_donations WHERE id = ? AND donor_id = ?');
$stmt->execute([$id, $donorId]);
$donation = $stmt->fetch();

if (!$donation) {
    flash_set('error', 'Donation not found.');
    header('Location: ' . $baseUrl . '/donor/my_donations.php');
    exit;
}

$categories = ['Cooked Food', 'Bakery', 'Raw Food', 'Fruits & Vegetables', 'Packaged Food', 'Beverages', 'Other'];
$units      = ['pcs', 'packs', 'kg', 'litres', 'boxes', 'servings'];
$statuses   = ['available', 'requested', 'completed', 'expired'];

$errors = [];
$old = [
    'title'          => $donation['title'],
    'description'    => $donation['description'] ?? '',
    'category'       => $donation['category'],
    'quantity'       => $donation['quantity'],
    'unit'           => $donation['unit'],
    'pickup_address' => $donation['pickup_address'],
    'expiry_time'    => date('Y-m-d\TH:i', strtotime($donation['expiry_time'])),
    'status'         => $donation['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['title']          = trim($_POST['title'] ?? '');
    $old['description']    = trim($_POST['description'] ?? '');
    $old['category']       = $_POST['category'] ?? $old['category'];
    $old['quantity']       = trim($_POST['quantity'] ?? '');
    $old['unit']           = $_POST['unit'] ?? $old['unit'];
    $old['pickup_address'] = trim($_POST['pickup_address'] ?? '');
    $old['expiry_time']    = trim($_POST['expiry_time'] ?? '');
    $old['status']         = $_POST['status'] ?? $old['status'];

    if ($old['title'] === '' || mb_strlen($old['title']) < 3) {
        $errors[] = 'Title is required (at least 3 characters).';
    }
    if (!in_array($old['category'], $categories, true)) {
        $errors[] = 'Please choose a valid category.';
    }
    if (!is_numeric($old['quantity']) || (float) $old['quantity'] <= 0) {
        $errors[] = 'Quantity must be a number greater than 0.';
    }
    if (!in_array($old['unit'], $units, true)) {
        $errors[] = 'Please choose a valid unit.';
    }
    if ($old['pickup_address'] === '') {
        $errors[] = 'Pickup address is required.';
    }
    $expiryTs = $old['expiry_time'] !== '' ? strtotime($old['expiry_time']) : false;
    if ($expiryTs === false) {
        $errors[] = 'Expiry time is required.';
    }
    if (!in_array($old['status'], $statuses, true)) {
        $errors[] = 'Please choose a valid status.';
    }

    // ---- Optional replacement image (same rules as add_food) ----
    $imageName = $donation['image'];
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed. Please try again.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image must be 2 MB or smaller.';
        } else {
            $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = function_exists('mime_content_type')
                ? mime_content_type($file['tmp_name'])
                : ($file['type'] ?? '');

            if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
                $errors[] = 'Image must be a JPG, PNG, GIF or WEBP file.';
            } else {
                $newName = 'donation_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], __DIR__ . '/../assets/uploads/' . $newName)) {
                    // Remove the old file after the new one is safely stored.
                    if (!empty($donation['image'])) {
                        $oldPath = __DIR__ . '/../assets/uploads/' . $donation['image'];
                        if (is_file($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $imageName = $newName;
                } else {
                    $errors[] = 'Could not save the uploaded image.';
                }
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE food_donations
             SET title = ?, description = ?, category = ?, quantity = ?, unit = ?,
                 pickup_address = ?, expiry_time = ?, image = ?, status = ?
             WHERE id = ? AND donor_id = ?'
        );
        $stmt->execute([
            $old['title'],
            $old['description'] !== '' ? $old['description'] : null,
            $old['category'],
            (float) $old['quantity'],
            $old['unit'],
            $old['pickup_address'],
            date('Y-m-d H:i:s', $expiryTs),
            $imageName,
            $old['status'],
            $id,
            $donorId,
        ]);

        flash_set('success', 'Donation updated.');
        header('Location: ' . $baseUrl . '/donor/my_donations.php');
        exit;
    }
}

$pageTitle = 'Edit Donation';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3">Edit donation</h1>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label" for="title">Title</label>
                <input type="text" class="form-control" id="title" name="title" required
                       value="<?= htmlspecialchars($old['title'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($old['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="category">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= $old['category'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="quantity">Quantity</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="quantity" name="quantity" required
                           value="<?= htmlspecialchars((string) $old['quantity'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="unit">Unit</label>
                    <select class="form-select" id="unit" name="unit" required>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>" <?= $old['unit'] === $u ? 'selected' : '' ?>><?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="pickup_address">Pickup address</label>
                    <input type="text" class="form-control" id="pickup_address" name="pickup_address" required
                           value="<?= htmlspecialchars($old['pickup_address'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="expiry_time">Expiry time</label>
                    <input type="datetime-local" class="form-control" id="expiry_time" name="expiry_time" required
                           value="<?= htmlspecialchars($old['expiry_time'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $old['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="image">Replace photo <span class="text-muted">(optional, JPG/PNG/GIF/WEBP, max 2 MB)</span></label>
                <?php if (!empty($donation['image'])): ?>
                    <div class="mb-2">
                        <img src="<?= $baseUrl ?>/assets/uploads/<?= htmlspecialchars($donation['image'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:120px;height:90px;object-fit:cover;border-radius:8px;">
                    </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
            </div>

            <button type="submit" class="btn btn-fb-green">Save changes</button>
            <a class="btn btn-outline-secondary" href="<?= $baseUrl ?>/donor/my_donations.php">Cancel</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
