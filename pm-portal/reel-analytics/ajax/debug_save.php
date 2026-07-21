<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG START ===\n\n";

// 1. Check Config
$mainConfigPath = '/home/sites/32a/2/2fb0787974/public_html/includes/config.php';
echo "1. Checking config at: " . $mainConfigPath . "\n";

if (!file_exists($mainConfigPath)) {
    echo "❌ Config NOT found!\n";
    exit;
}
echo "✅ Config found\n\n";

require_once $mainConfigPath;
global $conn;

// 2. Check Database Connection
echo "2. Checking database connection...\n";
if (!isset($conn) || !$conn) {
    echo "❌ Database NOT connected!\n";
    exit;
}
echo "✅ Database connected\n\n";

// 3. Check if table exists
echo "3. Checking table 'reel_analytics'...\n";
$tableCheck = $conn->query("SHOW TABLES LIKE 'reel_analytics'");
if ($tableCheck->num_rows == 0) {
    echo "❌ Table 'reel_analytics' does NOT exist!\n";
    echo "Creating table now...\n";
    
    $createSQL = "CREATE TABLE IF NOT EXISTS reel_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reel_url VARCHAR(500) NOT NULL,
        platform ENUM('instagram','facebook','tiktok','youtube') NOT NULL,
        video_id VARCHAR(100) NOT NULL,
        username VARCHAR(100) DEFAULT NULL,
        profile_name VARCHAR(255) DEFAULT NULL,
        profile_picture VARCHAR(500) DEFAULT NULL,
        followers INT DEFAULT 0,
        caption TEXT DEFAULT NULL,
        thumbnail_url VARCHAR(500) DEFAULT NULL,
        likes INT DEFAULT 0,
        comments INT DEFAULT 0,
        views INT DEFAULT 0,
        shares INT DEFAULT 0,
        duration VARCHAR(20) DEFAULT NULL,
        fetch_date DATETIME DEFAULT NULL,
        status ENUM('success','failed','pending') DEFAULT 'pending',
        error_message TEXT DEFAULT NULL,
        api_response TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_platform (platform),
        INDEX idx_video_id (video_id),
        INDEX idx_username (username),
        INDEX idx_fetch_date (fetch_date),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createSQL)) {
        echo "✅ Table created successfully!\n";
    } else {
        echo "❌ Table creation failed: " . $conn->error . "\n";
        exit;
    }
} else {
    echo "✅ Table exists\n\n";
}

// 4. Count records
echo "4. Counting records...\n";
$countResult = $conn->query("SELECT COUNT(*) as total FROM reel_analytics");
$count = $countResult->fetch_assoc();
echo "📊 Total records in table: " . $count['total'] . "\n\n";

// 5. Show recent records
echo "5. Recent records (last 5):\n";
$recentResult = $conn->query("SELECT id, platform, username, views, likes, fetch_date, status FROM reel_analytics ORDER BY id DESC LIMIT 5");
if ($recentResult->num_rows > 0) {
    while ($row = $recentResult->fetch_assoc()) {
        echo "   ID: " . $row['id'] . " | " . $row['platform'] . " | @" . $row['username'] . " | Views: " . $row['views'] . " | Status: " . $row['status'] . "\n";
    }
} else {
    echo "   No records found\n";
}

echo "\n=== DEBUG END ===\n";
?>