<?php
require_once __DIR__ . '/../includes/functions.php';

$stmt = get_db()->prepare("SELECT * FROM services WHERE active = TRUE ORDER BY id ASC");
$stmt->execute();
$services = $stmt->fetchAll();

require __DIR__ . '/../templates/header.php';
?>

<div class="hero">
    <h1><?= t('home_title') ?></h1>
    <p><?= t('home_subtitle') ?></p>
    <a href="<?= current_user() ? '/dashboard.php' : '/register.php' ?>" class="btn"><?= t('home_cta') ?></a>
</div>

<div class="cards">
    <?php foreach ($services as $s): ?>
        <div class="card">
            <h3><?= e($s['name']) ?></h3>
            <p><?= $s['sickw_service_id'] === '103' ? t('home_service_carrier_desc') : t('home_service_icloud_desc') ?></p>
            <span class="price-tag"><?= t('home_price') ?>: <?= money((float) $s['price']) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
