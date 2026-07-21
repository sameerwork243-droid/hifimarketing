<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

header('Content-Type: application/json');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM jobs WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    if ($job = mysqli_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'job' => $job]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
}
?>