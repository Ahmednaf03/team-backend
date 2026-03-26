<?php

class Prescription
{
   private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }



    public static function create($tenantId, $data, $createdBy = null)
    {
        $stmt = self::db($tenantId)->prepare("
            INSERT INTO prescriptions
            ( patient_id, doctor_id, appointment_id, notes, status, prescription_date)
            VALUES ( ?, ?, ?, ?, 'PENDING', NOW())
        ");

        $stmt->execute([
            $data['patient_id'],
            $data['doctor_id'],
            $data['appointment_id'],
            Encryption::encrypt($data['notes'])
        ]);

        return self::db($tenantId)->lastInsertId();
    }


    public static function getById($id, $tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT *
            FROM prescriptions
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $data['notes'] = Encryption::decrypt($data['notes']);

        return $data;
    }


    public static function updateStatus($tenantId, $id, $status, $userId = null)
    {
        if ($status === 'DISPENSED') {
            $stmt = self::db($tenantId)->prepare("
                UPDATE prescriptions
                SET status = ?,
                    dispensed_by = ?,
                    dispensed_at = NOW()
                WHERE id = ?
                AND deleted_at IS NULL
            ");

            return $stmt->execute([
                $status,
                $userId,
                $id,
            ]);
        } else {
            $stmt = self::db($tenantId)->prepare("
                UPDATE prescriptions
                SET status = ?
                WHERE id = ?
                AND deleted_at IS NULL
            ");

            return $stmt->execute([
                $status,
                $id,
            ]);
        }
    }


    public static function softDelete($tenantId, $id)
    {
        $stmt = self::db($tenantId)->prepare("
            UPDATE prescriptions
            SET deleted_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }


    public static function getAll($tenantId, array $params = [])
    {
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 10;
        $filters = $params['filters'] ?? [];
        $search = $params['search'] ?? '';

        $where = [
            'deleted_at IS NULL'
        ];
        $bindings = [];

        $filterMap = [
            'status' => 'status',
        ];

        foreach ($filterMap as $filterKey => $column) {
            if (!isset($filters[$filterKey]) || $filters[$filterKey] === '') {
                continue;
            }

            $placeholder = ':filter_' . $filterKey;
            $where[] = "{$column} = {$placeholder}";
            $bindings[$placeholder] = $filters[$filterKey];
        }

        if ($search !== '') {
            $where[] = "(
                CAST(id AS CHAR) LIKE :search
                OR CAST(patient_id AS CHAR) LIKE :search
                OR CAST(doctor_id AS CHAR) LIKE :search
                OR CAST(appointment_id AS CHAR) LIKE :search
                OR status LIKE :search
            )";
            $bindings[':search'] = '%' . $search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = self::db($tenantId)->prepare("
            SELECT COUNT(*)
            FROM prescriptions
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
            SELECT id, patient_id, doctor_id, appointment_id, status, prescription_date
            FROM prescriptions
            WHERE {$whereSql}
            ORDER BY prescription_date DESC, id DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => $pagination,
        ];
    }
}
