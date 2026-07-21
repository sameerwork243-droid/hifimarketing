<?php
// =============================================
// TEST AJAX HANDLER DIRECTLY
// =============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

$url = 'https://www.tiktok.com/@daraingularooba/video/7663186919722913042';

echo "<h2>Testing AJAX Handler</h2>";

// Simulate a POST request to fetch-reel.php
$postData = ['url' => $url];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/public_html/admin-portal/reel-analytics/ajax/fetch-reel.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";
?>