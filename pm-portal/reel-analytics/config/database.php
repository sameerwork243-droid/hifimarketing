<?php
// =============================================
// DATABASE CONFIGURATION - USING MAIN CONFIG
// =============================================

namespace Config;

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        // =============================================
        // USE THE EXACT ABSOLUTE PATH
        // =============================================
        $mainConfigPath = '/home/sites/32a/2/2fb0787974/public_html/includes/config.php';
        
        if (!file_exists($mainConfigPath)) {
            die("Main config file not found at: " . $mainConfigPath);
        }
        
        // Load the main config
        require_once $mainConfigPath;
        
        // Now use the $conn from the main config
        global $conn;
        
        if (isset($conn) && $conn) {
            $this->conn = $conn;
            return;
        }
        
        // If $conn is not set, try to create a new connection
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbname = defined('DB_NAME') ? DB_NAME : 'hifiwebsite-313031aed2';
        $username = defined('DB_USER') ? DB_USER : 'hifiweb_313031aed2';
        $password = defined('DB_PASS') ? DB_PASS : 'Hew0ieTdH!@#';
        
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $this->conn = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (\PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
}
?>