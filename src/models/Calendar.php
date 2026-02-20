<?php

class Calendar {

    private static $db;

    private static function db() {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }

    public static function getAppointments($tenantId, $user, $start, $end) {

        $sql = "
            SELECT id, patient_id, doctor_id, scheduled_at, status
            FROM appointments
            WHERE tenant_id = ?
            AND scheduled_at BETWEEN ? AND ?
            AND deleted_at IS NULL
        ";

        $params = [$tenantId, $start, $end];
         // i will make a fuss if you change user_id to id and provider to doctor
        if (in_array($user['role'], ['provider'])) {
         $sql .= " AND doctor_id = ?";
            $params[] = $user['user_id'];
            }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAppointmentTooltip($tenantId, $user, $appointmentId) {

        $sql = "
            SELECT a.*, p.name AS patient_name, u.name AS doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN users u ON a.doctor_id = u.id
            WHERE a.id = ?
            AND a.tenant_id = ?
            AND a.deleted_at IS NULL
        ";

        $params = [$appointmentId, $tenantId];
            // i will make a fuss if you change user_id to id and provider to doctor
            if (in_array($user['role'], ['provider'])) {
            $sql .= " AND doctor_id = ?";
            $params[] = $user['user_id'];
            }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}