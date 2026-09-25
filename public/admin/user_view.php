<?php
require_once __DIR__ . '/../../includes/auth.php';
$admin = require_admin();
$db = get_db();

$userId = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM users WHERE id = ? AND is_admin = FALSE');
$stmt->execute([$userId]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    header('Location: /admin/users.php');
    exit;
}

$tempPassword = null;
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_funds') {
        $amount = (float) ($_POST['amount'] ?? 0);
        $note = trim($_POST['note'] ?? '') ?: ($amount >= 0 ? 'Balance added by administrator' : 'Balance deducted by administrator');

        if ($amount != 0) {
            $db->beginTransaction();
            $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')->execute([$amount, $userId]);
            $db->prepare('INSERT INTO transactions (user_id, amount, type, description, created_by) VALUES (?, ?, ?, ?, ?)')
                ->execute([$userId, $amount, $amount >= 0 ? 'admin_credit' : 'admin_debit', $note, $admin['id']]);
            $db->commit();
            $flash = 'updated';
        }
    } elseif ($action === 'reset_password') {
        $tempPassword = bin2hex(random_bytes(4)); // 8-char temp password
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($tempPassword, PASSWORD_DEFAULT), $userId]);
    } elseif ($action === 'toggle_status') {
        $newStatus = $targetUser['status'] === 'active' ? 'suspended' : 'active';
        $db->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $userId]);
    }

    // Refresh target user record
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch();
}

$stmt = $db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

require __DIR__ . '/../../templates/header.php';

$statusBadge = function (string $status) {
    $map = ['success' => 'status_success', 'failed' => 'status_failed', 'refunded' => 'status_refunded', 'pending' => 'status_pending'];
    return '<span class="badge badge-' . $status . '">' . t($map[$status] ?? $status) . '</span>';
};
?>

<div class="admin-nav">
    <a href="/admin/index.php"><?= t('admin_dashboard') ?></a>
    <a href="/admin/users.php" class="active"><?= t('admin_users') ?></a>
    <a href="/admin/services.php"><?= t('admin_services') ?></a>
    <a href="/admin/orders.php"><?= t('admin_orders') ?></a>
</div>

<p><a href="/admin/users.php">&larr; <?= t('admin_back_users') ?></a></p>

<h2><?= e($targetUser['email']) ?></h2>

<?php if ($tempPassword): ?>
    <div class="alert alert-success">
        <?= t('admin_reset_password_result') ?><br>
        <strong style="font-size:1.3em;letter-spacing:1px;"><?= e($tempPassword) ?></strong>
    </div>
<?php endif; ?>
<?php if ($flash === 'updated'): ?>
    <div class="alert alert-success">✅ <?= t('admin_apply') ?></div>
<?php endif; ?>

<div class="balance-box">
    <div><?= t('dash_balance') ?></div>
    <div class="amount"><?= money((float) $targetUser['balance']) ?></div>
</div>

<div class="cards">
    <div class="card">
        <h3><?= t('admin_add_funds') ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_funds">
            <div class="form-group">
                <label><?= t('admin_amount') ?></label>
                <input type="number" step="0.01" name="amount" required>
            </div>
            <div class="form-group">
                <label><?= t('admin_note') ?></label>
                <input type="text" name="note" placeholder="Optional note for the user">
            </div>
            <button type="submit" class="btn" style="width:100%"><?= t('admin_apply') ?></button>
        </form>
    </div>
    <div class="card">
        <h3><?= t('admin_reset_password') ?></h3>
        <p style="color:#666;font-size:0.9em;">Generates a new random temporary password. Send it to the user securely (phone/WhatsApp).</p>
        <form method="POST" onsubmit="return confirm('<?= e(t('admin_reset_password_confirm')) ?>');">
            <input type="hidden" name="action" value="reset_password">
            <button type="submit" class="btn btn-orange" style="width:100%"><?= t('admin_reset_password') ?></button>
        </form>
        <br>
        <form method="POST" onsubmit="return confirm('Are you sure?');">
            <input type="hidden" name="action" value="toggle_status">
            <button type="submit" class="btn <?= $targetUser['status'] === 'active' ? 'btn-danger' : '' ?>" style="width:100%">
                <?= $targetUser['status'] === 'active' ? t('admin_suspend') : t('admin_unsuspend') ?>
            </button>
        </form>
    </div>
</div>

<h3 class="section-title"><?= t('dash_order_history') ?></h3>
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

<h3 class="section-title"><?= t('dash_funding_history') ?></h3>
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

<?php require __DIR__ . '/../../templates/footer.php'; ?>
