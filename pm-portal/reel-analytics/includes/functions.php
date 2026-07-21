<?php
// =============================================
// HELPER FUNCTIONS
// =============================================

/**
 * Sanitize user input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate URL
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    
    return $ip;
}

/**
 * Format time ago
 */
function timeAgo($datetime) {
    if (empty($datetime)) return 'N/A';
    
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    $periods = [
        31536000 => 'year',
        2592000 => 'month',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];
    
    foreach ($periods as $seconds => $label) {
        if ($difference >= $seconds) {
            $count = floor($difference / $seconds);
            $plural = $count > 1 ? 's' : '';
            return $count . ' ' . $label . $plural . ' ago';
        }
    }
    
    return 'just now';
}

/**
 * Format number
 */
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    }
    if ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}
?>