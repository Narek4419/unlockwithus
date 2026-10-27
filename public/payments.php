<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$db = get_db();

$stmt = $db->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 200');
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

require __DIR__ . '/../templates/header.php';
?>

<p><a href="/dashboard.php">&larr; <?= t('back_to_dashboard') ?></a></p>
<h2 class="section-title" style="margin-top:10px;"><?= t('dash_funding_history') ?></h2>

<div class="table-wrap">
<?php if (count($transactions) === 0): ?>
    <p><?= t('dash_no_transactions') ?></p>
<?php else: ?>
    <table>
        <tr>
            <th><?= t('dash_col_date') ?></th>
            <th><?= t('dash_col_amount') ?></th>
            <th><?= t('dash_col_description') ?></th>
        </tr>
        <?php foreach ($transactions as $tx): ?>
            <tr>
                <td><?= e(date('Y-m-d H:i', strtotime($tx['created_at']))) ?></td>
                <td style="color: <?= $tx['amount'] >= 0 ? '#1E7A34' : '#D9534F' ?>; font-weight:700;">
                    <?= $tx['amount'] >= 0 ? '+' : '' ?><?= money((float) $tx['amount']) ?>
                </td>
                <td><?= e((string) $tx['description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
