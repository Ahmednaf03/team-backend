<?php

class Medicine
{
   private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }



    public static function getAll($tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT id, name, stock_quantity, price
            FROM medicines
            WHERE deleted_at IS NULL
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public static function getById($id, $tenantId)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT id, name, stock_quantity, price
            FROM medicines
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public static function reduceStock($tenantId, $medicineId, $qty)
    {
        // Check stock first
        if (!self::checkStock($tenantId, $medicineId, $qty)) {
            return false;
        }

        $stmt = self::db($tenantId)->prepare("
            UPDATE medicines
            SET stock_quantity = stock_quantity - ?
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        return $stmt->execute([
            $qty,
            $medicineId,
        ]);
    }


    public static function checkStock($tenantId, $medicineId, $qty)
    {
        $stmt = self::db($tenantId)->prepare("
            SELECT stock_quantity
            FROM medicines
            WHERE id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$medicineId]);

        $stock = $stmt->fetchColumn();

        if ($stock === false) {
            return false; // medicine not found
        }

        return $stock >= $qty;
    }


    public static function softDelete($tenantId, $id)
    {
        $stmt = self::db($tenantId)->prepare("
            UPDATE medicines
            SET deleted_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }
}