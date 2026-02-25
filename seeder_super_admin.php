<?php

require_once __DIR__ . '/src/core/Database.php';
require_once __DIR__ . '/src/core/Encryption.php';

// Load .env if you use dotenv
// require_once __DIR__ . '/vendor/autoload.php';

public static function seeder(){



$pdo = Database::connect();

// Check if super admin already exists
$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE is_super_admin = 1
    LIMIT 1
");

$stmt->execute();

if ($stmt->fetch()) {
    echo "Super admin already exists.\n";
    exit;
}

// Read from .env
$superAdminEmail = $_ENV['SUPER_ADMIN_EMAIL'] ?? 'superadmin@system.com';
$superAdminPassword = $_ENV['SUPER_ADMIN_PASSWORD'] ?? 'StrongPassword123';
$superAdminName = $_ENV['SUPER_ADMIN_NAME'] ?? 'System Super Admin';

// Insert super admin
$insert = $pdo->prepare("
    INSERT INTO users
    (tenant_id, name, email, email_hash, password_hash, role, status, is_super_admin)
    VALUES (NULL, ?, ?, ?, ?, 'admin', 'active', 1)
");

$insert->execute([
    Encryption::encrypt($superAdminName),
    Encryption::encrypt($superAdminEmail),
    Encryption::blindIndex($superAdminEmail),
    password_hash($superAdminPassword, PASSWORD_BCRYPT)
]);

echo "Super admin created successfully.\n";

}