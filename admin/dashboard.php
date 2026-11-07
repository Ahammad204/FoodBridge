<?php
// FoodBridge — admin dashboard: platform stats + Chart.js charts
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$baseUrl = '/FoodBridge';

$count = fn(string $sql): int => (int) $pdo->query($sql)->fetchColumn();

$totalUsers        = $count('SELECT COUNT(*) FROM users');
$totalDonors       = $count("SELECT COUNT(*) FROM users WHERE role = 'donor'");
$totalReceivers    = $count("SELECT COUNT(*) FROM users WHERE role = 'receiver'");
$totalDonations    = $count('SELECT COUNT(*) FROM food_donations');
$totalRequests     = $count('SELECT COUNT(*) FROM food_requests');
$pendingRequests   = $count("SELECT COUNT(*) FROM food_requests WHERE status = 'pending'");
$completedDonations = $count("SELECT COUNT(*) FROM food_donations WHERE status = 'completed'");
$pendingVerify     = $count("SELECT COUNT(*) FROM users WHERE role = 'donor' AND is_verified = 0 AND status = 'active'");

// ---- Chart 1: donations per month (last 6 months, zero-filled) ----
$monthKeys = [];
$monthLabels = [];
$monthCounts = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("first day of -$i month");
    $monthKeys[]   = date('Y-m', $ts);
    $monthLabels[] = date('M Y', $ts);
    $monthCounts[date('Y-m', $ts)] = 0;
}
$rows = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
     FROM food_donations
     WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 MONTH), '%Y-%m-01')
     GROUP BY ym"
)->fetchAll();
foreach ($rows as $row) {
    if (isset($monthCounts[$row['ym']])) {
        $monthCounts[$row['ym']] = (int) $row['c'];
    }
}
$monthData = array_values($monthCounts);

// ---- Chart 2: donors vs receivers ----
$roleData = [$totalDonors, $totalReceivers];

$adminPage = 'dashboard';
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-3">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    </div>
    <div class="col-lg-9">
        <h1 class="h4 mb-3">Admin Dashboard</h1>

        <?php flash_show(); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-6">
                <div class="fb-stat p-3 text-center h-100">
                    <div class="fb-stat-number"><?= number_format($totalUsers) ?></div>
                    <div class="fb-stat-label">Total users</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fb-stat p-3 text-center h-100">
                    <div class="fb-stat-number"><?= number_format($totalDonations) ?></div>
                    <div class="fb-stat-label">Total donations</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fb-stat p-3 text-center h-100">
                    <div class="fb-stat-number"><?= number_format($totalRequests) ?></div>
                    <div class="fb-stat-label">Total requests</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fb-stat p-3 text-center h-100">
                    <div class="fb-stat-number"><?= number_format($completedDonations) ?></div>
                    <div class="fb-stat-label">Completed donations</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fb-stat p-3 text-center h-100" style="border-top-color: var(--fb-orange);">
                    <div class="fb-stat-number"><?= number_format($totalDonors) ?></div>
                    <div class="fb-stat-label">Donors</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fb-stat p-3 text-center h-100" style="border-top-color: var(--fb-orange);">
                    <div class="fb-stat-number"><?= number_format($totalReceivers) ?></div>
                    <div class="fb-stat-label">Receivers</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fb-stat p-3 text-center h-100" style="border-top-color: var(--fb-orange);">
                    <div class="fb-stat-number"><?= number_format($pendingRequests) ?></div>
                    <div class="fb-stat-label">Pending requests</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="fb-stat p-3 text-center h-100" style="border-top-color: var(--fb-orange);">
                    <div class="fb-stat-number"><?= number_format($pendingVerify) ?></div>
                    <div class="fb-stat-label">Donors to verify</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-bold">Donations per month (last 6 months)</h2>
                        <canvas id="donationsChart" height="220"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-bold">Donors vs Receivers</h2>
                        <canvas id="rolesChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('donationsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($monthLabels) ?>,
        datasets: [{
            label: 'Donations',
            data: <?= json_encode($monthData) ?>,
            backgroundColor: '#1b7a43',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

new Chart(document.getElementById('rolesChart'), {
    type: 'doughnut',
    data: {
        labels: ['Donors', 'Receivers'],
        datasets: [{
            data: <?= json_encode($roleData) ?>,
            backgroundColor: ['#1b7a43', '#f57c00']
        }]
    },
    options: { responsive: true }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
