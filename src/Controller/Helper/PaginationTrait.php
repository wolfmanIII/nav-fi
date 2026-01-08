<?php

namespace App\Controller\Helper;

trait PaginationTrait
{
    /**
     * @return array<int, int|null>
     */
    protected function buildPagination(int $current, int $totalPages): array
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
}
