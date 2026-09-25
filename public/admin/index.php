<?php
require_once __DIR__ . '/../../includes/auth.php';
$admin = require_admin();
$db = get_db();

$totalUsers = (int) $db->query("SELECT COUNT(*) FROM users WHERE is_admin = FALSE")->fetchColumn();
$totalOrders = (int) $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = (float) $db->query("SELECT COALESCE(SUM(price),0) FROM orders WHERE status = 'success'")->fetchColumn();

require __DIR__ . '/../../templates/header.php';
?>

<div class="admin-nav">
    <a href="/admin/index.php" class="active"><?= t('admin_dashboard') ?></a>
    <a href="/admin/users.php"><?= t('admin_users') ?></a>
    <a href="/admin/services.php"><?= t('admin_services') ?></a>
    <a href="/admin/orders.php"><?= t('admin_orders') ?></a>
</div>

<div class="stat-cards">
    <div class="stat-card">
        <div class="num"><?= $totalUsers ?></div>
        <div class="label"><?= t('admin_total_users') ?></div>
    </div>
    <div class="stat-card">
        <div class="num"><?= $totalOrders ?></div>
        <div class="label"><?= t('admin_total_orders') ?></div>
    </div>
    <div class="stat-card">
        <div class="num"><?= money($totalRevenue) ?></div>
        <div class="label"><?= t('admin_total_revenue') ?></div>
    </div>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
