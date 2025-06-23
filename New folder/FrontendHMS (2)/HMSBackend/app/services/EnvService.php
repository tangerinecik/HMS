<?php

namespace App\Services;

class EnvService
{
    // initialize env variables
    static function Init()
    {
        // Load .env file if it exists
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue; // Skip comments
                }
                
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    $_ENV[$key] = $value;
                }
            }
        }

        // Fallback values if not set in .env
        $_ENV["DB_HOST"] = $_ENV["DB_HOST"] ?? "mysql";
        $_ENV["DB_NAME"] = $_ENV["DB_NAME"] ?? "developmentdb";
        $_ENV["DB_USER"] = $_ENV["DB_USER"] ?? "user";
        $_ENV["DB_PASSWORD"] = $_ENV["DB_PASSWORD"] ?? "password";
        $_ENV["DB_CHARSET"] = $_ENV["DB_CHARSET"] ?? "utf8mb4";
        $_ENV["ENV"] = $_ENV["ENV"] ?? "LOCAL";
        $_ENV["JWT_SECRET"] = $_ENV["JWT_SECRET"] ?? "8RXVjZIyszZEZSyb6h2C6xdNnH3FD2eh";
        
        // Email settings
        $_ENV["FROM_EMAIL"] = $_ENV["FROM_EMAIL"] ?? "abughanim.bogdan@gmail.com";
        $_ENV["FROM_NAME"] = $_ENV["FROM_NAME"] ?? "Hotel Fortuna";
        $_ENV["FRONTEND_URL"] = $_ENV["FRONTEND_URL"] ?? "http://localhost:5174";
    }
}
