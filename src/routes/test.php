<?php

$router->get('/api/test', function ($request, $response) {
    //  $request->set('user', [
    //     'user_id'   => 1,
    //     'tenant_id' => 2,
    //     'role'      => 'admin'
    // ]);

    // TenantMiddleware::handle($request,$response);
    // RoleMiddleware::handle($request,$response, ['admin']);
    

require_once '../src/helpers/Encryption.php';

echo "Name: " . Encryption::encrypt("John Doe") . PHP_EOL;
echo "Age: " . Encryption::encrypt("30") . PHP_EOL;
echo "Gender: " . Encryption::encrypt("Male") . PHP_EOL;
echo "Phone: " . Encryption::encrypt("1234567890") . PHP_EOL;
echo "Address: " . Encryption::encrypt("Chennai") . PHP_EOL;
echo "Diagnosis: " . Encryption::encrypt("Flu") . PHP_EOL;

    // $response->json([
    //     'status' => 'ok',
    //     'method' => $request->method(),
    //     'uri' => $request->uri(),
    //     'tenant_id' => $request->get('tenant_id'),
    //     'message' => 'Middleware working',
    //     'user' => $request->get('user')
    // ]);
});
