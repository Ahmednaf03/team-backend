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
    | $routes =[
    |   'GET' => [
    |       '/api/patients' => callable,
    |       '/api/appintments/upcoming' => callable
    |   ],
    |   'POST' => [
    |       '/api/login' => callable,
    |   ]
    | ]
    |
    */
    protected array $routes = [];


    /* once load routes run this function will get
     all routes and store them in $routes array with
     with their handlers
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

      public function patch(string $uri, callable $handler): void{
        $this->routes['PATCH'][$uri] = $handler;
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
    public function loadRoutes(string $routesPath, $pdo): void{
        // Get all PHP files inside Routes folder
        $files = glob($routesPath . '/*.php');
        $router = $this;

        // Include each file
        foreach ($files as $file) {
            require $file;
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
    public function resolve($pdo,$request, $response): void{
        // Get current HTTP method (GET, POST, etc.)
        $method = $request->method();

        // Get current request URI without query parameters
        $uri = parse_url($request->uri(), PHP_URL_PATH);
    //    var_dump($this->routes);
    //     exit;


        // Normalize trailing slash:
        $uri = rtrim($uri, '/') ?: '/';

        // Try to find matching handler
       $handler = null;

    foreach ($this->routes[$method] ?? [] as $route => $h) {
    if ($uri === $route || str_starts_with($uri, $route . '/')) {
        $handler = $h;
        break;
    }
}


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
        call_user_func($handler,$request,$response,$pdo);
    }
}
