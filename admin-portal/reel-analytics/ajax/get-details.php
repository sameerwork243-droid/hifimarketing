<?php
// =============================================
// GET DETAILS - AJAX HANDLER
// =============================================

require_once __DIR__ . '/../includes/init.php';
requireAdmin();

use Includes\ReelAnalytics;

$analytics = new ReelAnalytics();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

$data = $analytics->getById($id);

if (!$data) {
    echo json_encode(['error' => 'Record not found']);
    exit();
}

echo json_encode($data);
?>