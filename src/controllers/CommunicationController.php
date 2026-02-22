<?php
// require_once __DIR__ . '/../models/Communication.php';
class CommunicationController {


    public static function get($request, $response, $appointmentId) {

        $user = $request->get('user');

        $tenantId = $user['tenant_id'] ?? null;
        $userId   = $user['user_id'] ?? null;

        $messages = Communication::get($tenantId, $appointmentId);

        Response::json($messages, 200, 'Messages fetched successfully');
    }


    public static function getById($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $message = Communication::getById($id, $tenantId);

        if (!$message) {
            Response::json(null, 404, 'Message not found');
            return;
        }

        Response::json($message, 200, 'Message fetched successfully');
    }


    public static function create($request, $response, $appointmentId) {
    // var_dump(class_exists('Communication'));
    // die;

        $user = $request->get('user');

        $tenantId = $user['tenant_id'] ?? null;
        $userId   = $user['user_id'] ?? null;


        $data = $request->body();

        if (empty($data['message'])) {
            Response::json(null, 422, 'Message is required');
            return;
        }

        $created = Communication::create(
            $tenantId,
            $appointmentId,
            $userId,
            $data
        );

        $updatedNotes = Appointment::updateNotes($tenantId, $appointmentId, $data['message']);

        if (!$updatedNotes) {
            Response::json(null, 500, 'Message updation in appointment failed');
            return;
        }

        if ($created) {
            Response::json($created, 201, 'Message created successfully');
            return;
        }

        Response::json(null, 500, 'Message creation failed');
    }


    public static function update($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $updated = Communication::update($tenantId, $id, $request->body());

        if (!$updated) {
            Response::json(null, 400, 'Message update failed');
            return;
        }

        Response::json($updated, 200, 'Message updated successfully');
    }

// soft delete
    public static function delete($request, $response, $id) {

        $tenantId = $request->get('tenant_id');

        $deleted = Communication::softDelete($tenantId, $id);

        if (!$deleted) {
            Response::json(null, 400, 'Message deletion failed');
            return;
        }

        Response::json($deleted, 200, 'Message deleted successfully');
    }

}
