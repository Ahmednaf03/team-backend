<?php

class CsrfMiddleware
{
    public static function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // The bulletproof way to grab the header, ignoring browser case quirks
        $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        // Fallback just in case $_SERVER misses it on certain local server setups
        if (!$csrfHeader) {
            $headers = getallheaders();
            // Check the exact casing Axios sends, or lowercase
            $csrfHeader = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
        }

        // 1️⃣ Header missing
        if (!$csrfHeader) {
            Response::json(['error' => 'CSRF token not found'], 403);
            exit; // Stop execution!
        }

        $csrfHeader = trim($csrfHeader);
        
        // 2️⃣ Header empty
        if ($csrfHeader === '') {
            Response::json(['error' => 'CSRF token is missing on header'], 403);
            exit;
        }

        // 3️⃣ Session token missing
        if (!isset($_SESSION['csrf_token'])) {
            Response::json(['error' => 'CSRF not found in session'], 403);
            exit;
        }

        // 4️⃣ Token mismatch
        if (!hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
            Response::json(['error' => 'CSRF validation failed'], 403);
            exit;
        }

        return true;
    }
}