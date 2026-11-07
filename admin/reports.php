<?php
// FoodBridge — admin: date-range report of donations + requests,
// with CSV export and a print-friendly view.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$baseUrl = BASE_URL;

// ---- Date range (defaults: first day of this month → today) ----
$from = trim($_GET['from'] ?? date('Y-m-01'));
$to   = trim($_GET['to'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = date('Y-m-d');
}
$fromDt = $from . ' 00:00:00';
$toDt   = $to . ' 23:59:59';

// ---- Data ----
$stmt = $pdo->prepare(
    "SELECT fd.*, u.name AS donor_name
     FROM food_donations fd JOIN users u ON u.id = fd.donor_id
     WHERE fd.created_at BETWEEN ? AND ?
     ORDER BY fd.created_at DESC"
);
$stmt->execute([$fromDt, $toDt]);
$donations = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT fr.*, fd.title AS donation_title,
            donor.name AS donor_name, receiver.name AS receiver_name
     FROM food_requests fr
     JOIN food_donations fd ON fd.id = fr.donation_id
     JOIN users donor ON donor.id = fd.donor_id
     JOIN users receiver ON receiver.id = fr.receiver_id
     WHERE fr.created_at BETWEEN ? AND ?
     ORDER BY fr.created_at DESC"
);
$stmt->execute([$fromDt, $toDt]);
$requests = $stmt->fetchAll();

// ---- CSV export (runs before any HTML is sent) ----
$export = $_GET['export'] ?? '';
if ($export === 'donations' || $export === 'requests') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="foodbridge_' . $export . '_' . $from . '_to_' . $to . '.csv"');
    $out = fopen('php://output', 'w');

    if ($export === 'donations') {
        fputcsv($out, ['ID', 'Title', 'Donor', 'Category', 'Quantity', 'Unit', 'Pickup Address', 'Expiry Time', 'Status', 'Created At']);
        foreach ($donations as $d) {
            fputcsv($out, [$d['id'], $d['title'], $d['donor_name'], $d['category'], $d['quantity'],
                $d['unit'], $d['pickup_address'], $d['expiry_time'], $d['status'], $d['created_at']]);
        }
    } else {
        fputcsv($out, ['ID', 'Donation', 'Donor', 'Receiver', 'Message', 'Status', 'Created At']);
        foreach ($requests as $r) {
            fputcsv($out, [$r['id'], $r['donation_title'], $r['donor_name'], $r['receiver_name'],
                $r['message'], $r['status'], $r['created_at']]);
        }
    }
    fclose($out);
    exit;
}

// ---- Status summaries ----
$donationByStatus = ['available' => 0, 'requested' => 0, 'completed' => 0, 'expired' => 0];
foreach ($donations as $d) {
    $donationByStatus[$d['status']] = ($donationByStatus[$d['status']] ?? 0) + 1;
}
$requestByStatus = ['pending' => 0, 'accepted' => 0, 'rejected' => 0, 'completed' => 0];
foreach ($requests as $r) {
    $requestByStatus[$r['status']] = ($requestByStatus[$r['status']] ?? 0) + 1;
}

$queryRange = http_build_query(['from' => $from, 'to' => $to]);

$adminPage = 'reports';
$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
    .fb-navbar, .fb-footer, .admin-sidebar, .no-print { display: none !important; }
    main.container { max-width: 100% !important; }
}
</style>

<div class="row g-4">
    <div class="col-lg-3 no-print">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
    </div>
    <div class="col-lg-9">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Report — <?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?> to <?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="no-print">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button>
                <a class="btn btn-outline-success btn-sm" href="?<?= $queryRange ?>&export=donations">Donations CSV</a>
                <a class="btn btn-outline-success btn-sm" href="?<?= $queryRange ?>&export=requests">Requests CSV</a>
            </div>
        </div>

        <form method="get" action="" class="card p-3 mb-4 no-print">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="from">From</label>
                    <input type="date" class="form-control" id="from" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="to">To</label>
                    <input type="date" class="form-control" id="to" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-fb-green">Generate report</button>
                </div>
            </div>
        </form>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="fb-stat p-3 text-center h-100">
                    <div class="fb-stat-number"><?= count($donations) ?></div>
                    <div class="fb-stat-label">Donations in range</div>
                    <div class="small text-muted mt-1">
                        Available <?= $donationByStatus['available'] ?> &middot;
                        Requested <?= $donationByStatus['requested'] ?> &middot;
                        Completed <?= $donationByStatus['completed'] ?> &middot;
                        Expired <?= $donationByStatus['expired'] ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="fb-stat p-3 text-center h-100" style="border-top-color: var(--fb-orange);">
                    <div class="fb-stat-number"><?= count($requests) ?></div>
                    <div class="fb-stat-label">Requests in range</div>
                    <div class="small text-muted mt-1">
                        Pending <?= $requestByStatus['pending'] ?> &middot;
                        Accepted <?= $requestByStatus['accepted'] ?> &middot;
                        Rejected <?= $requestByStatus['rejected'] ?> &middot;
                        Completed <?= $requestByStatus['completed'] ?>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="h6 fw-bold">Donations</h2>
        <div class="card mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead>
                            <tr><th>Title</th><th>Donor</th><th>Category</th><th>Qty</th><th>Status</th><th>Created</th></tr>
                        </thead>
                        <tbody>
                            <?php if (!$donations): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">No donations in this range.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($donations as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d['donor_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d['category'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d['quantity'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($d['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-capitalize"><?= htmlspecialchars($d['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($d['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <h2 class="h6 fw-bold">Requests</h2>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead>
                            <tr><th>Donation</th><th>Donor</th><th>Receiver</th><th>Status</th><th>Created</th></tr>
                        </thead>
                        <tbody>
                            <?php if (!$requests): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No requests in this range.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['donation_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($r['donor_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($r['receiver_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-capitalize"><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">Generated <?= date('Y-m-d H:i') ?> &middot; FoodBridge admin report</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
