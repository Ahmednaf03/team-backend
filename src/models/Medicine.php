<?php

class Medicine
{
    private static $db;

    private static function db()
    {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }


    public static function getAll($tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT id, name, stock_quantity, price
            FROM medicines
            WHERE tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function getById($id, $tenantId)
    {
        $stmt = self::db()->prepare("
            SELECT id, name, stock_quantity, price
            FROM medicines
            WHERE id = ?
            AND tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id, $tenantId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public static function reduceStock($tenantId, $medicineId, $qty)
    {
        // Check stock first
        if (!self::checkStock($tenantId, $medicineId, $qty)) {
            return false;
        }

        $stmt = self::db()->prepare("
            UPDATE medicines
            SET stock_quantity = stock_quantity - ?
            WHERE id = ?
            AND tenant_id = ?
            AND deleted_at IS NULL
        ");

        return $stmt->execute([
            $qty,
            $medicineId,
            $tenantId
        ]);
    }


    public static function checkStock($tenantId, $medicineId, $qty)
    {
        $stmt = self::db()->prepare("
            SELECT stock_quantity
            FROM medicines
            WHERE id = ?
            AND tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$medicineId, $tenantId]);

        $stock = $stmt->fetchColumn();

        if ($stock === false) {
            return false; // medicine not found
        }

        return $stock >= $qty;
    }


    public static function softDelete($tenantId, $id)
    {
        $stmt = self::db()->prepare("
            UPDATE medicines
            SET deleted_at = NOW()
            WHERE id = ?
            AND tenant_id = ?
        ");

        return $stmt->execute([$id, $tenantId]);
    }
}