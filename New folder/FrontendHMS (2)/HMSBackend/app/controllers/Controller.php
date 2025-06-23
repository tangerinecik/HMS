<?php

// base controller, this is a good place to put shared functionality like authentication/authorization, validation, etc

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

    // ensures all expected fields are set in data object and sends a bad request response if not
    // used to make sure all expected $_POST fields are at least set, additional validation may still need to be set
    protected function validateInput($expectedFields, $data)
    {
        foreach ($expectedFields as $field) {
            if (!isset($data[$field])) {
                ResponseService::Send("Required field: $field, is missing", 400);
                exit();
            }
        }
    }

    // gets the post data and returns it as an array
    protected function decodePostData()
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
            error_log("Exception in decodePostData: " . $th->getMessage());
            ResponseService::Error("Error decoding JSON in request body", 400);
            return null;
        }
    }

    // Common pagination handling
    protected function getPaginationParams(): array
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        // Validate page and limit
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        
        return [$page, $limit];
    }

    // Common response with pagination
    protected function sendPaginatedResponse(array $data, int $page, int $limit, int $totalCount, string $dataKey = 'data')
    {
        $pagination = new PaginationDTO($page, $limit, $totalCount);
        
        ResponseService::Send([
            $dataKey => $data,
            'pagination' => $pagination->toArray()
        ]);
    }

    // Handle validation errors consistently
    protected function handleValidationErrors(array $errors, string $prefix = 'Validation failed')
    {
        if (!empty($errors)) {
            ResponseService::Error($prefix . ': ' . implode(', ', $errors), 400);
            return true;
        }
        return false;
    }

    // Handle not found errors consistently
    protected function handleNotFound(string $resource = 'Resource')
    {
        ResponseService::Error($resource . ' not found', 404);
    }

    // Handle server errors consistently
    protected function handleServerError(string $message, ?\Exception $e = null)
    {
        if ($e) {
            $logFile = __DIR__ . '/../logs/server_errors.log';
            
            // Create a detailed error log
            $errorDetails = [
                'time' => date('Y-m-d H:i:s'),
                'message' => $message,
                'exception' => get_class($e),
                'error_message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'request_body' => file_get_contents('php://input')
            ];
            
            // Log to PHP error log
            error_log($message . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            
            // Log to custom file if possible
            if (is_dir(dirname($logFile))) {
                file_put_contents(
                    $logFile, 
                    json_encode($errorDetails, JSON_PRETTY_PRINT) . "\n\n", 
                    FILE_APPEND
                );
            }
            
            // In local development, provide more detailed error messages
            if ($_ENV["ENV"] === "LOCAL") {
                $errorMsg = $message . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
                ResponseService::Error($errorMsg, 500);
                return;
            }
        } else {
            error_log($message);
        }
        
        ResponseService::Error($message, 500);
    }

    // Legacy methods for backward compatibility - consider using AuthMiddleware instead
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
