<?php
// =============================================
// TEST AJAX HANDLER
// =============================================

$url = 'https://www.tiktok.com/@daraingularooba/video/7663186919722913042';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/public_html/admin-portal/reel-analytics/ajax/fetch-reel.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['url' => $url]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>AJAX Handler Test</h2>";
echo "<p>HTTP Code: " . $httpCode . "</p>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";
?>