<?php

class TenantResolver{
    public static function resolve($request){
        // JWT payload given by auth middleware
        $user = $request->get('user');
        $headers = getallheaders();

        $master = DatabaseManager::master();

        //  Normal authenticated user
        if ($user && empty($user['is_super_admin'])) {

            if (empty($user['tenant_id'])) {
                throw new Exception("Tenant missing in token");
            }
            // from JWT payload
            return (int)$user['tenant_id'];
        }

        // Super admin
        if ($user && !empty($user['is_super_admin'])) {

            $twoFactor = $headers['X-2FA-CODE'] ?? null;

            if (!$twoFactor || $twoFactor !== $_ENV['SUPER_ADMIN_2FA_CODE']) {
                throw new Exception("Invalid 2FA code");
            }

            $slug = $headers['X-TENANT-SLUG'] ?? null;

            if (!$slug) {
                throw new Exception("Tenant slug required");
            }

            return self::resolveBySlug($slug, $master);
        }

        //  Public routes (login/signup)
        $slug = $headers['X-TENANT-SLUG'] ?? null;

        if (!$slug) {
            throw new Exception("Tenant slug required");
        }

        return self::resolveBySlug($slug, $master);
    }

    private static function resolveBySlug($slug, $master)
    {
        $stmt = $master->prepare("
            SELECT id
            FROM tenants
            WHERE slug = ?
            AND status = 'active'
            AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([$slug]);
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tenant) {
            throw new Exception("Invalid tenant");
        }

        return (int)$tenant['id'];
    }
}