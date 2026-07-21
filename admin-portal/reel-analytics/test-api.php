<?php
// =============================================
// TEST API CONNECTION
// =============================================

header('Content-Type: text/html');

echo "<h2>🔍 API Connection Test</h2>";

$url = 'https://pulse.walls.sh/metrics?url=https://www.tiktok.com/@daraingularooba/video/7663186919722913042';

echo "<h3>Testing: " . htmlspecialchars($url) . "</h3>";

// Test cURL
if (function_exists('curl_version')) {
    echo "<p>✅ cURL is available</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p>HTTP Code: " . $httpCode . "</p>";
    
    if ($httpCode === 200 && $response) {
        echo "<p style='color:green;'>✅ API Response received!</p>";
        echo "<h4>Response Preview:</h4>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "...</pre>";
        
        $data = json_decode($response, true);
        if ($data) {
            echo "<p style='color:green;'>✅ JSON is valid!</p>";
            echo "<pre>" . print_r($data, true) . "</pre>";
        } else {
            echo "<p style='color:red;'>❌ Invalid JSON response</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ API request failed: " . $error . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ cURL is NOT available</p>";
}

// Test file_get_contents
echo "<h3>Testing file_get_contents:</h3>";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 15,
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n" .
                    "Accept: application/json\r\n"
    ],
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
]);
$response2 = @file_get_contents($url, false, $context);
if ($response2) {
    echo "<p style='color:green;'>✅ file_get_contents works!</p>";
} else {
    echo "<p style='color:red;'>❌ file_get_contents failed</p>";
}

echo "<h3>PHP Info:</h3>";
echo "<p>allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅ On' : '❌ Off') . "</p>";
echo "<p>open_basedir: " . (ini_get('open_basedir') ? ini_get('open_basedir') : 'Not set') . "</p>";
?>