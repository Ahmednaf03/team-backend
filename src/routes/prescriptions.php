<?php

/*
|--------------------------------------------------------------------------
| PRESCRIPTION ROUTES
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| GET /api/prescriptions
| GET /api/prescriptions/{id}
|--------------------------------------------------------------------------
*/

$router->get('/api/prescriptions', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'admin', 'pharmacist']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $id = $segments[2] ?? null;

    if ($id) {
        PrescriptionController::getById($request, $response, $id);
    } else {
        PrescriptionController::get($request, $response);
    }
});


/*
|--------------------------------------------------------------------------
| POST /api/prescriptions/items
|--------------------------------------------------------------------------
*/

$router->post('/api/prescriptions/items', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'admin']);
    
    PrescriptionController::addItem($request, $response);
});
/*
|--------------------------------------------------------------------------
| POST /api/prescriptions
|--------------------------------------------------------------------------
*/

$router->post('/api/prescriptions', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'admin']);

    PrescriptionController::create($request, $response);
});





/*
|--------------------------------------------------------------------------
| PATCH /api/prescriptions/verify/{id}
|--------------------------------------------------------------------------
*/

$router->patch('/api/prescriptions/verify', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['pharmacist', 'admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $id = $segments[3] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Prescription ID required');
        return;
    }

    PharmacyController::verify($request, $response, $id);
});


/*
|--------------------------------------------------------------------------
| PATCH /api/prescriptions/dispense/{id}
|--------------------------------------------------------------------------
*/

$router->patch('/api/prescriptions/dispense', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['pharmacist', 'admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    $id = $segments[3] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Prescription ID required');
        return;
    }

    PharmacyController::dispense($request, $response, $id);
});