<?php

class Prescription
{
    private static $db;

    private static function db()
    {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }


    public static function create($tenantId, $data, $createdBy = null)
    {
        $stmt = self::db()->prepare("
            INSERT INTO prescriptions
            (tenant_id, patient_id, doctor_id, appointment_id, notes, status, prescription_date)
            VALUES (?, ?, ?, ?, ?, 'PENDING', NOW())
        ");

        $stmt->execute([
            $tenantId,
            $data['patient_id'],
            $data['doctor_id'],
            $data['appointment_id'],
            Encryption::encrypt($data['notes'])
        ]);

        return self::db()->lastInsertId();
    }


    public static function getById($id, $tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT *
            FROM prescriptions
            WHERE id = ?
            AND tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id, $tenantId]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $data['notes'] = Encryption::decrypt($data['notes']);

        return $data;
    }


    public static function updateStatus($tenantId, $id, $status, $userId = null)
    {
        $stmt = self::db()->prepare("
            UPDATE prescriptions
            SET status = ?,
                dispensed_by = ?,
                dispensed_at = NOW()
            WHERE id = ?
            AND tenant_id = ?
            AND deleted_at IS NULL
        ");

        return $stmt->execute([
            $status,
            $userId,
            $id,
            $tenantId
        ]);
    }


    public static function softDelete($tenantId, $id)
    {
        $stmt = self::db()->prepare("
            UPDATE prescriptions
            SET deleted_at = NOW()
            WHERE id = ?
            AND tenant_id = ?
        ");

        return $stmt->execute([$id, $tenantId]);
    }


    public static function getAll($tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT id, patient_id, doctor_id, appointment_id, status, prescription_date
            FROM prescriptions
            WHERE tenant_id = ?
            AND deleted_at IS NULL
            ORDER BY prescription_date DESC
        ");

        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}