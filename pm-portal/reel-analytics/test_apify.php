<?php
// =============================================
// TEST APIFY API CONFIGURATION
// =============================================

require_once __DIR__ . '/includes/init.php';

// Check if token is set
$token = getenv('APIFY_API_TOKEN') ?: '';

echo "<h2>Apify Configuration Test</h2>";

if (empty($token)) {
    echo "<p style='color:red;'>❌ APIFY_API_TOKEN is not set in .env file</p>";
    echo "<p>Add this to your .env file:</p>";
    echo "<pre>APIFY_API_TOKEN=apify_api_YOUR_TOKEN_HERE</pre>";
    exit();
}

echo "<p style='color:green;'>✅ APIFY_API_TOKEN is configured</p>";
echo "<p>Token: " . substr($token, 0, 15) . "..." . substr($token, -5) . "</p>";

// Test the API
echo "<h3>Testing API Connection...</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.apify.com/v2/acts?token=" . $token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "<p style='color:green;'>✅ API connection successful!</p>";
    $data = json_decode($response, true);
    if (isset($data['data']['items'])) {
        echo "<p>Available Actors: " . count($data['data']['items']) . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ API connection failed. HTTP Code: " . $httpCode . "</p>";
    echo "<p>Check your token and try again.</p>";
}
?>