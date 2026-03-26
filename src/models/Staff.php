<?php

class Staff {

   private static function db($tenantId)
    {
        
        return DatabaseManager::tenant($tenantId);
    }


    private static function staffRoles() {
        return ['provider', 'nurse', 'pharmacist', 'receptionist'];
    }

    public static function getAll($tenantId, array $params = []) {

        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 10;
        $filters = $params['filters'] ?? [];
        $search = $params['search'] ?? '';
        $roles = self::staffRoles();

        $rolePlaceholders = [];
        $bindings = [];

        foreach ($roles as $index => $role) {
            $placeholder = ':role_scope_' . $index;
            $rolePlaceholders[] = $placeholder;
            $bindings[$placeholder] = $role;
        }

        $where = [
            'role IN (' . implode(', ', $rolePlaceholders) . ')',
            'deleted_at IS NULL'
        ];

        if (!empty($filters['role']) && in_array($filters['role'], $roles, true)) {
            $where[] = 'role = :filter_role';
            $bindings[':filter_role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :filter_status';
            $bindings[':filter_status'] = $filters['status'];
        }

        if ($search !== '') {
            $searchParts = [
                'CAST(id AS CHAR) LIKE :search_like',
                'role LIKE :search_like',
                'status LIKE :search_like'
            ];
            $bindings[':search_like'] = '%' . $search . '%';

            if (str_contains($search, '@')) {
                $searchParts[] = 'email_hash = :email_hash';
                $bindings[':email_hash'] = Encryption::blindIndex($search);
            }

            $where[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = self::db($tenantId)->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE {$whereSql}
        ");

        foreach ($bindings as $key => $value) {
            $countStmt->bindValue($key, $value);
        }

        $countStmt->execute();
        $totalRecords = (int) $countStmt->fetchColumn();
        $pagination = PaginationHelper::buildMeta($totalRecords, $page, $perPage);
        $offset = ($pagination['currentPage'] - 1) * $perPage;

        $stmt = self::db($tenantId)->prepare("
            SELECT id, name, email, role, status, created_at
            FROM users
            WHERE {$whereSql}
            ORDER BY id DESC
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

        $emailHash = Encryption::blindIndex($data['email']);

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
            $values[] = Encryption::encrypt($data['name']);
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
            AND role IN ('provider','nurse','pharmacist','receptionist')
            AND deleted_at IS NULL
        ");

        $stmt->execute([$id]);

        return $stmt->fetch() ? true : false;
    }
}
