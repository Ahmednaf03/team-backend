<?php

class AppointmentController {

    public static function get($request, $response) {

        $tenantId = $request->get('tenant_id');
        $params = PaginationHelper::parse($request, [
            'status' => 'string',
            'patient_id' => 'int',
            'doctor_id' => 'int',
            'scheduled_from' => 'string',
            'scheduled_to' => 'string',
        ]);

        $appointments = Appointment::getAll($tenantId, $params);

        Response::paginated($appointments['data'], $appointments['pagination']);
    }

    public static function getById($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $appointment = Appointment::getById($id, $tenantId);

        if (!$appointment) {
            Response::json(null, 404, 'Appointment not found');
            return;
        }

        Response::json($appointment, 200, 'Appointment fetched successfully');
    }

    public static function create($request, $response) {

        $tenantId = $request->get('tenant_id');

        $data = $request->body();

        if (
            empty($data['patient_id']) ||
            empty($data['doctor_id']) ||
            empty($data['scheduled_at'])
        ) {
            Response::json(null, 422, 'Missing required fields');
            return;
        }

        $created = Appointment::create($tenantId, $data);

        if (!$created) {
            Response::json(null, 409, 'Time conflict');
            return;
        }

        $patient = Patient::getById($data['patient_id'], $tenantId);
        $patientName = $patient['name'] ?? 'Patient';
        $staffMessage = "New appointment with {$patientName} on {$data['scheduled_at']}";

        // Notify patient
        Notification::create($tenantId, [
            'user_id' => $data['patient_id'],
            'user_type' => 'patient',
            'type' => 'appointment',
            'title' => 'Appointment Scheduled',
            'message' => "Your appointment is scheduled for {$data['scheduled_at']}",
            'reference_id' => $created
        ]);

        // Notify doctor
        Notification::create($tenantId, [
            'user_id' => $data['doctor_id'],
            'user_type' => 'staff',
            'type' => 'appointment',
            'title' => 'New Appointment',
            'message' => $staffMessage,
            'reference_id' => $created
        ]);

        Response::json(['appointment_id' => $created], 201, 'Appointment created successfully');
    }

    public static function update($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $updated = Appointment::update($tenantId, $id, $request->body());

        if (!$updated) {
            Response::json(null, 400, 'Update failed or conflict');
            return;
        }

        Response::json($updated, 200, 'Appointment updated successfully');
    }

    public static function cancel($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $cancelled = Appointment::cancel($tenantId, $id);

        if (!$cancelled) {
            Response::json(null, 400, 'Cancel failed');
            return;
        }

        Response::json($cancelled, 200, 'Appointment cancelled successfully');
    }

    public static function delete($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $deleted = Appointment::softDelete($tenantId, $id);

        if (!$deleted) {
            Response::json(null, 400, 'Delete failed');
            return;
        }

        Response::json($deleted, 200, 'Appointment deleted successfully');
    }

    public static function upcoming($request, $response) {

        $tenantId = $request->get('tenant_id');

        $appointments = Appointment::getUpcoming($tenantId);

        Response::json($appointments, 200, 'Upcoming appointments fetched');
    }
}
