<?php
// =============================================
// TEST DATABASE CONNECTION
// =============================================

echo "<h2>Database Connection Test</h2>";

// Try different connection methods
$methods = [
    'localhost' => 'localhost',
    '127.0.0.1' => '127.0.0.1',
    'socket' => '/tmp/mysql.sock',
    'socket2' => '/var/run/mysqld/mysqld.sock'
];

$dbname = 'hifiwebsite-313031aed2';
$username = 'hifiweb_313031aed2';
$password = 'Hew0ieTdH!@#';

foreach ($methods as $name => $host) {
    try {
        if (strpos($name, 'socket') !== false) {
            $dsn = "mysql:unix_socket={$host};dbname={$dbname};charset=utf8mb4";
        } else {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        }
        
        $conn = new PDO($dsn, $username, $password);
        echo "<p style='color:green;'>✅ Method '$name' - Connected!</p>";
        break;
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ Method '$name' - Failed: " . $e->getMessage() . "</p>";
    }
}
?>