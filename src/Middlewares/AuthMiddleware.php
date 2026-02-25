<?php

class AuthMiddleware
{
    public static function handle($request, $response)
    {
        $headers = getallheaders();

        if (empty($headers['Authorization'])) {
            Response::json(null, 401, 'Access token missing');
            exit;
        }

        $authHeader = $headers['Authorization'];

        if (!str_starts_with($authHeader, 'Bearer ')) {
            Response::json(null, 401, 'Invalid access token format');
            exit;
        }

        $token = trim(str_replace('Bearer', '', $authHeader));

        try {
            $payload = JWT::verify($token);
        } catch (Exception $e) {
            Response::json(null, 401, 'Invalid or expired access token');
            exit;
        }

        // Inject into request (NOT session)
        $request->set('user', [
            'user_id'   => $payload['user_id'],
            'tenant_id' => $payload['tenant_id'],
            'role'      => $payload['role'],
            'is_super_admin' => $payload['is_super_admin']
        ]);

        return $payload;
    }
}
