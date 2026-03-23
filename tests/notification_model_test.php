<?php

require_once __DIR__ . '/test_helpers.php';

class FakePdoStatement
{
    public array $executedWith = [];
    public array $fetchAllResult = [];
    public int $rowCountValue = 0;

    public function execute(array $params)
    {
        $this->executedWith[] = $params;
        return true;
    }

    public function fetchAll($mode)
    {
        return $this->fetchAllResult;
    }

    public function rowCount()
    {
        return $this->rowCountValue;
    }
}

class FakePdoConnection
{
    public array $preparedSql = [];
    public FakePdoStatement $statement;
    public string $lastInsertIdValue = '0';

    public function __construct()
    {
        $this->statement = new FakePdoStatement();
    }

    public function prepare($sql)
    {
        $this->preparedSql[] = $sql;
        return $this->statement;
    }

    public function lastInsertId()
    {
        return $this->lastInsertIdValue;
    }
}

class DatabaseManager
{
    public static array $tenantCalls = [];
    public static array $connections = [];

    public static function tenant($tenantId)
    {
        self::$tenantCalls[] = $tenantId;
        return self::$connections[$tenantId];
    }
}

require_once __DIR__ . '/../src/models/Notification.php';

runTestCase('Notification::getAll fetches notifications for request tenant and user context', function () {
    $_REQUEST['tenant_id'] = 7;

    $connection = new FakePdoConnection();
    $connection->statement->fetchAllResult = [
        ['id' => 11, 'title' => 'Appointment reminder'],
    ];
    DatabaseManager::$connections[7] = $connection;
    DatabaseManager::$tenantCalls = [];

    $result = Notification::getAll(3, 'staff');

    assertSameValue([7], DatabaseManager::$tenantCalls, 'Expected tenant-specific connection to be used.');
    assertContainsText('FROM notifications', $connection->preparedSql[0], 'Expected notifications query to be prepared.');
    assertSameValue([[3, 'staff']], $connection->statement->executedWith, 'Expected user filters to be passed to getAll.');
    assertSameValue($connection->statement->fetchAllResult, $result, 'Expected all fetched notifications to be returned.');
});

runTestCase('Notification::markRead returns affected row count', function () {
    $_REQUEST['tenant_id'] = 8;

    $connection = new FakePdoConnection();
    $connection->statement->rowCountValue = 1;
    DatabaseManager::$connections[8] = $connection;

    $updated = Notification::markRead(14, 5, 'patient');

    assertSameValue([[14, 5, 'patient']], $connection->statement->executedWith, 'Expected markRead to pass id, user_id and user_type.');
    assertSameValue(1, $updated, 'Expected markRead to return the number of affected rows.');
});

runTestCase('Notification::markAllRead returns affected row count', function () {
    $_REQUEST['tenant_id'] = 9;

    $connection = new FakePdoConnection();
    $connection->statement->rowCountValue = 4;
    DatabaseManager::$connections[9] = $connection;

    $updated = Notification::markAllRead(6, 'staff');

    assertSameValue([[6, 'staff']], $connection->statement->executedWith, 'Expected markAllRead to filter by user.');
    assertSameValue(4, $updated, 'Expected markAllRead to return the number of updated notifications.');
});

runTestCase('Notification::clearAll returns affected row count', function () {
    $_REQUEST['tenant_id'] = 10;

    $connection = new FakePdoConnection();
    $connection->statement->rowCountValue = 2;
    DatabaseManager::$connections[10] = $connection;

    $deleted = Notification::clearAll(7, 'patient');

    assertSameValue([[7, 'patient']], $connection->statement->executedWith, 'Expected clearAll to delete by user context.');
    assertSameValue(2, $deleted, 'Expected clearAll to return deleted rows.');
});

runTestCase('Notification::create inserts notification and falls back to null reference_id', function () {
    unset($_REQUEST['tenant_id']);

    $connection = new FakePdoConnection();
    $connection->lastInsertIdValue = '42';
    DatabaseManager::$connections[1] = $connection;
    DatabaseManager::$tenantCalls = [];

    $id = Notification::create([
        'user_id' => 4,
        'user_type' => 'staff',
        'type' => 'appointment',
        'title' => 'New booking',
        'message' => 'Patient booked a visit',
    ]);

    assertSameValue([1, 1], DatabaseManager::$tenantCalls, 'Expected create to use the default tenant connection and fetch the insert id.');
    assertSameValue(
        [[4, 'staff', 'appointment', 'New booking', 'Patient booked a visit', null]],
        $connection->statement->executedWith,
        'Expected create to insert a null reference_id when it is omitted.'
    );
    assertSameValue('42', $id, 'Expected create to return the last insert id.');
});
