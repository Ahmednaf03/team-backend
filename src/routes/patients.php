<?php



$router->get('/api/patients', function ($request, $response) {

    //un comment me once auth and tenant middleware are implemented
    // AuthMiddleware::handle($request);
    // TenantMiddleware::handle($request);
    // RoleMiddleware::handle($request,['admin', 'provider']);

    PatientController::get($request, $response);
});