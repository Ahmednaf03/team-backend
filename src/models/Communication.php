<?php

require_once __DIR__ . '/../helpers/Encryption.php';

class Communication {

    private static $db;

    private static function db() {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }


    public static function get($tenantId, $appointmentId) {

        $stmt = self::db()->prepare("
            SELECT id, sender_id, message, created_at
            FROM communication_messages
            WHERE tenant_id = ?
            AND appointment_id = ?
            AND deleted_at IS NULL
            ORDER BY created_at ASC
        ");

        $stmt->execute([$tenantId, $appointmentId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['message'] = Encryption::decrypt($row['message']);
        }

        return $data;
    }


    public static function create($tenantId, $appointmentId, $senderId, $data) {

        $stmt = self::db()->prepare("
            INSERT INTO communication_messages
            (tenant_id, appointment_id, sender_id, message)
            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([
            $tenantId,
            $appointmentId,
            $senderId,
            Encryption::encrypt($data['message'])
        ]);
    }


    public static function getById($id, $tenantId) {

        $stmt = self::db()->prepare("
            SELECT id, sender_id, appointment_id, message, created_at
            FROM communication_messages
            WHERE id = ?
            AND tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $row['message'] = Encryption::decrypt($row['message']);

        return $row;
    }


    public static function update($tenantId, $id, $data) {

    $allowed = ['message'];

    $fields = [];
    $values = [];

    foreach ($allowed as $column) {
        if (isset($data[$column])) {

            $fields[] = "$column = ?";

            $values[] = Encryption::encrypt($data[$column]);
        }
    }

    if (empty($fields)) {
        return false;
    }

    $sql = "UPDATE communication_messages SET " . implode(', ', $fields) . "
            WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL";

    $values[] = $id;
    $values[] = $tenantId;

    $stmt = self::db()->prepare($sql);

    return $stmt->execute($values);
}



    public static function softDelete($tenantId, $id) {

        $stmt = self::db()->prepare("
            UPDATE communication_messages
            SET deleted_at = NOW()
            WHERE id = ?
            AND tenant_id = ?
        ");

        return $stmt->execute([$id, $tenantId]);
    }

}
