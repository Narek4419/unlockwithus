<?php
require_once __DIR__ . '/../includes/functions.php';
app_session_start();

$lang = $_GET['lang'] ?? 'en';
if (in_array($lang, ['en', 'ru'], true)) {
    $_SESSION['lang'] = $lang;
}

$redirect = $_GET['redirect'] ?? '/index.php';
// Only allow local redirects
if (!str_starts_with($redirect, '/')) {
    $redirect = '/index.php';
}
header('Location: ' . $redirect);
exit;
