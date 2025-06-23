<?php

namespace App\DTO;

class PaginationDTO extends BaseDTO
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

    public static function fromArray(array $data): self
    {
        return new self(
            $data['page'] ?? 1,
            $data['perPage'] ?? 10,
            $data['totalItems'] ?? 0
        );
    }

    public function validate(): array
    {
        return $this->collectErrors([
            fn() => $this->validateNumericRange($this->page, 1, 9999, 'page'),
            fn() => $this->validateNumericRange($this->perPage, 1, 100, 'perPage'),
        ]);
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
