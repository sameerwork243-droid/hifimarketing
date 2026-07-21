<?php
// =============================================
// TEST AJAX DIRECTLY
// =============================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/init.php';

$url = 'https://www.tiktok.com/@test/video/123456';

// Simulate the AJAX call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/public_html/admin-portal/reel-analytics/ajax/fetch-reel-simple.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['url' => $url]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>AJAX Test Result</h2>";
echo "<p>HTTP Code: " . $httpCode . "</p>";
echo "<p>Response:</p>";
echo "<pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";
?>