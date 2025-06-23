<?php

namespace App\DTO;

class PaginationDTO
{
    public int $page;
    public int $totalPages;
    public int $perPage;
    public int $totalItems;
    public bool $hasNext;
    public bool $hasPrevious;
    
    public function __construct(int $page, int $perPage, int $totalItems)
    {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalItems = $totalItems;
        $this->totalPages = ceil($totalItems / $perPage);
        $this->hasNext = $page < $this->totalPages;
        $this->hasPrevious = $page > 1;
    }
    
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'perPage' => $this->perPage,
            'totalPages' => $this->totalPages,
            'totalItems' => $this->totalItems,
            'hasNext' => $this->hasNext,
            'hasPrevious' => $this->hasPrevious
        ];
    }
}
