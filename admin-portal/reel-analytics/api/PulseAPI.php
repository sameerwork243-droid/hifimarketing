<?php
// =============================================
// PULSE API WRAPPER - CORRECT ENDPOINT
// =============================================

namespace API;

class PulseAPI {
    private $baseUrl = 'https://pulse.walls.sh';
    
    public function fetchMetrics($url) {
        // CORRECT ENDPOINT: /metrics?url=...
        $apiUrl = $this->baseUrl . '/metrics?url=' . urlencode($url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['error' => "HTTP Error: $httpCode"];
        }
        
        return json_decode($response, true);
    }
    
    public function fetchProfile($url) {
        // Profile endpoint: /profile?url=...
        $apiUrl = $this->baseUrl . '/profile?url=' . urlencode($url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['error' => "HTTP Error: $httpCode"];
        }
        
        return json_decode($response, true);
    }
    
    public function formatResponse($data) {
        return [
            'platform' => $data['platform'] ?? 'unknown',
            'video_id' => $this->extractVideoId($data['url'] ?? ''),
            'username' => $this->extractUsername($data['author'] ?? ''),
            'profile_name' => $data['author'] ?? '',
            'profile_picture' => $data['thumbnail'] ?? '',
            'followers' => 0, // Pulse doesn't provide followers for posts
            'thumbnail_url' => $data['thumbnail'] ?? '',
            'caption' => $data['title'] ?? 'No caption',
            'likes' => (int)($data['likes'] ?? 0),
            'comments' => (int)($data['comments'] ?? 0),
            'views' => (int)($data['views'] ?? 0),
            'shares' => (int)($data['shares'] ?? 0),
            'duration' => 'N/A',
            'upload_date' => $data['publishedAt'] ?? date('Y-m-d H:i:s')
        ];
    }
    
    private function extractUsername($author) {
        if (empty($author)) return 'unknown';
        return ltrim($author, '@');
    }
    
    private function extractVideoId($url) {
        preg_match('/\/video\/(\d+)/', $url, $matches);
        return $matches[1] ?? '';
    }
}
?>