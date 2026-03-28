<?php

class Notification
{
    private static function db($tenantId)
    {
        return DatabaseManager::tenant($tenantId);
    }

    public static function getAll($tenantId, $userId, $userType){
        $stmt = self::db($tenantId)->prepare("
            SELECT id, type, title, message, is_read, reference_id, starts_at, ends_at, created_by, created_at
            FROM notifications
            WHERE user_id = ?
            AND user_type = ?
            ORDER BY created_at DESC
        ");

        $stmt->execute([$userId, $userType]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function markRead($tenantId, $id, $userId, $userType)
    {
        $stmt = self::db($tenantId)->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
            AND user_id = ?
            AND user_type = ?
        ");

        $stmt->execute([$id, $userId, $userType]);

        return $stmt->rowCount();
    }

    public static function markAllRead($tenantId, $userId, $userType)
    {
        $stmt = self::db($tenantId)->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
            AND user_type = ?
            AND is_read = 0
        ");

        $stmt->execute([$userId, $userType]);

        return $stmt->rowCount();
    }

    public static function clearAll($tenantId, $userId, $userType)
    {
        $stmt = self::db($tenantId)->prepare("
            DELETE FROM notifications
            WHERE user_id = ?
            AND user_type = ?
        ");

        $stmt->execute([$userId, $userType]);

        return $stmt->rowCount();
    }

    public static function create($tenantId, $data)
    {
        $db = self::db($tenantId);

        $stmt = $db->prepare("
            INSERT INTO notifications
            (user_id, user_type, type, title, message, reference_id, starts_at, ends_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['user_id'],
            $data['user_type'],
            $data['type'],
            $data['title'],
            $data['message'],
            $data['reference_id'] ?? null,
            $data['starts_at'] ?? null,
            $data['ends_at'] ?? null,
            $data['created_by'] ?? null
        ]);

        return $db->lastInsertId();
    }

    public static function getBroadcastRecipients($tenantId, $audience = 'staff')
    {
        if ($audience !== 'staff') {
            return [];
        }

        $stmt = self::db($tenantId)->prepare("
            SELECT id
            FROM users
            WHERE role IN ('admin', 'provider', 'nurse', 'pharmacist', 'receptionist')
            AND status = 'active'
            AND deleted_at IS NULL
        ");

        $stmt->execute();

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function broadcast($tenantId, $createdBy, array $data)
    {
        $recipientIds = self::getBroadcastRecipients($tenantId, $data['audience']);

        if (empty($recipientIds)) {
            return 0;
        }

        $db = self::db($tenantId);
        $stmt = $db->prepare("
            INSERT INTO notifications
            (user_id, user_type, type, title, message, reference_id, starts_at, ends_at, created_by)
            VALUES (?, 'staff', ?, ?, ?, ?, ?, ?, ?)
        ");

        $db->beginTransaction();

        try {
            foreach ($recipientIds as $recipientId) {
                $stmt->execute([
                    $recipientId,
                    $data['type'],
                    $data['title'],
                    $data['message'],
                    $data['reference_id'] ?? null,
                    $data['starts_at'] ?? null,
                    $data['ends_at'] ?? null,
                    $createdBy
                ]);
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }

        return count($recipientIds);
    }
}
