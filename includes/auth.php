<?php
require_once __DIR__ . '/functions.php';

function find_user_by_email(string $email): ?array {
    $stmt = get_db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function register_user(string $email, string $password): array {
    $email = strtolower(trim($email));

    if (find_user_by_email($email)) {
        return ['ok' => false, 'error' => 'register_error_exists'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'register_error_password_short'];
    }

    $stmt = get_db()->prepare('INSERT INTO users (email, password_hash, balance, is_admin, status) VALUES (?, ?, 0, FALSE, ?)');
    $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), 'active']);

    return ['ok' => true];
}

function attempt_login(string $email, string $password): array {
    $user = find_user_by_email(strtolower(trim($email)));
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'login_error'];
    }
    if ($user['status'] !== 'active') {
        return ['ok' => false, 'error' => 'login_banned'];
    }

    app_session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    return ['ok' => true, 'user' => $user];
}

function logout_user(): void {
    app_session_start();
    $_SESSION = [];
    session_destroy();
}
