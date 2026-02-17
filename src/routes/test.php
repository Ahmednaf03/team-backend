<?php

$router->get('/api/test', function ($request, $response) {
     $request->set('user', [
        'user_id'   => 1,
        // 'tenant_id' => 2,
        'role'      => 'admin'
    ]);

    TenantMiddleware::handle($request,$response);
    RoleMiddleware::handle($request,$response, ['admin']);
    $response->json([
        'status' => 'ok',
        'method' => $request->method(),
        'uri' => $request->uri(),
        'tenant_id' => $request->get('tenant_id'),
        'message' => 'Middleware working',
        'user' => $request->get('user')
    ]);
});
