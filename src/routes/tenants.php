<?php


$router->get('/api/tenants', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, [null]); 
    // empty allowed roles → only super admin passes

    TenantController::getAll($request, $response);
});


$router->get('/api/resolve', function ($request, $response) {

    // AuthMiddleware::handle($request, $response);
    // RoleMiddleware::handle($request, $response, [null]); 

    TenantController::getResolve($request, $response);
});


$router->post('/api/tenants', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, []); 
    // only super admin

    TenantController::create($request, $response);
});
