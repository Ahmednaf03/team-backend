<?php

class TenantMiddleware{

    public static function handle($request, $response){
        /* auth middleware should have set 
            $request->set('user', $payload);

        */
        $user = $request->get('user');

        if (!$user || empty($user['tenant_id'])) {
            $response->json(['error' => 'Unauthorized, tenant not found'], 403);
            exit;
        }

        $request->set('tenant_id', $user['tenant_id']);
    }
}