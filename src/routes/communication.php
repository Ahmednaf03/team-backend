<?php


$router->get('/api/messages', function ($request, $response) {

    // $request->set('user', [
    //     'user_id'   => 2,
    //     'tenant_id' => 1,
    //     'role'      => 'provider'
    // ]);

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'nurse', 'admin']);

    $segments = explode('/', trim($request->uri(), '/'));

    // /api/messages/appointment/1
    if (($segments[2] ?? null) === 'appointment') {

        $appointmentId = $segments[3] ?? null;

        if (!$appointmentId) {
            Response::json(null, 400, 'Appointment ID required');
            return;
        }

        CommunicationController::get($request, $response, $appointmentId);
        return;
    }

    // /api/messages/5
    $messageId = $segments[2] ?? null;

    if (!$messageId) {
        Response::json(null, 400, 'Message ID required');
        return;
    }

    CommunicationController::getById($request, $response, $messageId);
});



$router->post('/api/messages', function ($request, $response) {

    // $request->set('user', [
    //     'user_id'   => 2,
    //     'tenant_id' => 1,
    //     'role'      => 'provider'
    // ]);

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'nurse', 'admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $appointmentId = $segments[2] ?? null;

    if (!$appointmentId) {
        Response::json(null, 400, 'Appointment ID required');
        return;
    }

    CommunicationController::create($request, $response, $appointmentId);
});


$router->put('/api/messages', function ($request, $response) {

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

    $messageId = $segments[2] ?? null;

    if (!$messageId) {
        Response::json(null, 400, 'Message ID required');
        return;
    }

    CommunicationController::update($request, $response, $messageId);
});


$router->delete('/api/messages', function ($request, $response) {

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

    $messageId = $segments[2] ?? null;

    if (!$messageId) {
        Response::json(null, 400, 'Message ID required');
        return;
    }

    CommunicationController::delete($request, $response, $messageId);
});
