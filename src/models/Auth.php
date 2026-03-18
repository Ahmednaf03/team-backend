<?php

class Auth
{
    private static function db($tenantId)
    {
        return DatabaseManager::tenant($tenantId);
    }

    public static function findUserByEmail($tenantId, $emailHash)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT id, password_hash, role
            FROM users
            WHERE email_hash = ?
            LIMIT 1
        ");

        $stmt->execute([$emailHash]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function createRefreshToken($tenantId, $userId, $token)
    {
        // 1. HOUSEKEEPING: Delete any already-expired tokens for this user
        // This stops the database from bloating with hundreds of dead tokens!
        $cleanupStmt = self::db($tenantId)->prepare("
            DELETE FROM refresh_tokens 
            WHERE user_id = ? AND expires_at < NOW()
        ");
        $cleanupStmt->execute([$userId]);

        // 2. Hash the new token with lightning-fast SHA-256
        $hashedToken = hash('sha256', $token);

        // 3. INSERT the new token (so they can use their phone AND laptop!)
        $stmt = self::db($tenantId)->prepare("
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
        ");

        $stmt->execute([$userId, $hashedToken]);
    }

   public static function findValidRefreshToken($tenantId, $refreshToken)
    {
        // 1. Hash the incoming cookie exactly the same way (SHA-256)
        $hashedToken = hash('sha256', $refreshToken);

        // 2. Search the database DIRECTLY for the hash. No more slow loops!
        $stmt = self::db($tenantId)->prepare("
            SELECT * FROM refresh_tokens
            WHERE token_hash = ? AND expires_at > NOW()
            LIMIT 1
        ");

        $stmt->execute([$hashedToken]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function deleteRefreshToken($tenantId, $id)
    {
        $stmt = self::db($tenantId)->prepare("
            DELETE FROM refresh_tokens
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }

    public static function getUserById($tenantId, $id)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT * FROM users
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function updatePassword($tenantId, $userId, $newHash)
    {
        $stmt = self::db($tenantId)->prepare("
            UPDATE users
            SET password_hash = ?
            WHERE id = ?
        ");

        return $stmt->execute([$newHash, $userId]);
    }

    public static function findSuperAdminByEmail($email)
    {
        $db = DatabaseManager::master();

        $stmt = $db->prepare("
            SELECT id, email, password_hash
            FROM super_admins
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}