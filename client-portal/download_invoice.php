<?php
// download_invoice.php - Secure invoice attachment download
session_start();
require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Access denied');
}
if (!isset($_SESSION['portal_role']) || ($_SESSION['portal_role'] !== 'pm' && $_SESSION['portal_role'] !== 'admin')) {
    http_response_code(403);
    exit('Access denied');
}

$file = $_GET['file'] ?? '';

// Prevent directory traversal - allow only the bare filename
$file = basename($file);

if (empty($file)) {
    http_response_code(400);
    exit('No file specified');
}

// Verify the file is actually attached to a real invoice record (extra safety,
// stops someone from guessing/looping filenames to grab files not meant for them)
$check_sql = "SELECT id FROM invoices WHERE attachment = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "s", $file);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) === 0) {
    mysqli_stmt_close($check_stmt);
    http_response_code(404);
    exit('File not found');
}
mysqli_stmt_close($check_stmt);

$path = __DIR__ . '/uploads/invoices/' . $file;

if (!file_exists($path)) {
    http_response_code(404);
    exit('File not found on server');
}

$mime = mime_content_type($path);
if (!$mime) {
    $mime = 'application/octet-stream';
}

// Clear any previous output buffering to avoid corrupting the file stream
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($path);
exit();