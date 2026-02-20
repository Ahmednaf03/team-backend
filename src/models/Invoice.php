<?php

class Invoice {

    private static $db;

    private static function db() {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }

    public static function getValidPrescription($prescriptionId, $tenantId) {

        $stmt = self::db()->prepare("
            SELECT id, patient_id, status
            FROM prescriptions
            WHERE id = ?
            AND tenant_id = ?
        ");

        $stmt->execute([$prescriptionId, $tenantId]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data || $data['status'] !== 'DISPENSED') {
            return null;
        }

        return $data;
    }

    public static function existsForPrescription($prescriptionId, $tenantId) {

        $stmt = self::db()->prepare("
            SELECT id
            FROM invoices
            WHERE prescription_id = ?
            AND tenant_id = ?
        ");

        $stmt->execute([$prescriptionId, $tenantId]);

        return $stmt->fetch() ? true : false;
    }

    public static function create($tenantId, $prescriptionId, $patientId) {

        $stmt = self::db()->prepare("
            INSERT INTO invoices
            (tenant_id, prescription_id, patient_id, total_amount, status)
            VALUES (?, ?, ?, 0, 'PENDING')
        ");

        $success = $stmt->execute([
            $tenantId,
            $prescriptionId,
            $patientId
        ]);

        if (!$success) {
            return false;
        }

        return self::db()->lastInsertId();
    }

    public static function updateTotal($invoiceId, $tenantId, $total) {

        $stmt = self::db()->prepare("
            UPDATE invoices
            SET total_amount = ?
            WHERE id = ?
            AND tenant_id = ?
        ");

        return $stmt->execute([
            $total,
            $invoiceId,
            $tenantId
        ]);
    }

    public static function getById($invoiceId, $tenantId) {

        $stmt = self::db()->prepare("
            SELECT id, prescription_id, patient_id, total_amount, status, paid_at
            FROM invoices
            WHERE id = ?
            AND tenant_id = ?
        ");

        $stmt->execute([$invoiceId, $tenantId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function markPaid($invoiceId, $tenantId) {

        $stmt = self::db()->prepare("
            UPDATE invoices
            SET status = 'PAID', paid_at = NOW()
            WHERE id = ?
            AND tenant_id = ?
            AND status = 'PENDING'
        ");

        return $stmt->execute([$invoiceId, $tenantId]);
    }

    public static function getAll($tenantId) {

        $stmt = self::db()->prepare("
            SELECT id, prescription_id, patient_id, total_amount, status, paid_at
            FROM invoices
            WHERE tenant_id = ?
            ORDER BY id DESC
        ");

        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getSummaryByTenant($tenantId) {

        $stmt = self::db()->prepare("
            SELECT
                COUNT(*) AS total_invoices,
                SUM(CASE WHEN status = 'PAID' THEN total_amount ELSE 0 END) AS total_paid,
                SUM(CASE WHEN status = 'PENDING' THEN total_amount ELSE 0 END) AS total_pending
            FROM invoices
            WHERE tenant_id = ?
        ");

        $stmt->execute([$tenantId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}