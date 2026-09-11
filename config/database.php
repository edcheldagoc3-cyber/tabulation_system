<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_DATABASE'] ?? 'ustp_tabulation';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $this->conn = new PDO($dsn, $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->conn;
    }
}