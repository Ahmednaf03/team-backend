<?php

//  Load environment
require_once __DIR__ . '/../src/Config/EnvConfig.php';
loadEnv();
// db connectivity
require_once __DIR__ . '/../src/core/Database.php';
$pdo = Database::connect();

// Load core classes
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Core/Request.php';
require_once __DIR__ . '/../src/Core/Response.php';

$request = new Request();
$response = new Response();
//  Create router instance
$router = new Router();

//  Load all route files
$router->loadRoutes(__DIR__ . '/../src/Routes');

//  Resolve current request
$router->resolve($request, $response);
