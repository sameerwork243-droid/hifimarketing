<?php
// ===== DATABASE CONFIGURATION =====
$host = 'shareddb-m.hosting.stackcp.net';
$user = 'jaweria-3a97';
$pass = 'FAIzan!@#123';
$dbname = 'Hifiwebsite-313031aed2';

// ===== CREATE CONNECTION =====
$conn = mysqli_connect($host, $user, $pass, $dbname);

// ===== CHECK CONNECTION =====
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ===== SET CHARACTER SET (FIXED) =====
mysqli_set_charset($conn, "utf8mb4");

// ===== START SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== SITE URL =====
define('SITE_URL', 'https://hifimarketing.co/');
?>