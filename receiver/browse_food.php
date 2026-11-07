<?php
// FoodBridge — receiver: browse available (not expired) food
// Search by keyword, filter by category + location, paginated.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('receiver');

$baseUrl = '/FoodBridge';

$categories = ['Cooked Food', 'Bakery', 'Raw Food', 'Fruits & Vegetables', 'Packaged Food', 'Beverages', 'Other'];

$q        = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$location = trim($_GET['location'] ?? '');
if (!in_array($category, $categories, true)) {
    $category = '';
}

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

// ---- Build the filtered query (available + not expired, always) ----
$where  = ["fd.status = 'available'", 'fd.expiry_time > NOW()'];
$params = [];

if ($q !== '') {
    $where[]  = '(fd.title LIKE ? OR fd.description LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($category !== '') {
    $where[]  = 'fd.category = ?';
    $params[] = $category;
}
if ($location !== '') {
    $where[]  = 'fd.pickup_address LIKE ?';
    $params[] = '%' . $location . '%';
}
$whereSql = implode(' AND ', $where);

// Total rows for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM food_donations fd WHERE $whereSql");
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT fd.*, u.name AS donor_name, u.organization_name AS donor_org
     FROM food_donations fd
     JOIN users u ON u.id = fd.donor_id
     WHERE $whereSql
     ORDER BY fd.created_at DESC
     LIMIT ? OFFSET ?"
);
$listParams = array_merge($params, [$perPage, $offset]);
foreach ($listParams as $i => $value) {
    // LIMIT/OFFSET must be bound as integers.
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($i + 1, $value, $type);
}
$stmt->execute();
$donations = $stmt->fetchAll();

$queryBase = http_build_query([
    'q'        => $q,
    'category' => $category,
    'location' => $location,
]);

$pageTitle = 'Browse Food';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h4 mb-3">Available food</h1>

<?php flash_show(); ?>

<form method="get" action="" class="card p-3 mb-4">
    <div class="row g-2">
        <div class="col-md-4">
            <input type="text" class="form-control" name="q" placeholder="Search by keyword…"
                   value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= $category === $c ? 'selected' : '' ?>><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="location" placeholder="Location (e.g. Mirpur)…"
                   value="<?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-fb-green">Search</button>
        </div>
    </div>
    <?php if ($q !== '' || $category !== '' || $location !== ''): ?>
        <div class="mt-2">
            <a class="small" href="<?= $baseUrl ?>/receiver/browse_food.php">Clear filters</a>
            <span class="text-muted small">— <?= number_format($totalRows) ?> result(s)</span>
        </div>
    <?php endif; ?>
</form>

<div class="row g-3 mb-4">
    <?php if (!$donations): ?>
        <p class="text-muted">No food matches right now. Try clearing the filters, or check back later.</p>
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
                    <h2 class="card-title h5"><?= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="card-text mb-1"><strong>Quantity:</strong> <?= htmlspecialchars($d['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($d['unit'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="card-text mb-1"><strong>Donor:</strong> <?= htmlspecialchars($d['donor_name'], ENT_QUOTES, 'UTF-8') ?><?= !empty($d['donor_org']) ? ' (' . htmlspecialchars($d['donor_org'], ENT_QUOTES, 'UTF-8') . ')' : '' ?></p>
                    <p class="card-text mb-1"><strong>Location:</strong> <?= htmlspecialchars($d['pickup_address'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="card-text"><small class="text-muted">Expires: <?= htmlspecialchars($d['expiry_time'], ENT_QUOTES, 'UTF-8') ?></small></p>
                    <div class="mt-auto">
                        <a class="btn btn-fb-green btn-sm" href="<?= $baseUrl ?>/receiver/food_details.php?id=<?= (int) $d['id'] ?>">View details &amp; request</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav aria-label="Browse pages">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= $queryBase ?>&page=<?= $page - 1 ?>">Previous</a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= $queryBase ?>&page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= $queryBase ?>&page=<?= $page + 1 ?>">Next</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
