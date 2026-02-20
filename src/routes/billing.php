<?php

/*
|--------------------------------------------------------------------------
| BILLING ROUTES
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| GET /api/billing/summary
| should be registered before /api/billing
|--------------------------------------------------------------------------
*/

$router->get('/api/billing/summary', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    BillingController::summary($request, $response);
});


/*
|--------------------------------------------------------------------------
| POST /api/billing
|--------------------------------------------------------------------------
*/

$router->post('/api/billing', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin', 'pharmacist']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    // /api/billing/{prescriptionId}
    $id = $segments[2] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Prescription ID required');
        return;
    }

    BillingController::generate($request, $response, $id);
});


/*
|--------------------------------------------------------------------------
| PATCH /api/billing
|--------------------------------------------------------------------------
*/

$router->patch('/api/billing', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);

    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    // /api/billing/{invoiceId}
    $id = $segments[2] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Invoice ID required');
        return;
    }

    BillingController::pay($request, $response, $id);
});