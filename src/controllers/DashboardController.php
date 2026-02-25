<?php

class DashboardController
{
    public static function patientsCount($request, $response)
    {
        $tenantId = $request->get('tenant_id');

        $total = Dashboard::patientsCount($tenantId);

        Response::json([
            'total_patients' => $total
        ], 200, 'Patients count fetched');
    }


    public static function appointmentStats($request, $response)
    {
        $tenantId = $request->get('tenant_id');

        $stats = Dashboard::appointmentStats($tenantId);

        Response::json([
            'total' => (int)$stats['total'],
            'completed' => (int)$stats['completed'],
            'scheduled' => (int)$stats['scheduled'],
            'cancelled' => (int)$stats['cancelled']
        ], 200, 'Appointment stats fetched');
    }


    public static function prescriptionSummary($request, $response)
    {
        $tenantId = $request->get('tenant_id');

        $total = Dashboard::prescriptionSummary($tenantId);

        Response::json([
            'total_prescriptions' => $total
        ], 200, 'Prescription summary fetched');
    }


    // public static function tenantAnalytics($request, $response)
    // {
    //     $user = $request->get('user');
    //     $tenantId = $request->get('tenant_id');

    //     if ($user['role'] !== 'admin') {
    //         Response::json(null, 403, 'Forbidden');
    //         return;
    //     }

    //     $data = Dashboard::tenantAnalytics($tenantId);

    //     Response::json($data, 200, 'Tenant analytics fetched');
    // }
}