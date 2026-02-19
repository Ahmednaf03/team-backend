<?php

require_once __DIR__ . '/../helpers/Encryption.php';

class User
{
    private static $db;

    private static function db()
    {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }


    public static function create($data)
    {
        $stmt = self::db()->prepare("
            INSERT INTO users
            (tenant_id, name, email, email_hash, password_hash, role, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $stmt->execute([
            $data['tenant_id'],
            Encryption::encrypt($data['name']),
            Encryption::encrypt($data['email']),
            Encryption::blindIndex($data['email']),
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role']
        ]);

        return self::db()->lastInsertId();
    }


    public static function findByEmail($email, $tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT *
            FROM users
            WHERE tenant_id = ?
            AND email_hash = ?
            AND status = 'active'
            AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            $tenantId,
            Encryption::blindIndex($email)
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public static function getById($id, $tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT id, name, email, role, status, created_at
            FROM users
            WHERE id = ?
            AND tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id, $tenantId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $row['name'] = Encryption::decrypt($row['name']);
        $row['email'] = Encryption::decrypt($row['email']);

        return $row;
    }


    public static function softDelete($id, $tenantId)
    {
        $stmt = self::db()->prepare("
            UPDATE users
            SET deleted_at = NOW()
            WHERE id = ?
            AND tenant_id = ?
        ");

        return $stmt->execute([$id, $tenantId]);
    }
}
