<?php


class RoleMiddleware{

    public static function handle($request,$response, array $allowedRoles){

        $user = $request->get('user');

        if (!$user || !in_array($user['role'], $allowedRoles)) {
            $response->json(['error' => 'Unauthorized role'], 403);
            exit;
        }
    }
}