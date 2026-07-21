<?php
// ===== AUTHENTICATION CHECK =====
// This file is included on pages that require login

require_once 'config.php';
require_once 'functions.php';

// ===== CHECK IF USER IS LOGGED IN =====
if (!isLoggedIn()) {
    // Store current page for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}
?>