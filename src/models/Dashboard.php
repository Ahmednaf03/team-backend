<?php

class Dashboard
{
      private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }


    public static function patientsCount($tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT COUNT(*) as total
            FROM patients
            WHERE deleted_at IS NULL
        ");

        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public static function appointmentStats($tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT
                COUNT(*) as total,
                SUM(status='completed') as completed,
                SUM(status='scheduled') as scheduled,
                SUM(status='cancelled') as cancelled
            FROM appointments
            WHERE deleted_at IS NULL
        ");

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function prescriptionSummary($tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT COUNT(*) as total
            FROM prescriptions
            WHERE deleted_at IS NULL
        ");

        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public static function tenantAnalytics($tenantId)
    {
        $stmt = self::db($tenantId)->query("
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