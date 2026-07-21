<?php
require_once 'includes/config.php';

// Database connection check
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$email = 'hifimarketing.co@gmail.com';
$new_password = 'Admin@2026'; // Isko apni marzi se change karein

$hashed = password_hash($new_password, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = '$hashed' WHERE email = '$email'";

if (mysqli_query($conn, $sql)) {
    echo "✅ PASSWORD RESET SUCCESSFUL!<br><br>";
    echo "📧 Email: " . $email . "<br>";
    echo "🔑 New Password: " . $new_password . "<br><br>";
    echo "⚠️ <strong style='color:red;'>DELETE THIS FILE NOW FOR SECURITY!</strong>";
} else {
    echo "❌ Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>