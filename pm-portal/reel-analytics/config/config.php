<?php
// =============================================
// REEL ANALYTICS CONFIGURATION
// =============================================

// =============================================
// PATHS
// =============================================
define('REEL_ANALYTICS_PATH', __DIR__ . '/..');
define('UPLOAD_PATH', REEL_ANALYTICS_PATH . '/uploads');
define('LOG_PATH', REEL_ANALYTICS_PATH . '/logs');

// =============================================
// APP CONFIG
// =============================================
define('APP_NAME', 'Reel Analytics');
define('DEBUG_MODE', false);

// =============================================
// API CONFIG
// =============================================
define('API_TIMEOUT', 30);
define('CACHE_TTL', 3600);

// =============================================
// RATE LIMITS
// =============================================
define('RATE_LIMIT_PER_MINUTE', 10);
define('RATE_LIMIT_PER_HOUR', 50);

// =============================================
// ERROR REPORTING
// =============================================
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>