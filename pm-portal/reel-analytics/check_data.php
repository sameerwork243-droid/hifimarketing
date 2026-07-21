<?php
// =============================================
// CHECK DATA IN DATABASE
// =============================================

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/ReelAnalytics.php';

use Includes\ReelAnalytics;

$analytics = new ReelAnalytics();

// Get all data
$data = $analytics->getHistory(1, 50);

echo "<h2>Data in Database</h2>";

if (!empty($data['data'])) {
    echo "<p style='color:green;'>✅ Found " . count($data['data']) . " records</p>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Platform</th><th>Username</th><th>Likes</th><th>Views</th><th>Video ID</th></tr>";
    foreach ($data['data'] as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['platform'] . "</td>";
        echo "<td>" . ($row['username'] ?? 'N/A') . "</td>";
        echo "<td>" . ($row['likes'] ?? 0) . "</td>";
        echo "<td>" . ($row['views'] ?? 0) . "</td>";
        echo "<td>" . ($row['video_id'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>⚠️ No data found in database</p>";
    echo "<p>Your actor ran but the data might not have been saved to the database.</p>";
    echo "<p>Run the fetch script first to save data.</p>";
}
?>