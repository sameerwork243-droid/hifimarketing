<?php
// =============================================
// SAVE APIFY DATA TO DATABASE
// =============================================

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ReelAnalytics.php';

use Includes\ReelAnalytics;

$analytics = new ReelAnalytics();

// ===== YOUR APIFY DATASET ID =====
$datasetId = 'xqrq6iA2bEIxK0azp'; // Replace with your actual dataset ID

// ===== APIFY API TOKEN =====
$apiToken = getenv('APIFY_API_TOKEN') ?: '';

echo "<h2>Saving Apify Data to Database</h2>";

if (empty($apiToken)) {
    echo "<p style='color:red;'>❌ API Token not found in .env file</p>";
    echo "<p>Please add: APIFY_API_TOKEN=apify_api_your_token to .env</p>";
    exit();
}

if ($datasetId === 'xqrq6iA2bEIxK0azp') {
    echo "<p style='color:orange;'>⚠️ You're using the example dataset ID. Please replace it with your actual dataset ID.</p>";
    echo "<p>Find your dataset ID in the Apify Console → Runs → Click on your run → Look for 'Dataset ID'</p>";
    echo "<form method='POST'>";
    echo "<label>Enter your Dataset ID: </label>";
    echo "<input type='text' name='dataset_id' placeholder='xqrq6iA2bEIxK0azp' style='padding:8px;width:300px;'>";
    echo "<button type='submit' style='padding:8px 20px;background:#4a5cf5;color:#fff;border:none;border-radius:8px;cursor:pointer;'>Fetch Data</button>";
    echo "</form>";
    
    // Check if form submitted
    if (isset($_POST['dataset_id']) && !empty($_POST['dataset_id'])) {
        $datasetId = $_POST['dataset_id'];
        echo "<p>Using Dataset ID: <strong>$datasetId</strong></p>";
    } else {
        exit();
    }
}

echo "<h3>Fetching data from Apify...</h3>";
echo "<p>Dataset ID: <strong>$datasetId</strong></p>";

// Fetch data from Apify
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.apify.com/v2/datasets/{$datasetId}/items?token=" . $apiToken);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Response Code: <strong>$httpCode</strong></p>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (!empty($data)) {
        echo "<p style='color:green;'>✅ Data retrieved successfully! Found " . count($data) . " items.</p>";
        
        // Display the data
        echo "<div style='background:#f8fafc;padding:20px;border-radius:10px;border:1px solid #e9edf2;margin:10px 0;max-height:400px;overflow:auto;'>";
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        echo "</div>";
        
        // Save each item to database
        echo "<h3>Saving data to database...</h3>";
        
        $savedCount = 0;
        $errorCount = 0;
        
        foreach ($data as $item) {
            // Map Apify data to your database structure
            $saveData = [
                'reel_url' => $item['url'] ?? $item['videoUrl'] ?? '',
                'platform' => 'tiktok',
                'video_id' => $item['videoId'] ?? $item['id'] ?? '',
                'username' => $item['author'] ?? $item['username'] ?? '',
                'profile_name' => $item['author'] ?? $item['displayName'] ?? '',
                'profile_picture' => $item['avatarUrl'] ?? $item['profilePicture'] ?? '',
                'followers' => (int)($item['followers'] ?? 0),
                'caption' => $item['title'] ?? $item['description'] ?? '',
                'thumbnail_url' => $item['thumbnail'] ?? $item['coverUrl'] ?? '',
                'likes' => (int)($item['likes'] ?? 0),
                'comments' => (int)($item['comments'] ?? 0),
                'views' => (int)($item['views'] ?? 0),
                'shares' => (int)($item['shares'] ?? 0),
                'duration' => $item['duration'] ?? 'N/A',
                'status' => 'success'
            ];
            
            // Skip if no video_id or username
            if (empty($saveData['video_id']) && empty($saveData['username'])) {
                continue;
            }
            
            $result = $analytics->saveAnalytics($saveData);
            
            if ($result) {
                $savedCount++;
                echo "✅ Saved: " . ($saveData['username'] ?? 'Unknown') . " - " . ($saveData['video_id'] ?? 'No ID') . "<br>";
            } else {
                $errorCount++;
                echo "❌ Failed to save: " . ($saveData['username'] ?? 'Unknown') . "<br>";
            }
        }
        
        echo "<hr>";
        echo "<p style='font-size:16px;font-weight:bold;'>";
        echo "✅ Saved: $savedCount items | ";
        echo "❌ Failed: $errorCount items";
        echo "</p>";
        
        echo "<p style='color:green;'>🎉 Data saved successfully! <a href='index.php'>Go to Dashboard →</a></p>";
        
    } else {
        echo "<p style='color:orange;'>⚠️ No data found in the dataset. Make sure your actor ran successfully.</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Failed to fetch data from Apify. HTTP Code: $httpCode</p>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Invalid Dataset ID</li>";
    echo "<li>Invalid API Token</li>";
    echo "<li>Dataset is empty</li>";
    echo "</ul>";
}
?>