<?php
// CorsMiddleware.php

class CorsMiddleware
{
    public static function handle()
    {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $is_valid_origin = preg_match('/^http:\/\/(.*)\.localhost:3000$/', $origin) || $origin === 'http://localhost:3000';

        if ($is_valid_origin) {
            header("Access-Control-Allow-Origin: " . $origin);
        } else {
            header("Access-Control-Allow-Origin: http://localhost:3000"); 
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN, X-TENANT-ID, X-TENANT-SLUG, X-SUPER-ADMIN");
        header("Access-Control-Allow-Credentials: true");

        // Catch the preflight OPTIONS request and EXIT BEFORE the router runs
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit(); 
        }
    }
}