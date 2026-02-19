<?php

class TenantMiddleware {

    public static function handle($request, $response) {

        // Case 1: user already authenticated
        $user = $request->get('user');

        if ($user && !empty($user['tenant_id'])) {
            $request->set('tenant_id', $user['tenant_id']);
            return;
        }

        // Case 2: public route (signup/login)
        $headers = getallheaders();
        $tenantId = $headers['X-TENANT-ID'] ?? null;

        if (!$tenantId) {
            $response->json(['error' => 'Unauthorized, tenant not found'], 403);
            exit;
        }

        $request->set('tenant_id', (int) $tenantId);
    }
}
