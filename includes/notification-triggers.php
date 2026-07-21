<?php
// ===== TRIGGER NOTIFICATIONS ON EVENTS =====

// ===== WHEN USER APPLIES FOR JOB =====
function notifyAdminNewApplication($job_id, $user_name) {
    $title = "New Job Application";
    $message = "$user_name has applied for a new position.";
    $link = "admin/applications.php";
    addNotification('admin', null, $title, $message, $link);
}

// ===== WHEN ADMIN POSTS NEW JOB =====
function notifyUsersNewJob($job_title) {
    $title = "New Job Posted!";
    $message = "We have a new opening: $job_title";
    $link = "job-detail.php?id=" . getLastJobId();
    addNotification('user', null, $title, $message, $link);
}

// ===== WHEN APPLICATION STATUS CHANGES =====
function notifyUserStatusChange($user_id, $job_title, $status) {
    $title = "Application Status Updated";
    $message = "Your application for '$job_title' is now " . ucfirst($status);
    $link = "user/dashboard.php";
    addNotification('user', $user_id, $title, $message, $link);
}
?>