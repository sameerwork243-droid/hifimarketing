<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$url = $_POST['url'] ?? '';

if (empty($url)) {
    die(json_encode(['success' => false, 'message' => 'URL is required']));
}

$apiUrl = 'https://pulse.walls.sh/metrics?url=' . urlencode($url);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($response)) {
    die(json_encode(['success' => false, 'message' => 'API returned HTTP ' . $httpCode]));
}

$data = json_decode($response, true);
if (!$data) {
    die(json_encode(['success' => false, 'message' => 'Invalid JSON from API']));
}

die(json_encode([
    'success' => true,
    'data' => [
        'platform' => $data['platform'] ?? 'tiktok',
        'video_id' => $data['contentId'] ?? '',
        'username' => ltrim($data['author'] ?? '', '@'),
        'profile_name' => $data['author'] ?? '',
        'profile_picture' => $data['thumbnail'] ?? '',
        'thumbnail_url' => $data['thumbnail'] ?? '',
        'caption' => $data['title'] ?? 'No caption',
        'likes' => (int)($data['likes'] ?? 0),
        'comments' => (int)($data['comments'] ?? 0),
        'views' => (int)($data['views'] ?? 0),
        'shares' => (int)($data['shares'] ?? 0),
        'duration' => 'N/A',
        'upload_date' => $data['publishedAt'] ?? date('Y-m-d H:i:s')
    ]
]));