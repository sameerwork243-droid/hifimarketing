<?php
// =============================================
// FETCH RESULTS FROM APIFY RUN
// =============================================

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ReelAnalytics.php';

use Includes\ReelAnalytics;

$apiToken = getenv('APIFY_API_TOKEN') ?: '';

// YOUR DATASET ID FROM THE RUN
$datasetId = 'xqrq6iA2bEIxK0azp';

echo "<h2>Fetching Results from Apify Run</h2>";

if (empty($apiToken)) {
    echo "<p style='color:red;'>❌ API Token not found</p>";
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
        
        // Display the first item
        $item = $data[0];
        
        echo "<div style='background:#f8fafc;padding:20px;border-radius:10px;border:1px solid #e9edf2;margin:10px 0;'>";
        echo "<h4>Video Data:</h4>";
        echo "<table style='width:100%;border-collapse:collapse;'>";
        
        // Common TikTok fields
        $fields = [
            'Author' => 'author',
            'Username' => 'username',
            'Display Name' => 'displayName',
            'Followers' => 'followers',
            'Likes' => 'likes',
            'Comments' => 'comments',
            'Views' => 'views',
            'Shares' => 'shares',
            'Title' => 'title',
            'Duration' => 'duration',
            'Video URL' => 'videoUrl',
            'Cover URL' => 'coverUrl',
            'Created At' => 'createdAt',
            'Video ID' => 'id'
        ];
        
        $hasData = false;
        foreach ($fields as $label => $key) {
            if (isset($item[$key]) && !empty($item[$key])) {
                $value = $item[$key];
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                echo "<tr>";
                echo "<td style='padding:8px;border-bottom:1px solid #e9edf2;font-weight:600;color:#4a5260;width:150px;'><strong>$label</strong></td>";
                echo "<td style='padding:8px;border-bottom:1px solid #e9edf2;'>" . htmlspecialchars($value) . "</td>";
                echo "</tr>";
                $hasData = true;
            }
        }
        
        if (!$hasData) {
            echo "<tr><td colspan='2'>No data found. The video might be private or the actor didn't return data.</td></tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        // Show raw data for debugging
        echo "<h4>Raw Data (for debugging):</h4>";
        echo "<pre style='background:#1a1c26;color:#eaeef2;padding:15px;border-radius:8px;overflow:auto;max-height:400px;'>";
        print_r($data);
        echo "</pre>";
        
        // Save to database
        echo "<h4>Saving to Database:</h4>";
        
        $analytics = new ReelAnalytics();
        
        $videoId = $item['id'] ?? $item['videoId'] ?? $item['video_id'] ?? '';
        $username = $item['author'] ?? $item['username'] ?? '';
        $profileName = $item['displayName'] ?? $item['authorName'] ?? $username;
        $caption = $item['title'] ?? $item['caption'] ?? '';
        $thumbnailUrl = $item['coverUrl'] ?? $item['thumbnailUrl'] ?? '';
        $videoUrl = $item['videoUrl'] ?? $item['url'] ?? '';
        
        $saveData = [
            'reel_url' => $videoUrl ?: 'https://www.tiktok.com/@' . $username . '/video/' . $videoId,
            'platform' => 'tiktok',
            'video_id' => $videoId,
            'username' => $username,
            'profile_name' => $profileName,
            'profile_picture' => $item['avatarUrl'] ?? $item['profilePicture'] ?? '',
            'followers' => (int)($item['followers'] ?? $item['followerCount'] ?? 0),
            'caption' => $caption,
            'thumbnail_url' => $thumbnailUrl,
            'likes' => (int)($item['likes'] ?? $item['likeCount'] ?? 0),
            'comments' => (int)($item['comments'] ?? $item['commentCount'] ?? 0),
            'views' => (int)($item['views'] ?? $item['viewCount'] ?? 0),
            'shares' => (int)($item['shares'] ?? $item['shareCount'] ?? 0),
            'duration' => $item['duration'] ?? 'N/A',
            'status' => 'success'
        ];
        
        $result = $analytics->saveAnalytics($saveData);
        
        if ($result) {
            echo "<p style='color:green;'>✅ Data saved to database successfully!</p>";
            echo "<p>You can view it in the <a href='history.php'>History Page</a></p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to save data to database</p>";
        }
        
    } else {
        echo "<p style='color:orange;'>⚠️ No data found. The video might be private or the URL is invalid.</p>";
        echo "<p>Try again with a different TikTok video URL.</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Failed to fetch data. HTTP Code: " . $httpCode . "</p>";
    echo "<p>Response: " . $response . "</p>";
}
?>