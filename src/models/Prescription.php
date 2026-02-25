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

        return self::db()->lastInsertId();
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


    public static function getAll($tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT id, patient_id, doctor_id, appointment_id, status, prescription_date
            FROM prescriptions
            WHERE deleted_at IS NULL 
            ORDER BY prescription_date DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}