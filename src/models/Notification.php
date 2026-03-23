<?php

class Notification
{
    private static function db($tenantId = null)
    {
        // If tenantId not provided, get from request context
        if ($tenantId === null) {
            $tenantId = $_REQUEST['tenant_id'] ?? 1;
        }
        return DatabaseManager::tenant($tenantId);
    }

    public static function getAll($userId, $userType)
    {
        $stmt = self::db()->prepare("
            SELECT id, type, title, message, is_read, reference_id, created_at
            FROM notifications
            WHERE user_id = ?
            AND user_type = ?
            ORDER BY created_at DESC
        ");

        $stmt->execute([$userId, $userType]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function markRead($id, $userId, $userType)
    {
        $stmt = self::db()->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
            AND user_id = ?
            AND user_type = ?
        ");

        $stmt->execute([$id, $userId, $userType]);

        return $stmt->rowCount();
    }

    public static function markAllRead($userId, $userType)
    {
        $stmt = self::db()->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
            AND user_type = ?
            AND is_read = 0
        ");

        $stmt->execute([$userId, $userType]);

        return $stmt->rowCount();
    }

    public static function clearAll($userId, $userType)
    {
        $stmt = self::db()->prepare("
            DELETE FROM notifications
            WHERE user_id = ?
            AND user_type = ?
        ");

        $stmt->execute([$userId, $userType]);

        return $stmt->rowCount();
    }

    public static function create($data)
    {
        $stmt = self::db()->prepare("
            INSERT INTO notifications
            (user_id, user_type, type, title, message, reference_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['user_id'],
            $data['user_type'],
            $data['type'],
            $data['title'],
            $data['message'],
            $data['reference_id'] ?? null
        ]);

        return self::db()->lastInsertId();
    }
}
