<?php
// =============================================
// FIND CORRECT PATHS
// =============================================

echo "<h2>Path Finder</h2>";

// Current file location
echo "<p><strong>Current File:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";

// Try different paths
$paths = [
    __DIR__ . '/../../includes/config.php',
    __DIR__ . '/../includes/config.php',
    __DIR__ . '/../config.php',
    __DIR__ . '/../../../includes/config.php',
    __DIR__ . '/../../../../includes/config.php',
    '/home/sites/32a/2/2fb0787974/public_html/includes/config.php',
    '/home/sites/32a/2/2fb0787974/public_html/config.php',
];

echo "<h3>Testing Paths:</h3>";
foreach ($paths as $path) {
    if (file_exists($path)) {
        echo "<p style='color:green;'>✅ FOUND: " . $path . "</p>";
        echo "<p style='color:blue;font-size:16px;'>Use this path: <strong>" . $path . "</strong></p>";
        // Show content preview
        echo "<pre>" . substr(file_get_contents($path), 0, 500) . "...</pre>";
        break;
    } else {
        echo "<p style='color:red;'>❌ NOT FOUND: " . $path . "</p>";
    }
}
?>