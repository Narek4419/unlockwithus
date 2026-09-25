<?php
require_once __DIR__ . '/../../includes/auth.php';
$admin = require_admin();
$db = get_db();

$users = $db->query("SELECT * FROM users WHERE is_admin = FALSE ORDER BY created_at DESC")->fetchAll();

require __DIR__ . '/../../templates/header.php';
?>

<div class="admin-nav">
    <a href="/admin/index.php"><?= t('admin_dashboard') ?></a>
    <a href="/admin/users.php" class="active"><?= t('admin_users') ?></a>
    <a href="/admin/services.php"><?= t('admin_services') ?></a>
    <a href="/admin/orders.php"><?= t('admin_orders') ?></a>
</div>

<div class="table-wrap">
<table>
    <tr>
        <th><?= t('admin_col_email') ?></th>
        <th><?= t('admin_col_balance') ?></th>
        <th><?= t('admin_col_status') ?></th>
        <th><?= t('admin_col_joined') ?></th>
        <th></th>
    </tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['email']) ?></td>
            <td><?= money((float) $u['balance']) ?></td>
            <td><?= $u['status'] === 'active' ? '✅ active' : '⛔ suspended' ?></td>
            <td><?= e(date('Y-m-d', strtotime($u['created_at']))) ?></td>
            <td><a href="/admin/user_view.php?id=<?= (int) $u['id'] ?>" class="btn btn-small"><?= t('admin_view') ?></a></td>
        </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
