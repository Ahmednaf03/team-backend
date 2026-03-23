<?php

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new Exception(
            $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)
        );
    }
}

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        throw new Exception($message);
    }
}

function assertContainsText($needle, $haystack, $message)
{
    if (strpos($haystack, $needle) === false) {
        throw new Exception($message . "\nMissing: " . $needle . "\nIn: " . $haystack);
    }
}

function runTestCase($name, callable $test)
{
    try {
        $test();
        echo "[PASS] {$name}" . PHP_EOL;
    } catch (Throwable $e) {
        echo "[FAIL] {$name}" . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
        exit(1);
    }
}
