<?php
// =============================================
// YOUTUBE API
// =============================================

namespace API;

class YouTubeAPI extends BaseAPI {
    protected $platform = 'youtube';
    private $apiKey;
    
    public function __construct() {
        parent::__construct();
        $this->apiKey = YOUTUBE_API_KEY;
    }
    
    public function validateVideoId($videoId) {
        return !empty($videoId) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId);
    }
    
    public function fetchAnalytics($videoId, $url) {
        if (!$this->validateVideoId($videoId)) {
            throw new \Exception('Invalid YouTube video ID');
        }
        
        if (empty($this->apiKey)) {
            return $this->getMockData($videoId, $url, 'youtube');
        }
        
        try {
            $apiUrl = "https://www.googleapis.com/youtube/v3/videos";
            $params = [
                'part' => 'snippet,statistics,contentDetails',
                'id' => $videoId,
                'key' => $this->apiKey
            ];
            
            $result = $this->makeRequest($apiUrl, [], $params);
            
            if ($result['statusCode'] !== 200) {
                throw new \Exception('API request failed: ' . ($result['response']['error']['message'] ?? 'Unknown error'));
            }
            
            $items = $result['response']['items'] ?? [];
            if (empty($items)) {
                throw new \Exception('Video not found');
            }
            
            $video = $items[0];
            $snippet = $video['snippet'] ?? [];
            $statistics = $video['statistics'] ?? [];
            $contentDetails = $video['contentDetails'] ?? [];
            
            return $this->formatResponse([
                'video_id' => $videoId,
                'username' => $snippet['channelId'] ?? '',
                'profile_name' => $snippet['channelTitle'] ?? 'YouTube Channel',
                'profile_picture' => '',
                'followers' => 0,
                'thumbnail_url' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'] ?? '',
                'caption' => $snippet['title'] ?? '',
                'likes' => $statistics['likeCount'] ?? 0,
                'comments' => $statistics['commentCount'] ?? 0,
                'views' => $statistics['viewCount'] ?? 0,
                'shares' => 0,
                'duration' => $contentDetails['duration'] ?? 'N/A',
                'upload_date' => $snippet['publishedAt'] ?? ''
            ]);
            
        } catch (\Exception $e) {
            $this->logger->logError('YouTube API Error', ['message' => $e->getMessage(), 'video_id' => $videoId]);
            return $this->getMockData($videoId, $url, 'youtube');
        }
    }
}
?>