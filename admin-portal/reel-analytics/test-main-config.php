<?php
// =============================================
// TEST MAIN CONFIG
// =============================================

echo "<h2>Testing Main Config Connection</h2>";

// Load main config
$mainConfigPath = __DIR__ . '/../../includes/config.php';

if (file_exists($mainConfigPath)) {
    echo "<p style='color:green;'>✅ Main config found at: " . $mainConfigPath . "</p>";
    require_once $mainConfigPath;
    
    // Check if connection exists
    global $conn;
    if (isset($conn) && $conn) {
        echo "<p style='color:green;'>✅ Database connection exists!</p>";
        
        // Test query
        try {
            $stmt = $conn->query("SELECT DATABASE() as db");
            $result = $stmt->fetch_assoc();
            echo "<p style='color:green;'>✅ Connected to database: <strong>" . $result['db'] . "</strong></p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Query failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ No database connection found in main config</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Main config NOT found at: " . $mainConfigPath . "</p>";
}

// Show PHP info
echo "<h3>PHP Info:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "</p>";
?>