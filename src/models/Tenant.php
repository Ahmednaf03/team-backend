<?php

class Tenant
{
    private static function db()
    {
        return DatabaseManager::master(); // master_db connection
    }

    public static function getAll()
    {
        $stmt = self::db()->prepare("
            SELECT id, name, slug, email, subdomain, custom_domain,
                   plan, status, is_verified, created_at
            FROM tenants
            WHERE deleted_at IS NULL
            ORDER BY id DESC
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


public static function getResolveBySlug($slug)
    {
        $stmt = self::db()->prepare("
            SELECT name, slug, subdomain, custom_domain 
            FROM tenants
            WHERE slug = :slug 
              AND status = 'active' 
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([':slug' => $slug]);
        
        // Use fetch() because we only want a single workspace record
        return $stmt->fetch(PDO::FETCH_ASSOC); 
    }

    public static function create(PDO $pdo, array $data)
    {
        $stmt = $pdo->prepare("
            INSERT INTO tenants
            (name, slug, email, address_line1, city, state, country,
             db_name, db_user, db_pass_encrypted)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['email'],
            $data['address_line1'],
            $data['city'],
            $data['state'],
            $data['country'],
            $data['db_name'],
            $data['db_user'],
            $data['db_pass_encrypted']
        ]);

        return $pdo->lastInsertId();
    }
}