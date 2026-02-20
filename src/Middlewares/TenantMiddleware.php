<?php

class TenantMiddleware
{
    public static function handle($request, $response)
    {
        $tenantId = null;

        // -------------------------
        // Case 1: Authenticated user
        // -------------------------
        $user = $request->get('user');

        if ($user && !empty($user['tenant_id'])) {
            $tenantId = (int) $user['tenant_id'];
        }

        // -------------------------
        // Case 2: Public routes (signup/login)
        // -------------------------
        if (!$tenantId) {
            $headers = getallheaders();
            $headerTenant = $headers['X-TENANT-ID'] ?? null;

            if (!$headerTenant) {
                Response::json(null, 403, 'Tenant ID missing');
                return;
            }

            $tenantId = (int) $headerTenant;
        }

        // -------------------------
        // Validate Tenant Exists
        // -------------------------
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT id
            FROM tenants
            WHERE id = ?
            AND status = 'active'
            AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([$tenantId]);

        if (!$stmt->fetch()) {
            Response::json(null, 403, 'Invalid or inactive tenant');
            return;
        }

        // Store validated tenant_id in request
        $request->set('tenant_id', $tenantId);
    }
}