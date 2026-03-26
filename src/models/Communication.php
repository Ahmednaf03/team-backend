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


    public static function getSummaries($tenantId, array $appointmentIds = []) {

        $sql = "
            SELECT
                cm.appointment_id,
                cm.id AS latest_message_id,
                cm.message AS latest_message,
                cm.created_at AS latest_message_at,
                cm.sender_id
            FROM communication_messages cm
            WHERE cm.deleted_at IS NULL
              AND cm.id = (
                  SELECT inner_cm.id
                  FROM communication_messages inner_cm
                  WHERE inner_cm.appointment_id = cm.appointment_id
                    AND inner_cm.deleted_at IS NULL
                  ORDER BY inner_cm.created_at DESC, inner_cm.id DESC
                  LIMIT 1
              )
        ";

        $params = [];

        if (!empty($appointmentIds)) {
            $placeholders = implode(', ', array_fill(0, count($appointmentIds), '?'));
            $sql .= " AND cm.appointment_id IN ($placeholders)";
            $params = array_values($appointmentIds);
        }

        $sql .= " ORDER BY cm.created_at DESC, cm.id DESC";

        $stmt = self::db($tenantId)->prepare($sql);
        $stmt->execute($params);

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['latest_message'] = Encryption::decrypt($row['latest_message']);
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
