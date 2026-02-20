<?php

class Staff {

    private static $db;

    private static function db() {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }

    private static function staffRoles() {
        return ['provider', 'nurse', 'pharmacist'];
        // add 'receptionist' if needed
    }

    public static function getAll($tenantId) {

        $roles = implode("','", self::staffRoles());

        $stmt = self::db()->prepare("
            SELECT id, name, email, role, status, created_at
            FROM users
            WHERE tenant_id = ?
            AND role IN ('$roles')
            AND deleted_at IS NULL
            ORDER BY id DESC
        ");

        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($tenantId, $id) {

        $roles = implode("','", self::staffRoles());

        $stmt = self::db()->prepare("
            SELECT id, name, email, role, status, created_at
            FROM users
            WHERE id = ?
            AND tenant_id = ?
            AND role IN ('$roles')
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id, $tenantId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($tenantId, $data) {

        if (!in_array($data['role'], self::staffRoles())) {
            return false;
        }

        $stmt = self::db()->prepare("
            INSERT INTO users
            (tenant_id, name, email, email_hash, password_hash, role, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $emailHash = hash('sha256', strtolower(trim($data['email'])));

        $success = $stmt->execute([
            $tenantId,
            $data['name'],
            $data['email'],
            $emailHash,
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role']
        ]);

        if (!$success) {
            return false;
        }

        return self::db()->lastInsertId();
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
                AND tenant_id = ?
                AND deleted_at IS NULL";

        $values[] = $id;
        $values[] = $tenantId;

        $stmt = self::db()->prepare($sql);

        return $stmt->execute($values);
    }

    public static function delete($tenantId, $id) {

        $stmt = self::db()->prepare("
            UPDATE users
            SET deleted_at = NOW()
            WHERE id = ?
            AND tenant_id = ?
        ");

        return $stmt->execute([$id, $tenantId]);
    }

    public static function exists($tenantId, $id) {

        $stmt = self::db()->prepare("
            SELECT id
            FROM users
            WHERE id = ?
            AND tenant_id = ?
            AND role IN ('provider','nurse','pharmacist')
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id, $tenantId]);

        return $stmt->fetch() ? true : false;
    }
}