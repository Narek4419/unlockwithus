<?php
require_once __DIR__ . '/config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    migrate($pdo);

    return $pdo;
}

function migrate(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            balance NUMERIC(10,2) NOT NULL DEFAULT 0,
            is_admin BOOLEAN NOT NULL DEFAULT FALSE,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        );

        CREATE TABLE IF NOT EXISTS services (
            id SERIAL PRIMARY KEY,
            sickw_service_id VARCHAR(10) NOT NULL,
            name VARCHAR(255) NOT NULL,
            price NUMERIC(10,2) NOT NULL,
            active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        );

        CREATE TABLE IF NOT EXISTS orders (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id),
            service_id INTEGER NOT NULL REFERENCES services(id),
            service_name VARCHAR(255) NOT NULL,
            imei VARCHAR(50) NOT NULL,
            price NUMERIC(10,2) NOT NULL,
            result TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        );

        CREATE TABLE IF NOT EXISTS transactions (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id),
            amount NUMERIC(10,2) NOT NULL,
            type VARCHAR(30) NOT NULL,
            description TEXT,
            created_by INTEGER REFERENCES users(id),
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        );
    ");

    // Seed the two default services if the table is empty
    $count = (int) $pdo->query("SELECT COUNT(*) AS c FROM services")->fetch()['c'];
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO services (sickw_service_id, name, price, active) VALUES (?, ?, ?, TRUE)");
        $stmt->execute(['103', 'iPhone Carrier Check', 0.60]);
        $stmt->execute(['3', 'iCloud ON/OFF Check', 0.20]);
    }

    // Seed the admin account if no admin exists yet, using env vars.
    $adminCount = (int) $pdo->query("SELECT COUNT(*) AS c FROM users WHERE is_admin = TRUE")->fetch()['c'];
    if ($adminCount === 0 && ADMIN_PASSWORD !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, balance, is_admin, status)
            VALUES (?, ?, 0, TRUE, 'active')
            ON CONFLICT (email) DO UPDATE SET is_admin = TRUE
        ");
        $stmt->execute([ADMIN_EMAIL, password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT)]);
    }
}
