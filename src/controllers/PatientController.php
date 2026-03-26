<?php

class PatientController
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET ALL  (admin / staff view)
    // ─────────────────────────────────────────────────────────────────────────
    public static function get($request, $response)
    {
        $tenantId = $request->get('tenant_id');
        $patients = Patient::getAll($tenantId);

        Response::json($patients);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET BY ID  (admin / staff view)
    // ─────────────────────────────────────────────────────────────────────────
    public static function getById($request, $response, $id)
    {
        $tenantId = $request->get('tenant_id');
        $patient  = Patient::getById($id, $tenantId);

        if (!$patient) {
            Response::json(null, 404, 'Patient not found');
            return;
        }

        Response::json($patient, 200, 'Patient fetched successfully');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET PROFILE  (patient's own view — called after patient login)
    //
    // JWT payload sets role = 'patient' and user_id = patients.id
    // AuthMiddleware injects this into $request->get('user')
    // ─────────────────────────────────────────────────────────────────────────
    public static function getProfile($request, $response)
    {
        $user     = $request->get('user');
        $tenantId = $request->get('tenant_id');

        // user_id in the JWT IS the patients.id for patient-role tokens
        $patientId = $user['user_id'];

        // Guard: only patients can hit this endpoint
        if ($user['role'] !== 'patient') {
            Response::json(null, 403, 'Forbidden');
            return;
        }

        $profile = Patient::getProfile($patientId, $tenantId);

        if (!$profile) {
            Response::json(null, 404, 'Patient profile not found');
            return;
        }

        Response::json($profile, 200, 'Profile fetched successfully');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATE  (admin only)
    // ─────────────────────────────────────────────────────────────────────────
    public static function create($request, $response)
    {
        $tenantId = $request->get('tenant_id');
        $data     = $request->body();

        $required = ['name', 'age', 'gender', 'phone', 'email', 'password'];
        $missing  = array_filter($required, fn($f) => empty($data[$f]));

        if (!empty($missing)) {
            Response::json(null, 422, 'Missing required fields: ' . implode(', ', $missing));
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::json(null, 422, 'Invalid email format');
            return;
        }

        if (strlen($data['password']) < 6) {
            Response::json(null, 422, 'Password must be at least 6 characters');
            return;
        }

        $newId = Patient::create($tenantId, $data);

        if (!$newId) {
            Response::json(null, 500, 'Patient creation failed');
            return;
        }

        Response::json(['id' => $newId], 201, 'Patient created successfully');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE  (admin only)
    // ─────────────────────────────────────────────────────────────────────────
    public static function update($request, $response, $id)
    {
        $tenantId = $request->get('tenant_id');
        $updated  = Patient::update($tenantId, $id, $request->body());

        if (!$updated) {
            Response::json(null, 400, 'Update failed or nothing changed');
            return;
        }

        Response::json(['rows_affected' => $updated], 200, 'Patient updated successfully');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE  (admin only)
    // ─────────────────────────────────────────────────────────────────────────
    public static function delete($request, $response, $id)
    {
        $tenantId = $request->get('tenant_id');
        $result   = Patient::softDelete($tenantId, $id);

        Response::json($result, 200, 'Patient deleted successfully');
    }
}