<?php
require_once __DIR__ . '/../includes/auth.php';

if (current_user()) {
    header('Location: /dashboard.php');
    exit;
}

$error = null;
if (!empty($_GET['banned'])) {
    $error = 'login_banned';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $result = attempt_login($email, $password);

    if ($result['ok']) {
        if ($result['user']['is_admin']) {
            header('Location: /admin/index.php');
        } else {
            header('Location: /dashboard.php');
        }
        exit;
    }
    $error = $result['error'];
}

require __DIR__ . '/../templates/header.php';
?>

<div class="form-box">
    <h2><?= t('login_title') ?></h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= t($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label><?= t('login_email') ?></label>
            <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label><?= t('login_password') ?></label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn" style="width:100%"><?= t('login_button') ?></button>
    </form>

    <p style="text-align:center;margin-top:16px;font-size:0.9em;color:#666;">
        <?= t('login_forgot') ?> <?= t('login_forgot_text') ?>: <a href="tel:+37493722027">+374 93 72 20 27</a>
    </p>
    <p style="text-align:center;"><?= t('login_no_account') ?> <a href="/register.php"><?= t('nav_register') ?></a></p>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
