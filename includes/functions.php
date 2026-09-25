<?php
require_once __DIR__ . '/db.php';

function app_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_lang(): string {
    app_session_start();
    $lang = $_SESSION['lang'] ?? 'en';
    return in_array($lang, ['en', 'ru'], true) ? $lang : 'en';
}

function t(string $key): string {
    static $strings = null;
    if ($strings === null) {
        $strings = require __DIR__ . '/../lang/' . current_lang() . '.php';
    }
    return $strings[$key] ?? $key;
}

function money(float $amount): string {
    return '$' . number_format($amount, 2);
}

function current_user(): ?array {
    app_session_start();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = get_db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    if ($user['status'] !== 'active') {
        app_session_start();
        session_destroy();
        header('Location: /login.php?banned=1');
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if (!$user['is_admin']) {
        header('Location: /dashboard.php');
        exit;
    }
    return $user;
}

function base_url(string $path = ''): string {
    return '/' . ltrim($path, '/');
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
