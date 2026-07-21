<?php
// =============================================
// EXPORT CSV - AJAX HANDLER
// =============================================

require_once __DIR__ . '/../includes/init.php';
requireAdmin();

use Includes\ReelAnalytics;

$analytics = new ReelAnalytics();

// Get filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$platform = isset($_GET['platform']) ? $_GET['platform'] : '';

// Get all data (no pagination limit)
$data = $analytics->getHistory(1, 10000, $search, $platform);

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="reel-analytics-' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// CSV Headers
fputcsv($output, [
    'ID',
    'Platform',
    'Profile Name',
    'Username',
    'Followers',
    'Caption',
    'Likes',
    'Comments',
    'Views',
    'Shares',
    'Duration',
    'Video ID',
    'URL',
    'Status',
    'Fetched Date'
]);

// CSV Data
foreach ($data['data'] as $row) {
    fputcsv($output, [
        $row['id'],
        $row['platform'],
        $row['profile_name'] ?? '',
        $row['username'] ?? '',
        $row['followers'] ?? 0,
        $row['caption'] ?? '',
        $row['likes'] ?? 0,
        $row['comments'] ?? 0,
        $row['views'] ?? 0,
        $row['shares'] ?? 0,
        $row['duration'] ?? '',
        $row['video_id'] ?? '',
        $row['reel_url'] ?? '',
        $row['status'] ?? '',
        $row['fetch_date'] ?? $row['created_at'] ?? ''
    ]);
}

fclose($output);
exit();
?>