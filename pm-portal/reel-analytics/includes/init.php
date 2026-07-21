<?php
// =============================================
// INITIALIZATION - LOAD ALL REQUIRED FILES
// =============================================

// =============================================
// USE THE EXACT ABSOLUTE PATH (FOUND FROM find-path.php)
// =============================================
$mainConfigPath = '/home/sites/32a/2/2fb0787974/public_html/includes/config.php';

if (!file_exists($mainConfigPath)) {
    die("Main config file not found at: " . $mainConfigPath);
}

// Load the main config
require_once $mainConfigPath;

// =============================================
// NOW LOAD REEL ANALYTICS CONFIG
// =============================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// =============================================
// LOAD HELPER FUNCTIONS
// =============================================
require_once __DIR__ . '/functions.php';

// =============================================
// LOAD CORE CLASSES
// =============================================
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/ReelAnalytics.php';

// =============================================
// SESSION CHECK
// =============================================
function requireLogin() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../../client-portal/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['portal_role']) || 
        ($_SESSION['portal_role'] !== 'admin' && 
         $_SESSION['portal_role'] !== 'pm' && 
         $_SESSION['portal_role'] !== 'super_admin')) {
        header('Location: ../index.php');
        exit();
    }
}

// =============================================
// CREATE REQUIRED DIRECTORIES
// =============================================
function createDirectories() {
    $dirs = [
        LOG_PATH,
        UPLOAD_PATH
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

createDirectories();

// =============================================
// SESSION START
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>