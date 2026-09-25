<?php
require_once __DIR__ . '/../../includes/auth.php';
$admin = require_admin();
$db = get_db();

$orders = $db->query("
    SELECT o.*, u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 200
")->fetchAll();

require __DIR__ . '/../../templates/header.php';

$statusBadge = function (string $status) {
    $map = ['success' => 'status_success', 'failed' => 'status_failed', 'refunded' => 'status_refunded', 'pending' => 'status_pending'];
    return '<span class="badge badge-' . $status . '">' . t($map[$status] ?? $status) . '</span>';
};
?>

<div class="admin-nav">
    <a href="/admin/index.php"><?= t('admin_dashboard') ?></a>
    <a href="/admin/users.php"><?= t('admin_users') ?></a>
    <a href="/admin/services.php"><?= t('admin_services') ?></a>
    <a href="/admin/orders.php" class="active"><?= t('admin_orders') ?></a>
</div>

<div class="table-wrap">
<table>
    <tr>
        <th><?= t('dash_col_date') ?></th>
        <th><?= t('admin_col_email') ?></th>
        <th><?= t('dash_col_service') ?></th>
        <th><?= t('dash_col_imei') ?></th>
        <th><?= t('dash_col_price') ?></th>
        <th><?= t('dash_col_status') ?></th>
        <th><?= t('dash_col_result') ?></th>
    </tr>
    <?php foreach ($orders as $o): ?>
        <tr>
            <td><?= e(date('Y-m-d H:i', strtotime($o['created_at']))) ?></td>
            <td><?= e($o['email']) ?></td>
            <td><?= e($o['service_name']) ?></td>
            <td><?= e($o['imei']) ?></td>
            <td><?= money((float) $o['price']) ?></td>
            <td><?= $statusBadge($o['status']) ?></td>
            <td class="result-text"><?= e((string) $o['result']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
