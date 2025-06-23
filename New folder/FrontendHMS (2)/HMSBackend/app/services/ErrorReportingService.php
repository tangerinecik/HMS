<?php

namespace App\Services;

class ErrorReportingService
{
    // initialize error reporting based on environment
    static function Init()
    {
        if ($_ENV["ENV"] === "LOCAL") {
            // Report all errors but don't display them to prevent headers already sent issues
            error_reporting(E_ALL);
            ini_set('display_errors', 0);  // Don't display errors to browser
            ini_set('display_startup_errors', 0);
            ini_set('log_errors', 1);  // Still log errors to error log
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            ini_set('log_errors', '1');
        }
    }
}
