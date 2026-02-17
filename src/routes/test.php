<?php

$router->get('/api/test', function ($request, $response) {
    $response->json([
        'status' => 'ok',
        'method' => $request->method(),
        'uri' => $request->uri()
    ]);
});
