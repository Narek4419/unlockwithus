<?php
require_once __DIR__ . '/../../includes/auth.php';
$admin = require_admin();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $sid = preg_replace('/[^0-9]+/', '', $_POST['sickw_service_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        if ($sid !== '' && $name !== '' && $price > 0) {
            $db->prepare('INSERT INTO services (sickw_service_id, name, price, active) VALUES (?, ?, ?, TRUE)')
                ->execute([$sid, $name, $price]);
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $sid = preg_replace('/[^0-9]+/', '', $_POST['sickw_service_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $active = isset($_POST['active']) ? 'TRUE' : 'FALSE';
        $db->prepare("UPDATE services SET sickw_service_id = ?, name = ?, price = ?, active = $active WHERE id = ?")
            ->execute([$sid, $name, $price, $id]);
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
    }

    header('Location: /admin/services.php');
    exit;
}

$services = $db->query('SELECT * FROM services ORDER BY id ASC')->fetchAll();

require __DIR__ . '/../../templates/header.php';
?>

<div class="admin-nav">
    <a href="/admin/index.php"><?= t('admin_dashboard') ?></a>
    <a href="/admin/users.php"><?= t('admin_users') ?></a>
    <a href="/admin/services.php" class="active"><?= t('admin_services') ?></a>
    <a href="/admin/orders.php"><?= t('admin_orders') ?></a>
</div>

<h3 class="section-title"><?= t('admin_services_add') ?></h3>
<div class="form-box" style="max-width:600px;">
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label><?= t('admin_service_id') ?></label>
            <input type="text" name="sickw_service_id" required placeholder="e.g. 103">
        </div>
        <div class="form-group">
            <label><?= t('admin_service_name') ?></label>
            <input type="text" name="name" required placeholder="e.g. iPhone Carrier Check">
        </div>
        <div class="form-group">
            <label><?= t('admin_service_price') ?></label>
            <input type="number" step="0.01" name="price" required placeholder="e.g. 0.60">
        </div>
        <button type="submit" class="btn" style="width:100%"><?= t('admin_add') ?></button>
    </form>
</div>

<h3 class="section-title"><?= t('admin_services') ?></h3>
<div class="table-wrap">
<table>
    <tr>
        <th><?= t('admin_service_id') ?></th>
        <th><?= t('admin_service_name') ?></th>
        <th><?= t('admin_service_price') ?></th>
        <th><?= t('admin_service_active') ?></th>
        <th></th>
    </tr>
    <?php foreach ($services as $s): $formId = 'svc-' . (int) $s['id']; ?>
        <tr>
            <td><input form="<?= $formId ?>" type="text" name="sickw_service_id" value="<?= e($s['sickw_service_id']) ?>" style="width:80px;"></td>
            <td><input form="<?= $formId ?>" type="text" name="name" value="<?= e($s['name']) ?>"></td>
            <td><input form="<?= $formId ?>" type="number" step="0.01" name="price" value="<?= e((string) $s['price']) ?>" style="width:90px;"></td>
            <td><input form="<?= $formId ?>" type="checkbox" name="active" <?= $s['active'] ? 'checked' : '' ?>></td>
            <td style="white-space:nowrap;">
                <form id="<?= $formId ?>" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <button type="submit" class="btn btn-small"><?= t('admin_service_edit') ?></button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this service?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <button type="submit" class="btn btn-small btn-danger"><?= t('admin_service_delete') ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
