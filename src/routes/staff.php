<?php

/*
|--------------------------------------------------------------------------
| STAFF ROUTES
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| GET /api/staff
|--------------------------------------------------------------------------
*/

$router->get('/api/staff', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    // /api/staff/{id}
    $id = $segments[2] ?? null;

    if ($id) {
        StaffController::getById($request, $response, $id);
    } else {
        StaffController::get($request, $response);
    }
});


/*
|--------------------------------------------------------------------------
| POST /api/staff
|--------------------------------------------------------------------------
*/

$router->post('/api/staff', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    StaffController::create($request, $response);
});


/*
|--------------------------------------------------------------------------
| PUT /api/staff
|--------------------------------------------------------------------------
*/

$router->put('/api/staff', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $id = $segments[2] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Staff ID required');
        return;
    }

    StaffController::update($request, $response, $id);
});


/*
|--------------------------------------------------------------------------
| DELETE /api/staff
|--------------------------------------------------------------------------
*/

$router->delete('/api/staff', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $id = $segments[2] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Staff ID required');
        return;
    }

    StaffController::delete($request, $response, $id);
});