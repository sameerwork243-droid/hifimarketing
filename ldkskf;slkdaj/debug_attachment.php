<?php
// debug_attachment.php - Debug attachment download
// Place in: public_html/admin-portal/debug_attachment.php

session_start();

// ===== CORRECT PATH TO CONFIG =====
// Since this file is in admin-portal/, we need to go up one level to public_html/
require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Not logged in');
}

echo "<h2>🔍 Attachment Debug Information</h2>";

// Check GET parameter
$file = $_GET['file'] ?? '';
echo "<p><strong>File from GET:</strong> '" . $file . "'</p>";

if (empty($file)) {
    echo "<p style='color:red;'>❌ No file parameter found in URL!</p>";
    echo "<p>URL should be: <code>debug_attachment.php?file=filename.png</code></p>";
    echo "<p>Try: <a href='debug_attachment.php?file=1783901399_biz.png'>debug_attachment.php?file=1783901399_biz.png</a></p>";
    exit();
}

$file = basename($file);
echo "<p><strong>Sanitized File:</strong> '" . $file . "'</p>";

// Check database
echo "<h3>📊 Database Check:</h3>";
$check_sql = "SELECT id, client_id, attachment FROM invoices WHERE attachment = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "s", $file);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if ($row = mysqli_fetch_assoc($check_result)) {
    echo "<p style='color:green;'>✅ Found in invoice ID: " . $row['id'] . "</p>";
    echo "<p>Attachment: " . $row['attachment'] . "</p>";
} else {
    echo "<p style='color:red;'>❌ NOT found in database</p>";
}
mysqli_stmt_close($check_stmt);

// Check file paths
echo "<h3>📁 File Path Check:</h3>";
$paths = [
    __DIR__ . '/uploads/invoices/' . $file,
    __DIR__ . '/../client-portal/uploads/invoices/' . $file,
    __DIR__ . '/../uploads/invoices/' . $file,
];

$found_path = null;
foreach ($paths as $path) {
    $exists = file_exists($path);
    $display_path = str_replace('E:\\XAMPP\\htdocs\\', '', $path);
    echo "<p>" . ($exists ? '✅' : '❌') . " " . $display_path . 
         ($exists ? ' - <span style="color:green;">FOUND</span>' : ' - <span style="color:red;">NOT FOUND</span>') . "</p>";
    if ($exists) {
        $found_path = $path;
    }
}

// If file found, show download link
if ($found_path) {
    echo "<h3>📥 Download File:</h3>";
    echo "<p><a href='direct_download.php?file=" . urlencode($file) . "' target='_blank'>Download via direct_download.php</a></p>";
    echo "<p><a href='../download_invoice.php?file=" . urlencode($file) . "' target='_blank'>Download via root download_invoice.php</a></p>";
}

// Check session
echo "<h3>👤 Session Info:</h3>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>Role: " . ($_SESSION['portal_role'] ?? 'Not set') . "</p>";
echo "<p>User Name: " . ($_SESSION['user']['name'] ?? 'Not set') . "</p>";
?>