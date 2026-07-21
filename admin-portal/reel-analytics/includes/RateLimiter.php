<?php
// =============================================
// RATE LIMITER CLASS
// =============================================

namespace Includes;

use Config\Database;

class RateLimiter {
    private $db;
    private $ip;
    private $limitPerMinute;
    private $limitPerHour;
    private $lockFile;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ip = $this->getClientIP();
        $this->limitPerMinute = RATE_LIMIT_PER_MINUTE;
        $this->limitPerHour = RATE_LIMIT_PER_HOUR;
        $this->lockFile = LOG_PATH . '/rate_limit_' . md5($this->ip) . '.lock';
    }
    
    private function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        return $ip;
    }
    
    public function checkRateLimit() {
        // Check minute limit
        $minuteQuery = "SELECT COUNT(*) as count FROM rate_limits 
                        WHERE ip_address = ? 
                        AND last_request >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        $stmt = $this->db->prepare($minuteQuery);
        $stmt->execute([$this->ip]);
        $minuteCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        
        if ($minuteCount >= $this->limitPerMinute) {
            return [
                'allowed' => false,
                'message' => 'Rate limit exceeded. Please wait a minute.',
                'limit' => $this->limitPerMinute,
                'current' => $minuteCount,
                'reset_in' => 60
            ];
        }
        
        // Check hour limit
        $hourQuery = "SELECT COUNT(*) as count FROM rate_limits 
                      WHERE ip_address = ? 
                      AND last_request >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->db->prepare($hourQuery);
        $stmt->execute([$this->ip]);
        $hourCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        
        if ($hourCount >= $this->limitPerHour) {
            return [
                'allowed' => false,
                'message' => 'Rate limit exceeded. Please wait an hour.',
                'limit' => $this->limitPerHour,
                'current' => $hourCount,
                'reset_in' => 3600
            ];
        }
        
        // Log the request
        $insertQuery = "INSERT INTO rate_limits (ip_address, request_count, last_request) 
                        VALUES (?, 1, NOW()) 
                        ON DUPLICATE KEY UPDATE 
                        request_count = request_count + 1, 
                        last_request = NOW()";
        $stmt = $this->db->prepare($insertQuery);
        $stmt->execute([$this->ip]);
        
        return [
            'allowed' => true,
            'message' => 'Request allowed',
            'limit_minute' => $this->limitPerMinute,
            'limit_hour' => $this->limitPerHour,
            'current_minute' => $minuteCount + 1,
            'current_hour' => $hourCount + 1
        ];
    }
    
    public function getRateLimitStatus() {
        $minuteQuery = "SELECT COUNT(*) as count FROM rate_limits 
                        WHERE ip_address = ? 
                        AND last_request >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        $stmt = $this->db->prepare($minuteQuery);
        $stmt->execute([$this->ip]);
        $minuteCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        
        $hourQuery = "SELECT COUNT(*) as count FROM rate_limits 
                      WHERE ip_address = ? 
                      AND last_request >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->db->prepare($hourQuery);
        $stmt->execute([$this->ip]);
        $hourCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        
        return [
            'ip' => $this->ip,
            'minute' => [
                'current' => $minuteCount,
                'limit' => $this->limitPerMinute,
                'remaining' => max(0, $this->limitPerMinute - $minuteCount)
            ],
            'hour' => [
                'current' => $hourCount,
                'limit' => $this->limitPerHour,
                'remaining' => max(0, $this->limitPerHour - $hourCount)
            ]
        ];
    }
    
    public function resetRateLimit() {
        $sql = "DELETE FROM rate_limits WHERE ip_address = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$this->ip]);
    }
}
?>