<?php


error_log("INDEX.PHP HIT — method: " . $_SERVER['REQUEST_METHOD']);
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
CorsMiddleware::handle();
//  Load environment
require_once __DIR__ . '/../src/Config/EnvConfig.php';
loadEnv();
// db connectivity
require_once __DIR__ . '/../src/core/Database.php';
$pdo = Database::connect();


$request = new Request();
$response = new Response();

JsonMiddleware::handle($request, $response);
//  Create router instance
$router = new Router();


$router->loadRoutes(__DIR__ . '/../src/Routes', $pdo);

//  Resolve current request
$router->resolve($pdo, $request, $response);
