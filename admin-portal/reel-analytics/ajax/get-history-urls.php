<?php
header('Content-Type: application/json');

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
    $history = $analytics->getHistory(1, 1000);
    $data = $history['data'] ?? [];
    
    $urls = [];
    foreach ($data as $item) {
        if (!empty($item['reel_url'])) {
            $urls[] = $item['reel_url'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'total' => count($urls),
        'urls' => $urls
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>