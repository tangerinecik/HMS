<?php

namespace App\Controllers;

use App\Services\ServiceContainer;
use App\Services\Interfaces\ResponseServiceInterface;
use App\Services\JWTService;
use App\Middleware\AuthMiddleware;
use App\DTO\PaginationDTO;

/**
 * Base controller providing common functionality for all controllers
 * Uses dependency injection for better testability and maintainability
 */
class Controller
{
    protected AuthMiddleware $authMiddleware;
    protected JWTService $jwtService;
    protected ResponseServiceInterface $responseService;
    protected ServiceContainer $container;

    public function __construct()
    {
        $this->container = ServiceContainer::getInstance();
        $this->container->bootstrap(); // Ensure services are registered
        $this->authMiddleware = $this->container->resolve('auth_middleware');
        $this->jwtService = $this->container->resolve('jwt_service');
        $this->responseService = $this->container->resolve('response');
    }

    /**
     * Parse and validate JSON request body
     */
    protected function getRequestData(): ?array
    {
        try {
            $input = file_get_contents('php://input');
            
            if (empty($input)) {
                $this->responseService->sendError("Request body is empty", 400);
                return null;
            }
            
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->responseService->sendError("Invalid JSON: " . json_last_error_msg(), 400);
                return null;
            }
            
            return $data;
        } catch (\Throwable $e) {
            error_log("Request parsing error: " . $e->getMessage());
            $this->responseService->sendError("Error parsing request", 400);
            return null;
        }
    }

    /**
     * Get pagination parameters with validation
     */
    protected function getPaginationParams(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
        
        return ['page' => $page, 'limit' => $limit];
    }

    /**
     * Send paginated response
     */
    protected function sendPaginatedResponse(array $data, int $page, int $limit, int $totalCount, string $dataKey = 'data'): void
    {
        $pagination = new PaginationDTO($page, $limit, $totalCount);
        
        $this->responseService->sendSuccess([
            $dataKey => $data,
            'pagination' => $pagination->toArray()
        ]);
    }

    /**
     * Map array of items to DTOs efficiently
     */
    protected function mapToDTOs(array $items, string $dtoClass): array
    {
        return array_map(fn($item) => $dtoClass::fromArray($item)->toArray(), $items);
    }

    /**
     * Get query parameters with default values
     */
    protected function getQueryParams(array $defaults = []): array
    {
        $params = [];
        foreach ($defaults as $key => $default) {
            $params[$key] = $_GET[$key] ?? $default;
        }
        return $params;
    }

    /**
     * Validate required query parameters
     */
    protected function validateQueryParams(array $required): bool
    {
        $missing = [];
        foreach ($required as $param) {
            if (!isset($_GET[$param]) || empty($_GET[$param])) {
                $missing[] = $param;
            }
        }
        
        if (!empty($missing)) {
            $this->responseService->sendError('Missing required parameters: ' . implode(', ', $missing), 400);
            return false;
        }
        
        return true;
    }

    /**
     * Handle validation errors
     */
    protected function handleValidationErrors(array $errors): bool
    {
        if (!empty($errors)) {
            $this->responseService->sendValidationError($errors);
            return true;
        }
        return false;
    }

    /**
     * Handle not found errors
     */
    protected function handleNotFound(string $resource = 'Resource'): void
    {
        $this->responseService->sendNotFound($resource);
    }

    /**
     * Handle server errors
     */
    protected function handleServerError(string $message, ?\Exception $e = null): void
    {
        if ($e) {
            error_log($message . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            
            // Show detailed errors only in development
            if (($_ENV["ENV"] ?? 'production') === "LOCAL") {
                $this->responseService->sendError($message . ': ' . $e->getMessage(), 500);
                return;
            }
        } else {
            error_log($message);
        }
        
        $this->responseService->sendError($message, 500);
    }

    /**
     * Check resource ownership or admin access
     */
    protected function checkResourceAccess(object $user, int $resourceUserId): bool
    {
        return $user->role === 'admin' || $user->id === $resourceUserId;
    }

    /**
     * Extract filter parameters from request
     */
    protected function extractFilters(array $allowedFilters = []): array
    {
        $filters = [];
        foreach ($allowedFilters as $filter) {
            if (isset($_GET[$filter])) {
                $filters[$filter] = $_GET[$filter];
            }
        }
        return $filters;
    }

    /**
     * Filter data to only include allowed fields
     */
    protected function filterAllowedFields(array $data, array $allowedFields): array
    {
        $filtered = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $filtered[$field] = $data[$field];
            }
        }
        return $filtered;
    }

    /**
     * Generate reference code for entities
     */
    protected function generateRefCode(string $prefix = 'REF'): string
    {
        return strtoupper($prefix) . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
