<?php

class Auth
{
    private static $db;

    private static function db()
    {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }


    public static function findUserByEmail($emailHash, $tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT * FROM users
            WHERE email_hash = ?
            AND tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$emailHash, $tenantId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public static function createRefreshToken($userId, $tenantId, $refreshToken)
    {
        $hash = password_hash($refreshToken, PASSWORD_BCRYPT);

        $stmt = self::db()->prepare("
            INSERT INTO refresh_tokens
            (user_id, tenant_id, token_hash, expires_at)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
        ");

        return $stmt->execute([$userId, $tenantId, $hash]);
    }


    public static function findValidRefreshToken($refreshToken)
    {
        $stmt = self::db()->prepare("
            SELECT * FROM refresh_tokens
            WHERE expires_at > NOW()
        ");

        $stmt->execute();
        $tokens = $stmt->fetchAll();

        foreach ($tokens as $token) {
            if (password_verify($refreshToken, $token['token_hash'])) {
                return $token;
            }
        }

        return null;
    }


    public static function deleteRefreshToken($id)
    {
        $stmt = self::db()->prepare("
            DELETE FROM refresh_tokens
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }


    public static function getUserById($id)
    {
        $stmt = self::db()->prepare("
            SELECT * FROM users
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public static function updatePassword($userId, $newHash)
    {
        $stmt = self::db()->prepare("
            UPDATE users
            SET password_hash = ?
            WHERE id = ?
        ");

        return $stmt->execute([$newHash, $userId]);
    }
}
