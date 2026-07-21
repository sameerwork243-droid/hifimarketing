<?php
// admin-portal/logout.php - Admin/Super Admin Logout
// Works on both localhost and shared hosting

// ===== Start session =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== Clear all session variables =====
$_SESSION = array();

// ===== Destroy session cookie =====
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// ===== Destroy session =====
session_destroy();

// ===== Redirect to login page =====
// Works for both localhost and shared hosting
header('Location: ../client-portal/login.php?logout=success');
exit();
?>