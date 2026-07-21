<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$url = $_POST['url'] ?? '';
$saveOnly = isset($_POST['save']) && $_POST['save'] === 'true';
$batchMode = isset($_POST['batch']) && $_POST['batch'] === 'true';

if (empty($url)) {
    echo json_encode(['success' => false, 'message' => 'URL is required']);
    exit;
}

$mainConfigPath = '/home/sites/32a/2/2fb0787974/public_html/includes/config.php';
if (!file_exists($mainConfigPath)) {
    echo json_encode(['success' => false, 'message' => 'Config not found']);
    exit;
}
require_once $mainConfigPath;

$analyticsPath = __DIR__ . '/../includes/ReelAnalytics.php';
if (!file_exists($analyticsPath)) {
    echo json_encode(['success' => false, 'message' => 'ReelAnalytics.php not found']);
    exit;
}
require_once $analyticsPath;

try {
    $analytics = new Includes\ReelAnalytics();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

$platform = 'tiktok';
if (strpos($url, 'instagram.com') !== false) $platform = 'instagram';
elseif (strpos($url, 'facebook.com') !== false) $platform = 'facebook';
elseif (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) $platform = 'youtube';

$videoId = '';
if ($platform === 'tiktok') {
    preg_match('/\/video\/(\d+)/', $url, $matches);
    $videoId = $matches[1] ?? '';
} elseif ($platform === 'youtube') {
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    $videoId = $query['v'] ?? '';
    if (empty($videoId) && strpos($url, 'youtu.be/') !== false) {
        $parts = explode('/', $url);
        $videoId = end($parts);
        $videoId = explode('?', $videoId)[0];
    }
} elseif ($platform === 'instagram') {
    preg_match('/\/p\/([A-Za-z0-9_-]+)/', $url, $matches);
    $videoId = $matches[1] ?? '';
    if (empty($videoId)) {
        preg_match('/\/reel\/([A-Za-z0-9_-]+)/', $url, $matches);
        $videoId = $matches[1] ?? '';
    }
} elseif ($platform === 'facebook') {
    preg_match('/\/videos\/(\d+)/', $url, $matches);
    $videoId = $matches[1] ?? '';
}

// =============================================
// FETCH FROM API WITH RETRY
// =============================================
function fetchFromAPI($url, $retry = 2) {
    $apiUrl = 'https://pulse.walls.sh/metrics?url=' . urlencode($url);
    $attempt = 0;
    
    while ($attempt <= $retry) {
        $attempt++;
        
        // Method 1: cURL
        if (function_exists('curl_version')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Accept-Language: en-US,en;q=0.9',
                'Cache-Control: no-cache'
            ]);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode === 200 && !empty($response)) {
                $data = json_decode($response, true);
                if ($data && !isset($data['error'])) {
                    return ['success' => true, 'data' => $data];
                }
            }
        }
        
        // Method 2: file_get_contents
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                            "Accept: application/json\r\n" .
                            "Accept-Language: en-US,en;q=0.9\r\n" .
                            "Cache-Control: no-cache\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents($apiUrl, false, $context);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && !isset($data['error'])) {
                return ['success' => true, 'data' => $data];
            }
        }
        
        // Wait before retry
        if ($attempt <= $retry) {
            usleep(500000); // 0.5 second delay
        }
    }
    
    return ['success' => false, 'message' => 'API unavailable after ' . $retry . ' retries'];
}

// =============================================
// GET EXISTING DATA FROM DATABASE (FALLBACK)
// =============================================
function getExistingData($analytics, $videoId, $platform) {
    if (empty($videoId)) return null;
    try {
        return $analytics->getByVideoId($videoId, $platform);
    } catch (Exception $e) {
        return null;
    }
}

