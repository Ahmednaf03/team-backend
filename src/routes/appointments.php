<?php

/* i should always be at the top during registration
    i should be parsed before get /api/appointments
*/
$router->get('/api/appointments/upcoming', function ($request, $response) {

   

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'nurse', 'admin']);

    AppointmentController::upcoming($request, $response);
});
$router->get('/api/appointments', function ($request, $response) {

   AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'nurse', 'admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $id = $segments[2] ?? null;

    if ($id) {
        AppointmentController::getById($request, $response, $id);
    } else {
        AppointmentController::get($request, $response);
    }
});


$router->post('/api/appointments', function ($request, $response) {

    // $request->set('user', [
    //     'user_id'   => 2,
    //     'tenant_id' => 2,
    //     'role'      => 'provider'
    // ]);

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'nurse', 'admin']);

    AppointmentController::create($request, $response);
});


$router->put('/api/appointments', function ($request, $response) {

    // $request->set('user', [
    //     'user_id'   => 2,
    //     'tenant_id' => 1,
    //     'role'      => 'provider'
    // ]);
    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $id = $segments[2] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Appointment ID required');
        return;
    }

    AppointmentController::update($request, $response, $id);
});


$router->patch('/api/appointments', function ($request, $response) {

    // $request->set('user', [
    //     'user_id'   => 2,
    //     'tenant_id' => 1,
    //     'role'      => 'provider'
    // ]);
    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $id = $segments[2] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Appointment ID required');
        return;
    }

    AppointmentController::cancel($request, $response, $id);
});


$router->delete('/api/appointments', function ($request, $response) {

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
        Response::json(null, 400, 'Appointment ID required');
        return;
    }

    AppointmentController::delete($request, $response, $id);
});



