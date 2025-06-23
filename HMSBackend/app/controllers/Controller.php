<?php

namespace App\Controllers;

use App\Services\ResponseService;
use App\Services\JWTService;
use App\Middleware\AuthMiddleware;
use App\DTO\PaginationDTO;

class Controller
{
    protected AuthMiddleware $authMiddleware;
    protected JWTService $jwtService;

    public function __construct()
    {
        $this->authMiddleware = new AuthMiddleware();
        $this->jwtService = new JWTService();
    }

    /**
     * Get request data from POST body
     */
    protected function getRequestData(): ?array
    {
        try {
            $input = file_get_contents('php://input');
            
            if (empty($input)) {
                ResponseService::Error("Request body is empty", 400);
                return null;
            }
            
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                ResponseService::Error("Invalid JSON in request body: " . json_last_error_msg(), 400);
                return null;
            }
            
            return $data;
        } catch (\Throwable $th) {
            error_log("Exception in getRequestData: " . $th->getMessage());
            ResponseService::Error("Error decoding JSON in request body", 400);
            return null;
        }
    }

    /**
     * Get pagination parameters from query string
     */
    protected function getPaginationParams(): array
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        // Validate page and limit
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        
        return [$page, $limit];
    }

    /**
     * Send paginated response
     */
    protected function sendPaginatedResponse(array $data, int $page, int $limit, int $totalCount, string $dataKey = 'data'): void
    {
        $pagination = new PaginationDTO($page, $limit, $totalCount);
        
        ResponseService::Send([
            $dataKey => $data,
            'pagination' => $pagination->toArray()
        ]);
    }

    /**
     * Handle validation errors consistently
     */
    protected function handleValidationErrors(array $errors, string $prefix = 'Validation failed'): bool
    {
        if (!empty($errors)) {
            ResponseService::Error($prefix . ': ' . implode(', ', $errors), 400);
            return true;
        }
        return false;
    }

    /**
     * Handle not found errors consistently
     */
    protected function handleNotFound(string $resource = 'Resource'): void
    {
        ResponseService::Error($resource . ' not found', 404);
    }

    /**
     * Handle server errors consistently
     */
    protected function handleServerError(string $message, ?\Exception $e = null): void
    {
        if ($e) {
            $this->logError($message, $e);
            
            // In local development, provide more detailed error messages
            if (($_ENV["ENV"] ?? 'LOCAL') === "LOCAL") {
                $errorMsg = $message . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                ResponseService::Error($errorMsg, 500);
                return;
            }
        } else {
            error_log($message);
        }
        
        ResponseService::Error($message, 500);
    }

    /**
     * Handle service responses consistently
     */
    protected function handleServiceResponse(array $serviceResponse, int $successCode = 200): void
    {
        if ($serviceResponse['success']) {
            if (isset($serviceResponse['data'])) {
                ResponseService::Send($serviceResponse['data'], $successCode);
            } else {
                ResponseService::Send(['message' => $serviceResponse['message'] ?? 'Success'], $successCode);
            }
        } else {
            if (isset($serviceResponse['errors'])) {
                $this->handleValidationErrors($serviceResponse['errors']);
            } else {
                ResponseService::Error($serviceResponse['message'] ?? 'Operation failed', 400);
            }
        }
    }

    /**
     * Extract common filters from query parameters
     */
    protected function extractFilters(array $allowedFilters): array
    {
        $filters = [];
        foreach ($allowedFilters as $filter) {
            if (isset($_GET[$filter]) && !empty($_GET[$filter])) {
                $filters[$filter] = $_GET[$filter];
            }
        }
        return $filters;
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
    private function logError(string $message, ?\Exception $e = null): void
    {
        $logFile = __DIR__ . '/../logs/server_errors.log';
        
        // Create a detailed error log
        $errorDetails = [
            'time' => date('Y-m-d H:i:s'),
            'message' => $message,
            'exception' => $e ? get_class($e) : 'N/A',
            'error_message' => $e ? $e->getMessage() : '',
            'code' => $e ? $e->getCode() : 0,
            'file' => $e ? $e->getFile() : '',
            'line' => $e ? $e->getLine() : 0,
            'trace' => $e ? $e->getTraceAsString() : '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'request_body' => file_get_contents('php://input')
        ];
        
        // Log to PHP error log
        error_log($message . ($e ? ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() : ''));
        
        // Log to custom file if possible
        if (is_dir(dirname($logFile))) {
            file_put_contents(
                $logFile, 
                json_encode($errorDetails, JSON_PRETTY_PRINT) . "\n\n", 
                FILE_APPEND
            );
        }
    }

    // Legacy methods for backward compatibility
    protected function decodePostData(): ?array
    {
        return $this->getRequestData();
    }

    public function getAuthenticatedUser()
    {
        try {
            return $this->authMiddleware->authenticate();
        } catch (\Exception $e) {
            ResponseService::Error('Authentication failed', 401);
            return null;
        }
    }

    public function validateIsMe($id)
    {
        try {
            return $this->authMiddleware->requireOwnership((int)$id);
        } catch (\Exception $e) {
            ResponseService::Error('Access denied', 403);
            return null;
        }
    }
}
