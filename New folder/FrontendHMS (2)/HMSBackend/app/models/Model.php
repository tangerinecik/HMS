<?php

namespace App\Models;

/**
 * NOTE! this base model handles initializing PDO for PostgreSQL
 * 
 * To use PDO in a derived class, use self::$pdo
 */

class Model
{
    protected static $pdo;

    function __construct()
    {
        if (!self::$pdo) {
            $host = $_ENV["DB_HOST"];
            $port = $_ENV["DB_PORT"] ?? '3306';
            $db = $_ENV["DB_NAME"];
            $user = $_ENV["DB_USER"];
            $pass = $_ENV["DB_PASSWORD"];

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            self::$pdo = new \PDO($dsn, $user, $pass, $options);
        }
    }
}
