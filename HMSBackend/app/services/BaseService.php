<?php

namespace App\Services;

use App\Services\ResponseService;

abstract class BaseService
{
    /**
     * Validate data using DTO
     */
    protected function validateDTO($dto): array
    {
        if (method_exists($dto, 'validate')) {
            return $dto->validate();
        }
        return [];
    }

    /**
     * Handle validation errors
     */
    protected function hasValidationErrors(array $errors): bool
    {
        return !empty($errors);
    }

    /**
     * Convert array of data to DTOs
     */
    protected function mapToDTOs(array $data, string $dtoClass): array
    {
        return array_map(function ($item) use ($dtoClass) {
            return $dtoClass::fromArray($item)->toArray();
        }, $data);
    }

    /**
     * Log error for debugging
     */
    protected function logError(string $message, ?\Exception $e = null): void
    {
        $errorMsg = $message;
        if ($e) {
            $errorMsg .= ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        error_log($errorMsg);
    }
}
