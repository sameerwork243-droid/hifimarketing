<?php
// download_invoice.php - Universal Invoice Download
// Place in: public_html/download_invoice.php

session_start();

// ===== CORRECT PATH TO CONFIG =====
require_once __DIR__ . '/includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Access denied');
}

$user_role = $_SESSION['portal_role'] ?? 'client';
if (!in_array($user_role, ['pm', 'admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied');
}

// ===== GET FILE NAME =====
$file = $_GET['file'] ?? '';

if (empty($file)) {
    http_response_code(400);
    exit('No file specified. Use: download_invoice.php?file=filename.png');
}

// Sanitize
$file = basename($file);

// ===== CHECK PATHS =====
$paths = [
    __DIR__ . '/admin-portal/uploads/invoices/' . $file,
    __DIR__ . '/client-portal/uploads/invoices/' . $file,
    __DIR__ . '/uploads/invoices/' . $file,
];

$found = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        $found = $path;
        break;
    }
}

if (!$found) {
    http_response_code(404);
    exit('File not found: ' . $file);
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
header('Content-Description: File Transfer');
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