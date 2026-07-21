<?php
// =============================================
// BASE API CLASS
// =============================================

namespace API;

use Includes\Logger;

abstract class BaseAPI {
    protected $logger;
    protected $apiKey;
    protected $timeout;
    protected $platform;
    
    public function __construct() {
        $this->logger = new Logger();
        $this->timeout = API_TIMEOUT;
    }
    
    abstract public function fetchAnalytics($videoId, $url);
    abstract public function validateVideoId($videoId);
    
    protected function makeRequest($url, $headers = [], $params = []) {
        $ch = curl_init();
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000);
        
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Log the request
        $this->logger->logAPI(
            $this->platform,
            $url,
            ['params' => $params],
            $response,
            $responseTime,
            $statusCode
        );
        
        if ($error) {
            throw new \Exception("CURL Error: " . $error);
        }
        
        return [
            'statusCode' => $statusCode,
            'response' => json_decode($response, true),
            'responseTime' => $responseTime
        ];
    }
    
    protected function formatResponse($data) {
        return [
            'success' => true,
            'data' => [
                'platform' => $this->platform,
                'video_id' => $data['video_id'] ?? '',
                'username' => $data['username'] ?? '',
                'profile_name' => $data['profile_name'] ?? '',
                'profile_picture' => $data['profile_picture'] ?? '',
                'followers' => (int)($data['followers'] ?? 0),
                'thumbnail_url' => $data['thumbnail_url'] ?? '',
                'caption' => $data['caption'] ?? '',
                'likes' => (int)($data['likes'] ?? 0),
                'comments' => (int)($data['comments'] ?? 0),
                'views' => (int)($data['views'] ?? 0),
                'shares' => (int)($data['shares'] ?? 0),
                'duration' => $data['duration'] ?? 'N/A',
                'upload_date' => $data['upload_date'] ?? ''
            ],
            'message' => 'Data fetched successfully'
        ];
    }
    
    protected function getMockData($videoId, $url, $platform) {
        $mockData = [
            'instagram' => [
                'video_id' => $videoId,
                'username' => 'instagram_user',
                'profile_name' => 'Instagram User',
                'profile_picture' => 'https://via.placeholder.com/100/4a5cf5/ffffff?text=IG',
                'followers' => 1250,
                'thumbnail_url' => 'https://via.placeholder.com/300x400/4a5cf5/ffffff?text=Instagram+Reel',
                'caption' => 'Not available via official API. Please configure Instagram API key.',
                'likes' => 0,
                'comments' => 0,
                'views' => 0,
                'shares' => 0,
                'duration' => 'N/A',
                'upload_date' => date('Y-m-d H:i:s')
            ],
            'facebook' => [
                'video_id' => $videoId,
                'username' => 'facebook_user',
                'profile_name' => 'Facebook User',
                'profile_picture' => 'https://via.placeholder.com/100/1877F2/ffffff?text=FB',
                'followers' => 850,
                'thumbnail_url' => 'https://via.placeholder.com/300x400/1877F2/ffffff?text=Facebook+Reel',
                'caption' => 'Not available via official API. Please configure Facebook API key.',
                'likes' => 0,
                'comments' => 0,
                'views' => 0,
                'shares' => 0,
                'duration' => 'N/A',
                'upload_date' => date('Y-m-d H:i:s')
            ],
            'tiktok' => [
                'video_id' => $videoId,
                'username' => 'tiktok_user',
                'profile_name' => 'TikTok User',
                'profile_picture' => 'https://via.placeholder.com/100/000000/ffffff?text=TT',
                'followers' => 2000,
                'thumbnail_url' => 'https://via.placeholder.com/300x400/000000/ffffff?text=TikTok+Video',
                'caption' => 'Not available via official API. Please configure TikTok API key.',
                'likes' => 0,
                'comments' => 0,
                'views' => 0,
                'shares' => 0,
                'duration' => 'N/A',
                'upload_date' => date('Y-m-d H:i:s')
            ],
            'youtube' => [
                'video_id' => $videoId,
                'username' => 'youtube_channel',
                'profile_name' => 'YouTube Channel',
                'profile_picture' => 'https://via.placeholder.com/100/FF0000/ffffff?text=YT',
                'followers' => 1500,
                'thumbnail_url' => 'https://via.placeholder.com/300x400/FF0000/ffffff?text=YouTube+Short',
                'caption' => 'Not available via official API. Please configure YouTube API key.',
                'likes' => 0,
                'comments' => 0,
                'views' => 0,
                'shares' => 0,
                'duration' => 'N/A',
                'upload_date' => date('Y-m-d H:i:s')
            ]
        ];
        
        return [
            'success' => true,
            'data' => $mockData[$platform] ?? $mockData['instagram'],
            'message' => 'Data fetched successfully (Mock Mode - API Key Not Configured)'
        ];
    }
}
?>