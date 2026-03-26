<?php

require_once __DIR__ . '/test_helpers.php';
require_once __DIR__ . '/../src/core/Request.php';
require_once __DIR__ . '/../src/helpers/PaginationHelper.php';

runTestCase('PaginationHelper parses query params and builds metadata', function () {
    $_GET = [
        'page' => '3',
        'per_page' => '5',
        'search' => 'pending',
        'status' => 'PENDING',
        'doctor_id' => '12',
    ];

    $request = new Request();

    $parsed = PaginationHelper::parse($request, [
        'status' => 'string',
        'doctor_id' => 'int',
    ]);

    assertSameValue(3, $parsed['page'], 'Expected page to be parsed.');
    assertSameValue(5, $parsed['per_page'], 'Expected per_page to be parsed.');
    assertSameValue('pending', $parsed['search'], 'Expected search to be parsed.');
    assertSameValue('PENDING', $parsed['filters']['status'], 'Expected string filter to be parsed.');
    assertSameValue(12, $parsed['filters']['doctor_id'], 'Expected int filter to be parsed.');

    $meta = PaginationHelper::buildMeta(47, 3, 5);

    assertSameValue(3, $meta['currentPage'], 'Expected current page metadata.');
    assertSameValue(10, $meta['totalPages'], 'Expected total pages metadata.');
    assertSameValue(47, $meta['totalRecords'], 'Expected total records metadata.');
    assertSameValue(5, $meta['perPage'], 'Expected per-page metadata.');
});
