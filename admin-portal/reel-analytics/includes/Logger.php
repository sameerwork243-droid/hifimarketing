<?php
// =============================================
// LOGGER CLASS
// =============================================

namespace Includes;

use Config\Database;

class Logger {
    private $db;
    private $logDir;
    private $debugMode;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logDir = LOG_PATH;
        $this->debugMode = DEBUG_MODE;
        
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }
    
    /**
     * Log API request/response
     */
    public function logAPI($apiName, $endpoint, $requestData, $responseData, $responseTime, $statusCode) {
        try {
            $sql = "INSERT INTO api_logs (api_name, endpoint, request_data, response_data, response_time, status_code, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $apiName,
                $endpoint,
                json_encode($requestData),
                json_encode($responseData),
                $responseTime,
                $statusCode,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // Also write to file if debug mode is enabled
            if ($this->debugMode) {
                $this->writeToFile("API", [
                    'api' => $apiName,
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'time' => $responseTime . 'ms'
                ]);
            }
        } catch (\Exception $e) {
            // Silent fail for logging
            error_log("Logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log error
     */
    public function logError($message, $context = []) {
        try {
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => $message,
                'context' => $context,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
            $this->writeToFile("ERROR", $logEntry);
            
            if ($this->debugMode) {
                error_log(json_encode($logEntry));
            }
        } catch (\Exception $e) {
            error_log("Error logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log user activity
     */
    public function logActivity($userId, $action, $details = null) {
        try {
            $sql = "INSERT INTO user_activity (user_id, action, details, ip_address) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $userId,
                $action,
                json_encode($details),
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (\Exception $e) {
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Write to log file
     */
    private function writeToFile($type, $data) {
        try {
            $filename = $this->logDir . '/' . date('Y-m-d') . '_' . strtolower($type) . '.log';
            $logLine = '[' . date('Y-m-d H:i:s') . '] ' . json_encode($data) . PHP_EOL;
            file_put_contents($filename, $logLine, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Silent fail
        }
    }
    
    /**
     * Get log files
     */
    public function getLogFiles() {
        $files = [];
        if (is_dir($this->logDir)) {
            $scanned = scandir($this->logDir);
            foreach ($scanned as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $files[] = [
                        'name' => $file,
                        'size' => filesize($this->logDir . '/' . $file),
                        'modified' => filemtime($this->logDir . '/' . $file)
                    ];
                }
            }
        }
        return $files;
    }
    
    /**
     * Get recent API logs
     */
    public function getRecentApiLogs($limit = 100) {
        try {
            $sql = "SELECT * FROM api_logs ORDER BY created_at DESC LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }
}
?>