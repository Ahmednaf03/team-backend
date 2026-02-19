<?php
require_once __DIR__ . '/../helpers/Encryption.php';
    class Patient  {
     private static $db;

    private static function db() {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }
     
 
    //  $db = Database::connect();

    public static function getAll($tenantId) {

        $stmt = self::db()->prepare("
            SELECT id, name, age, gender, phone, address, diagnosis
            FROM patients
            WHERE tenant_id = ?
            AND status = 'active'
            AND deleted_at IS NULL
        ");

        $stmt->execute([$tenantId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['name'] = Encryption::decrypt($row['name']);
            $row['age'] = (int) Encryption::decrypt($row['age']);
            $row['gender'] = Encryption::decrypt($row['gender']);
            $row['phone'] = Encryption::decrypt($row['phone']);
            $row['address'] = Encryption::decrypt($row['address']);
            $row['diagnosis'] = Encryption::decrypt($row['diagnosis']);
        }

        return $data;
    }

    public static function create($tenantId, $data) {

        // $db = Database::connect();

        $stmt = self::db()->prepare("
            INSERT INTO patients
            (tenant_id, name, age, gender, phone, address, diagnosis)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $tenantId,
            Encryption::encrypt($data['name']),
            Encryption::encrypt((string)$data['age']),
            Encryption::encrypt($data['gender']),
            Encryption::encrypt($data['phone']),
            Encryption::encrypt($data['address']),
            Encryption::encrypt($data['diagnosis'] ?? '')
        ]);
    }

public static function getById($id, $tenantId) {

    $stmt = self::db()->prepare("
        SELECT id, name, age, gender, phone, address, diagnosis
        FROM patients
        WHERE id = ?
        AND tenant_id = ?
        AND status = 'active'
        AND deleted_at IS NULL
    ");

    $stmt->execute([$id, $tenantId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) return null;

    $row['name'] = Encryption::decrypt($row['name']);
    $row['age'] = (int) Encryption::decrypt($row['age']);
    $row['gender'] = Encryption::decrypt($row['gender']);
    $row['phone'] = Encryption::decrypt($row['phone']);
    $row['address'] = Encryption::decrypt($row['address']);
    $row['diagnosis'] = Encryption::decrypt($row['diagnosis']);

    return $row;
}


public static function update($tenantId, $id, $data) {

    $allowed = ['name', 'age', 'gender', 'phone', 'address', 'diagnosis'];

    $fields = [];
    $values = [];

    foreach ($allowed as $column) {
        if (isset($data[$column])) {

            $fields[] = "$column = ?";

            if ($column === 'age') {
                $values[] = Encryption::encrypt((string)$data[$column]);
            } else {
                $values[] = Encryption::encrypt($data[$column]);
            }
        }
    }

    if (empty($fields)) {
        return false; // nothing to update
    }

    // $fields[] = "updated_at = NOW()";

    $sql = "UPDATE patients SET " . implode(', ', $fields) . "
            WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL";

    $values[] = $id;
    $values[] = $tenantId;

    $stmt = self::db()->prepare($sql);

    return $stmt->execute($values);
}


    public static function forceDelete($tenantId, $id) {

    // $db = Database::connect();

    $sql = "DELETE FROM patients
            WHERE id = ? AND tenant_id = ?";

    $stmt = self::db()->prepare($sql);

    return $stmt->execute([$id, $tenantId]);
}

}


 