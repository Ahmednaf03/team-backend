<?php

/*
|--------------------------------------------------------------------------
| CALENDAR ROUTES
|--------------------------------------------------------------------------
*/




/*
|--------------------------------------------------------------------------
| GET /api/calendar/tooltip
|--------------------------------------------------------------------------
*/

$router->get('/api/calendar/tooltip', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);

    RoleMiddleware::handle($request, $response, [
        'provider',
        'nurse',
        'admin'
    ]);

    $uri = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));

    // /api/calendar/tooltip/{id}
    $id = $segments[3] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Appointment ID required');
        return;
    }

    CalendarController::tooltip($request, $response, $id);
});
/*
|--------------------------------------------------------------------------
| GET /api/calendar
|--------------------------------------------------------------------------
*/

$router->get('/api/calendar', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);

    // Adjust allowed roles as needed
    RoleMiddleware::handle($request, $response, [
        'provider',
        'nurse',
        'admin'
    ]);

    CalendarController::fetch($request, $response);
});


