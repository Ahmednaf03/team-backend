<?php

class Request{
    private array $attributes = [];

    public function method(): string{
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

public function uri(): string{
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $basePath = '/team-backend';

    if (str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
    }

    return rtrim($uri, '/') ?: '/';
}


    public function query(string $key = null){
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? null;
    }

    public function body(): array{
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $raw = file_get_contents('php://input');
        $cached = json_decode($raw, true) ?? [];

        return $cached;
    }

    public function header(string $key): ?string{
        $headers = getallheaders();
        return $headers[$key] ?? null;
    }

    public function set(string $key, $value): void{
        $this->attributes[$key] = $value;
    }

    public function get(string $key){
        return $this->attributes[$key] ?? null;
    }
}
