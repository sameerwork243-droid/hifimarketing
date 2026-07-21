<?php
// =============================================
// TEST CONNECTION - DEBUGGING
// =============================================

echo "<h2>Reel Analytics - Connection Test</h2>";

// Test 1: PHP Version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Test 2: Check file paths
echo "<h3>File Checks:</h3>";
$files = [
    'includes/init.php',
    'includes/ReelAnalytics.php',
    'config/config.php',
    'config/database.php',
    'ajax/fetch-reel.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p style='color:green;'>✅ " . $file . " - Found</p>";
    } else {
        echo "<p style='color:red;'>❌ " . $file . " - NOT Found at: " . $path . "</p>";
    }
}

// Test 3: Database Connection
echo "<h3>Database Test:</h3>";
try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    $db = Config\Database::getInstance();
    echo "<p style='color:green;'>✅ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 4: Pulse API
echo "<h3>Pulse API Test:</h3>";
$testUrl = 'https://pulse.walls.sh/metrics?url=https://www.tiktok.com/@daraingularooba/video/7663186919722913042';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "<p style='color:green;'>✅ Pulse API is reachable</p>";
} else {
    echo "<p style='color:red;'>❌ Pulse API returned: HTTP $httpCode</p>";
}

// Test 5: Directory Permissions
echo "<h3>Permissions Test:</h3>";
$dirs = ['uploads', 'logs'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        echo "<p style='color:green;'>✅ $dir exists and is writable</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ $dir does not exist. Creating...</p>";
        mkdir($path, 0777, true);
        echo "<p style='color:green;'>✅ $dir created</p>";
    }
}
?>