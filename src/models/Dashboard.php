<?php

class Dashboard
{
    private static $db;

    private static function db()
    {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }

    public static function patientsCount($tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT COUNT(*) as total
            FROM patients
            WHERE tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$tenantId]);
        return (int) $stmt->fetchColumn();
    }

    public static function appointmentStats($tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT
                COUNT(*) as total,
                SUM(status='completed') as completed,
                SUM(status='scheduled') as scheduled,
                SUM(status='cancelled') as cancelled
            FROM appointments
            WHERE tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function prescriptionSummary($tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT COUNT(*) as total
            FROM prescriptions
            WHERE tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$tenantId]);
        return (int) $stmt->fetchColumn();
    }

    public static function tenantAnalytics()
    {
        $stmt = self::db()->query("
            SELECT
                t.id,
                t.name,
                COUNT(DISTINCT p.id) as patients,
                COUNT(DISTINCT a.id) as appointments
            FROM tenants t
            LEFT JOIN patients p ON p.tenant_id = t.id AND p.deleted_at IS NULL
            LEFT JOIN appointments a ON a.tenant_id = t.id AND a.deleted_at IS NULL
            GROUP BY t.id
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}