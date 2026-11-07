<?php
// FoodBridge — public home page
$pageTitle = 'Home';
require_once __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';

$baseUrl = '/FoodBridge';
$isLoggedIn = !empty($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;

// ---- Live statistics (straight from the database) ----
$totalDonations = (int) $pdo->query('SELECT COUNT(*) FROM food_donations')->fetchColumn();
$mealsShared    = (float) $pdo->query(
    "SELECT COALESCE(SUM(quantity), 0) FROM food_donations WHERE status = 'completed'"
)->fetchColumn();
$activeDonors   = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'donor' AND status = 'active'"
)->fetchColumn();

// ---- Latest available food ----
$stmt = $pdo->query(
    "SELECT fd.*, u.name AS donor_name
     FROM food_donations fd
     JOIN users u ON u.id = fd.donor_id
     WHERE fd.status = 'available'
     ORDER BY fd.created_at DESC
     LIMIT 6"
);
$donations = $stmt->fetchAll();
?>

<!-- Hero -->
<section class="fb-hero p-4 p-md-5 mb-5">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold mb-3">Good food should never go to waste.</h1>
            <p class="lead mb-4">
                FoodBridge connects restaurants, bakeries, caterers and households that have surplus food
                with people and community kitchens that need it — quickly, safely, and for free.
            </p>
            <?php if ($isLoggedIn): ?>
                <a class="btn btn-fb-orange btn-lg me-2" href="<?= $baseUrl ?>/<?= htmlspecialchars((string) $role, ENT_QUOTES, 'UTF-8') ?>/dashboard.php">Go to my dashboard</a>
            <?php else: ?>
                <a class="btn btn-fb-orange btn-lg me-2" href="<?= $baseUrl ?>/auth/register.php">Donate food</a>
                <a class="btn btn-outline-light btn-lg" href="<?= $baseUrl ?>/auth/register.php">Request food</a>
            <?php endif; ?>
        </div>
        <div class="col-lg-4 text-center d-none d-lg-block">
            <div style="font-size: 7rem; line-height: 1;">&#129368;</div>
            <p class="mb-0 text-white-50">Share a meal. Bridge a gap.</p>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="mb-5">
    <h2 class="fb-section-title text-center mb-4">How it works</h2>
    <div class="row g-4 text-center">
        <div class="col-6 col-lg-3">
            <div class="fb-step-circle">1</div>
            <h3 class="h6 fw-bold">Register</h3>
            <p class="text-muted small mb-0">Sign up free as a donor or a receiver in under a minute.</p>
        </div>
        <div class="col-6 col-lg-3">
            <div class="fb-step-circle">2</div>
            <h3 class="h6 fw-bold">Post or browse</h3>
            <p class="text-muted small mb-0">Donors list surplus food with pickup place and expiry time. Receivers browse what is available nearby.</p>
        </div>
        <div class="col-6 col-lg-3">
            <div class="fb-step-circle">3</div>
            <h3 class="h6 fw-bold">Request &amp; confirm</h3>
            <p class="text-muted small mb-0">A receiver sends a request. The donor accepts it and the pickup is arranged.</p>
        </div>
        <div class="col-6 col-lg-3">
            <div class="fb-step-circle">4</div>
            <h3 class="h6 fw-bold">Pick up &amp; share</h3>
            <p class="text-muted small mb-0">Food changes hands before it expires — a meal shared, not wasted.</p>
        </div>
    </div>
</section>

<!-- Live statistics -->
<section class="mb-5">
    <div class="row g-3 text-center">
        <div class="col-4">
            <div class="fb-stat p-3 p-md-4 h-100">
                <div class="fb-stat-number"><?= number_format($totalDonations) ?></div>
                <div class="fb-stat-label">Total donations</div>
            </div>
        </div>
        <div class="col-4">
            <div class="fb-stat p-3 p-md-4 h-100">
                <div class="fb-stat-number"><?= number_format($mealsShared) ?></div>
                <div class="fb-stat-label">Meals shared</div>
            </div>
        </div>
        <div class="col-4">
            <div class="fb-stat p-3 p-md-4 h-100">
                <div class="fb-stat-number"><?= number_format($activeDonors) ?></div>
                <div class="fb-stat-label">Active donors</div>
            </div>
        </div>
    </div>
    <p class="text-center text-muted small mt-2 mb-0">Live from the FoodBridge database. Meals shared counts the quantity of completed donations.</p>
</section>

<!-- Latest available food -->
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fb-section-title mb-0">Latest available food</h2>
    </div>
    <div class="row g-3">
        <?php if (!$donations): ?>
            <p class="text-muted">No donations available right now. Please check back later.</p>
        <?php endif; ?>
        <?php foreach ($donations as $d): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-donation h-100">
                    <?php if (!empty($d['image'])): ?>
                        <img src="<?= $baseUrl ?>/assets/uploads/<?= htmlspecialchars($d['image'], ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="">
                    <?php else: ?>
                        <div class="fb-no-image">&#127858;</div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge badge-fb-category"><?= htmlspecialchars($d['category'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-success">Available</span>
                        </div>
                        <h3 class="card-title h5"><?= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php if (!empty($d['description'])): ?>
                            <p class="card-text text-muted small"><?= htmlspecialchars($d['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <p class="card-text mb-1"><strong>Quantity:</strong> <?= htmlspecialchars($d['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($d['unit'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="card-text mb-1"><strong>Pickup:</strong> <?= htmlspecialchars($d['pickup_address'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="card-text"><small class="text-muted">Expires: <?= htmlspecialchars($d['expiry_time'], ENT_QUOTES, 'UTF-8') ?> &middot; by <?= htmlspecialchars($d['donor_name'], ENT_QUOTES, 'UTF-8') ?></small></p>
                        <div class="mt-auto">
                            <?php if ($isLoggedIn && $role === 'receiver'): ?>
                                <a class="btn btn-fb-green btn-sm" href="<?= $baseUrl ?>/receiver/dashboard.php">Request this food</a>
                            <?php elseif (!$isLoggedIn): ?>
                                <a class="btn btn-fb-green btn-sm" href="<?= $baseUrl ?>/auth/login.php">Login to request</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Call to action -->
<section class="fb-cta p-4 p-md-5 text-center">
    <h2 class="fw-bold mb-2">Have surplus food? Someone nearby needs it tonight.</h2>
    <p class="mb-4">Join FoodBridge as a donor or receiver. Posting a donation takes less than two minutes.</p>
    <?php if (!$isLoggedIn): ?>
        <a class="btn btn-light btn-lg me-2" href="<?= $baseUrl ?>/auth/register.php">Create free account</a>
        <a class="btn btn-outline-light btn-lg" href="<?= $baseUrl ?>/about.php">Learn more</a>
    <?php else: ?>
        <a class="btn btn-light btn-lg" href="<?= $baseUrl ?>/about.php">Learn more about our mission</a>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
