<?php

class NotificationController
{
    public static function getAll($request, $response)
    {
        $tenantId = $request->get('tenant_id');
        $user = $request->get('user');

        // Determine user type based on role
        $userType = self::getUserType($user);

        $notifications = Notification::getAll($tenantId, $user['user_id'], $userType);

        Response::json($notifications, 200, 'Notifications fetched successfully');
    }

    private static function getUserType($user)
    {
        // Map role to user_type for notifications
        $userRole = $user['role'] ?? 'patient';
        
        $staffRoles = ['admin', 'provider', 'pharmacist', 'nurse', 'receptionist'];
        
        return in_array($userRole, $staffRoles) ? 'staff' : 'patient';
    }

    public static function markRead($request, $response, $id)
    {
        $tenantId = $request->get('tenant_id');
        $user = $request->get('user');

        $userType = self::getUserType($user);

        $updated = Notification::markRead($tenantId, $id, $user['user_id'], $userType);

        if ($updated < 1) {
            Response::json(null, 404, 'Notification not found or access denied');
            return;
        }

        Response::json(null, 200, 'Notification marked as read');
    }

    public static function markAllRead($request, $response)
    {
        $tenantId = $request->get('tenant_id');
        $user = $request->get('user');

        $userType = self::getUserType($user);

        $updated = Notification::markAllRead($tenantId, $user['user_id'], $userType);

        Response::json(['updated_count' => $updated], 200, 'All notifications marked as read');
    }

    public static function clearAll($request, $response)
    {
        $tenantId = $request->get('tenant_id');
        $user = $request->get('user');

        $userType = self::getUserType($user);

        $deleted = Notification::clearAll($tenantId, $user['user_id'], $userType);

        Response::json(['deleted_count' => $deleted], 200, 'All notifications cleared');
    }

    public static function broadcast($request, $response)
    {
        $tenantId = $request->get('tenant_id');
        $user = $request->get('user');
        $data = $request->body();

        self::validateBroadcastPayload($data);

        $startsAt = self::normalizeDateTime($data['starts_at'], 'starts_at');
        $endsAt = self::normalizeDateTime($data['ends_at'], 'ends_at');

        if ($startsAt >= $endsAt) {
            Response::json([
                'errors' => [
                    'ends_at' => 'ends_at must be later than starts_at'
                ]
            ], 422, 'Validation failed');
            return;
        }

        $payload = [
            'type' => 'maintenance',
            'title' => trim($data['title']),
            'message' => trim($data['message']),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'audience' => 'staff',
            'reference_id' => null,
        ];

        $recipientCount = Notification::broadcast($tenantId, $user['user_id'], $payload);

        Response::json([
            'type' => $payload['type'],
            'audience' => $payload['audience'],
            'title' => $payload['title'],
            'message' => $payload['message'],
            'starts_at' => $payload['starts_at'],
            'ends_at' => $payload['ends_at'],
            'created_by' => $user['user_id'],
            'recipient_count' => $recipientCount
        ], 201, 'Maintenance notification broadcast successfully');
    }

    private static function validateBroadcastPayload(array $data)
    {
        $errors = [];

        if (trim((string) ($data['type'] ?? '')) !== 'maintenance') {
            $errors['type'] = 'type must be maintenance';
        }

        if (trim((string) ($data['audience'] ?? '')) !== 'staff') {
            $errors['audience'] = 'audience must be staff';
        }

        if (trim((string) ($data['title'] ?? '')) === '') {
            $errors['title'] = 'title is required';
        }

        if (trim((string) ($data['message'] ?? '')) === '') {
            $errors['message'] = 'message is required';
        }

        if (trim((string) ($data['starts_at'] ?? '')) === '') {
            $errors['starts_at'] = 'starts_at is required';
        }

        if (trim((string) ($data['ends_at'] ?? '')) === '') {
            $errors['ends_at'] = 'ends_at is required';
        }

        if (!empty($errors)) {
            Response::json(['errors' => $errors], 422, 'Validation failed');
        }
    }

    private static function normalizeDateTime($value, $field)
    {
        try {
            $dateTime = new DateTime((string) $value);
        } catch (Exception $e) {
            Response::json([
                'errors' => [
                    $field => $field . ' must be a valid datetime'
                ]
            ], 422, 'Validation failed');
        }

        return $dateTime->format('Y-m-d H:i:s');
    }
}
