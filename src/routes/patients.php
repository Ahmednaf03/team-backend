<?php



$router->get('/api/patients', function ($request, $response) use ($pdo) {

    //un comment me once auth and tenant middleware are implemented
    // AuthMiddleware::handle($request);
    //    $request->set('user', [
    //     'user_id'   => 2,
    //     'tenant_id' => 1,
    //     'role'      => 'provider'
    // ]);

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request,$response);
    RoleMiddleware::handle($request,$response, ['admin', 'provider']);
       // If URL is /api/patients/1
    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    // segments: ['api','patients','1']
    $id = $segments[2] ?? null;

    if ($id) {
        PatientController::getById($request, $response, $id);
    } else {
        PatientController::get($request, $response);
    }

    // PatientController::get($request, $response);
});

// $router->get('/api/patients/{id}', function ($request, $response, $id) use ($pdo) {

//     $request->set('user', [
//         'user_id'   => 2,
//         'tenant_id' => 1,
//         'role'      => 'provider'
//     ]);

//     TenantMiddleware::handle($request, $response);
//     RoleMiddleware::handle($request, $response, ['admin', 'provider']);

//     PatientController::getById($request, $response, $id);
// });


$router->post('/api/patients', function ($request, $response) use ($pdo) {

    // $request->set('user', [
    //     'user_id'   => 2,
    //     'tenant_id' => 1,
    //     'role'      => 'admin'
    // ]);


    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    PatientController::create($request, $response);
});


$router->put('/api/patients', function ($request, $response) use ($pdo) {

    // $request->set('user', [
    //     'user_id'   => 2,
    //     'tenant_id' => 1,
    //     'role'      => 'admin'
    // ]);

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $id = $segments[2] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Patient ID required');
        return;
    }

    PatientController::update($request, $response, $id);
});



$router->delete('/api/patients', function ($request, $response) use ($pdo) {

    // $request->set('user', [
    //     'user_id'   => 2,
    //     'tenant_id' => 1,
    //     'role'      => 'admin'
    // ]);


     $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    // segments: ['api','patients','1']
    $id = $segments[2] ?? null;
    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    PatientController::delete($request, $response, $id);
});
