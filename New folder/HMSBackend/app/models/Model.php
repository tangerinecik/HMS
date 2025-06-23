<?php

namespace App\Models;

use App\Services\LoggerService;

/**
 * Base model class with improved error handling and logging
 * Provides connection management and common utilities for all models
 */
abstract class Model
{
    protected static ?\PDO $pdo = null;
    protected LoggerService $logger;

    public function __construct()
    {
        $this->logger = new LoggerService();
        $this->initializeConnection();
    }

    /**
     * Initialize database connection with proper error handling
     */
    private function initializeConnection(): void
    {
        if (self::$pdo === null) {
            try {
                $host = $_ENV["DB_HOST"] ?? 'localhost';
                $port = $_ENV["DB_PORT"] ?? '3306';
                $db = $_ENV["DB_NAME"] ?? '';
                $user = $_ENV["DB_USER"] ?? '';
                $pass = $_ENV["DB_PASSWORD"] ?? '';

                if (empty($db) || empty($user)) {
                    throw new \InvalidArgumentException('Database configuration is incomplete');
                }

                $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
                $options = [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];

                self::$pdo = new \PDO($dsn, $user, $pass, $options);
                
                // Test connection
                self::$pdo->query('SELECT 1');
                
            } catch (\PDOException $e) {
                $this->logger->logError('Database connection failed', 500, [
                    'error' => $e->getMessage(),
                    'host' => $host,
                    'database' => $db
                ]);
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Execute a prepared statement with error logging
     */
    protected function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            $this->logger->logError('Database query failed', 500, [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Database query failed: ' . $e->getMessage());
        }
    }

    /**
     * Begin database transaction
     */
    protected function beginTransaction(): void
    {
        self::$pdo->beginTransaction();
    }

    /**
     * Commit database transaction
     */
    protected function commit(): void
    {
        self::$pdo->commit();
    }

    /**
     * Rollback database transaction
     */
    protected function rollback(): void
    {
        self::$pdo->rollBack();
    }

    /**
     * Get the last inserted ID
     */
    protected function getLastInsertId(): int
    {
        return (int) self::$pdo->lastInsertId();
    }

    /**
     * Check if a record exists
     */
    protected function recordExists(string $table, string $column, mixed $value): bool
    {
        $sql = "SELECT 1 FROM {$table} WHERE {$column} = :value LIMIT 1";
        $stmt = $this->executeQuery($sql, ['value' => $value]);
        return $stmt->rowCount() > 0;
    }
}
