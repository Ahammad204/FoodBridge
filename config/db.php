<?php
// FoodBridge — database connection (PDO)
//
// Local XAMPP uses the defaults below (host=localhost, user=root, empty
// password). On any real host, credentials come from
// config/config.production.php instead (kept out of Git — see .gitignore).

require_once __DIR__ . '/app.php'; // defines BASE_URL + FB_IS_LOCAL

if (FB_IS_LOCAL) {
    $host    = 'localhost';
    $dbname  = 'foodbridge';
    $user    = 'root';
    $pass    = '';
    $charset = 'utf8mb4';
} else {
    $prodConfigFile = __DIR__ . '/config.production.php';
    if (!is_file($prodConfigFile)) {
        die('Missing config/config.production.php — production database credentials are not set up.');
    }
    $prod = require $prodConfigFile;
    if (!is_array($prod)) {
        die('config/config.production.php must return an array of database credentials.');
    }
    $host    = (string) ($prod['host'] ?? '');
    $dbname  = (string) ($prod['dbname'] ?? '');
    $user    = (string) ($prod['user'] ?? '');
    $pass    = (string) ($prod['pass'] ?? '');
    $charset = (string) ($prod['charset'] ?? 'utf8mb4');
    unset($prod, $prodConfigFile);
}

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// ---- Auto-expire donations ----
// Any donation still 'available' whose expiry_time has passed becomes
// 'expired'. Runs once per page load (cheap single UPDATE).
// Donations already 'requested' (promised to a receiver) are left alone so
// an accepted pickup can still be completed.
try {
    $pdo->exec(
        "UPDATE food_donations SET status = 'expired'
         WHERE status = 'available' AND expiry_time <= NOW()"
    );
} catch (PDOException $e) {
    // Table may not exist yet (before database.sql is imported) — ignore.
}
