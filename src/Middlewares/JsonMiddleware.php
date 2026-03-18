<?php

class JsonMiddleware
{
    public static function handle($request, $response)
    {
        // CORS
       $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// 2. Validate the Origin (Highly recommended for production)
// This regex allows any subdomain on localhost:3000
// For production, change this to match your actual domain e.g., '/^https:\/\/(.*)\.yourdomain\.com$/'
$is_valid_origin = preg_match('/^http:\/\/(.*)\.localhost:3000$/', $origin) || $origin === 'http://localhost:3000';

if ($is_valid_origin) {
    // 3. Echo the specific origin back to satisfy the browser
    header("Access-Control-Allow-Origin: " . $origin);
} else {
    // Fallback (will fail browser credential checks, which is what you want for invalid origins)
    header("Access-Control-Allow-Origin: http://localhost:3000"); 
}

// 4. Set the rest of your headers
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN, X-TENANT-ID, X-TENANT-SLUG, X-SUPER-ADMIN");
header("Access-Control-Allow-Credentials: true");

// 5. Catch the preflight OPTIONS request and exit cleanly
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(); 
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
