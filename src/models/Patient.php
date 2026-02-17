<?php



class Patient{
    public static function getAll($tenantId){
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM patients
            WHERE tenant_id = ?
            AND deleted_at IS NULL
        ");

        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
