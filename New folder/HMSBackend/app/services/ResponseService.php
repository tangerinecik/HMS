<?php

namespace App\Services;

use App\Services\Interfaces\ResponseServiceInterface;

/**
 * Improved ResponseService following OOP principles
 * - Uses dependency injection
 * - Implements proper logging
 * - Better error categorization
 */
class ResponseService implements ResponseServiceInterface
{
    private LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }

    public function sendSuccess(mixed $data, int $status = 200): void
    {
        $this->sendResponse($data, $status);
    }

    public function sendError(string $message, int $status = 500, ?array $details = null): void
    {
        $errorData = ['error' => $message];
        if ($details) {
            $errorData['details'] = $details;
        }
        
        $this->logger->logError($message, $status, $details);
        $this->sendResponse($errorData, $status);
    }

    public function sendValidationError(array $errors): void
    {
        $this->logger->logValidationError($errors);
        $this->sendResponse([
            'error' => 'Validation failed',
            'validation_errors' => $errors
        ], 422);
    }

    public function sendNotFound(string $resource = 'Resource'): void
    {
        $this->sendError("{$resource} not found", 404);
    }

    public function sendUnauthorized(string $message = 'Unauthorized'): void
    {
        $this->sendError($message, 401);
    }

    public function sendForbidden(string $message = 'Access denied'): void
    {
        $this->sendError($message, 403);
    }

    private function sendResponse(mixed $data, int $status): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
}