// =============================================
// BATCH MODE - Update All History Links
// =============================================
if ($batchMode) {
    try {
        $history = $analytics->getHistory(1, 1000);
        $allLinks = $history['data'] ?? [];
        
        $updated = 0;
        $failed = 0;
        $skipped = 0;
        $results = [];
        
        foreach ($allLinks as $link) {
            $url = $link['reel_url'];
            if (empty($url)) continue;
            
            // Check if already updated recently (within 5 minutes)
            $lastFetch = strtotime($link['fetch_date'] ?? '1970-01-01');
            if (time() - $lastFetch < 300) { // 5 minutes
                $skipped++;
                continue;
            }
            
            $result = fetchFromAPI($url);
            if ($result['success']) {
                $data = $result['data'];
                $saveData = [
                    'reel_url' => $url,
                    'platform' => $data['platform'] ?? $link['platform'],
                    'video_id' => $data['contentId'] ?? $link['video_id'],
                    'username' => ltrim($data['author'] ?? '', '@'),
                    'profile_name' => $data['author'] ?? 'Unknown',
                    'profile_picture' => $data['thumbnail'] ?? '',
                    'followers' => 0,
                    'thumbnail_url' => $data['thumbnail'] ?? '',
                    'caption' => $data['title'] ?? 'No caption',
                    'likes' => (int)($data['likes'] ?? 0),
                    'comments' => (int)($data['comments'] ?? 0),
                    'views' => (int)($data['views'] ?? 0),
                    'shares' => (int)($data['shares'] ?? 0),
                    'duration' => $data['duration'] ?? 'N/A',
                    'status' => 'success'
                ];
                $analytics->saveAnalytics($saveData);
                $updated++;
                $results[] = ['url' => $url, 'status' => 'updated'];
            } else {
                $failed++;
                $results[] = ['url' => $url, 'status' => 'failed'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Batch update: $updated updated, $failed failed, $skipped skipped",
            'total' => count($allLinks),
            'updated' => $updated,
            'failed' => $failed,
            'skipped' => $skipped,
            'results' => $results
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Batch error: ' . $e->getMessage()]);
    }
    exit;
}

// =============================================
// SAVE ONLY
// =============================================
if ($saveOnly) {
    try {
        $result = fetchFromAPI($url);
        
        // If API fails, try to get existing data
        if (!$result['success']) {
            $existing = getExistingData($analytics, $videoId, $platform);
            if ($existing) {
                echo json_encode([
                    'success' => true, 
                    'message' => '⚠️ Using cached data (API unavailable)',
                    'data' => [
                        'platform' => $existing['platform'],
                        'video_id' => $existing['video_id'],
                        'username' => $existing['username'],
                        'profile_name' => $existing['profile_name'],
                        'profile_picture' => $existing['profile_picture'],
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
                exit;
            }
            echo json_encode(['success' => false, 'message' => 'API unavailable and no cached data found']);
            exit;
        }
        
        $data = $result['data'];
        $saveData = [
            'reel_url' => $url,
            'platform' => $data['platform'] ?? $platform,
            'video_id' => $data['contentId'] ?? $videoId,
            'username' => ltrim($data['author'] ?? '', '@'),
            'profile_name' => $data['author'] ?? 'Unknown',
            'profile_picture' => $data['thumbnail'] ?? '',
            'followers' => 0,
            'thumbnail_url' => $data['thumbnail'] ?? '',
            'caption' => $data['title'] ?? 'No caption',
            'likes' => (int)($data['likes'] ?? 0),
            'comments' => (int)($data['comments'] ?? 0),
            'views' => (int)($data['views'] ?? 0),
            'shares' => (int)($data['shares'] ?? 0),
            'duration' => $data['duration'] ?? 'N/A',
            'status' => 'success'
        ];
        $analytics->saveAnalytics($saveData);
        echo json_encode(['success' => true, 'message' => '✅ Data saved successfully!', 'data' => $saveData]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// =============================================
// NORMAL FETCH
// =============================================
$result = fetchFromAPI($url);

// If API fails, try to get existing data
if (!$result['success']) {
    $existing = getExistingData($analytics, $videoId, $platform);
    if ($existing) {
        echo json_encode([
            'success' => true,
            'message' => '⚠️ Using cached data (API unavailable)',
            'data' => [
                'platform' => $existing['platform'],
                'video_id' => $existing['video_id'],
                'username' => $existing['username'],
                'profile_name' => $existing['profile_name'],
                'profile_picture' => $existing['profile_picture'],
                'thumbnail_url' => $existing['thumbnail_url'],
                'caption' => $existing['caption'],
                'likes' => (int)$existing['likes'],
                'comments' => (int)$existing['comments'],
                'views' => (int)$existing['views'],
                'shares' => (int)$existing['shares'],
                'duration' => $existing['duration'],
                'upload_date' => $existing['fetch_date'],
                'followers' => 0
            ]
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'API unavailable. Please try again later.']);
    exit;
}

$data = $result['data'];
if (!$data || isset($data['error'])) {
    echo json_encode(['success' => false, 'message' => $data['error'] ?? 'Invalid API response']);
    exit;
}

$formattedData = [
    'platform' => $data['platform'] ?? 'tiktok',
    'video_id' => $data['contentId'] ?? $videoId,
    'username' => ltrim($data['author'] ?? '', '@'),
    'profile_name' => $data['author'] ?? 'Unknown',
    'profile_picture' => $data['thumbnail'] ?? '',
    'thumbnail_url' => $data['thumbnail'] ?? '',
    'caption' => $data['title'] ?? 'No caption available',
    'likes' => (int)($data['likes'] ?? 0),
    'comments' => (int)($data['comments'] ?? 0),
    'views' => (int)($data['views'] ?? 0),
    'shares' => (int)($data['shares'] ?? 0),
    'duration' => $data['duration'] ?? 'N/A',
    'upload_date' => $data['publishedAt'] ?? date('Y-m-d H:i:s'),
    'followers' => 0
];

try {
    $saveData = $formattedData;
    $saveData['reel_url'] = $url;
    $saveData['status'] = 'success';
    $analytics->saveAnalytics($saveData);
} catch (Exception $e) {}

echo json_encode([
    'success' => true,
    'message' => '✅ Data fetched successfully!',
    'data' => $formattedData
]);
?>