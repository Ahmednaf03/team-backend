<?php

class Router{
    /*
    |--------------------------------------------------------------------------
    | Route Storage
    |--------------------------------------------------------------------------
    |
    | This array stores all registered routes.
    | Structure looks like:
    |
    | [
    |   'GET' => [
    |       '/api/patients' => callable,
    |   ],
    |   'POST' => [
    |       '/api/login' => callable,
    |   ]
    | ]
    |
    */
    protected array $routes = [];


    /*
    |--------------------------------------------------------------------------
    | Register GET Route
    |--------------------------------------------------------------------------
    */
    public function get(string $uri, callable $handler): void{
        $this->routes['GET'][$uri] = $handler;
    }

    // post
    public function post(string $uri, callable $handler): void{
        $this->routes['POST'][$uri] = $handler;
    }


    /*
    |--------------------------------------------------------------------------
    | Register PUT Route
    |--------------------------------------------------------------------------
    */
    public function put(string $uri, callable $handler): void{
        $this->routes['PUT'][$uri] = $handler;
    }


    /*
    |--------------------------------------------------------------------------
    | Register DELETE Route
    |--------------------------------------------------------------------------
    */
    public function delete(string $uri, callable $handler): void{
        $this->routes['DELETE'][$uri] = $handler;
    }


    /*
    Load All Route Files

    */
    public function loadRoutes(string $routesPath): void{
        // Get all PHP files inside Routes folder
        $files = glob($routesPath . '/*.php');

        // Include each file
        foreach ($files as $file) {
            require $file;
            $router = $this;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Current Request
    |--------------------------------------------------------------------------
    |
    | This method:
    | 1. Reads current HTTP method (GET, POST, etc.)
    | 2. Reads current URI
    | 3. Finds matching route
    | 4. Executes its handler
    | 5. Returns 404 if not found
    |
    */
    public function resolve($request, $response): void{
        // Get current HTTP method (GET, POST, etc.)
        $method = $request->method();

        // Get current request URI without query parameters
        $uri = parse_url($request->uri(), PHP_URL_PATH);
    //    var_dump($this->routes);
    //     exit;


        // Normalize trailing slash:
        $uri = rtrim($uri, '/') ?: '/';

        // Try to find matching handler
        $handler = $this->routes[$method][$uri] ?? null;

        // If route not found → return 404
        if (!$handler) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Route not found'
            ]);
            return;
        }

        // Execute the stored callback
        // This will run middleware + controller
        call_user_func($handler,$request,$response);
    }
}
