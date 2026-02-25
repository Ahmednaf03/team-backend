<?php

class TenantMiddleware
{
    public static function handle($request, $response)
    {
        try {

            $tenantId = TenantResolver::resolve($request);

            // validate tenant in master DB
            $master = DatabaseManager::master();

            $stmt = $master->prepare("
                SELECT id
                FROM tenants
                WHERE id = ?
                AND status = 'active'
                AND deleted_at IS NULL
                LIMIT 1
            ");

            $stmt->execute([$tenantId]);

            if (!$stmt->fetch()) {
                throw new Exception("Invalid or inactive tenant");
            }

            $request->set('tenant_id', $tenantId);

        } catch (Exception $e) {
            Response::json(null, 403, $e->getMessage());
            exit;
        }
    }
}