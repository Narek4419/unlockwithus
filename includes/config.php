<?php
// ============================================================
// All secrets come from environment variables (set in Render's
// dashboard). Nothing sensitive is written in this file, so it
// is safe to commit this file to a public GitHub repository.
// ============================================================

// --- Database ---
// Render gives you DATABASE_URL like: postgres://user:pass@host:5432/dbname
$databaseUrl = getenv('DATABASE_URL') ?: '';

$dbHost = 'localhost';
$dbPort = '5432';
$dbName = 'unlockwithus';
$dbUser = 'postgres';
$dbPass = '';

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    $dbHost = $parts['host'] ?? $dbHost;
    $dbPort = $parts['port'] ?? $dbPort;
    $dbName = isset($parts['path']) ? ltrim($parts['path'], '/') : $dbName;
    $dbUser = $parts['user'] ?? $dbUser;
    $dbPass = $parts['pass'] ?? $dbPass;
}

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

// --- sickw.com API ---
define('SICKW_API_KEY', getenv('SICKW_API_KEY') ?: '');
define('SICKW_API_URL', 'https://sickw.com/api.php');

// --- First-boot admin bootstrap ---
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@unlockwithus.com');
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: '');

// --- App ---
define('APP_NAME', 'Unlockwithus.com');
define('APP_SECRET', getenv('APP_SECRET') ?: 'insecure-default-change-me');
define('MIN_TOPUP', 5.00); // informational only, shown to users
