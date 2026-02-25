<?php

require_once __DIR__ . '/../helpers/Encryption.php';

class Communication {

    private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }


    public static function get($tenantId, $appointmentId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT id, sender_id, message, created_at
            FROM communication_messages
            WHERE appointment_id = ?
            AND deleted_at IS NULL
            ORDER BY created_at ASC
        ");

        $stmt->execute([$appointmentId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['message'] = Encryption::decrypt($row['message']);
        }

        return $data;
    }


    public static function create($tenantId, $appointmentId, $senderId, $data) {

        $stmt = self::db($tenantId)->prepare("
            INSERT INTO communication_messages
            ( appointment_id, sender_id, message)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([
            $appointmentId,
            $senderId,
            Encryption::encrypt($data['message'])
        ]);
    }


    public static function getById($id, $tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT id, sender_id, appointment_id, message, created_at
            FROM communication_messages
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);
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
            WHERE id = ? AND deleted_at IS NULL";

    $values[] = $id;
    // $values[] = $tenantId;

    $stmt = self::db($tenantId)->prepare($sql);

    return $stmt->execute($values);
}



    public static function softDelete($tenantId, $id) {

        $stmt = self::db($tenantId)->prepare("
            UPDATE communication_messages
            SET deleted_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }

}
