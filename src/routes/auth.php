<?php

require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Middlewares/JsonMiddleware.php';
require_once __DIR__ . '/../Middlewares/CsrfMiddleware.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../Middlewares/TenantMiddleware.php';

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/


$router->post('/api/signup', function ($request, $response) {

    JsonMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);

    AuthController::signup($request, $response);
});


$router->post('/api/login', function ($request, $response) {

    JsonMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);

    AuthController::login($request, $response);
});


$router->post('/api/refresh', function ($request, $response) {

    JsonMiddleware::handle($request, $response);

    AuthController::refresh($request, $response);
});


$router->post('/api/logout', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CsrfMiddleware::handle($request, $response);

    AuthController::logout($request, $response);
});


$router->get('/api/profile', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);

    $user = $request->get('user');

    Response::json([
        'user' => $user
    ], 200, 'Profile fetched successfully');
});


$router->post('/api/change-password', function ($request, $response) {

    JsonMiddleware::handle($request, $response);
    AuthMiddleware::handle($request, $response);
    CsrfMiddleware::handle($request, $response);

    AuthController::changePassword($request, $response);
});
