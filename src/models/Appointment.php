<?php

require_once __DIR__ . '/../helpers/Encryption.php';

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
            'a.deleted_at IS NULL'
        ];
        $bindings = [];

        $filterMap = [
            'status' => 'a.status',
            'patient_id' => 'a.patient_id',
            'doctor_id' => 'a.doctor_id',
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
            $where[] = 'a.scheduled_at >= :scheduled_from';
            $bindings[':scheduled_from'] = $filters['scheduled_from'];
        }

        if (!empty($filters['scheduled_to'])) {
            $where[] = 'a.scheduled_at <= :scheduled_to';
            $bindings[':scheduled_to'] = $filters['scheduled_to'];
        }

        if ($search !== '') {
            $where[] = "(
                CAST(a.id AS CHAR) LIKE :search
                OR CAST(a.patient_id AS CHAR) LIKE :search
                OR CAST(a.doctor_id AS CHAR) LIKE :search
                OR a.status LIKE :search
                OR CAST(a.scheduled_at AS CHAR) LIKE :search
            )";
            $bindings[':search'] = '%' . $search . '%';
        }

        $whereSql = implode(' AND ', $where);
        $fromSql = "
            FROM appointments a
            LEFT JOIN users u
                ON u.id = a.doctor_id
               AND u.deleted_at IS NULL
        ";

        $countStmt = self::db($tenantId)->prepare("
            SELECT COUNT(*)
            {$fromSql}
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
            SELECT
                a.id,
                a.patient_id,
                a.doctor_id,
                a.scheduled_at,
                a.status,
                a.notes,
                u.name AS doctor_name
            {$fromSql}
            WHERE {$whereSql}
            ORDER BY a.scheduled_at ASC, a.id ASC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => self::normalizeCollection($stmt->fetchAll(PDO::FETCH_ASSOC)),
            'pagination' => $pagination,
        ];
    }

    public static function getById($id, $tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT
                a.id,
                a.patient_id,
                a.doctor_id,
                a.scheduled_at,
                a.status,
                a.notes,
                u.name AS doctor_name
            FROM appointments a
            LEFT JOIN users u
                ON u.id = a.doctor_id
               AND u.deleted_at IS NULL
            WHERE a.id = ?
            AND a.deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        return self::normalizeRow($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
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
                    $appointment = self::getById($id, $tenantId);

                    if (!$appointment) {
                        return false;
                    }

                    $doctorId = $data['doctor_id'] ?? $appointment['doctor_id'];

                    if (self::hasConflict($tenantId, $doctorId, $data['scheduled_at'], $id)) {
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

    public static function getUpcoming($tenantId, $patientId = null) {

        $params = [];
        $where = [
            'a.scheduled_at >= NOW()',
            "a.status = 'scheduled'",
            'a.deleted_at IS NULL'
        ];

        if ($patientId !== null) {
            $where[] = 'a.patient_id = ?';
            $params[] = $patientId;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = self::db($tenantId)->prepare("
            SELECT
                a.id,
                a.patient_id,
                a.doctor_id,
                a.scheduled_at,
                a.status,
                a.notes,
                u.name AS doctor_name
            FROM appointments a
            LEFT JOIN users u
                ON u.id = a.doctor_id
               AND u.deleted_at IS NULL
            WHERE {$whereSql}
            ORDER BY a.scheduled_at ASC
        ");

        $stmt->execute($params);

        return self::normalizeCollection($stmt->fetchAll(PDO::FETCH_ASSOC));
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

    private static function normalizeCollection(array $appointments) {

        foreach ($appointments as &$appointment) {
            $appointment = self::normalizeRow($appointment);
        }

        return $appointments;
    }

    private static function normalizeRow($appointment) {

        if (!$appointment) {
            return null;
        }

        if (!empty($appointment['doctor_name'])) {
            $appointment['doctor_name'] = Encryption::decrypt($appointment['doctor_name']);
        } else {
            $appointment['doctor_name'] = null;
        }

        return $appointment;
    }
}
