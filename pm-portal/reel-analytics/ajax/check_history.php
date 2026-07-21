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

global $conn;
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database not connected']);
    exit;
}

// Get all records
$result = $conn->query("SELECT id, platform, username, profile_name, views, likes, fetch_date, status FROM reel_analytics ORDER BY id DESC LIMIT 20");

$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($records),
    'records' => $records
]);
?>