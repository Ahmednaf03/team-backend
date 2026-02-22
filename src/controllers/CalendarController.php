<?php

class CalendarController {

    public static function fetch($request, $response) {

        $tenantId = $request->get('tenant_id');
        $user     = $request->get('user');

        $start = $request->query('start');
        $end   = $request->query('end');

        if (!$start || !$end) {
            Response::json(null, 400, 'Date range required');
            return;
        }

        $appointments = Calendar::getAppointments(
            $tenantId,
            $user,
            $start,
            $end
        );

        Response::json($appointments, 200, 'Calendar data fetched');
    }

    public static function tooltip($request, $response, $id) {

        $tenantId = $request->get('tenant_id');
        $user     = $request->get('user');

        if (!$id) {
            Response::json(null, 400, 'Appointment ID required');
            return;
        }

        $appointment = Calendar::getAppointmentTooltip(
            $tenantId,
            $user,
            $id
        );

        if (!$appointment) {
            Response::json(null, 403, 'Not allowed or not found');
            return;
        }
        $appointment['patient_name'] = Encryption::decrypt($appointment['patient_name']);
        $appointment['doctor_name']  = Encryption::decrypt($appointment['doctor_name']);
        Response::json($appointment, 200, 'Tooltip fetched');
    }
}