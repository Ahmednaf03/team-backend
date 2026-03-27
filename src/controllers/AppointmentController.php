<?php

class AppointmentController {

    public static function get($request, $response) {

        $tenantId = $request->get('tenant_id');
        $user = $request->get('user');
        $params = PaginationHelper::parse($request, [
            'status' => 'string',
            'patient_id' => 'int',
            'doctor_id' => 'int',
            'scheduled_from' => 'string',
            'scheduled_to' => 'string',
        ]);

        if (($user['role'] ?? null) === 'patient') {
            $params['filters']['patient_id'] = (int) ($user['user_id'] ?? 0);
        }

        $appointments = Appointment::getAll($tenantId, $params);

        Response::paginated($appointments['data'], $appointments['pagination']);
    }

    public static function getById($request, $response, $id) {

        $tenantId = $request->get('tenant_id');
        $user = $request->get('user');

        $appointment = Appointment::getById($id, $tenantId);

        if (!$appointment) {
            Response::json(null, 404, 'Appointment not found');
            return;
        }

        if (
            ($user['role'] ?? null) === 'patient' &&
            (int) ($appointment['patient_id'] ?? 0) !== (int) ($user['user_id'] ?? 0)
        ) {
            Response::json(null, 404, 'Appointment not found');
            return;
        }

        Response::json($appointment, 200, 'Appointment fetched successfully');
    }

    public static function create($request, $response) {

        $tenantId = $request->get('tenant_id');
        $data = $request->body();
        $user = $request->get('user');
        $isPatient = ($user['role'] ?? null) === 'patient';

        if ($isPatient) {
            $authenticatedPatientId = (int) ($user['user_id'] ?? 0);

            if (
                isset($data['patient_id']) &&
                (int) $data['patient_id'] !== $authenticatedPatientId
            ) {
                Response::json(null, 403, 'Patients can only book appointments for themselves');
                return;
            }

            $data['patient_id'] = $authenticatedPatientId;
        }

        if (
            empty($data['patient_id']) ||
            empty($data['doctor_id']) ||
            empty($data['scheduled_at'])
        ) {
            Response::json(null, 422, 'Missing required fields');
            return;
        }

        $patient = Patient::getById($data['patient_id'], $tenantId);

        if (!$patient) {
            Response::json(null, 404, 'Patient not found');
            return;
        }

        $doctor = Staff::getById($tenantId, $data['doctor_id']);

        if (!$doctor || ($doctor['role'] ?? null) !== 'provider') {
            Response::json(null, 422, 'Valid provider is required');
            return;
        }

        $created = Appointment::create($tenantId, $data);

        if (!$created) {
            Response::json(null, 409, 'Time conflict');
            return;
        }

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
        $data = $request->body();
        $appointment = Appointment::getById($id, $tenantId);

        if (!$appointment) {
            Response::json(null, 404, 'Appointment not found');
            return;
        }

        $shouldNotifyPatient = (
            isset($data['status']) &&
            $data['status'] === 'completed' &&
            ($appointment['status'] ?? null) !== 'completed'
        );

        $updated = Appointment::update($tenantId, $id, $data);

        if (!$updated) {
            Response::json(null, 400, 'Update failed or conflict');
            return;
        }

        if ($shouldNotifyPatient) {
            $scheduledAt = $data['scheduled_at'] ?? $appointment['scheduled_at'];
            $patient = Patient::getById($appointment['patient_id'], $tenantId);
            $patientName = $patient['name'] ?? 'Patient';

            Notification::create($tenantId, [
                'user_id' => $appointment['patient_id'],
                'user_type' => 'patient',
                'type' => 'appointment',
                'title' => 'Appointment Confirmed',
                'message' => "Your appointment on {$scheduledAt} has been confirmed.",
                'reference_id' => $id
            ]);

            Notification::create($tenantId, [
                'user_id' => $appointment['doctor_id'],
                'user_type' => 'staff',
                'type' => 'appointment',
                'title' => 'Appointment Confirmed',
                'message' => "Appointment with {$patientName} on {$scheduledAt} has been confirmed.",
                'reference_id' => $id
            ]);
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
        $user = $request->get('user');
        $patientId = null;

        if (($user['role'] ?? null) === 'patient') {
            $patientId = (int) ($user['user_id'] ?? 0);
        }

        $appointments = Appointment::getUpcoming($tenantId, $patientId);

        Response::json($appointments, 200, 'Upcoming appointments fetched');
    }
}
