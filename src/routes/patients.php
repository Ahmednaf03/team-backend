<?php

// ─────────────────────────────────────────────────────────────────────────────
// PATIENT PROFILE  (patient's own — called after patient portal login)
// Role guard is enforced inside PatientController::getProfile()
// ─────────────────────────────────────────────────────────────────────────────
$router->get('/api/patient/profile', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    // No CSRFMiddleware on GET — CSRF only applies to state-mutating requests
    // No RoleMiddleware — PatientController::getProfile() enforces role = 'patient'

    PatientController::getProfile($request, $response);
});


// ─────────────────────────────────────────────────────────────────────────────
// ADMIN / STAFF ROUTES (existing — unchanged)
// ─────────────────────────────────────────────────────────────────────────────

$router->get('/api/patients', function ($request, $response) use ($pdo) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin', 'provider', 'nurse', 'receptionist']);

    $uri      = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $id       = $segments[2] ?? null;

    if ($id) {
        PatientController::getById($request, $response, $id);
    } else {
        PatientController::get($request, $response);
    }
});


$router->post('/api/patients', function ($request, $response) use ($pdo) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    PatientController::create($request, $response);
});


$router->put('/api/patients', function ($request, $response) use ($pdo) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    $uri      = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $id       = $segments[2] ?? null;

    if (!$id) {
        Response::json(null, 400, 'Patient ID required');
        return;
    }

    PatientController::update($request, $response, $id);
});


$router->delete('/api/patients', function ($request, $response) use ($pdo) {

    $uri      = parse_url($request->uri(), PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $id       = $segments[2] ?? null;

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['admin']);

    PatientController::delete($request, $response, $id);
});