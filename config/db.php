<?php
// FoodBridge — database connection (PDO)
// XAMPP defaults: host=localhost, user=root, password='' (empty)

$host    = 'localhost';
$dbname  = 'foodbridge';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

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
