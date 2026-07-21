<?php
// =============================================
// INSTAGRAM API
// =============================================

namespace API;

class InstagramAPI extends BaseAPI {
    protected $platform = 'instagram';
    private $accessToken;
    
    public function __construct() {
        parent::__construct();
        $this->accessToken = INSTAGRAM_API_KEY;
    }
    
    public function validateVideoId($videoId) {
        return !empty($videoId) && preg_match('/^[a-zA-Z0-9_-]+$/', $videoId);
    }
    
    public function fetchAnalytics($videoId, $url) {
        if (!$this->validateVideoId($videoId)) {
            throw new \Exception('Invalid Instagram video ID');
        }
        
        if (empty($this->accessToken)) {
            return $this->getMockData($videoId, $url, 'instagram');
        }
        
        try {
            $fields = 'id,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count,media_type,caption';
            $apiUrl = "https://graph.instagram.com/{$videoId}";
            $params = [
                'fields' => $fields,
                'access_token' => $this->accessToken
            ];
            
            $result = $this->makeRequest($apiUrl, [], $params);
            
            if ($result['statusCode'] !== 200) {
                throw new \Exception('API request failed: ' . ($result['response']['error']['message'] ?? 'Unknown error'));
            }
            
            $data = $result['response'];
            
            return $this->formatResponse([
                'video_id' => $videoId,
                'username' => 'instagram_user',
                'profile_name' => 'Instagram User',
                'profile_picture' => '',
                'followers' => 0,
                'thumbnail_url' => $data['thumbnail_url'] ?? $data['media_url'] ?? '',
                'caption' => $data['caption'] ?? '',
                'likes' => $data['like_count'] ?? 0,
                'comments' => $data['comments_count'] ?? 0,
                'views' => 0,
                'shares' => 0,
                'duration' => 'N/A',
                'upload_date' => $data['timestamp'] ?? ''
            ]);
            
        } catch (\Exception $e) {
            $this->logger->logError('Instagram API Error', ['message' => $e->getMessage(), 'video_id' => $videoId]);
            return $this->getMockData($videoId, $url, 'instagram');
        }
    }
}
?>