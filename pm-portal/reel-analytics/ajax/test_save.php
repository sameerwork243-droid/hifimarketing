<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    
    // Test data
    $testData = [
        'reel_url' => 'https://www.tiktok.com/@test/video/123456789',
        'platform' => 'tiktok',
        'video_id' => '123456789',
        'username' => 'test_user',
        'profile_name' => 'Test User',
        'profile_picture' => 'https://via.placeholder.com/100',
        'followers' => 1000,
        'caption' => 'Test caption',
        'thumbnail_url' => 'https://via.placeholder.com/400x225',
        'likes' => 500,
        'comments' => 50,
        'views' => 10000,
        'shares' => 100,
        'duration' => '0:30',
        'status' => 'success'
    ];
    
    echo "Saving test data...\n";
    $result = $analytics->saveAnalytics($testData);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => '✅ Test data saved!']);
    } else {
        echo json_encode(['success' => false, 'message' => '❌ Failed to save test data']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>