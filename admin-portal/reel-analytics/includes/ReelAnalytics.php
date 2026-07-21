<?php
// =============================================
// REEL ANALYTICS MODEL - FINAL WORKING
// =============================================

namespace Includes;

class ReelAnalytics {
    private $db;
    
    public function __construct() {
        $mainConfigPath = '/home/sites/32a/2/2fb0787974/public_html/includes/config.php';
        if (file_exists($mainConfigPath)) {
            require_once $mainConfigPath;
        }
        
        global $conn;
        
        if (!isset($conn) || !$conn) {
            throw new \Exception("Database connection not available");
        }
        
        $this->db = $conn;
        $this->createTableIfNotExists();
    }
    
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS reel_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reel_url VARCHAR(500) NOT NULL,
            platform VARCHAR(50) NOT NULL,
            video_id VARCHAR(100) NOT NULL,
            username VARCHAR(100) DEFAULT NULL,
            profile_name VARCHAR(255) DEFAULT NULL,
            profile_picture VARCHAR(500) DEFAULT NULL,
            followers INT DEFAULT 0,
            caption TEXT DEFAULT NULL,
            thumbnail_url VARCHAR(500) DEFAULT NULL,
            likes INT DEFAULT 0,
            comments INT DEFAULT 0,
            views INT DEFAULT 0,
            shares INT DEFAULT 0,
            duration VARCHAR(20) DEFAULT NULL,
            fetch_date DATETIME DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            error_message TEXT DEFAULT NULL,
            api_response TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_platform (platform),
            INDEX idx_video_id (video_id),
            INDEX idx_username (username),
            INDEX idx_fetch_date (fetch_date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $this->db->query($sql);
        } catch (\Exception $e) {
            error_log("Table creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Save or Update Analytics Data (REAL-TIME)
     */
    public function saveAnalytics($data) {
        try {
            // Check if already exists
            $checkSql = "SELECT id FROM reel_analytics WHERE video_id = ? AND platform = ?";
            $checkStmt = $this->db->prepare($checkSql);
            if (!$checkStmt) {
                error_log("Prepare failed: " . $this->db->error);
                return false;
            }
            
            $video_id = $data['video_id'] ?? '';
            $platform = $data['platform'] ?? 'tiktok';
            
            $checkStmt->bind_param("ss", $video_id, $platform);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $existing = $checkResult->fetch_assoc();
            $checkStmt->close();
            
            // Prepare values
            $reel_url = $data['reel_url'] ?? '';
            $username = $data['username'] ?? '';
            $profile_name = $data['profile_name'] ?? '';
            $profile_picture = $data['profile_picture'] ?? '';
            $followers = (int)($data['followers'] ?? 0);
            $caption = $data['caption'] ?? '';
            $thumbnail_url = $data['thumbnail_url'] ?? '';
            $likes = (int)($data['likes'] ?? 0);
            $comments = (int)($data['comments'] ?? 0);
            $views = (int)($data['views'] ?? 0);
            $shares = (int)($data['shares'] ?? 0);
            $duration = $data['duration'] ?? 'N/A';
            $status = $data['status'] ?? 'success';
            
            if ($existing) {
                // UPDATE existing record with latest data
                $sql = "UPDATE reel_analytics SET 
                        reel_url = ?,
                        username = ?,
                        profile_name = ?,
                        profile_picture = ?,
                        followers = ?,
                        caption = ?,
                        thumbnail_url = ?,
                        likes = ?,
                        comments = ?,
                        views = ?,
                        shares = ?,
                        duration = ?,
                        status = ?,
                        fetch_date = NOW()
                        WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    error_log("Update prepare failed: " . $this->db->error);
                    return false;
                }
                
                $id = (int)$existing['id'];
                
                $stmt->bind_param(
                    "ssssisssiiiisi",
                    $reel_url,
                    $username,
                    $profile_name,
                    $profile_picture,
                    $followers,
                    $caption,
                    $thumbnail_url,
                    $likes,
                    $comments,
                    $views,
                    $shares,
                    $duration,
                    $status,
                    $id
                );
                
                $result = $stmt->execute();
                $stmt->close();
                return $result;
                
            } else {
                // INSERT new record
                $sql = "INSERT INTO reel_analytics 
                        (reel_url, platform, video_id, username, profile_name, profile_picture, 
                         followers, caption, thumbnail_url, likes, comments, views, shares, duration, 
                         status, fetch_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $this->db->prepare($sql);
                if (!$stmt) {
                    error_log("Insert prepare failed: " . $this->db->error);
                    return false;
                }
                
                $stmt->bind_param(
                    "ssssssisssiiiis",
                    $reel_url,
                    $platform,
                    $video_id,
                    $username,
                    $profile_name,
                    $profile_picture,
                    $followers,
                    $caption,
                    $thumbnail_url,
                    $likes,
                    $comments,
                    $views,
                    $shares,
                    $duration,
                    $status
                );
                
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
            
        } catch (\Exception $e) {
            error_log("saveAnalytics error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update or Create (for real-time updates)
     */
    public function updateOrCreate($data) {
        return $this->saveAnalytics($data);
    }
    
    /**
     * Get Latest Data by Video ID (for real-time refresh)
     */
    public function getLatestByVideoId($videoId, $platform) {
        $sql = "SELECT * FROM reel_analytics WHERE video_id = ? AND platform = ? ORDER BY fetch_date DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("ss", $videoId, $platform);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
    
    /**
     * Get History with Pagination
     */
    public function getHistory($page = 1, $limit = 20, $search = null, $platform = null) {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];
        $types = "";
        
        $sql = "SELECT * FROM reel_analytics";
        
        if ($search) {
            $where[] = "(username LIKE ? OR reel_url LIKE ? OR caption LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "sss";
        }
        
        if ($platform) {
            $where[] = "platform = ?";
            $params[] = $platform;
            $types .= "s";
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY fetch_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['data' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'totalPages' => 0];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM reel_analytics";
        if (!empty($where)) {
            $countSql .= " WHERE " . implode(" AND ", $where);
        }
        $countStmt = $this->db->prepare($countSql);
        if (!$countStmt) {
            return ['data' => $data, 'total' => 0, 'page' => $page, 'limit' => $limit, 'totalPages' => 0];
        }
        
        if (!empty($params)) {
            $countParams = array_slice($params, 0, count($params) - 2);
            if (!empty($countParams)) {
                $countStmt->bind_param($types, ...$countParams);
            }
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'] ?? 0;
        $countStmt->close();
        
        return [
            'data' => $data,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get By Video ID (Alias for getLatestByVideoId)
     */
    public function getByVideoId($videoId, $platform) {
        return $this->getLatestByVideoId($videoId, $platform);
    }
    
    /**
     * Get Statistics
     */
    public function getStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'success' THEN 1 END) as success_count,
                        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                        platform,
                        COUNT(*) as platform_count
                    FROM reel_analytics
                    GROUP BY platform WITH ROLLUP";
            
            $result = $this->db->query($sql);
            
            if ($result === false) {
                return [['total' => 0, 'success_count' => 0, 'failed_count' => 0, 'pending_count' => 0]];
            }
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {
            error_log("getStats error: " . $e->getMessage());
            return [['total' => 0, 'success_count' => 0, 'failed_count' => 0, 'pending_count' => 0]];
        }
    }
    
    /**
     * Get Recent Records
     */
    public function getRecent($limit = 10) {
        try {
            $sql = "SELECT * FROM reel_analytics ORDER BY fetch_date DESC LIMIT ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        } catch (\Exception $e) {
            error_log("getRecent error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete Record
     */
    public function delete($id) {
        $sql = "DELETE FROM reel_analytics WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Get Record By ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM reel_analytics WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }
}
?>