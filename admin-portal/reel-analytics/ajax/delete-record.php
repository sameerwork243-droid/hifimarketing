<?php
// =============================================
// DELETE RECORD - AJAX HANDLER
// =============================================

require_once __DIR__ . '/../includes/init.php';
requireAdmin();

use Includes\ReelAnalytics;
use Includes\Logger;

$analytics = new ReelAnalytics();
$logger = new Logger();
$action = $_POST['action'] ?? '';

if ($action === 'delete_all') {
    // Get all records and delete one by one
    $allData = $analytics->getHistory(1, 10000);
    
    $deleted = 0;
    foreach ($allData['data'] as $row) {
        if ($analytics->delete($row['id'])) {
            $deleted++;
        }
    }
    
    $logger->logActivity($_SESSION['user_id'], 'delete_all_reels', ['count' => $deleted]);
    
    echo json_encode([
        'success' => true,
        'message' => "Deleted $deleted records"
    ]);
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$result = $analytics->delete($id);

if ($result) {
    $logger->logActivity($_SESSION['user_id'], 'delete_reel', ['id' => $id]);
    echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete record']);
}
?>