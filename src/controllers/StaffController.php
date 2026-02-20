<?php

class StaffController {

    public static function get($request, $response) {

        $tenantId = $request->get('tenant_id');

        $staff = Staff::getAll($tenantId);

        Response::json($staff, 200, 'Staff fetched successfully');
    }

    public static function getById($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $staff = Staff::getById($tenantId, $id);

        if (!$staff) {
            Response::json(null, 404, 'Staff not found');
            return;
        }

        Response::json($staff, 200, 'Staff fetched successfully');
    }

    public static function create($request, $response) {

        $tenantId = $request->get('tenant_id');
        $data     = $request->body();

        if (empty($data['name']) || empty($data['role'])) {
            Response::json(null, 422, 'Missing required fields');
            return;
        }

        $created = Staff::create($tenantId, $data);

        if (!$created) {
            Response::json(null, 400, 'Staff creation failed');
            return;
        }

        Response::json(
            ['id' => $created],
            201,
            'Staff created successfully'
        );
    }

    public static function update($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        if (!Staff::exists($tenantId, $id)) {
            Response::json(null, 404, 'Staff not found');
            return;
        }

        $updated = Staff::update($tenantId, $id, $request->body());

        if (!$updated) {
            Response::json(null, 400, 'Update failed');
            return;
        }

        Response::json($updated, 200, 'Staff updated successfully');
    }

    public static function delete($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        if (!Staff::exists($tenantId, $id)) {
            Response::json(null, 404, 'Staff not found');
            return;
        }

        $deleted = Staff::delete($tenantId, $id);

        if (!$deleted) {
            Response::json(null, 400, 'Delete failed');
            return;
        }

        Response::json($deleted, 200, 'Staff deleted successfully');
    }
}