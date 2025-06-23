<?php

namespace App\Services;

class ResponseService
{
    /**
     * Send a JSON response with appropriate header and status
     * 
     * @param mixed $data Data to be JSON encoded and sent
     * @param int $status HTTP status code
     */
    static function Send($data, $status = 200)
    {
        // Check if headers have already been sent
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data);
        exit();
    }

    /**
     * Send JSON error response with status
     * 
     * @param string $error Error message
     * @param int $status HTTP status code
     */
    static function Error($error = "An error occurred", $status = 500)
    {
        // Log all errors to our custom log file
        $logFile = __DIR__ . '/../logs/api_errors.log';
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $error . ' - Status: ' . $status . ' - ' . 
                    (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'Unknown URI');
        
        // Add request body to log for debugging
        $requestBody = file_get_contents('php://input');
        if (!empty($requestBody)) {
            $logMessage .= ' - Request: ' . $requestBody;
        }
        
        // Add debug trace for server errors
        if ($status >= 500) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $logMessage .= "\nTrace: " . json_encode($trace);
        }
        
        // Write to log file
        if (is_dir(dirname($logFile))) {
            file_put_contents($logFile, $logMessage . "\n", FILE_APPEND);
        }
        
        // Also log to PHP error log
        error_log($logMessage);
        
        self::Send(["error" => $error], $status);
    }

    /**
     * Set CORS headers to allow cross-origin requests
     */
    static function SetCorsHeaders()
    {
        // Allow requests from Vue.js development servers
        $allowed_origins = [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174'
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            // Fallback for development - allow all origins
            header("Access-Control-Allow-Origin: *");
        }
        
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");

        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}
