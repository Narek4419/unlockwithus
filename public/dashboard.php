<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();
$db = get_db();

// A fresh one-time token for the order form. It gets invalidated the instant
// it's used, so the exact same submission can never be charged twice even if
// the browser resends it (refresh, double-click, slow network retry, etc.)
if (empty($_SESSION['order_token'])) {
    $_SESSION['order_token'] = bin2hex(random_bytes(16));
}

// Handle new order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $submittedToken = (string) ($_POST['order_token'] ?? '');

    if (!hash_equals($_SESSION['order_token'], $submittedToken)) {
        // Token missing, wrong, or already used = this is a duplicate/stale
        // resubmission. Ignore it completely: no charge, no API call.
        header('Location: /dashboard.php');
        exit;
    }

    // Invalidate the token immediately, before doing anything else, so any
    // duplicate request carrying the same token is rejected by the check above.
    $_SESSION['order_token'] = bin2hex(random_bytes(16));

    // Keep running even if the client closes the tab, so the order always
    // finishes (or gets refunded) properly.
    ignore_user_abort(true);
    set_time_limit(120);

    $serviceId = (int) ($_POST['service_id'] ?? 0);
    // IMEI or Serial Number: letters and digits only, uppercase
    $imei = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', $_POST['imei'] ?? ''));

    $stmt = $db->prepare('SELECT * FROM services WHERE id = ? AND active = TRUE');
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();

    if (!$service || strlen($imei) < 10 || strlen($imei) > 20) {
        $_SESSION['flash_error'] = 'dash_imei_invalid';
    } else {
        // Re-fetch fresh balance
        $stmt = $db->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
        $db->beginTransaction();
        $stmt->execute([$user['id']]);
        $freshBalance = (float) $stmt->fetchColumn();
        $price = (float) $service['price'];

        if ($freshBalance < $price) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'dash_insufficient';
        } else {
            // Deduct balance and create pending order
            $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')->execute([$price, $user['id']]);
            $db->prepare('INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)')
                ->execute([$user['id'], -$price, 'order_charge', 'Order: ' . $service['name'] . ' (' . $imei . ')']);

            $insertOrder = $db->prepare('INSERT INTO orders (user_id, service_id, service_name, imei, price, status) VALUES (?, ?, ?, ?, ?, ?) RETURNING id');
            $insertOrder->execute([$user['id'], $service['id'], $service['name'], $imei, $price, 'pending']);
            $orderId = $insertOrder->fetchColumn();

            $db->commit();

            // Call the sickw API
            require_once __DIR__ . '/../includes/sickw.php';
            $apiResult = sickw_check($imei, $service['sickw_service_id']);

            if ($apiResult['ok']) {
                $db->prepare('UPDATE orders SET status = ?, result = ? WHERE id = ?')
                    ->execute(['success', $apiResult['result'], $orderId]);
                $_SESSION['flash_order_result'] = ['ok' => true, 'text' => $apiResult['result']];
            } else {
                // Refund automatically
                $db->beginTransaction();
                $db->prepare('UPDATE orders SET status = ?, result = ? WHERE id = ?')
                    ->execute(['refunded', $apiResult['error'], $orderId]);
                $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')->execute([$price, $user['id']]);
                $db->prepare('INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)')
                    ->execute([$user['id'], $price, 'order_refund', 'Refund: ' . $service['name'] . ' (' . $imei . ') — ' . $apiResult['error']]);
                $db->commit();
                $_SESSION['flash_order_result'] = ['ok' => false, 'text' => $apiResult['error']];
            }
        }
    }

    // Always redirect after a POST (Post/Redirect/Get). This means refreshing
    // the resulting page, or the browser silently resending the request,
    // can never resubmit the order form again.
    header('Location: /dashboard.php');
    exit;
}

// Pull one-time flash messages left by the redirect above (if any)
$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
$orderResult = $_SESSION['flash_order_result'] ?? null;
unset($_SESSION['flash_order_result']);

// Load active services for the order form
$services = $db->query('SELECT * FROM services WHERE active = TRUE ORDER BY id ASC')->fetchAll();

// Load this user's order history
$stmt = $db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

// Load this user's transaction/balance history
$stmt = $db->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

require __DIR__ . '/../templates/header.php';

$statusBadge = function (string $status) {
    $map = [
        'success' => 'status_success',
        'failed' => 'status_failed',
        'refunded' => 'status_refunded',
        'pending' => 'status_pending',
    ];
    $class = 'badge-' . $status;
    return '<span class="badge ' . $class . '">' . t($map[$status] ?? $status) . '</span>';
};
?>

<div class="balance-box">
    <div><?= t('dash_balance') ?></div>
    <div class="amount"><?= money((float) $user['balance']) ?></div>
</div>

<div class="alert alert-info">
    <?= t('dash_add_funds') ?> <?= t('dash_add_funds_text') ?> <a href="tel:+37493722027"><strong>+374 93 72 20 27</strong></a>
</div>

<?php if ($orderResult): ?>
    <div class="alert <?= $orderResult['ok'] ? 'alert-success' : 'alert-error' ?>">
        <div class="result-text"><?= e($orderResult['text']) ?></div>
    </div>
<?php endif; ?>

<div class="form-box" style="max-width:600px;">
    <h2><?= t('dash_new_order') ?></h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= t($error) ?></div>
    <?php endif; ?>
    <form method="POST" onsubmit="return startOrder(this);">
        <input type="hidden" name="action" value="place_order">
        <input type="hidden" name="order_token" value="<?= e($_SESSION['order_token']) ?>">
        <div class="form-group">
            <label><?= t('dash_select_service') ?></label>
            <select name="service_id" required>
                <?php foreach ($services as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?> — <?= money((float) $s['price']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label><?= t('dash_imei') ?></label>
            <input type="text" name="imei" required maxlength="20" placeholder="354442067957452 / F2LXXXXXXXXX">
        </div>
        <button type="submit" class="btn" style="width:100%"><?= t('dash_submit') ?></button>
    </form>
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

<div id="loadingOverlay" class="loading-overlay" style="display:none;">
    <div class="loading-box">
        <div class="spinner"></div>
        <h3><?= t('dash_loading_title') ?></h3>
        <p><?= t('dash_loading_text') ?></p>
        <p class="loading-warn"><?= t('dash_loading_warn') ?></p>
    </div>
</div>
<script>
function startOrder(form) {
    var btn = form.querySelector('button[type=submit]');
    if (btn.dataset.sent) { return false; }
    btn.dataset.sent = '1';
    btn.disabled = true;
    document.getElementById('loadingOverlay').style.display = 'flex';
    return true;
}
window.addEventListener('pageshow', function (e) {
    if (e.persisted) { window.location.reload(); }
});
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
