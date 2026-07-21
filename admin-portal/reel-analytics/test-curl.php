<?php
// =============================================
// TEST CURL CONNECTION
// =============================================

echo "<h2>🔍 Testing cURL Connection</h2>";

// Check if cURL is installed
if (function_exists('curl_version')) {
    echo "<p style='color:green;'>✅ cURL is installed</p>";
    $version = curl_version();
    echo "<p>cURL Version: " . $version['version'] . "</p>";
} else {
    echo "<p style='color:red;'>❌ cURL is NOT installed</p>";
    exit;
}

// Test 1: Simple HTTP request to httpbin
echo "<h3>Test 1: Simple HTTP Request</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://httpbin.org/get');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response) {
    echo "<p style='color:green;'>✅ HTTP request successful (HTTP $httpCode)</p>";
} else {
    echo "<p style='color:red;'>❌ HTTP request failed: " . $error . "</p>";
}

// Test 2: Pulse API Connection
echo "<h3>Test 2: Pulse API Connection</h3>";
$url = 'https://pulse.walls.sh/metrics?url=https://www.tiktok.com/@daraingularooba/video/7663186919722913042';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
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

echo "<p>HTTP Code: " . $httpCode . "</p>";

if ($httpCode === 200) {
    echo "<p style='color:green;'>✅ Pulse API is reachable!</p>";
    echo "<pre>" . substr($response, 0, 500) . "...</pre>";
} else {
    echo "<p style='color:red;'>❌ Pulse API failed: HTTP " . $httpCode . "</p>";
    if ($error) {
        echo "<p>Error: " . $error . "</p>";
    }
}

// Test 3: DNS Resolution
echo "<h3>Test 3: DNS Resolution</h3>";
$host = 'pulse.walls.sh';
$ip = gethostbyname($host);
if ($ip && $ip !== $host) {
    echo "<p style='color:green;'>✅ DNS resolved: " . $host . " → " . $ip . "</p>";
} else {
    echo "<p style='color:red;'>❌ DNS resolution failed for: " . $host . "</p>";
}
?>