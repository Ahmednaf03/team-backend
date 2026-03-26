<?php

class Appointment {

   private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }

    public static function getAll($tenantId, array $params = []) {
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
            'patient_id' => 'patient_id',
            'doctor_id' => 'doctor_id',
        ];

        foreach ($filterMap as $filterKey => $column) {
            if (!isset($filters[$filterKey]) || $filters[$filterKey] === '') {
                continue;
            }

            $placeholder = ':filter_' . $filterKey;
            $where[] = "{$column} = {$placeholder}";
            $bindings[$placeholder] = $filters[$filterKey];
        }

        if (!empty($filters['scheduled_from'])) {
            $where[] = 'scheduled_at >= :scheduled_from';
            $bindings[':scheduled_from'] = $filters['scheduled_from'];
        }

        if (!empty($filters['scheduled_to'])) {
            $where[] = 'scheduled_at <= :scheduled_to';
            $bindings[':scheduled_to'] = $filters['scheduled_to'];
        }

        if ($search !== '') {
            $where[] = "(
                CAST(id AS CHAR) LIKE :search
                OR CAST(patient_id AS CHAR) LIKE :search
                OR CAST(doctor_id AS CHAR) LIKE :search
                OR status LIKE :search
                OR CAST(scheduled_at AS CHAR) LIKE :search
            )";
            $bindings[':search'] = '%' . $search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = self::db($tenantId)->prepare("
            SELECT COUNT(*)
            FROM appointments
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
            SELECT id, patient_id, doctor_id, scheduled_at, status, notes
            FROM appointments
            WHERE {$whereSql}
            ORDER BY scheduled_at ASC, id ASC
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

    public static function getById($id, $tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT id, patient_id, doctor_id, scheduled_at, status, notes
            FROM appointments
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($tenantId, $data) {

        if (self::hasConflict($tenantId, $data['doctor_id'], $data['scheduled_at'])) {
            return false;
        }

        $stmt = self::db($tenantId)->prepare("
            INSERT INTO appointments
            (patient_id, doctor_id, scheduled_at, status, notes)
            VALUES (?, ?, ?, 'scheduled', ?)
        ");

        $success = $stmt->execute([
            $data['patient_id'],
            $data['doctor_id'],
            $data['scheduled_at'],
            $data['notes'] ?? null
        ]);

        if (!$success) {
            return false;
        }

        return self::db($tenantId)->lastInsertId();
    }

    public static function update($tenantId, $id, $data) {

        $allowed = ['scheduled_at', 'status', 'notes'];

        $fields = [];
        $values = [];

        foreach ($allowed as $column) {
            if (isset($data[$column])) {

                if ($column === 'scheduled_at') {
                    if (self::hasConflict($tenantId, $data['doctor_id'], $data['scheduled_at'], $id)) {
                        return false;
                    }
                }

                $fields[] = "$column = ?";
                $values[] = $data[$column];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE appointments SET " . implode(', ', $fields) . "
                WHERE id = ? AND deleted_at IS NULL";

        $values[] = $id;
        // $values[] = $tenantId;

        $stmt = self::db($tenantId)->prepare($sql);

        return $stmt->execute($values);
    }

    public static function updateNotes($tenantId, $id, $notes) {

        $stmt = self::db($tenantId)->prepare("
            UPDATE appointments
            SET notes = ?
            WHERE id = ? AND deleted_at IS NULL
        ");

        return $stmt->execute([$notes, $id]);
    }
    public static function cancel($tenantId, $id) {

        $stmt = self::db($tenantId)->prepare("
            UPDATE appointments
            SET status = 'cancelled'
            WHERE id = ? AND deleted_at IS NULL
        ");

        return $stmt->execute([$id]);
    }

    public static function softDelete($tenantId, $id) {

        $stmt = self::db($tenantId)->prepare("
            UPDATE appointments
            SET deleted_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }

    public static function getUpcoming($tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT id, patient_id, doctor_id, scheduled_at, status, notes
            FROM appointments
            WHERE scheduled_at >= NOW()
            AND status = 'scheduled'
            AND deleted_at IS NULL
            ORDER BY scheduled_at ASC
        ");

        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function hasConflict($tenantId, $doctorId, $scheduledAt, $excludeId = null) {

        $sql = "
            SELECT COUNT(*)
            FROM appointments
            WHERE doctor_id = ?
            AND scheduled_at = ?
            AND status != 'cancelled'
            AND deleted_at IS NULL
        ";

        $params = [$doctorId, $scheduledAt];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = self::db($tenantId)->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }
}
