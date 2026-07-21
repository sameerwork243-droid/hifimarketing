<?php
// =============================================
// VALIDATOR CLASS
// =============================================

namespace Includes;

class Validator {
    
    /**
     * Validate Reel URL
     */
    public function validateReelUrl($url) {
        $url = trim($url);
        
        if (empty($url)) {
            return ['valid' => false, 'message' => 'URL is required'];
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'message' => 'Invalid URL format'];
        }
        
        $platform = $this->detectPlatform($url);
        
        if (!$platform) {
            return ['valid' => false, 'message' => 'Unsupported platform. Please use Instagram, Facebook, TikTok, or YouTube.'];
        }
        
        return ['valid' => true, 'platform' => $platform, 'url' => $url];
    }
    
    /**
     * Detect platform from URL
     */
    public function detectPlatform($url) {
        $patterns = [
            'instagram' => '/instagram\.com\/(?:p|reel|tv)\/[a-zA-Z0-9_-]+/',
            'facebook' => '/facebook\.com\/(?:watch|reel|video)\/[a-zA-Z0-9_-]+/',
            'tiktok' => '/tiktok\.com\/@[a-zA-Z0-9_.]+\/video\/[0-9]+/',
            'youtube' => '/youtube\.com\/(?:shorts\/|watch\?v=)[a-zA-Z0-9_-]+/'
        ];
        
        foreach ($patterns as $platform => $pattern) {
            if (preg_match($pattern, $url)) {
                return $platform;
            }
        }
        
        return null;
    }
    
    /**
     * Extract Video ID from URL
     */
    public function extractVideoId($url, $platform) {
        $patterns = [
            'instagram' => '/instagram\.com\/(?:p|reel|tv)\/([a-zA-Z0-9_-]+)/',
            'facebook' => '/facebook\.com\/(?:watch|reel|video)\/([a-zA-Z0-9_-]+)/',
            'tiktok' => '/tiktok\.com\/@[a-zA-Z0-9_.]+\/video\/([0-9]+)/',
            'youtube' => '/youtube\.com\/(?:shorts\/|watch\?v=)([a-zA-Z0-9_-]+)/'
        ];
        
        if (isset($patterns[$platform]) && preg_match($patterns[$platform], $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Sanitize input
     */
    public function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate video ID for specific platform
     */
    public function validateVideoId($videoId, $platform) {
        $patterns = [
            'instagram' => '/^[a-zA-Z0-9_-]+$/',
            'facebook' => '/^[a-zA-Z0-9_-]+$/',
            'tiktok' => '/^[0-9]+$/',
            'youtube' => '/^[a-zA-Z0-9_-]{11}$/'
        ];
        
        if (!isset($patterns[$platform])) {
            return false;
        }
        
        return preg_match($patterns[$platform], $videoId) === 1;
    }
}
?>