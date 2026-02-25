<?php

class DatabaseManager{
    private static $master;
    private static $tenantConnections = [];

    public static function master(){
        if (!self::$master) {
            self::$master = new PDO(
                "mysql:host=localhost;dbname=master_db",
                "root",
                ""
            );
        }

        return self::$master;
    }

    public static function tenant($tenantId){
        if (isset(self::$tenantConnections[$tenantId])) {
            return self::$tenantConnections[$tenantId];
        }

        $master = self::master();

        $stmt = $master->prepare("
            SELECT db_name, db_user, db_pass_encrypted
            FROM tenants
            WHERE id = ?
            AND status = 'active'
            AND deleted_at IS NULL
        ");

        $stmt->execute([$tenantId]);
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tenant) {
            throw new Exception("Invalid tenant");
        }
        $password = Encryption::decrypt($tenant['db_pass_encrypted']);
        $pdo = new PDO(
            "mysql:host=localhost;dbname={$tenant['db_name']}",
            $tenant['db_user'],
            $password
        );

        self::$tenantConnections[$tenantId] = $pdo;

        return $pdo;
    }
}