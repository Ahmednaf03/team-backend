<?php

class Invoice {

 private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }

    public static function getValidPrescription($prescriptionId, $tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT id, patient_id, status
            FROM prescriptions
            WHERE id = ?
        ");

        $stmt->execute([$prescriptionId]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data || $data['status'] !== 'DISPENSED') {
            return null;
        }

        return $data;
    }

    public static function existsForPrescription($prescriptionId, $tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT id
            FROM invoices
            WHERE prescription_id = ?
        ");

        $stmt->execute([$prescriptionId]);

        return $stmt->fetch() ? true : false;
    }

public static function create($tenantId, $prescriptionId, $patientId){
    $db = self::db($tenantId);

    $stmt = $db->prepare("
        INSERT INTO invoices
        (prescription_id, patient_id, total_amount, status)
        VALUES (?, ?, 0, 'PENDING')
    ");

    $success = $stmt->execute([$prescriptionId, $patientId]);

    if (!$success) return false;

    return $db->lastInsertId();
}

    public static function updateTotal($invoiceId, $tenantId, $total) {

        $stmt = self::db($tenantId)->prepare("
            UPDATE invoices
            SET total_amount = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $total,
            $invoiceId,
        ]);
    }

    public static function getById($invoiceId, $tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT id, prescription_id, patient_id, total_amount, status, paid_at
            FROM invoices
            WHERE id = ?
        ");

        $stmt->execute([$invoiceId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function markPaid($invoiceId, $tenantId) {

        $stmt = self::db($tenantId)->prepare("
            UPDATE invoices
            SET status = 'PAID', paid_at = NOW()
            WHERE id = ?
            AND status = 'PENDING'
        ");

        return $stmt->execute([$invoiceId]);
    }

    public static function getAll($tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT id, prescription_id, patient_id, total_amount, status, paid_at
            FROM invoices
            ORDER BY id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getSummaryByTenant($tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT
                COUNT(*) AS total_invoices,
                SUM(CASE WHEN status = 'PAID' THEN total_amount ELSE 0 END) AS total_paid,
                SUM(CASE WHEN status = 'PENDING' THEN total_amount ELSE 0 END) AS total_pending
            FROM invoices
        ");

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}