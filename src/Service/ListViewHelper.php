<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

final class ListViewHelper
{
    /**
     * Retourne il valore da utilizzare per la pagina richiesta.
     */
    public function getPage(Request $request, string $param = 'page'): int
    {
        $page = (int) $request->query->get($param, 1);
        if ($page < 1) {
            return 1;
        }

        return $page;
    }

    /**
     * @param array<string, string|array<string, mixed>> $definitions
     */
    public function collectFilters(Request $request, array $definitions): array
    {
        $filters = [];

        foreach ($definitions as $key => $definition) {
            if (!\is_string($key)) {
                $key = (string) $definition;
                $definition = [];
            }

            $definition = \is_array($definition) ? $definition : [];
            $param = $definition['param'] ?? $key;
            $type = $definition['type'] ?? 'string';
            $rawValue = $request->query->get($param, '');

            $filters[$key] = $this->normalizeFilterValue($rawValue, $type);
        }

        return $filters;
    }

    public function clampPage(int $current, int $totalPages): int
    {
        $totalPages = max(1, $totalPages);

        if ($current < 1) {
            return 1;
        }

        if ($current > $totalPages) {
            return $totalPages;
        }

        return $current;
    }

    /**
     * @return array<int, int|null>
     */
    private function buildPages(int $current, int $totalPages): array
    {
        if ($totalPages <= 1) {
            return [1];
        }

        if ($totalPages <= 7) {
            return range(1, $totalPages);
        }

        $pages = [1];
        $windowStart = max(2, $current - 2);
        $windowEnd = min($totalPages - 1, $current + 2);

        if ($windowStart > 2) {
            $pages[] = null;
        }

        for ($i = $windowStart; $i <= $windowEnd; $i++) {
            $pages[] = $i;
        }

        if ($windowEnd < $totalPages - 1) {
            $pages[] = null;
        }

        $pages[] = $totalPages;

        return $pages;
    }

    /**
     * @return array{
     *     current: int,
     *     total: int,
     *     per_page: int,
     *     total_pages: int,
     *     pages: array<int, int|null>,
     *     from: int,
     *     to: int
     * }
     */
    public function buildPaginationPayload(int $current, int $perPage, int $total): array
    {
        $perPage = max(1, $perPage);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $current = $this->clampPage($current, $totalPages);

        return [
            'current' => $current,
            'total' => $total,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'pages' => $this->buildPages($current, $totalPages),
            'from' => $total > 0 ? (($current - 1) * $perPage) + 1 : 0,
            'to' => $total > 0 ? min($current * $perPage, $total) : 0,
        ];
    }

    /**
     * @param string|int|float|null $rawValue
     * @return int|null|string
     */
    private function normalizeFilterValue(mixed $rawValue, string $type): mixed
    {
        if ($type === 'int') {
            $value = trim((string) $rawValue);
            if ($value === '' || !ctype_digit($value)) {
                return null;
            }

            return (int) $value;
        }

        if ($type === 'float') {
            $value = trim((string) $rawValue);
            if ($value === '') {
                return null;
            }

            return is_numeric($value) ? (float) $value : null;
        }

        return trim((string) $rawValue);
    }
}
