<?php
// =============================================
// TEST FETCH RESULTS FROM APIFY RUN
// =============================================

require_once __DIR__ . '/includes/init.php';

$apiToken = getenv('APIFY_API_TOKEN') ?: '';

// REPLACE THIS WITH YOUR ACTUAL DATASET ID
$datasetId = 'YOUR_DATASET_ID_HERE';

echo "<h2>Fetching Results from Apify</h2>";

if (empty($apiToken)) {
    echo "<p style='color:red;'>❌ API Token not found</p>";
    exit();
}

if ($datasetId === 'YOUR_DATASET_ID_HERE') {
    echo "<p style='color:orange;'>⚠️ Please replace YOUR_DATASET_ID_HERE with your actual dataset ID</p>";
    echo "<p>Find the dataset ID in the <strong>Dataset</strong> tab of your run.</p>";
    exit();
}

// Fetch the data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.apify.com/v2/datasets/{$datasetId}/items?token=" . $apiToken);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: " . $httpCode . "</p>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (!empty($data)) {
        echo "<h3 style='color:green;'>✅ Data Retrieved Successfully!</h3>";
        echo "<h4>Preview:</h4>";
        echo "<pre>" . print_r($data[0], true) . "</pre>";
        
        // Check if we have the required fields
        $item = $data[0];
        echo "<h4>Key Data Points:</h4>";
        echo "<ul>";
        echo "<li><strong>Author:</strong> " . ($item['author'] ?? 'N/A') . "</li>";
        echo "<li><strong>Username:</strong> " . ($item['username'] ?? 'N/A') . "</li>";
        echo "<li><strong>Likes:</strong> " . ($item['likes'] ?? ($item['likeCount'] ?? 0)) . "</li>";
        echo "<li><strong>Comments:</strong> " . ($item['comments'] ?? ($item['commentCount'] ?? 0)) . "</li>";
        echo "<li><strong>Views:</strong> " . ($item['views'] ?? ($item['viewCount'] ?? 0)) . "</li>";
        echo "<li><strong>Caption:</strong> " . (substr($item['title'] ?? $item['caption'] ?? '', 0, 100) . '...') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color:orange;'>⚠️ No data found in the dataset</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Failed to fetch data. HTTP Code: " . $httpCode . "</p>";
    echo "<p>Response: " . $response . "</p>";
}
?>