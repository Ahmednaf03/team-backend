<?php
require_once __DIR__ . '/../helpers/Encryption.php';
    class Patient  {
   private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }

     
 
    //  $db = Database::connect();

    public static function getAll($tenantId, array $params = []) {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 10;
        $search = $params['search'] ?? '';

        $where = [
            "deleted_at IS NULL",
            "status = :status"
        ];
        $bindings = [];
        $bindings[':status'] = 'active';

        if ($search !== '') {
            $where[] = "CAST(id AS CHAR) LIKE :search";
            $bindings[':search'] = '%' . $search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = self::db($tenantId)->prepare("
            SELECT COUNT(*)
            FROM patients
            WHERE {$whereSql}
        ");

        foreach ($bindings as $key => $value) {
            $countStmt->bindValue($key, $value);
        }

        $countStmt->execute();
        $totalRecords = (int) $countStmt->fetchColumn();
        $pagination = PaginationHelper::buildMeta($totalRecords, $page, $perPage);
        $offset = ($pagination['currentPage'] - 1) * $perPage;

        $stmt = self::db($tenantId)->prepare("
            SELECT id, name, age, gender, phone, address, diagnosis, status, created_at
            FROM patients
            WHERE {$whereSql}
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['name'] = Encryption::decrypt($row['name']);
            $row['age'] = (int) Encryption::decrypt($row['age']);
            $row['gender'] = Encryption::decrypt($row['gender']);
            $row['phone'] = Encryption::decrypt($row['phone']);
            $row['address'] = Encryption::decrypt($row['address']);
            $row['diagnosis'] = Encryption::decrypt($row['diagnosis']);
        }

        return [
            'data' => $data,
            'pagination' => $pagination,
        ];
    }

    public static function create($tenantId, $data) {

        // $db = Database::connect();

        $stmt = self::db($tenantId)->prepare("
            INSERT INTO patients
            (name, age, gender, phone, address, diagnosis)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

         $stmt->execute([
            Encryption::encrypt($data['name']),
            Encryption::encrypt((string)$data['age']),
            Encryption::encrypt($data['gender']),
            Encryption::encrypt($data['phone']),
            Encryption::encrypt($data['address']),
            Encryption::encrypt($data['diagnosis'] ?? '')
        ]);

        return self::db($tenantId)->lastInsertId();
    }

public static function getById($id, $tenantId) {

    $stmt = self::db($tenantId)->prepare("
        SELECT id, name, age, gender, phone, address, diagnosis
        FROM patients
        WHERE id = ?
        AND status = 'active'
        AND deleted_at IS NULL
    ");

    $stmt->execute([$id]);
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
            WHERE id = ? AND deleted_at IS NULL";

    $values[] = $id;
    // $values[] = $tenantId;

    $stmt = self::db($tenantId)->prepare($sql);

     $stmt->execute($values);

    return $stmt->rowCount();
}


    public static function forceDelete($tenantId, $id) {

    // $db = Database::connect();

    $sql = "DELETE FROM patients
            WHERE id = ? ";

    $stmt = self::db($tenantId)->prepare($sql);

    return $stmt->execute([$id]);
}

    public static function softDelete($tenantId, $id) {

        $stmt = self::db($tenantId)->prepare("
            UPDATE patients
            SET deleted_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
    }
 
