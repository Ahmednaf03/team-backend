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
        
        // provided by tenant middleware
        $tenantId = $request->get('tenant_id');

        if (!$tenantId) {
            Response::json(null, 400, 'Tenant missing');
            return;
        }

        $data['tenant_id'] = $tenantId;

        $userId = User::create($tenantId, $data);

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

        $headers = getallheaders();

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN LOGIN
        |--------------------------------------------------------------------------
        */
        if (!empty($headers['X-SUPER-ADMIN'])) {

            $admin = Auth::findSuperAdminByEmail($data['email']);

            if (!$admin || !password_verify($data['password'], $admin['password_hash'])) {
                Response::json(null, 401, 'Invalid credentials');
                return;
            }

            $accessToken = JWT::generateAccessToken([
                'user_id'        => $admin['id'],
                'tenant_id'      => null,
                'role'           => null,
                'is_super_admin' => true
            ]);

            Response::json([
                'access_token' => $accessToken
            ], 200, 'Super admin login successful');

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | TENANT USER LOGIN
        |--------------------------------------------------------------------------
        */

        $tenantId = $request->get('tenant_id');

        if (!$tenantId) {
            Response::json(null, 400, 'Tenant missing');
            return;
        }

        $hashedEmail = Encryption::blindIndex($data['email']);

        $user = Auth::findUserByEmail($tenantId, $hashedEmail);

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            Response::json(null, 401, 'Invalid credentials');
            return;
        }else{
        //check if the credential match in patiets table 
        // $user = Auth::findPatientByEmail($tenantId, $hashedEmail);
        }

       

        if ($user['status'] !== 'active') {
            Response::json(null, 401, 'Account not active');
            return;
        }

        $accessToken = JWT::generateAccessToken([
            'user_id'        => $user['id'],
            'tenant_id'      => $tenantId,
            'role'           => $user['role'],
            'is_super_admin' => false
        ]);

        // 1. Generate the RAW token
        $refreshToken = bin2hex(random_bytes(64));
        
        // 2. Pass the RAW token to the model (The model will hash it before saving)
        Auth::createRefreshToken(
            $tenantId,
            $user['id'],
            $refreshToken
        );

        // 3. Put the RAW token in the cookie so it matches what password_verify expects
        setcookie("refresh_token", $refreshToken, [
            'expires'  => time() + $_ENV['REFRESH_EXPIRY'],
            'path'     => '/',
            'httponly' => true,
            'secure'   => false,
            'samesite' => 'Lax'
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
        
        // Grab the Tenant ID from middleware or request
        $tenantId = $request->tenant_id ?? $request->get('tenant_id'); 
        
        if (!$tenantId) {
            Response::json(null, 400, 'Missing Tenant ID');
            return;
        }

        // Verify the raw cookie against the hashed database tokens
        $token = Auth::findValidRefreshToken($tenantId, $_COOKIE['refresh_token']);

        if (!$token) {
            Response::json(null, 401, 'Invalid refresh token');
            return;
        }

        Auth::deleteRefreshToken($tenantId, $token['id']);

        $user = Auth::getUserById($tenantId, $token['user_id']);

        $accessToken = JWT::generateAccessToken([
            'user_id'   => $user['id'],
            'tenant_id' => $tenantId,
            'role'      => $user['role']
        ]);

        $_SESSION['access_token'] = $accessToken;

        // 1. Generate the new RAW token
        $newRefresh = bin2hex(random_bytes(64));

        // 2. Pass the RAW token to the model to be hashed and saved
        Auth::createRefreshToken(
            $tenantId, 
            $user['id'],        
            $newRefresh
        );

        // 3. Set the RAW token in the new cookie
        setcookie("refresh_token", $newRefresh, [
            'expires'  => time() + $_ENV['REFRESH_EXPIRY'],
            'path'     => '/',
            'httponly' => true,
            'secure'   => false,
            'samesite' => 'Lax'
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
        $tenantId = $request->get('tenant_id');

        if (!$user) {
            Response::json(null, 401, 'Unauthorized');
            return;
        }

        $data = $request->body();

        if (!isset($data['old_password'], $data['new_password'])) {
            Response::json(null, 400, 'Missing fields');
            return;
        }

        $dbUser = Auth::getUserById($tenantId, $user['user_id']);

        if (!password_verify($data['old_password'], $dbUser['password_hash'])) {
            Response::json(null, 401, 'Old password incorrect');
            return;
        }

        $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);

        Auth::updatePassword($tenantId, $user['user_id'], $newHash);

        Response::json(null, 200, 'Password changed successfully');
    }

  public static function logout($request, $response)
    {
        // 1. Grab the decoded user payload
        $user = $request->get('user');
        $tenantId = $user['tenant_id'] ?? $request->get('tenant_id');

        // 2. Revoke the token in the database
        if ($tenantId && isset($_COOKIE['refresh_token'])) {
            $token = Auth::findValidRefreshToken($tenantId, $_COOKIE['refresh_token']);
            
            if ($token) {
                Auth::deleteRefreshToken($tenantId, $token['id']);
            }
        }

        // 3. Destroy the cookie in the browser
        setcookie("refresh_token", "", time() - 3600, "/");

        // 4. Destroy the PHP Session
        $_SESSION = [];
        session_unset();
        session_destroy();

        Response::json(null, 200, 'Logged out successfully');
    }



    public static function patientLogin($request, $response)
    {
        $data = $request->body();
 
        if (!isset($data['email'], $data['password'])) {
            Response::json(null, 400, 'Email and password required');
            return;
        }
 
        $tenantId = $request->get('tenant_id');
 
        if (!$tenantId) {
            Response::json(null, 400, 'Tenant missing');
            return;
        }
 
        // Blind-index the incoming email exactly like the staff login does
        $hashedEmail = Encryption::blindIndex($data['email']);
 
        $patient = Auth::findPatientByEmail($tenantId, $hashedEmail);
 
        if (!$patient || !password_verify($data['password'], $patient['password_hash'])) {
            Response::json(null, 401, 'Invalid credentials');
            return;
        }
 
        if ($patient['status'] !== 'active') {
            Response::json(null, 401, 'Account not active');
            return;
        }
 
        // Issue access token — role is always 'patient'
        $accessToken = JWT::generateAccessToken([
            'user_id'        => $patient['id'],
            'tenant_id'      => $tenantId,
            'role'           => 'patient',
            'is_super_admin' => false,
        ]);
 
        // Refresh token — reuses the same refresh_tokens table
        $refreshToken = bin2hex(random_bytes(64));
 
        Auth::createRefreshToken($tenantId, $patient['id'], $refreshToken);
 
        setcookie('refresh_token', $refreshToken, [
            'expires'  => time() + $_ENV['REFRESH_EXPIRY'],
            'path'     => '/',
            'httponly' => true,
            'secure'   => false,
            'samesite' => 'Lax',
        ]);
 
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
 
        Response::json([
            'access_token' => $accessToken,
            'csrf_token'   => $_SESSION['csrf_token'],
        ], 200, 'Patient login successful');
    }
}