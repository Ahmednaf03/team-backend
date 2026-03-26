<?php

class PaginationHelper
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;

    public static function parse(Request $request, array $filterConfig = []): array
    {
        $page = max(
            self::DEFAULT_PAGE,
            (int) ($request->query('page') ?? self::DEFAULT_PAGE)
        );

        $perPage = (int) ($request->query('per_page') ?? self::DEFAULT_PER_PAGE);
        if ($perPage < 1) {
            $perPage = self::DEFAULT_PER_PAGE;
        }
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $search = trim((string) ($request->query('search') ?? ''));
        $filters = [];

        foreach ($filterConfig as $field => $type) {
            $value = $request->query($field);

            if ($value === null || $value === '') {
                continue;
            }

            if ($type === 'int') {
                if (!is_numeric($value)) {
                    continue;
                }

                $filters[$field] = (int) $value;
                continue;
            }

            $filters[$field] = trim((string) $value);
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search,
            'filters' => $filters,
        ];
    }

    public static function buildMeta(int $totalRecords, int $page, int $perPage): array
    {
        $totalPages = $totalRecords > 0
            ? (int) ceil($totalRecords / $perPage)
            : 0;

        $currentPage = $totalPages > 0
            ? min($page, $totalPages)
            : self::DEFAULT_PAGE;

        return [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'perPage' => $perPage,
        ];
    }
}
