<?php

declare(strict_types=1);

namespace App\Helpers;

class Paginator
{
    public readonly int $currentPage;
    public readonly int $perPage;
    public readonly int $total;
    public readonly int $totalPages;
    public readonly int $offset;

    public function __construct(int $total, int $perPage = 20, int $currentPage = 1)
    {
        $this->total       = max(0, $total);
        $this->perPage     = max(1, $perPage);
        $this->totalPages  = (int) ceil($this->total / $this->perPage);
        $this->currentPage = max(1, min($currentPage, max(1, $this->totalPages)));
        $this->offset      = ($this->currentPage - 1) * $this->perPage;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function previousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    public function nextPage(): int
    {
        return min($this->totalPages, $this->currentPage + 1);
    }
}
