<?php

require_once __DIR__ . '/test_helpers.php';

class Notification
{
    public static array $calls = [];
    public static $getAllResult = [];
    public static int $markReadResult = 0;
    public static int $markAllReadResult = 0;
    public static int $clearAllResult = 0;

    public static function reset()
    {
        self::$calls = [];
        self::$getAllResult = [];
        self::$markReadResult = 0;
        self::$markAllReadResult = 0;
        self::$clearAllResult = 0;
    }

    public static function getAll($userId, $userType)
    {
        self::$calls[] = ['method' => 'getAll', 'args' => [$userId, $userType]];
        return self::$getAllResult;
    }

    public static function markRead($id, $userId, $userType)
    {
        self::$calls[] = ['method' => 'markRead', 'args' => [$id, $userId, $userType]];
        return self::$markReadResult;
    }

    public static function markAllRead($userId, $userType)
    {
        self::$calls[] = ['method' => 'markAllRead', 'args' => [$userId, $userType]];
        return self::$markAllReadResult;
    }

    public static function clearAll($userId, $userType)
    {
        self::$calls[] = ['method' => 'clearAll', 'args' => [$userId, $userType]];
        return self::$clearAllResult;
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
    private array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }
}

require_once __DIR__ . '/../src/controllers/NotificationController.php';

runTestCase('NotificationController::getAll maps staff roles to staff notifications', function () {
    Notification::reset();
    Response::reset();
    Notification::$getAllResult = [['id' => 1]];

    $request = new FakeRequest([
        'user' => ['user_id' => 12, 'role' => 'admin'],
    ]);

    NotificationController::getAll($request, null);

    assertSameValue(
        [['method' => 'getAll', 'args' => [12, 'staff']]],
        Notification::$calls,
        'Expected admin users to load staff notifications.'
    );
    assertSameValue(200, Response::$lastJson['status'], 'Expected getAll to return success.');
    assertSameValue('Notifications fetched successfully', Response::$lastJson['message'], 'Expected success message.');
});

runTestCase('NotificationController::getAll defaults missing roles to patient', function () {
    Notification::reset();
    Response::reset();

    $request = new FakeRequest([
        'user' => ['user_id' => 21],
    ]);

    NotificationController::getAll($request, null);

    assertSameValue(
        [['method' => 'getAll', 'args' => [21, 'patient']]],
        Notification::$calls,
        'Expected missing roles to fall back to patient notifications.'
    );
});

runTestCase('NotificationController::markRead returns 404 when no rows were updated', function () {
    Notification::reset();
    Response::reset();
    Notification::$markReadResult = 0;

    $request = new FakeRequest([
        'user' => ['user_id' => 9, 'role' => 'patient'],
    ]);

    NotificationController::markRead($request, null, 77);

    assertSameValue(
        [['method' => 'markRead', 'args' => [77, 9, 'patient']]],
        Notification::$calls,
        'Expected markRead to use patient notification scope.'
    );
    assertSameValue(404, Response::$lastJson['status'], 'Expected missing notifications to return 404.');
});

runTestCase('NotificationController::markRead returns 200 when a notification is updated', function () {
    Notification::reset();
    Response::reset();
    Notification::$markReadResult = 1;

    $request = new FakeRequest([
        'user' => ['user_id' => 5, 'role' => 'nurse'],
    ]);

    NotificationController::markRead($request, null, 18);

    assertSameValue(
        [['method' => 'markRead', 'args' => [18, 5, 'staff']]],
        Notification::$calls,
        'Expected nurse users to be treated as staff.'
    );
    assertSameValue(200, Response::$lastJson['status'], 'Expected successful markRead response.');
    assertSameValue('Notification marked as read', Response::$lastJson['message'], 'Expected success message.');
});

runTestCase('NotificationController::markAllRead returns updated_count', function () {
    Notification::reset();
    Response::reset();
    Notification::$markAllReadResult = 3;

    $request = new FakeRequest([
        'user' => ['user_id' => 41, 'role' => 'provider'],
    ]);

    NotificationController::markAllRead($request, null);

    assertSameValue(
        [['method' => 'markAllRead', 'args' => [41, 'staff']]],
        Notification::$calls,
        'Expected provider users to be treated as staff.'
    );
    assertSameValue(['updated_count' => 3], Response::$lastJson['data'], 'Expected updated_count payload.');
});

runTestCase('NotificationController::clearAll returns deleted_count', function () {
    Notification::reset();
    Response::reset();
    Notification::$clearAllResult = 2;

    $request = new FakeRequest([
        'user' => ['user_id' => 99, 'role' => 'patient'],
    ]);

    NotificationController::clearAll($request, null);

    assertSameValue(
        [['method' => 'clearAll', 'args' => [99, 'patient']]],
        Notification::$calls,
        'Expected patient users to clear only patient notifications.'
    );
    assertSameValue(['deleted_count' => 2], Response::$lastJson['data'], 'Expected deleted_count payload.');
});
