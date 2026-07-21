<?php
// ============================================================
// NOTIFICATIONS SYSTEM
// ============================================================

// ===== ADD NOTIFICATION FUNCTION =====
function addNotification($type, $user_id, $title, $message, $link = null) {
    global $conn;
    $query = "INSERT INTO notifications (type, user_id, title, message, link, is_read) 
              VALUES (?, ?, ?, ?, ?, 0)";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sisss", $type, $user_id, $title, $message, $link);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    return false;
}

// ===== GET UNREAD NOTIFICATIONS =====
function getUnreadNotifications($user_id = null, $type = null) {
    global $conn;
    $query = "SELECT * FROM notifications WHERE is_read = 0";
    
    if ($user_id !== null) {
        $query .= " AND (user_id = $user_id OR user_id IS NULL)";
    }
    if ($type !== null) {
        $query .= " AND type = '$type'";
    }
    
    $query .= " ORDER BY created_at DESC LIMIT 20";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return [];
}

// ===== MARK NOTIFICATION AS READ =====
function markNotificationRead($id) {
    global $conn;
    $query = "UPDATE notifications SET is_read = 1 WHERE id = $id";
    return mysqli_query($conn, $query);
}

// ===== MARK ALL NOTIFICATIONS AS READ =====
function markAllNotificationsRead($user_id = null) {
    global $conn;
    $query = "UPDATE notifications SET is_read = 1 WHERE is_read = 0";
    if ($user_id !== null) {
        $query .= " AND (user_id = $user_id OR user_id IS NULL)";
    }
    return mysqli_query($conn, $query);
}

// ===== GET NOTIFICATION COUNT =====
function getNotificationCount($user_id = null, $type = null) {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM notifications WHERE is_read = 0";
    
    if ($user_id !== null) {
        $query .= " AND (user_id = $user_id OR user_id IS NULL)";
    }
    if ($type !== null) {
        $query .= " AND type = '$type'";
    }
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }
    return 0;
}

// ===== DELETE OLD NOTIFICATIONS =====
function deleteOldNotifications($days = 30) {
    global $conn;
    $query = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)";
    return mysqli_query($conn, $query);
}

// ===== TRIGGER FUNCTIONS =====

// When user applies for job
function notifyAdminNewApplication($job_title, $user_name) {
    $title = "📝 New Job Application";
    $message = "$user_name has applied for: $job_title";
    $link = "admin/applications.php";
    return addNotification('admin', null, $title, $message, $link);
}

// When admin posts new job
function notifyUsersNewJob($job_title, $job_id) {
    $title = "🎯 New Job Posted!";
    $message = "We have a new opening: $job_title";
    $link = "../job-detail.php?id=$job_id";
    return addNotification('user', null, $title, $message, $link);
}

// When application status changes
function notifyUserStatusChange($user_id, $job_title, $status) {
    $status_labels = [
        'pending' => 'Pending',
        'reviewed' => 'Reviewed',
        'shortlisted' => 'Shortlisted',
        'rejected' => 'Rejected'
    ];
    $status_label = $status_labels[$status] ?? ucfirst($status);
    $title = "📊 Application Status Updated";
    $message = "Your application for '$job_title' is now: $status_label";
    $link = "user/dashboard.php";
    return addNotification('user', $user_id, $title, $message, $link);
}

// When user registers
function notifyAdminNewUser($username) {
    $title = "👤 New User Registered";
    $message = "$username has created an account.";
    $link = "admin/users.php";
    return addNotification('admin', null, $title, $message, $link);
}

// When contact message received
function notifyAdminNewMessage($name, $subject) {
    $title = "✉️ New Contact Message";
    $message = "$name sent: $subject";
    $link = "admin/messages.php";
    return addNotification('admin', null, $title, $message, $link);
}
?>