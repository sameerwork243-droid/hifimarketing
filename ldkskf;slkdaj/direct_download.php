<?php
// direct_download.php - Simple direct file download
// Place in: public_html/admin-portal/direct_download.php

session_start();

// ===== CORRECT PATH TO CONFIG =====
require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Access denied');
}

// ===== GET FILE NAME =====
$file = $_GET['file'] ?? '';

if (empty($file)) {
    die('No file specified. Please use: direct_download.php?file=filename.png');
}

// Sanitize
$file = basename($file);

// ===== CHECK PATHS =====
$paths = [
    __DIR__ . '/uploads/invoices/' . $file,           // admin-portal/uploads/invoices/
    __DIR__ . '/../client-portal/uploads/invoices/' . $file,  // client-portal/uploads/invoices/
    __DIR__ . '/../uploads/invoices/' . $file,        // public_html/uploads/invoices/
];

$found = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        $found = $path;
        break;
    }
}

if (!$found) {
    die('File not found: ' . $file);
}

// ===== SEND FILE =====
$mime = mime_content_type($found);
if (!$mime) {
    $mime = 'application/octet-stream';
}

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Headers
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($found));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Send file
readfile($found);
exit();
?>