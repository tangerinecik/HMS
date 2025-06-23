<?php

namespace App\Services;

/**
 * Centralized logging service for the application
 */
class LoggerService
{
    private string $logDirectory;
    
    public function __construct()
    {
        $this->logDirectory = __DIR__ . '/../logs';
        $this->ensureLogDirectoryExists();
    }

    public function logError(string $message, int $status, ?array $details = null): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'ERROR',
            'message' => $message,
            'status' => $status,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip' => $this->getClientIp(),
            'details' => $details
        ];

        $this->writeToLog('api_errors.log', $logData);
    }

    public function logValidationError(array $errors): void
    {
        $this->logError('Validation failed', 422, ['validation_errors' => $errors]);
    }

    public function logInfo(string $message, ?array $context = null): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'INFO',
            'message' => $message,
            'context' => $context
        ];

        $this->writeToLog('app.log', $logData);
    }

    public function logDebug(string $message, ?array $context = null): void
    {
        if (($_ENV['ENV'] ?? 'production') === 'LOCAL') {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => 'DEBUG',
                'message' => $message,
                'context' => $context
            ];

            $this->writeToLog('debug.log', $logData);
        }
    }

    private function writeToLog(string $filename, array $data): void
    {
        $logFile = $this->logDirectory . '/' . $filename;
        $logEntry = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    private function ensureLogDirectoryExists(): void
    {
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }
    }

    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
