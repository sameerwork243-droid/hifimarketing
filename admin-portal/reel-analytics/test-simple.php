<?php
// =============================================
// SIMPLE PHP TEST
// =============================================

echo "Hello from PHP! The server is working.";

// Check if index.php exists
if (file_exists('index.php')) {
    echo "<br>✅ index.php exists.";
} else {
    echo "<br>❌ index.php NOT found!";
}
?>