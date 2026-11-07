<?php
// FoodBridge — page header. Set $pageTitle before including.
require_once __DIR__ . '/../config/app.php'; // defines BASE_URL + FB_IS_LOCAL
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
$pageTitle = $pageTitle ?? 'FoodBridge';
$baseUrl   = $baseUrl   ?? BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | FoodBridge</title>
    <meta name="description" content="FoodBridge connects food donors with people who need it — share surplus food, reduce waste.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
<main class="container py-4">
