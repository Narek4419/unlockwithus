<?php
require_once __DIR__ . '/../includes/functions.php';
app_session_start();
$__user = current_user();
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../public/assets/css/style.css') ?: 1 ?>">
    <link rel="icon" href="/assets/img/logo.png">
</head>
<body>
<div class="navbar">
    <div class="brand">
        <a href="/index.php"><img src="/assets/img/logo.png" alt="Unlockwithus"></a>
    </div>
    <nav>
        <a href="/index.php"><?= t('nav_home') ?></a>
        <?php if ($__user): ?>
            <?php if ($__user['is_admin']): ?>
                <a href="/admin/index.php"><?= t('nav_admin') ?></a>
            <?php else: ?>
                <a href="/dashboard.php"><?= t('nav_dashboard') ?></a>
            <?php endif; ?>
            <a href="/logout.php"><?= t('nav_logout') ?></a>
        <?php else: ?>
            <a href="/login.php"><?= t('nav_login') ?></a>
            <a href="/register.php"><?= t('nav_register') ?></a>
        <?php endif; ?>
        <span class="lang-switch">
            <a href="/set_lang.php?lang=en&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="<?= current_lang() === 'en' ? 'active' : '' ?>">EN</a>
            <a href="/set_lang.php?lang=ru&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="<?= current_lang() === 'ru' ? 'active' : '' ?>">RU</a>
        </span>
    </nav>
</div>
<div class="container">
