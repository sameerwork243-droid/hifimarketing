<?php
// =============================================
// TIKTOK API
// =============================================

namespace API;

class TikTokAPI extends BaseAPI {
    protected $platform = 'tiktok';
    private $accessToken;
    
    public function __construct() {
        parent::__construct();
        $this->accessToken = TIKTOK_API_KEY;
    }
    
    public function validateVideoId($videoId) {
        return !empty($videoId) && is_numeric($videoId);
    }
    
    public function fetchAnalytics($videoId, $url) {
        if (!$this->validateVideoId($videoId)) {
            throw new \Exception('Invalid TikTok video ID');
        }
        
        if (empty($this->accessToken)) {
            return $this->getMockData($videoId, $url, 'tiktok');
        }
        
        try {
            $apiUrl = "https://open-api.tiktok.com/video/query";
            $params = [
                'video_id' => $videoId,
                'access_token' => $this->accessToken
            ];
            
            $result = $this->makeRequest($apiUrl, [], $params);
            
            if ($result['statusCode'] !== 200) {
                throw new \Exception('API request failed: ' . ($result['response']['error']['message'] ?? 'Unknown error'));
            }
            
            $data = $result['response']['data'] ?? [];
            
            return $this->formatResponse([
                'video_id' => $videoId,
                'username' => $data['author_name'] ?? 'tiktok_user',
                'profile_name' => $data['author_name'] ?? 'TikTok User',
                'profile_picture' => $data['author_avatar'] ?? '',
                'followers' => $data['author_followers'] ?? 0,
                'thumbnail_url' => $data['cover_url'] ?? '',
                'caption' => $data['title'] ?? '',
                'likes' => $data['like_count'] ?? 0,
                'comments' => $data['comment_count'] ?? 0,
                'views' => $data['view_count'] ?? 0,
                'shares' => $data['share_count'] ?? 0,
                'duration' => $data['duration'] ?? 'N/A',
                'upload_date' => $data['create_time'] ?? ''
            ]);
            
        } catch (\Exception $e) {
            $this->logger->logError('TikTok API Error', ['message' => $e->getMessage(), 'video_id' => $videoId]);
            return $this->getMockData($videoId, $url, 'tiktok');
        }
    }
}
?>