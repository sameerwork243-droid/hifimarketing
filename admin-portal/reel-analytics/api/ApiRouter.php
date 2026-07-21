<?php
// =============================================
// API ROUTER
// =============================================

namespace API;

use Includes\Validator;
use Includes\RateLimiter;
use Includes\Logger;
use Includes\ReelAnalytics;

class ApiRouter {
    private $validator;
    private $rateLimiter;
    private $logger;
    private $analytics;
    
    public function __construct() {
        $this->validator = new Validator();
        $this->rateLimiter = new RateLimiter();
        $this->logger = new Logger();
        $this->analytics = new ReelAnalytics();
    }
    
    public function handleRequest() {
        // Check rate limit
        $rateCheck = $this->rateLimiter->checkRateLimit();
        if (!$rateCheck['allowed']) {
            $this->sendResponse(false, $rateCheck['message'], null, 429);
            return;
        }
        
        // Get and validate input
        $input = json_decode(file_get_contents('php://input'), true);
        $url = $input['url'] ?? $_POST['url'] ?? $_GET['url'] ?? '';
        
        if (empty($url)) {
            $this->sendResponse(false, 'URL is required', null, 400);
            return;
        }
        
        // Validate URL
        $validation = $this->validator->validateReelUrl($url);
        if (!$validation['valid']) {
            $this->sendResponse(false, $validation['message'], null, 400);
            return;
        }
        
        $platform = $validation['platform'];
        $videoId = $this->validator->extractVideoId($url, $platform);
        
        if (!$videoId) {
            $this->sendResponse(false, 'Could not extract video ID from URL', null, 400);
            return;
        }
        
        // Check if already exists in database
        $existing = $this->checkExisting($videoId, $platform);
        if ($existing) {
            $this->sendResponse(true, 'Data retrieved from cache', $existing, 200);
            return;
        }
        
        // Fetch from appropriate API
        try {
            $apiClass = $this->getApiClass($platform);
            $api = new $apiClass();
            $result = $api->fetchAnalytics($videoId, $url);
            
            if ($result['success']) {
                // Save to database
                $this->saveToDatabase($url, $platform, $videoId, $result['data']);
                $this->sendResponse(true, $result['message'], $result['data'], 200);
            } else {
                $this->sendResponse(false, $result['message'] ?? 'Failed to fetch data', null, 500);
            }
            
        } catch (\Exception $e) {
            $this->logger->logError('API Router Error', ['message' => $e->getMessage(), 'platform' => $platform]);
            $this->sendResponse(false, 'Error fetching data: ' . $e->getMessage(), null, 500);
        }
    }
    
    private function getApiClass($platform) {
        $classes = [
            'instagram' => 'API\InstagramAPI',
            'facebook' => 'API\FacebookAPI',
            'tiktok' => 'API\TikTokAPI',
            'youtube' => 'API\YouTubeAPI'
        ];
        return $classes[$platform] ?? null;
    }
    
    private function checkExisting($videoId, $platform) {
        $sql = "SELECT * FROM reel_analytics WHERE video_id = ? AND platform = ? AND status = 'success' ORDER BY fetch_date DESC LIMIT 1";
        $db = \Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$videoId, $platform]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    private function saveToDatabase($url, $platform, $videoId, $data) {
        $analyticsData = [
            'reel_url' => $url,
            'platform' => $platform,
            'video_id' => $videoId,
            'username' => $data['username'] ?? '',
            'profile_name' => $data['profile_name'] ?? '',
            'profile_picture' => $data['profile_picture'] ?? '',
            'followers' => $data['followers'] ?? 0,
            'caption' => $data['caption'] ?? '',
            'thumbnail_url' => $data['thumbnail_url'] ?? '',
            'likes' => $data['likes'] ?? 0,
            'comments' => $data['comments'] ?? 0,
            'views' => $data['views'] ?? 0,
            'shares' => $data['shares'] ?? 0,
            'duration' => $data['duration'] ?? 'N/A',
            'status' => 'success'
        ];
        
        $this->analytics->saveAnalytics($analyticsData);
    }
    
    private function sendResponse($success, $message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
}
?>