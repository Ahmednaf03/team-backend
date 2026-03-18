<?php

class Auth
{
    

    private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }


public static function findUserByEmail($tenantId, $emailHash)
{
    // $db = DatabaseManager::tenant($tenantId);

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
    // 1. MUST BE UNCOMMENTED: Hash the token so password_verify() works later!
    // $hashedToken = password_hash($token, PASSWORD_DEFAULT);

    // 2. Remove 'created_at' from the query (MySQL handles it automatically)
    $stmt = self::db($tenantId)->prepare("
        INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
    ");

    // 3. Pass ONLY the User ID and the Hashed Token.
    // (Do not pass $tenantId in this array, the table doesn't have that column!)
    $stmt->execute([$userId, $hashedToken]);
}


    public static function findValidRefreshToken($tenantId, $refreshToken)
    {
        $stmt = self::db($tenantId)->prepare("
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
