<?php

class CsrfMiddleware
{
    public static function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $headers = getallheaders();

        // 1️⃣ Header missing
        if (!isset($headers['X-CSRF-TOKEN'])) {
            Response::json(['error' => 'CSRF token not found'], 403);
        }

        $csrfHeader = trim($headers['X-CSRF-TOKEN']);
        
        // 2️⃣ Header empty
        if ($csrfHeader === '') {
            Response::json(['error' => 'CSRF token is missing on header'], 403);
        }

        // 3️⃣ Session token missing
        if (!isset($_SESSION['csrf_token'])) {
            Response::json(['error' => 'CSRF not found in session'], 403);
        }

        // 4️⃣ Token mismatch
        if (!hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
            Response::json(['error' => 'CSRF validation failed'], 403);
        }

        return true;
    }
}
