<?php

/*
|--------------------------------------------------------------------------
| NOTIFICATION ROUTES
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| GET /api/notifications
|--------------------------------------------------------------------------
*/

$router->get('/api/notifications', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin', 'provider', 'pharmacist', 'receptionist', 'staff', 'patient']);

    NotificationController::getAll($request, $response);
});

/*
|--------------------------------------------------------------------------
| PATCH /api/notifications/:id/read
|--------------------------------------------------------------------------
*/

$router->patch('/api/notifications/read', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin', 'provider', 'pharmacist', 'receptionist', 'staff', 'patient']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    // /api/notifications/read/{id}
    $id = $segments[3] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Notification ID required');
        return;
    }

    NotificationController::markRead($request, $response, $id);
});

/*
|--------------------------------------------------------------------------
| PATCH /api/notifications/read-all
|--------------------------------------------------------------------------
*/

$router->patch('/api/notifications/read-all', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin', 'provider', 'pharmacist', 'receptionist', 'staff', 'patient']);

    NotificationController::markAllRead($request, $response);
});

/*
|--------------------------------------------------------------------------
| DELETE /api/notifications
|--------------------------------------------------------------------------
*/

$router->delete('/api/notifications', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin', 'provider', 'pharmacist', 'staff', 'patient']);

    NotificationController::clearAll($request, $response);
});
