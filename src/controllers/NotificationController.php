<?php

class NotificationController
{
    public static function getAll($request, $response)
    {
        $user = $request->get('user');

        // Determine user type based on role
        $userType = self::getUserType($user);

        $notifications = Notification::getAll($user['user_id'], $userType);

        Response::json($notifications, 200, 'Notifications fetched successfully');
    }

    private static function getUserType($user)
    {
        // Map role to user_type for notifications
        $userRole = $user['role'] ?? 'patient';
        
        $staffRoles = ['admin', 'provider', 'pharmacist', 'nurse'];
        
        return in_array($userRole, $staffRoles) ? 'staff' : 'patient';
    }

    public static function markRead($request, $response, $id)
    {
        $user = $request->get('user');

        $userType = self::getUserType($user);

        $updated = Notification::markRead($id, $user['user_id'], $userType);

        if ($updated < 1) {
            Response::json(null, 404, 'Notification not found or access denied');
            return;
        }

        Response::json(null, 200, 'Notification marked as read');
    }

    public static function markAllRead($request, $response)
    {
        $user = $request->get('user');

        $userType = self::getUserType($user);

        $updated = Notification::markAllRead($user['user_id'], $userType);

        Response::json(['updated_count' => $updated], 200, 'All notifications marked as read');
    }

    public static function clearAll($request, $response)
    {
        $user = $request->get('user');

        $userType = self::getUserType($user);

        $deleted = Notification::clearAll($user['user_id'], $userType);

        Response::json(['deleted_count' => $deleted], 200, 'All notifications cleared');
    }
}
