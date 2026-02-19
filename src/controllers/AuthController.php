<?php

require_once __DIR__ . '/../Models/Auth.php';

session_start();
class AuthController
{

public static function signup($request, $response)
{
    $data = $request->body();

    if (
        empty($data['name']) ||
        empty($data['email']) ||
        empty($data['password']) ||
        empty($data['role'])
    ) {
        Response::json(null, 422, 'Missing required fields');
        return;
    }

    $tenantId = $request->get('tenant_id');

    if (!$tenantId) {
        Response::json(null, 400, 'Tenant missing');
        return;
    }

    $data['tenant_id'] = $tenantId;

    $userId = User::create($data);

    if (!$userId) {
        Response::json(null, 500, 'User creation failed');
        return;
    }

    Response::json([
        'user_id' => $userId
    ], 201, 'User created successfully');
}


    public static function login($request, $response)
    {
        $data = $request->body();

        if (!isset($data['email'], $data['password'])) {
            Response::json(null, 400, 'Email and password required');
            return;
        }
        // in login route the tenant_id is passed as a header
        $tenantId = $request->get('tenant_id');

        if (!$tenantId) {
            Response::json(null, 400, 'Tenant missing');
            return;
        }
        $hashedEmail = Encryption::blindIndex($data['email']);
        $user = Auth::findUserByEmail($hashedEmail, $tenantId);

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            Response::json(null, 401, 'Invalid credentials');
            return;
        }

        $accessToken = JWT::generateAccessToken([
            'user_id'   => $user['id'],
            'tenant_id' => $user['tenant_id'],
            'role'      => $user['role']
        ]);

        $_SESSION['access_token'] = $accessToken;

        $refreshToken = bin2hex(random_bytes(64));

        Auth::createRefreshToken(
            $user['id'],
            $user['tenant_id'],
            $refreshToken
        );

        setcookie("refresh_token", $refreshToken, [
            'expires'  => time() + (60*60*24*7),
            'path'     => '/',
            'httponly' => true,
            'secure'   => false,
            'samesite' => 'Strict'
        ]);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        Response::json([
            'access_token' => $accessToken,
            'csrf_token'   => $_SESSION['csrf_token']
        ], 200, 'Login successful');
    }


    public static function refresh($request, $response)
    {
        if (!isset($_COOKIE['refresh_token'])) {
            Response::json(null, 401, 'No refresh token');
            return;
        }

        $token = Auth::findValidRefreshToken($_COOKIE['refresh_token']);

        if (!$token) {
            Response::json(null, 401, 'Invalid refresh token');
            return;
        }

        Auth::deleteRefreshToken($token['id']);

        $user = Auth::getUserById($token['user_id']);

        $accessToken = JWT::generateAccessToken([
            'user_id'   => $user['id'],
            'tenant_id' => $user['tenant_id'],
            'role'      => $user['role']
        ]);

        $_SESSION['access_token'] = $accessToken;

        $newRefresh = bin2hex(random_bytes(64));

        Auth::createRefreshToken(
            $user['id'],
            $user['tenant_id'],
            $newRefresh
        );

        setcookie("refresh_token", $newRefresh, [
            'expires'  => time() + (60*60*24*7),
            'path'     => '/',
            'httponly' => true,
            'secure'   => false,
            'samesite' => 'Strict'
        ]);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        Response::json([
            'access_token' => $accessToken,
            'csrf_token'   => $_SESSION['csrf_token']
        ], 200, 'Token refreshed');
    }


    public static function changePassword($request, $response)
    {
        $user = $request->get('user');

        if (!$user) {
            Response::json(null, 401, 'Unauthorized');
            return;
        }

        $data = $request->body();

        if (!isset($data['old_password'], $data['new_password'])) {
            Response::json(null, 400, 'Missing fields');
            return;
        }

        $dbUser = Auth::getUserById($user['user_id']);

        if (!password_verify($data['old_password'], $dbUser['password_hash'])) {
            Response::json(null, 401, 'Old password incorrect');
            return;
        }

        $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);

        Auth::updatePassword($user['user_id'], $newHash);

        Response::json(null, 200, 'Password changed successfully');
    }


    public static function logout($request, $response)
    {
        if (isset($_COOKIE['refresh_token'])) {

            $token = Auth::findValidRefreshToken($_COOKIE['refresh_token']);

            if ($token) {
                Auth::deleteRefreshToken($token['id']);
            }
        }

        setcookie("refresh_token", "", time() - 3600, "/");

        $_SESSION = [];
        session_unset();
        session_destroy();

        Response::json(null, 200, 'Logged out successfully');
    }
}
