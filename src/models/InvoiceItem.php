<?php

class InvoiceItem {

 private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }

    public static function hasItems($prescriptionId, $tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT COUNT(*)
            FROM prescription_items
            WHERE prescription_id = ?
        ");

        $stmt->execute([$prescriptionId]);

        return $stmt->fetchColumn() > 0;
    }

    public static function createFromPrescription($invoiceId, $prescriptionId,$tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT pi.medicine_id, pi.quantity, m.price
            FROM prescription_items pi
            JOIN medicines m ON m.id = pi.medicine_id
            WHERE pi.prescription_id = ?
        ");

        $stmt->execute([$prescriptionId]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return 0;
        }

        $total = 0;

        foreach ($items as $item) {

            $lineTotal = $item['quantity'] * $item['price'];

            $insert = self::db($tenantId)->prepare("
                INSERT INTO invoice_items
                (invoice_id, medicine_id, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?)
            ");

            $insert->execute([
                $invoiceId,
                $item['medicine_id'],
                $item['quantity'],
                $item['price'],
                $lineTotal
            ]);

            $total += $lineTotal;
        }

        return $total;
    }

    public static function getByInvoiceId($invoiceId, $tenantId) {

        $stmt = self::db($tenantId)->prepare("
            SELECT medicine_id, quantity, unit_price, total_price
            FROM invoice_items
            WHERE invoice_id = ?
        ");

        $stmt->execute([$invoiceId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}