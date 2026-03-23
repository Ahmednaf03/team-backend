<?php

require_once __DIR__ . '/test_helpers.php';

class Router
{
    public array $routes = [];

    public function get(string $uri, callable $handler): void
    {
        $this->routes['GET'][$uri] = $handler;
    }

    public function patch(string $uri, callable $handler): void
    {
        $this->routes['PATCH'][$uri] = $handler;
    }

    public function delete(string $uri, callable $handler): void
    {
        $this->routes['DELETE'][$uri] = $handler;
    }
}

class AuthMiddleware
{
    public static array $calls = [];

    public static function handle($request, $response)
    {
        self::$calls[] = 'auth';
    }
}

class CSRFMiddleware
{
    public static array $calls = [];

    public static function handle($request, $response)
    {
        self::$calls[] = 'csrf';
    }
}

class TenantMiddleware
{
    public static array $calls = [];

    public static function handle($request, $response)
    {
        self::$calls[] = 'tenant';
    }
}

class RoleMiddleware
{
    public static array $calls = [];

    public static function handle($request, $response, array $allowedRoles)
    {
        self::$calls[] = $allowedRoles;
    }
}

class NotificationController
{
    public static array $calls = [];

    public static function getAll($request, $response)
    {
        self::$calls[] = ['method' => 'getAll'];
    }

    public static function markRead($request, $response, $id)
    {
        self::$calls[] = ['method' => 'markRead', 'id' => $id];
    }

    public static function markAllRead($request, $response)
    {
        self::$calls[] = ['method' => 'markAllRead'];
    }

    public static function clearAll($request, $response)
    {
        self::$calls[] = ['method' => 'clearAll'];
    }
}

class Response
{
    public static array $lastJson = [];

    public static function reset()
    {
        self::$lastJson = [];
    }

    public static function json($data, int $status = 200, $message = null): void
    {
        self::$lastJson = [
            'data' => $data,
            'status' => $status,
            'message' => $message,
        ];
    }
}

class FakeRequest
{
    private string $uri;

    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    public function uri()
    {
        return $this->uri;
    }
}

function resetRouteState()
{
    AuthMiddleware::$calls = [];
    CSRFMiddleware::$calls = [];
    TenantMiddleware::$calls = [];
    RoleMiddleware::$calls = [];
    NotificationController::$calls = [];
    Response::reset();
}

$router = new Router();
require __DIR__ . '/../src/routes/notifications.php';

$expectedRoles = ['admin', 'provider', 'pharmacist', 'staff', 'patient'];

runTestCase('Notification routes register all expected endpoints', function () use ($router) {
    assertTrueValue(isset($router->routes['GET']['/api/notifications']), 'Expected GET /api/notifications route.');
    assertTrueValue(isset($router->routes['PATCH']['/api/notifications/read']), 'Expected PATCH /api/notifications/read route.');
    assertTrueValue(isset($router->routes['PATCH']['/api/notifications/read-all']), 'Expected PATCH /api/notifications/read-all route.');
    assertTrueValue(isset($router->routes['DELETE']['/api/notifications']), 'Expected DELETE /api/notifications route.');
});

runTestCase('GET /api/notifications applies middleware and delegates to controller', function () use ($router, $expectedRoles) {
    resetRouteState();

    $handler = $router->routes['GET']['/api/notifications'];
    $handler(new FakeRequest('/api/notifications'), null);

    assertSameValue(['auth'], AuthMiddleware::$calls, 'Expected auth middleware to run.');
    assertSameValue(['csrf'], CSRFMiddleware::$calls, 'Expected CSRF middleware to run.');
    assertSameValue(['tenant'], TenantMiddleware::$calls, 'Expected tenant middleware to run.');
    assertSameValue([$expectedRoles], RoleMiddleware::$calls, 'Expected notification route roles.');
    assertSameValue([['method' => 'getAll']], NotificationController::$calls, 'Expected controller delegation.');
});

runTestCase('PATCH /api/notifications/read extracts the notification id from the URI', function () use ($router, $expectedRoles) {
    resetRouteState();

    $handler = $router->routes['PATCH']['/api/notifications/read'];
    $handler(new FakeRequest('/api/notifications/read/55'), null);

    assertSameValue([$expectedRoles], RoleMiddleware::$calls, 'Expected role check before markRead.');
    assertSameValue([['method' => 'markRead', 'id' => '55']], NotificationController::$calls, 'Expected URI id to be forwarded to controller.');
});

runTestCase('PATCH /api/notifications/read returns 400 when the id segment is missing', function () use ($router) {
    resetRouteState();

    $handler = $router->routes['PATCH']['/api/notifications/read'];
    $handler(new FakeRequest('/api/notifications/read'), null);

    assertSameValue(400, Response::$lastJson['status'], 'Expected missing ids to return 400.');
    assertSameValue('Notification ID required', Response::$lastJson['message'], 'Expected validation message.');
    assertSameValue([], NotificationController::$calls, 'Expected controller to be skipped when id is missing.');
});

runTestCase('PATCH /api/notifications/read-all delegates to NotificationController::markAllRead', function () use ($router) {
    resetRouteState();

    $handler = $router->routes['PATCH']['/api/notifications/read-all'];
    $handler(new FakeRequest('/api/notifications/read-all'), null);

    assertSameValue([['method' => 'markAllRead']], NotificationController::$calls, 'Expected markAllRead delegation.');
});

runTestCase('DELETE /api/notifications delegates to NotificationController::clearAll', function () use ($router) {
    resetRouteState();

    $handler = $router->routes['DELETE']['/api/notifications'];
    $handler(new FakeRequest('/api/notifications'), null);

    assertSameValue([['method' => 'clearAll']], NotificationController::$calls, 'Expected clearAll delegation.');
});
