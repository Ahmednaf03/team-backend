<?php

class Staff {

   private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }


    private static function staffRoles() {
        return ['provider', 'nurse', 'pharmacist'];
        // add 'receptionist' if needed
    }

    public static function getAll($tenantId) {

        $roles = implode("','", self::staffRoles());

        $stmt = self::db($tenantId)->prepare("
            SELECT id, name, email, role, status, created_at
            FROM users
            WHERE role IN ('$roles')
            AND deleted_at IS NULL
            ORDER BY id DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($tenantId, $id) {

        $roles = implode("','", self::staffRoles());

        $stmt = self::db($tenantId)->prepare("
            SELECT id, name, email, role, status, created_at
            FROM users
            WHERE id = ?
            AND role IN ('$roles')
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($tenantId, $data) {

        if (!in_array($data['role'], self::staffRoles())) {
            return false;
        }

        $stmt = self::db($tenantId)->prepare("
            INSERT INTO users
            (name, email, email_hash, password_hash, role, status)
            VALUES ( ?, ?, ?, ?, ?, 'active')
        ");

        $emailHash = hash('sha256', strtolower(trim($data['email'])));

        $success = $stmt->execute([
        Encryption::encrypt($data['name']),
        Encryption::encrypt($data['email']),
            $emailHash,
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role']
        ]);

        if (!$success) {
            return false;
        }
        $db = self::db($tenantId);
        return $db->lastInsertId();
        // return self::db()->lastInsertId();
    }

    public static function update($tenantId, $id, $data) {

        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = $data['name'];
        }

        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $values[] = $data['status'];
        }

        if (isset($data['role']) && in_array($data['role'], self::staffRoles())) {
            $fields[] = "role = ?";
            $values[] = $data['role'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . "
                WHERE id = ?
                AND deleted_at IS NULL";

        $values[] = $id;
        // $values[] = $tenantId;

        $stmt = self::db($tenantId)->prepare($sql);

         $stmt->execute($values);
         return $stmt->rowCount();
    }

    public static function delete($tenantId, $id) {

        $stmt = self::db($tenantId)->prepare("
            UPDATE users
            SET deleted_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }

    public static function exists($tenantId, $id) {

        $stmt = self::db($tenantId)->prepare("
            SELECT id
            FROM users
            WHERE id = ?
            AND role IN ('provider','nurse','pharmacist')
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        return $stmt->fetch() ? true : false;
    }
}