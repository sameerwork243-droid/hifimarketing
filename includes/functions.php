<?php
// ===== CHECK IF USER IS LOGGED IN =====
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ===== CHECK IF USER IS ADMIN =====
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// ===== GET CURRENT USER =====
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// ===== REDIRECT IF NOT LOGGED IN =====
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// ===== REDIRECT IF NOT ADMIN =====
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

// ===== SANITIZE INPUT =====
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// ===== VALIDATE EMAIL =====
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
// ===== TIME AGO FUNCTION =====
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "1 day ago" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}
?>
