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

    public static function getAll($tenantId, array $params = []) {

        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 10;
        $filters = $params['filters'] ?? [];
        $search = $params['search'] ?? '';

        $where = [];
        $bindings = [];

        if (!empty($filters['status'])) {
            $where[] = 'i.status = :status';
            $bindings[':status'] = $filters['status'];
        }

        if ($search !== '') {
            $where[] = "(
                CAST(i.id AS CHAR) LIKE :search
                OR CAST(i.prescription_id AS CHAR) LIKE :search
            )";
            $bindings[':search'] = '%' . $search . '%';
        }

        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $countStmt = self::db($tenantId)->prepare("
            SELECT COUNT(*)
            FROM invoices i
            {$whereSql}
        ");

        foreach ($bindings as $key => $value) {
            $countStmt->bindValue($key, $value);
        }

        $countStmt->execute();
        $totalRecords = (int) $countStmt->fetchColumn();
        $pagination = PaginationHelper::buildMeta($totalRecords, $page, $perPage);
        $offset = ($pagination['currentPage'] - 1) * $perPage;

        $stmt = self::db($tenantId)->prepare("
            SELECT i.id, i.prescription_id, i.patient_id, i.total_amount, i.status, i.created_at, i.paid_at
            FROM invoices i
            {$whereSql}
            ORDER BY i.created_at DESC, i.id DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => $pagination,
        ];
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
