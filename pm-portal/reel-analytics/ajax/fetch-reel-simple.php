<?php
// =============================================
// FETCH REEL - USING PUPPETEER (NO API!)
// =============================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ReelAnalytics.php';

use Includes\ReelAnalytics;

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get the URL
$url = $_POST['url'] ?? '';

if (empty($url)) {
    echo json_encode(['success' => false, 'message' => 'URL is required']);
    exit();
}

try {
    $analytics = new ReelAnalytics();
    
    // Detect platform from URL
    $platform = 'tiktok';
    if (strpos($url, 'instagram.com') !== false) $platform = 'instagram';
    elseif (strpos($url, 'facebook.com') !== false) $platform = 'facebook';
    elseif (strpos($url, 'tiktok.com') !== false) $platform = 'tiktok';
    elseif (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) $platform = 'youtube';
    
    // Extract video ID
    $videoId = '';
    if ($platform === 'tiktok') {
        preg_match('/\/video\/(\d+)/', $url, $matches);
        $videoId = $matches[1] ?? '';
    }
    
    // Check if already in database
    if (!empty($videoId)) {
        $existing = $analytics->getByVideoId($videoId, $platform);
        if ($existing && $existing['status'] === 'success') {
            echo json_encode([
                'success' => true,
                'message' => 'Data retrieved from cache',
                'data' => [
                    'platform' => $existing['platform'],
                    'video_id' => $existing['video_id'],
                    'username' => $existing['username'],
                    'profile_name' => $existing['profile_name'],
                    'profile_picture' => $existing['profile_picture'],
                    'followers' => (int)$existing['followers'],
                    'thumbnail_url' => $existing['thumbnail_url'],
                    'caption' => $existing['caption'],
                    'likes' => (int)$existing['likes'],
                    'comments' => (int)$existing['comments'],
                    'views' => (int)$existing['views'],
                    'shares' => (int)$existing['shares'],
                    'duration' => $existing['duration'],
                    'upload_date' => $existing['fetch_date']
                ]
            ]);
            exit();
        }
    }
    
    // =============================================
    // SCRAPE USING PUPPETEER
    // =============================================
    $scrapedData = scrapeWithPuppeteer($url);
    
    if ($scrapedData) {
        // Format data
        $formattedData = [
            'platform' => $platform,
            'video_id' => $videoId,
            'username' => $scrapedData['username'] ?? 'unknown',
            'profile_name' => $scrapedData['username'] ?? 'Unknown',
            'profile_picture' => $scrapedData['profilePicture'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($scrapedData['username'] ?? 'User'),
            'followers' => (int)($scrapedData['stats']['followers'] ?? 0),
            'caption' => $scrapedData['caption'] ?? 'No caption',
            'thumbnail_url' => $scrapedData['thumbnail'] ?? '',
            'likes' => (int)($scrapedData['stats']['likes'] ?? 0),
            'comments' => (int)($scrapedData['stats']['comments'] ?? 0),
            'views' => (int)($scrapedData['stats']['views'] ?? 0),
            'shares' => (int)($scrapedData['stats']['shares'] ?? 0),
            'duration' => 'N/A',
            'upload_date' => date('Y-m-d H:i:s'),
            'status' => 'success'
        ];
        
        // Save to database
        $saveData = $formattedData;
        $saveData['reel_url'] = $url;
        $analytics->saveAnalytics($saveData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Real data fetched successfully! (Puppeteer)',
            'data' => $formattedData
        ]);
        exit();
    }
    
    // If scraping fails
    echo json_encode([
        'success' => false,
        'message' => 'Could not fetch data. The video might be private or the URL is invalid.'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// =============================================
// SCRAPE WITH PUPPETEER
// =============================================
function scrapeWithPuppeteer($url) {
    try {
        // Path to node script
        $scriptPath = __DIR__ . '/../scrape-tiktok.js';
        
        if (!file_exists($scriptPath)) {
            error_log("Puppeteer script not found at: " . $scriptPath);
            return null;
        }
        
        // Run the Node.js script
        $command = 'node "' . $scriptPath . '" "' . $url . '" 2>&1';
        $output = shell_exec($command);
        
        if (empty($output)) {
            error_log("No output from Puppeteer script");
            return null;
        }
        
        // Find JSON in output
        $jsonStart = strpos($output, '{');
        if ($jsonStart === false) {
            error_log("No JSON found in output: " . substr($output, 0, 200));
            return null;
        }
        
        $jsonString = substr($output, $jsonStart);
        $data = json_decode($jsonString, true);
        
        if (!$data) {
            error_log("Failed to parse JSON: " . substr($jsonString, 0, 200));
            return null;
        }
        
        return $data;
        
    } catch (Exception $e) {
        error_log("Puppeteer error: " . $e->getMessage());
        return null;
    }
}
?>