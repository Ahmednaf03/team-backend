
<?php
class RoleMiddleware
{
    public static function handle($request, $response, array $allowedRoles)
    {
        $user = $request->get('user');
        // var_dump($user);
        // die;
        if (!$user) {
            Response::json(null, 401, 'Unauthorized');
            exit;
        }

        // Super admin bypass
        if (!empty($user['is_super_admin']) && $user['is_super_admin'] === true) {
            return;
        }

        if (!in_array($user['role'], $allowedRoles)) {
            Response::json(null, 403, 'Forbidden');
            exit;
        }
    }
}