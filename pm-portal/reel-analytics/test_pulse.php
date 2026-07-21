<?php
// =============================================
// TEST PULSE API DIRECTLY
// =============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

$url = 'https://www.tiktok.com/@daraingularooba/video/7663186919722913042';
$apiUrl = 'https://pulse.walls.sh/metrics?url=' . urlencode($url);

echo "<h2>Testing Pulse API</h2>";
echo "<p><strong>API URL:</strong> " . htmlspecialchars($apiUrl) . "</p>";

// Try with curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";

if ($error) {
    echo "<p style='color:red;'><strong>CURL Error:</strong> " . $error . "</p>";
}

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    echo "<h3 style='color:green;'>✅ SUCCESS!</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    // Test if we can extract key data
    echo "<h4>Extracted Data:</h4>";
    echo "<ul>";
    echo "<li>Views: " . ($data['views'] ?? 'N/A') . "</li>";
    echo "<li>Likes: " . ($data['likes'] ?? 'N/A') . "</li>";
    echo "<li>Comments: " . ($data['comments'] ?? 'N/A') . "</li>";
    echo "<li>Shares: " . ($data['shares'] ?? 'N/A') . "</li>";
    echo "<li>Author: " . ($data['author'] ?? 'N/A') . "</li>";
    echo "<li>Title: " . (substr($data['title'] ?? '', 0, 50) . '...') . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color:red;'>❌ FAILED</p>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
}
?>