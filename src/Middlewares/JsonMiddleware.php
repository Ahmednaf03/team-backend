<?php
// JsonMiddleware.php

class JsonMiddleware
{
    public static function handle($request, $response)
    {
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