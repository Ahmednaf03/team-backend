<?php



spl_autoload_register(function ($class) {

    $paths = [
        __DIR__ . '/../src/core/',
        __DIR__ . '/../src/models/',
        __DIR__ . '/../src/controllers/',
        __DIR__ . '/../src/Middlewares/',
        __DIR__ . '/../src/helpers/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
//  Load environment
require_once __DIR__ . '/../src/Config/EnvConfig.php';
loadEnv();
// db connectivity
require_once __DIR__ . '/../src/core/Database.php';
$pdo = Database::connect();

// // Load core classes
// require_once __DIR__ . '/../src/Core/Router.php';
// require_once __DIR__ . '/../src/Core/Request.php';
// require_once __DIR__ . '/../src/Core/Response.php';

// // Load middlewares in index becasue loadRoutes is executed here
// require_once __DIR__ . '/../src/Middlewares/AuthMiddleware.php';
// require_once __DIR__ . '/../src/Middlewares/TenantMiddleware.php';
// require_once __DIR__ . '/../src/Middlewares/RoleMiddleware.php';
// require_once __DIR__ . '/../src/models/ModelFactory.php';

$request = new Request();
$response = new Response();

JsonMiddleware::handle($request, $response);
//  Create router instance
$router = new Router();

// $factory = new ModelFactory($pdo);
//  Load all route files
$router->loadRoutes(__DIR__ . '/../src/Routes', $pdo);

//  Resolve current request
$router->resolve($pdo, $request, $response);
