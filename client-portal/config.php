<?php
// config.php - Database Configuration
session_start();

$host = 'shareddb-m.hosting.stackcp.net';
$user = 'jaweria-3a97';
$pass = 'FAIzan!@#123';
$dbname = 'Hifiwebsite-313031aed2';

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Timezone
date_default_timezone_set('Asia/Karachi');

// NO extra spaces or output after this!
?>