<?php
// download.php - File Download Handler (PUBLIC HOSTING READY)
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== FIXED PATH FOR PUBLIC HOSTING =====
require_once dirname(__DIR__) . '/includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: client-portal/login.php');
    exit();
}

$doc_id = isset($_GET['doc_id']) ? intval($_GET['doc_id']) : 0;
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['portal_role'] ?? 'client';

if ($doc_id <= 0) {
    die('Invalid file ID');
}

// ===== GET FILE DETAILS =====
$sql = "SELECT d.*, c.user_id as client_user_id, c.id as client_id FROM documents d 
        JOIN clients c ON d.client_id = c.id 
        WHERE d.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $doc_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doc = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$doc) {
    die('File not found in database');
}

// ===== CHECK ACCESS =====
$allowed = false;

// PM and Admin can download any file
if ($user_role === 'admin' || $user_role === 'pm') {
    $allowed = true;
} 
// Client can download their own files
elseif ($user_role === 'client' || $user_role === 'user') {
    $client_check_sql = "SELECT id FROM clients WHERE user_id = ?";
    $client_check_stmt = mysqli_prepare($conn, $client_check_sql);
    mysqli_stmt_bind_param($client_check_stmt, "i", $user_id);
    mysqli_stmt_execute($client_check_stmt);
    $client_check_result = mysqli_stmt_get_result($client_check_stmt);
    $client_check = mysqli_fetch_assoc($client_check_result);
    mysqli_stmt_close($client_check_stmt);
    
    $client_id = $client_check['id'] ?? 0;
    
    if ($doc['client_id'] == $client_id) {
        $allowed = true;
    }
}

if (!$allowed) {
    die('Unauthorized access. Please contact your account manager.');
}

// ===== CHECK FILE EXISTS =====
// Fix: File path calculation for public hosting
$file_path = dirname(__DIR__) . '/' . $doc['file_path'];

// Debug - Remove after testing
error_log("File path: " . $file_path);
error_log("File exists: " . (file_exists($file_path) ? 'YES' : 'NO'));

if (!file_exists($file_path)) {
    // Try alternative paths
    $alt_paths = [
        dirname(__DIR__) . '/uploads/brand2social/' . basename($doc['file_path']),
        __DIR__ . '/uploads/brand2social/' . basename($doc['file_path']),
        dirname(__DIR__) . '/uploads/brand2social/' . basename($doc['file_path'])
    ];
    
    $found = false;
    foreach ($alt_paths as $alt) {
        if (file_exists($alt)) {
            $file_path = $alt;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        die('File not found on server. Please contact support.');
    }
}

// ===== GET MIME TYPE =====
$ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
$mime_types = [
    'pdf' => 'application/pdf',
    'csv' => 'text/csv',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xls' => 'application/vnd.ms-excel'
];
$mime_type = $mime_types[$ext] ?? 'application/octet-stream';

// ===== DOWNLOAD FILE =====
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}
flush();
readfile($file_path);
exit();
?>