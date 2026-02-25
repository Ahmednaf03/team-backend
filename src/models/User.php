<?php

require_once __DIR__ . '/../helpers/Encryption.php';

class User
{
 private static function db($tenantId)
    {
    return DatabaseManager::tenant($tenantId);
    }


    public static function create($tenantId, $data)
{
    $db = self::db($tenantId);   // get once

    $stmt = $db->prepare("
        INSERT INTO users
        (name, email, email_hash, password_hash, role, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");

    $stmt->execute([
        Encryption::encrypt($data['name']),
        Encryption::encrypt($data['email']),
        Encryption::blindIndex($data['email']),
        password_hash($data['password'], PASSWORD_BCRYPT),
        $data['role']
    ]);

    return $db->lastInsertId();  // same connection
}


    public static function findByEmail($email, $tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT *
            FROM users
            AND email_hash = ?
            AND status = 'active'
            AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            Encryption::blindIndex($email)
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public static function getById($id, $tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT id, name, email, role, status, created_at
            FROM users
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $row['name'] = Encryption::decrypt($row['name']);
        $row['email'] = Encryption::decrypt($row['email']);

        return $row;
    }


    public static function softDelete($id, $tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            UPDATE users
            SET deleted_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id, $tenantId]);
    }
}
