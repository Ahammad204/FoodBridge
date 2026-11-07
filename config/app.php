<?php
// FoodBridge — shared app settings.
//
// BASE_URL is auto-detected from the host name:
//   - local XAMPP (localhost / 127.0.0.1 / ::1)  -> '/FoodBridge'
//   - any real domain (site in htdocs root)      -> '' (domain root)
//
// Every link, redirect, form action and asset path in the app is built as
// BASE_URL . '/some/path', so the same code runs in both places.

if (!defined('BASE_URL')) {
    [$fbBaseUrl, $fbIsLocal] = (static function (): array {
        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));

        // Strip the port (handles "localhost:8080" and "[::1]:8080").
        if (str_starts_with($host, '[')) {
            $end = strpos($host, ']');
            $host = $end === false ? trim($host, '[]') : substr($host, 1, $end - 1);
        } else {
            $host = explode(':', $host)[0];
        }

        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1', ''], true);

        return [$isLocal ? '/FoodBridge' : '', $isLocal];
    })();

    define('BASE_URL', $fbBaseUrl);
    define('FB_IS_LOCAL', $fbIsLocal);
    unset($fbBaseUrl, $fbIsLocal);
}
