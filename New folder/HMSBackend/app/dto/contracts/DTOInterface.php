<?php

namespace App\DTO\Contracts;

/**
 * Interface for all DTO objects
 * Enforces consistent behavior across DTOs
 */
interface DTOInterface
{
    /**
     * Create DTO from array data
     */
    public static function fromArray(array $data): self;

    /**
     * Convert DTO to array
     */
    public function toArray(): array;

    /**
     * Validate DTO data
     */
    public function validate(): array;
}

/**
 * Interface for DTOs that support validation context
 */
interface ValidatableDTO extends DTOInterface
{
    /**
     * Validate with context (useful for update vs create scenarios)
     */
    public function validateWithContext(string $context = 'create'): array;
}
