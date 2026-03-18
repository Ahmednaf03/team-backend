<?php

class TenantController
{
    public static function getAll($request, $response)
    {
        $tenants = Tenant::getAll();
        Response::json($tenants, 200, 'Tenants fetched successfully');
    }


public static function getResolve($request, $response)
    {
        // 1. Extract the header using your custom method.
        // (Note: HTTP headers sent by Axios are usually title-cased or lower-cased, 
        // so checking 'X-Tenant-Slug' usually matches what React sends).
        $slug = $request->header('X-Tenant-Slug') ?? $request->header('X-TENANT-SLUG');

        if (!$slug) {
            Response::json(['error' => 'Missing tenant slug header'], 400);
            return;
        }

        // 2. Query the model to find the specific tenant
        $tenant = Tenant::getResolveBySlug($slug);

        // 3. Handle the "Workspace Not Found" scenario
        if (!$tenant) {
            Response::json(['error' => 'Workspace not found or inactive'], 404);
            return;
        }

        // 4. Return the safe public data (No DB credentials!)
        Response::json($tenant, 200, 'Tenant resolved successfully');
    }

    public static function create($request, $response)
    {
        $data = $request->body();

        $required = [
            'name',
            'slug',
            'email',
            'address_line1',
            'city',
            'state',
            'country'
        ];
        // ensure all fields are present
        foreach ($required as $field) {
            if (empty($data[$field])) {
                Response::json(null, 422, "$field is required");
                return;
            }
        }

        // Generate isolated DB credentials
        $slug = strtolower($data['slug']);

        $dbName       = "tenant_$slug";
        $dbUser       = "user_$slug";
        $dbPassPlain  = bin2hex(random_bytes(16));

        $data['db_name']           = $dbName;
        $data['db_user']           = $dbUser;
        $data['db_pass_encrypted'] = Encryption::encrypt($dbPassPlain);

 $master = DatabaseManager::master();

try {

    //  Insert tenant into master DB
    $tenantId = Tenant::create($master, $data);

    if (!$tenantId) {
        throw new Exception("Tenant insert failed");
    }

    // Provision database (DDL auto-commit safe)
    $master->exec("CREATE DATABASE `$dbName`");

    $master->exec("DROP USER IF EXISTS '$dbUser'@'localhost'");

    $master->exec("
        CREATE USER '$dbUser'@'localhost'
        IDENTIFIED BY '$dbPassPlain'
    ");

    $master->exec("
        GRANT ALL PRIVILEGES
        ON `$dbName`.*
        TO '$dbUser'@'localhost'
    ");

    $master->exec("FLUSH PRIVILEGES");

    self::runSchema($dbName);

} catch (Throwable $e) {

    // Manual cleanup
    try {
        $master->exec("DROP DATABASE IF EXISTS `$dbName`");
        $master->exec("DROP USER IF EXISTS '$dbUser'@'localhost'");
        $master->prepare("DELETE FROM tenants WHERE slug = ?")
               ->execute([$data['slug']]);
    } catch (Throwable $cleanupException) {
        // ignore
    }

    Response::json([
        'error' => $e->getMessage()
    ], 500, 'Tenant provisioning failed');

    return;
}

        Response::json([
            'tenant_id' => $tenantId,
            'db_name'   => $dbName
        ], 201, 'Tenant created successfully');
    }

    private static function runSchema($dbName)
    {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=$dbName",
            $_ENV['DB_USER'],   // master root user
            $_ENV['DB_PASS']
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $schemaPath = __DIR__ . '/../schema/sass.sql';

        if (!file_exists($schemaPath)) {
            throw new Exception("Schema file not found");
        }

        $schema = file_get_contents($schemaPath);

        if (!$schema) {
            throw new Exception("Schema file empty");
        }

        $pdo->exec($schema);
    }
}