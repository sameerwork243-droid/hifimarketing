<?php
// =============================================
// FULL TEST - RUN ACTOR AND FETCH RESULTS
// =============================================

require_once __DIR__ . '/includes/init.php';

$apiToken = getenv('APIFY_API_TOKEN') ?: '';
$url = 'https://www.tiktok.com/@smartaffilx/video/7648321217132350740?is_from_webapp=1&sender_device=pc';

echo "<h2>Full Apify TikTok Test</h2>";

if (empty($apiToken)) {
    echo "<p style='color:red;'>❌ API Token not found in .env file</p>";
    echo "<p>Add: APIFY_API_TOKEN=apify_api_your_token to .env</p>";
    exit();
}

// Step 1: Run the actor
echo "<h3>Step 1: Running Actor...</h3>";

$input = [
    'location' => 'search',
    'keywords' => [$url]
];

$actorId = 'datapilot~tiktok-profile-search-scraper';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.apify.com/v2/acts/{$actorId}/runs?token=" . $apiToken);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "<p style='color:red;'>❌ Actor run failed. HTTP Code: " . $httpCode . "</p>";
    echo "<p>" . $response . "</p>";
    exit();
}

$data = json_decode($response, true);
$datasetId = $data['data']['defaultDatasetId'] ?? null;

if (!$datasetId) {
    echo "<p style='color:red;'>❌ No dataset ID returned</p>";
    exit();
}

echo "<p style='color:green;'>✅ Actor run successful!</p>";
echo "<p>Dataset ID: <strong>" . $datasetId . "</strong></p>";

// Step 2: Wait for results
echo "<h3>Step 2: Waiting for results...</h3>";
echo "<p>Waiting 15 seconds...</p>";
sleep(15);

// Step 3: Fetch results
echo "<h3>Step 3: Fetching results...</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.apify.com/v2/datasets/{$datasetId}/items?token=" . $apiToken);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "<p style='color:red;'>❌ Failed to fetch results. HTTP Code: " . $httpCode . "</p>";
    echo "<p>" . $result . "</p>";
    exit();
}

$items = json_decode($result, true);

if (empty($items)) {
    echo "<p style='color:orange;'>⚠️ No data found. The URL might be private or the video doesn't exist.</p>";
    exit();
}

echo "<p style='color:green;'>✅ Data retrieved successfully!</p>";

// Step 4: Display results
echo "<h3>Step 4: Results:</h3>";

$item = $items[0];

echo "<div style='background:#f8fafc;padding:20px;border-radius:10px;border:1px solid #e9edf2;'>";
echo "<table style='width:100%;border-collapse:collapse;'>";

$fields = [
    'Video URL' => 'videoUrl',
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
    'Cover URL' => 'coverUrl',
    'Created At' => 'createdAt'
];

foreach ($fields as $label => $key) {
    $value = $item[$key] ?? 'N/A';
    if (is_array($value)) {
        $value = json_encode($value);
    }
    echo "<tr>";
    echo "<td style='padding:8px;border-bottom:1px solid #e9edf2;font-weight:600;color:#4a5260;'><strong>$label</strong></td>";
    echo "<td style='padding:8px;border-bottom:1px solid #e9edf2;'>" . htmlspecialchars($value) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Step 5: Save to database
echo "<h3>Step 5: Saving to database...</h3>";

require_once __DIR__ . '/includes/ReelAnalytics.php';
use Includes\ReelAnalytics;

$analytics = new ReelAnalytics();

$saveData = [
    'reel_url' => $url,
    'platform' => 'tiktok',
    'video_id' => $item['id'] ?? $item['videoId'] ?? $item['video_id'] ?? '',
    'username' => $item['author'] ?? $item['username'] ?? '',
    'profile_name' => $item['displayName'] ?? $item['authorName'] ?? '',
    'profile_picture' => $item['avatarUrl'] ?? $item['profilePicture'] ?? '',
    'followers' => $item['followers'] ?? $item['followerCount'] ?? 0,
    'caption' => $item['title'] ?? $item['caption'] ?? '',
    'thumbnail_url' => $item['coverUrl'] ?? $item['thumbnailUrl'] ?? '',
    'likes' => $item['likes'] ?? $item['likeCount'] ?? 0,
    'comments' => $item['comments'] ?? $item['commentCount'] ?? 0,
    'views' => $item['views'] ?? $item['viewCount'] ?? 0,
    'shares' => $item['shares'] ?? $item['shareCount'] ?? 0,
    'duration' => $item['duration'] ?? 'N/A',
    'status' => 'success'
];

$result = $analytics->saveAnalytics($saveData);

if ($result) {
    echo "<p style='color:green;'>✅ Data saved to database!</p>";
} else {
    echo "<p style='color:red;'>❌ Failed to save data to database</p>";
}

echo "<h3>Done!</h3>";
echo "<p>You can now view this data in the <a href='history.php'>History page</a>.</p>";
?>