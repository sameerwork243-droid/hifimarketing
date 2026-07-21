<?php
// client-portal/download_invoice.php - Secure invoice attachment download for clients
session_start();
require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Access denied');
}

$user_id = $_SESSION['user_id'] ?? 0;

// ===== RESOLVE THE LOGGED-IN CLIENT'S client_id =====
$client_id = 0;
if ($user_id > 0) {
    $client_sql = "SELECT id FROM clients WHERE user_id = ?";
    $client_stmt = mysqli_prepare($conn, $client_sql);
    mysqli_stmt_bind_param($client_stmt, "i", $user_id);
    mysqli_stmt_execute($client_stmt);
    $client_result = mysqli_stmt_get_result($client_stmt);
    $client_row = mysqli_fetch_assoc($client_result);
    mysqli_stmt_close($client_stmt);
    if ($client_row) {
        $client_id = $client_row['id'];
    }
}

if ($client_id <= 0) {
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

// ===== VERIFY THIS ATTACHMENT ACTUALLY BELONGS TO THIS CLIENT'S OWN INVOICE =====
// This is critical: without this check, any logged-in client could download
// any other client's invoice attachment just by guessing/looping filenames.
$check_sql = "SELECT id FROM invoices WHERE attachment = ? AND client_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "si", $file, $client_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) === 0) {
    mysqli_stmt_close($check_stmt);
    http_response_code(404);
    exit('File not found');
}
mysqli_stmt_close($check_stmt);

// Files are uploaded/stored under pm-portal/uploads/invoices/
$path = __DIR__ . '/uploads/invoices/' . $file;

if (!file_exists($path)) {
    http_response_code(404);
    exit('File not found on server');
}

$mime = mime_content_type($path);
if (!$mime) {
    $mime = 'application/octet-stream';
}

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