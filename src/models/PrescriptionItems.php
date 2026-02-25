<?php

class PrescriptionItems
{
   private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }



    public static function add($tenantId, $data)
    {
        // Validate prescription belongs to tenant
        $prescription = Prescription::getById(
            $data['prescription_id'],
            $tenantId
        );

        if (!$prescription) {
            return false;
        }

        // Validate medicine belongs to tenant
        if (!self::validateMedicine($tenantId, $data['medicine_id'])) {
            return false;
        }

        // Check existing item
        $check = self::db($tenantId)->prepare("
            SELECT id, quantity
            FROM prescription_items
            WHERE prescription_id = ?
            AND medicine_id = ?
            AND deleted_at IS NULL
        ");

        $check->execute([
            $data['prescription_id'],
            $data['medicine_id']
        ]);

        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $update = self::db()->prepare("
                UPDATE prescription_items
                SET quantity = quantity + ?
                WHERE id = ?
            ");

            return $update->execute([
                $data['quantity'],
                $existing['id']
            ]);
        }

        $stmt = self::db()->prepare("
            INSERT INTO prescription_items
            ( prescription_id, medicine_id,
             dosage, frequency, duration_days, quantity, instructions)
            VALUES ( ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['prescription_id'],
            $data['medicine_id'],
            Encryption::encrypt($data['dosage']),
            $data['frequency'],
            $data['duration_days'],
            $data['quantity'],
            Encryption::encrypt($data['instructions'] ?? '')
        ]);
    }


    public static function getByPrescription($tenantId, $prescriptionId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT pi.*, m.name AS medicine
            FROM prescription_items pi
            JOIN medicines m
                ON m.id = pi.medicine_id
                AND m.deleted_at IS NULL
            WHERE pi.prescription_id = ?
            AND pi.deleted_at IS NULL
        ");

        $stmt->execute([
            $prescriptionId
        ]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $item['dosage'] = Encryption::decrypt($item['dosage']);
            $item['instructions'] = Encryption::decrypt($item['instructions']);
        }

        return $items;
    }


    public static function validateMedicine($tenantId, $medicineId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT id
            FROM medicines
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$medicineId]);

        return (bool) $stmt->fetch();
    }


    public static function softDelete($tenantId, $id)
    {
        $stmt = self::db($tenantId)->prepare("
            UPDATE prescription_items
            SET deleted_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
}