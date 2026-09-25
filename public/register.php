<?php
require_once __DIR__ . '/../includes/auth.php';

if (current_user()) {
    header('Location: /dashboard.php');
    exit;
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($password !== $confirm) {
        $error = 'register_error_password';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'register_error_exists'; // reuse generic message for invalid format too
    } else {
        $result = register_user($email, $password);
        if ($result['ok']) {
            $success = true;
        } else {
            $error = $result['error'];
        }
    }
}

require __DIR__ . '/../templates/header.php';
?>

<div class="form-box">
    <h2><?= t('register_title') ?></h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= t($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= t('register_success') ?> <a href="/login.php"><?= t('login_button') ?></a></div>
    <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label><?= t('register_email') ?></label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= t('register_password') ?></label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label><?= t('register_password_confirm') ?></label>
                <input type="password" name="password_confirm" required minlength="6">
            </div>
            <button type="submit" class="btn" style="width:100%"><?= t('register_button') ?></button>
        </form>
        <p style="text-align:center;margin-top:16px;"><?= t('register_have_account') ?> <a href="/login.php"><?= t('nav_login') ?></a></p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
