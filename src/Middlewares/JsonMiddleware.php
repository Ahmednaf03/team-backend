<?php

class JsonMiddleware
{
    public static function handle($request, $response)
    {
        // CORS
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN, X-TENANT-ID");
        header("Access-Control-Allow-Credentials: true");

        // Preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Enforce JSON for write operations
        $method = $_SERVER['REQUEST_METHOD'];

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {

            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/json') === false) {
                Response::json(null, 415, 'Content-Type must be application/json');
                exit;
            }
        }
    }
}
