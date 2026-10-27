<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$db = get_db();

$stmt = $db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 200');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

require __DIR__ . '/../templates/header.php';

$statusBadge = function (string $status) {
    $map = [
        'success' => 'status_success',
        'failed' => 'status_failed',
        'refunded' => 'status_refunded',
        'pending' => 'status_pending',
    ];
    return '<span class="badge badge-' . $status . '">' . t($map[$status] ?? $status) . '</span>';
};
?>

<p><a href="/dashboard.php">&larr; <?= t('back_to_dashboard') ?></a></p>
<h2 class="section-title" style="margin-top:10px;"><?= t('dash_order_history') ?></h2>

<div class="table-wrap">
<?php if (count($orders) === 0): ?>
    <p><?= t('dash_no_orders') ?></p>
<?php else: ?>
    <table>
        <tr>
            <th><?= t('dash_col_date') ?></th>
            <th><?= t('dash_col_service') ?></th>
            <th><?= t('dash_col_imei') ?></th>
            <th><?= t('dash_col_price') ?></th>
            <th><?= t('dash_col_status') ?></th>
            <th><?= t('dash_col_result') ?></th>
        </tr>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= e(date('Y-m-d H:i', strtotime($o['created_at']))) ?></td>
                <td><?= e($o['service_name']) ?></td>
                <td><?= e($o['imei']) ?></td>
                <td><?= money((float) $o['price']) ?></td>
                <td><?= $statusBadge($o['status']) ?></td>
                <td class="result-text"><?= e((string) $o['result']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
