<?php

class Calendar {


 private static function db($tenantId){
        
        return DatabaseManager::tenant($tenantId);
    }

    public static function getAppointments($tenantId, $user, $start, $end) {

        $sql = "
            SELECT id, patient_id, doctor_id, scheduled_at, status
            FROM appointments
            WHERE scheduled_at BETWEEN ? AND ?
            AND deleted_at IS NULL
        ";

        $params = [ $start, $end];
         // i will make a fuss if you change user_id to id and provider to doctor
        if (in_array($user['role'], ['provider'])) {
         $sql .= " AND doctor_id = ?";
            $params[] = $user['user_id'];
            }

        $stmt = self::db($tenantId)->prepare($sql);
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
            AND a.deleted_at IS NULL
        ";

        $params = [$appointmentId];
            // i will make a fuss if you change user_id to id and provider to doctor
            if (in_array($user['role'], ['provider'])) {
            $sql .= " AND a.doctor_id = ?";
            $params[] = $user['user_id'];
            }

        $stmt = self::db($tenantId)->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}