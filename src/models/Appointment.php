<?php

class Appointment {

    private static $db;

    private static function db() {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }

    public static function getAll($tenantId) {

        $stmt = self::db()->prepare("
            SELECT id, patient_id, doctor_id, scheduled_at, status, notes
            FROM appointments
            WHERE tenant_id = ?
            AND deleted_at IS NULL
            ORDER BY scheduled_at ASC
        ");

        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($id, $tenantId) {

        $stmt = self::db()->prepare("
            SELECT id, patient_id, doctor_id, scheduled_at, status, notes
            FROM appointments
            WHERE id = ?
            AND tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id, $tenantId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($tenantId, $data) {

        if (self::hasConflict($tenantId, $data['doctor_id'], $data['scheduled_at'])) {
            return false;
        }

        $stmt = self::db()->prepare("
            INSERT INTO appointments
            (tenant_id, patient_id, doctor_id, scheduled_at, status, notes)
            VALUES (?, ?, ?, ?, 'scheduled', ?)
        ");

        return $stmt->execute([
            $tenantId,
            $data['patient_id'],
            $data['doctor_id'],
            $data['scheduled_at'],
            $data['notes'] ?? null
        ]);
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
                WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL";

        $values[] = $id;
        $values[] = $tenantId;

        $stmt = self::db()->prepare($sql);

        return $stmt->execute($values);
    }

    public static function updateNotes($tenantId, $id, $notes) {

        $stmt = self::db()->prepare("
            UPDATE appointments
            SET notes = ?
            WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL
        ");

        return $stmt->execute([$notes, $id, $tenantId]);
    }
    public static function cancel($tenantId, $id) {

        $stmt = self::db()->prepare("
            UPDATE appointments
            SET status = 'cancelled'
            WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL
        ");

        return $stmt->execute([$id, $tenantId]);
    }

    public static function softDelete($tenantId, $id) {

        $stmt = self::db()->prepare("
            UPDATE appointments
            SET deleted_at = NOW()
            WHERE id = ? AND tenant_id = ?
        ");

        return $stmt->execute([$id, $tenantId]);
    }

    public static function getUpcoming($tenantId) {

        $stmt = self::db()->prepare("
            SELECT id, patient_id, doctor_id, scheduled_at, status, notes
            FROM appointments
            WHERE tenant_id = ?
            AND scheduled_at >= NOW()
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
            WHERE tenant_id = ?
            AND doctor_id = ?
            AND scheduled_at = ?
            AND status != 'cancelled'
            AND deleted_at IS NULL
        ";

        $params = [$tenantId, $doctorId, $scheduledAt];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }
}
