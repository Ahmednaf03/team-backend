<?php

require_once __DIR__ . '/../Controllers/DashboardController.php';

/*
|--------------------------------------------------------------------------
| DASHBOARD ROUTES
|--------------------------------------------------------------------------
*/

/*
| Patients Count
*/
$router->get('/api/dashboard/patients-count', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'nurse', 'admin']);

    DashboardController::patientsCount($request, $response);
});


/*
| Appointment Stats
*/
$router->get('/api/dashboard/appointments-stats', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'nurse', 'admin']);

    DashboardController::appointmentStats($request, $response);
});


/*
| Prescription Summary
*/
$router->get('/api/dashboard/prescription-summary', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    TenantMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['provider', 'nurse', 'admin']);

    DashboardController::prescriptionSummary($request, $response);
});


/*
| Super Admin Tenant Analytics
*/
// implement me later 
$router->get('/api/dashboard/tenant-analytics', function ($request, $response) {

    AuthMiddleware::handle($request, $response);
    CSRFMiddleware::handle($request, $response);
    RoleMiddleware::handle($request, $response, ['super_admin']);

    DashboardController::tenantAnalytics($request, $response);
});