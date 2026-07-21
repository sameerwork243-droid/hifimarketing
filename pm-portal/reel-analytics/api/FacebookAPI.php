<?php
// =============================================
// FACEBOOK API
// =============================================

namespace API;

class FacebookAPI extends BaseAPI {
    protected $platform = 'facebook';
    private $accessToken;
    
    public function __construct() {
        parent::__construct();
        $this->accessToken = FACEBOOK_API_KEY;
    }
    
    public function validateVideoId($videoId) {
        return !empty($videoId) && preg_match('/^[a-zA-Z0-9_-]+$/', $videoId);
    }
    
    public function fetchAnalytics($videoId, $url) {
        if (!$this->validateVideoId($videoId)) {
            throw new \Exception('Invalid Facebook video ID');
        }
        
        if (empty($this->accessToken)) {
            return $this->getMockData($videoId, $url, 'facebook');
        }
        
        try {
            $fields = 'id,title,description,created_time,length,views,comments,likes,shares,thumbnail_url';
            $apiUrl = "https://graph.facebook.com/v18.0/{$videoId}";
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
                'username' => 'facebook_user',
                'profile_name' => 'Facebook User',
                'profile_picture' => '',
                'followers' => 0,
                'thumbnail_url' => $data['thumbnail_url'] ?? '',
                'caption' => $data['title'] ?? $data['description'] ?? '',
                'likes' => $data['likes']['summary']['total_count'] ?? 0,
                'comments' => $data['comments']['summary']['total_count'] ?? 0,
                'views' => $data['views'] ?? 0,
                'shares' => $data['shares'] ?? 0,
                'duration' => $data['length'] ?? 'N/A',
                'upload_date' => $data['created_time'] ?? ''
            ]);
            
        } catch (\Exception $e) {
            $this->logger->logError('Facebook API Error', ['message' => $e->getMessage(), 'video_id' => $videoId]);
            return $this->getMockData($videoId, $url, 'facebook');
        }
    }
}
?>