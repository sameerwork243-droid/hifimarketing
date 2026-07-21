<?php
// ===== REQUIRE ADMIN AUTH =====
require_once '../includes/config.php';
require_once '../includes/functions.php';

// ===== CHECK ADMIN ACCESS =====
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

if (!isAdmin()) {
    header('Location: ../user/dashboard.php');
    exit();
}

// ===== INCLUDE NOTIFICATIONS =====
require_once '../includes/notifications.php';

// ... rest of your code ...


// ===== GET STATISTICS =====
$jobs_query = "SELECT COUNT(*) as total FROM jobs WHERE is_active = 1";
$jobs_result = mysqli_query($conn, $jobs_query);
$total_jobs = mysqli_fetch_assoc($jobs_result)['total'];

$apps_query = "SELECT COUNT(*) as total FROM applications";
$apps_result = mysqli_query($conn, $apps_query);
$total_applications = mysqli_fetch_assoc($apps_result)['total'];

$pending_query = "SELECT COUNT(*) as total FROM applications WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_applications = mysqli_fetch_assoc($pending_result)['total'];

// ===== GET UNREAD MESSAGES COUNT =====
$msg_query = "SELECT COUNT(*) as total FROM messages WHERE status = 'unread'";
$msg_result = mysqli_query($conn, $msg_query);
$unread_messages = mysqli_fetch_assoc($msg_result)['total'];

// ===== GET ADMIN NOTIFICATIONS =====
$admin_notifications = getUnreadNotifications(null, 'admin');
$admin_notification_count = getNotificationCount(null, 'admin');

// ===== MARK ALL NOTIFICATIONS AS READ =====
if (isset($_GET['mark_read'])) {
    markAllNotificationsRead(null);
    header('Location: dashboard.php');
    exit();
}

// ===== MARK SINGLE NOTIFICATION AS READ =====
if (isset($_GET['mark_single']) && is_numeric($_GET['mark_single'])) {
    markNotificationRead((int)$_GET['mark_single']);
    header('Location: dashboard.php');
    exit();
}

$recent_query = "SELECT a.*, j.title as job_title FROM applications a 
                 LEFT JOIN jobs j ON a.job_id = j.id 
                 ORDER BY a.applied_at DESC LIMIT 5";
$recent_result = mysqli_query($conn, $recent_query);
$recent_applications = mysqli_fetch_all($recent_result, MYSQLI_ASSOC);

$recent_jobs_query = "SELECT * FROM jobs WHERE is_active = 1 ORDER BY posted_date DESC LIMIT 5";
$recent_jobs_result = mysqli_query($conn, $recent_jobs_query);
$recent_jobs = mysqli_fetch_all($recent_jobs_result, MYSQLI_ASSOC);

$status_query = "SELECT status, COUNT(*) as count FROM applications GROUP BY status";
$status_result = mysqli_query($conn, $status_query);
$status_counts = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_counts[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard | HIFI Marketing & Technologies</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        :root {
            --admin-sidebar-width: 260px;
            --admin-header-height: 70px;
            --admin-primary: #4a5cf5;
            --admin-primary-dark: #3a4be0;
            --admin-bg: #f0f2f5;
            --admin-card-bg: #ffffff;
            --admin-text-primary: #1a1c26;
            --admin-text-secondary: #3d4452;
            --admin-text-muted: #8a94a0;
            --admin-border: #e9edf2;
            --admin-shadow-hover: 0 8px 40px rgba(0,0,0,0.08);
            --admin-radius: 16px;
            --admin-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--admin-bg);
            color: var(--admin-text-primary);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }

        .admin-sidebar {
            width: var(--admin-sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #ffffff;
            border-right: 1px solid var(--admin-border);
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        .admin-sidebar .logo {
            font-size: 22px;
            font-weight: 900;
            color: var(--admin-text-primary);
            padding-bottom: 24px;
            border-bottom: 1px solid var(--admin-border);
            margin-bottom: 24px;
        }
        .admin-sidebar .logo span { color: var(--admin-primary); }
        .admin-sidebar .logo small {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: var(--admin-text-muted);
            letter-spacing: 0.5px;
        }

        .admin-sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--admin-text-secondary);
            font-weight: 600;
            font-size: 14px;
            transition: var(--admin-transition);
            cursor: pointer;
            margin-bottom: 4px;
        }
        .admin-sidebar .nav-item i {
            width: 20px;
            font-size: 16px;
            color: var(--admin-text-muted);
            transition: var(--admin-transition);
        }
        .admin-sidebar .nav-item:hover {
            background: #f8fafc;
            color: var(--admin-primary);
        }
        .admin-sidebar .nav-item:hover i {
            color: var(--admin-primary);
        }
        .admin-sidebar .nav-item.active {
            background: var(--admin-primary);
            color: #ffffff;
        }
        .admin-sidebar .nav-item.active i {
            color: #ffffff;
        }
        .admin-sidebar .nav-item .badge {
            margin-left: auto;
            background: #f0f3ff;
            color: var(--admin-primary);
            font-size: 11px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .admin-sidebar .nav-item.active .badge {
            background: rgba(255,255,255,0.2);
            color: #ffffff;
        }

        /* ===== HOME BUTTON IN SIDEBAR ===== */
        .admin-sidebar .nav-item.home-link {
            background: #f0f3ff;
            border: 1px solid #e9edf2;
            margin-bottom: 12px;
        }
        .admin-sidebar .nav-item.home-link:hover {
            background: #4a5cf5;
            color: #ffffff;
        }
        .admin-sidebar .nav-item.home-link:hover i {
            color: #ffffff;
        }
        .admin-sidebar .nav-item.home-link i {
            color: #4a5cf5;
        }

        .admin-sidebar .sidebar-divider {
            border: none;
            border-top: 1px solid var(--admin-border);
            margin: 8px 0 12px;
        }

        .admin-sidebar .sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--admin-border);
        }
        .admin-sidebar .sidebar-footer .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            background: #f8fafc;
        }
        .admin-sidebar .sidebar-footer .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--admin-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        .admin-sidebar .sidebar-footer .user-info .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--admin-text-primary);
        }
        .admin-sidebar .sidebar-footer .user-info .user-role {
            font-size: 12px;
            color: var(--admin-text-muted);
        }
        .admin-sidebar .sidebar-footer .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 12px;
            color: #dc3545;
            font-weight: 600;
            font-size: 14px;
            transition: var(--admin-transition);
            margin-top: 8px;
            cursor: pointer;
        }
        .admin-sidebar .sidebar-footer .logout-btn:hover {
            background: #fee2e2;
        }

        .admin-main {
            margin-left: var(--admin-sidebar-width);
            flex: 1;
            padding: 0;
            min-height: 100vh;
        }

        .admin-header {
            height: var(--admin-header-height);
            background: #ffffff;
            border-bottom: 1px solid var(--admin-border);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .admin-header .page-title h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--admin-text-primary);
        }
        .admin-header .page-title p {
            font-size: 13px;
            color: var(--admin-text-muted);
        }
        .admin-header .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
        }

        /* ===== NOTIFICATION BUTTON ===== */
        .admin-header .header-actions .notification-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--admin-border);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--admin-transition);
            position: relative;
            color: var(--admin-text-secondary);
        }
        .admin-header .header-actions .notification-btn:hover {
            background: #f8fafc;
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }
        .admin-header .header-actions .notification-btn .dot {
            position: absolute;
            top: 6px;
            right: 6px;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #dc3545;
            border: 2px solid #fff;
            font-size: 10px;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        .admin-header .header-actions .theme-toggle-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--admin-border);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--admin-transition);
            color: var(--admin-text-secondary);
        }
        .admin-header .header-actions .theme-toggle-btn:hover {
            background: #f8fafc;
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }

        /* ===== NOTIFICATION DROPDOWN ===== */
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 55px;
            right: 0;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--admin-border);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            width: 380px;
            max-height: 460px;
            overflow-y: auto;
            z-index: 999;
        }
        .notification-dropdown.show {
            display: block;
        }
        .notification-dropdown .dropdown-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: #ffffff;
            z-index: 1;
        }
        .notification-dropdown .dropdown-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--admin-text-primary);
        }
        .notification-dropdown .dropdown-header a {
            font-size: 12px;
            color: var(--admin-primary);
            font-weight: 600;
        }
        .notification-dropdown .dropdown-header a:hover {
            text-decoration: underline;
        }
        .notification-dropdown .notification-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f2f5;
            transition: 0.2s;
            cursor: pointer;
        }
        .notification-dropdown .notification-item:hover {
            background: #f8fafc;
        }
        .notification-dropdown .notification-item .notif-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--admin-text-primary);
        }
        .notification-dropdown .notification-item .notif-message {
            font-size: 13px;
            color: var(--admin-text-secondary);
            margin-top: 2px;
        }
        .notification-dropdown .notification-item .notif-time {
            font-size: 11px;
            color: var(--admin-text-muted);
            margin-top: 4px;
        }
        .notification-dropdown .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: var(--admin-text-muted);
        }
        .notification-dropdown .empty-state i {
            font-size: 32px;
            display: block;
            margin-bottom: 8px;
            color: #d0d7e0;
        }

        .admin-content {
            padding: 28px 32px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--admin-card-bg);
            border-radius: var(--admin-radius);
            border: 1px solid var(--admin-border);
            padding: 22px 24px;
            transition: var(--admin-transition);
        }
        .stat-card:hover {
            box-shadow: var(--admin-shadow-hover);
            transform: translateY(-2px);
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        .stat-card .stat-icon.blue { background: #e8edfe; color: #4a5cf5; }
        .stat-card .stat-icon.green { background: #e8f5e9; color: #2e7d32; }
        .stat-card .stat-icon.orange { background: #fff3e0; color: #e65100; }
        .stat-card .stat-icon.purple { background: #f3e5f5; color: #6a1b9a; }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 900;
            color: var(--admin-text-primary);
            line-height: 1.2;
        }
        .stat-card .stat-label {
            font-size: 14px;
            color: var(--admin-text-muted);
            font-weight: 500;
        }
        .stat-card .stat-change {
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
        }
        .stat-card .stat-change.up { background: #e8f5e9; color: #2e7d32; }
        .stat-card .stat-change.down { background: #fee2e2; color: #dc3545; }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .chart-card {
            background: var(--admin-card-bg);
            border-radius: var(--admin-radius);
            border: 1px solid var(--admin-border);
            padding: 24px;
            transition: var(--admin-transition);
        }
        .chart-card:hover {
            box-shadow: var(--admin-shadow-hover);
        }
        .chart-card .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .chart-card .chart-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--admin-text-primary);
        }
        .chart-card .chart-header .chart-action {
            font-size: 13px;
            color: var(--admin-primary);
            font-weight: 600;
            cursor: pointer;
        }
        .chart-card .chart-header .chart-action:hover {
            color: var(--admin-primary-dark);
        }

        .status-bars {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .status-bar-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .status-bar-item .status-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--admin-text-secondary);
            min-width: 80px;
        }
        .status-bar-item .status-track {
            flex: 1;
            height: 8px;
            border-radius: 10px;
            background: #e9edf2;
            overflow: hidden;
        }
        .status-bar-item .status-track .status-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }
        .status-bar-item .status-track .status-fill.blue { background: #4a5cf5; }
        .status-bar-item .status-track .status-fill.green { background: #2e7d32; }
        .status-bar-item .status-track .status-fill.orange { background: #e65100; }
        .status-bar-item .status-track .status-fill.red { background: #dc3545; }
        .status-bar-item .status-count {
            font-size: 14px;
            font-weight: 700;
            color: var(--admin-text-primary);
            min-width: 30px;
            text-align: right;
        }

        .recent-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .recent-card {
            background: var(--admin-card-bg);
            border-radius: var(--admin-radius);
            border: 1px solid var(--admin-border);
            padding: 24px;
            transition: var(--admin-transition);
        }
        .recent-card:hover {
            box-shadow: var(--admin-shadow-hover);
        }
        .recent-card .recent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .recent-card .recent-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--admin-text-primary);
        }
        .recent-card .recent-header a {
            font-size: 13px;
            color: var(--admin-primary);
            font-weight: 600;
        }
        .recent-card .recent-header a:hover {
            color: var(--admin-primary-dark);
        }

        .recent-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
        }
        .recent-item:last-child { border-bottom: none; }
        .recent-item .recent-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .recent-item .recent-icon.blue { background: #e8edfe; color: #4a5cf5; }
        .recent-item .recent-icon.green { background: #e8f5e9; color: #2e7d32; }
        .recent-item .recent-icon.orange { background: #fff3e0; color: #e65100; }
        .recent-item .recent-icon.purple { background: #f3e5f5; color: #6a1b9a; }
        .recent-item .recent-content {
            flex: 1;
        }
        .recent-item .recent-content .recent-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--admin-text-primary);
        }
        .recent-item .recent-content .recent-sub {
            font-size: 12px;
            color: var(--admin-text-muted);
        }
        .recent-item .recent-time {
            font-size: 12px;
            color: var(--admin-text-muted);
            white-space: nowrap;
        }

        /* ===== RESUME BUTTON ===== */
        .btn-resume {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: #e8edfe;
            color: var(--admin-primary);
            border: none;
            cursor: pointer;
            transition: var(--admin-transition);
            text-decoration: none;
        }
        .btn-resume:hover {
            background: var(--admin-primary);
            color: #fff;
        }
        .btn-resume.download {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .btn-resume.download:hover {
            background: #2e7d32;
            color: #fff;
        }

        .hamburger-btn {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--admin-border);
            background: transparent;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--admin-text-secondary);
            font-size: 18px;
        }
        .hamburger-btn:hover {
            background: #f8fafc;
        }

        /* ===== RESUME PREVIEW MODAL ===== */
        .resume-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(4px);
        }
        .resume-modal.show {
            display: flex;
        }
        .resume-modal .modal-content {
            background: #ffffff;
            border-radius: 16px;
            max-width: 820px;
            width: 100%;
            max-height: 92vh;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
        }
        .resume-modal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid #e9edf2;
        }
        .resume-modal .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1a1c26;
        }
        .resume-modal .modal-header h3 i {
            color: #dc3545;
        }
        .resume-modal .modal-header .close-btn {
            background: transparent;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #8a94a0;
            transition: 0.2s;
        }
        .resume-modal .modal-header .close-btn:hover {
            color: #dc3545;
        }
        .resume-modal .modal-body {
            padding: 20px;
            overflow-y: auto;
            max-height: calc(92vh - 80px);
        }
        .resume-modal .modal-body iframe {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 8px;
            background: #f8fafc;
        }
        .resume-modal .modal-body .download-hint {
            text-align: center;
            margin-top: 12px;
            font-size: 13px;
            color: #8a94a0;
        }
        .resume-modal .modal-body .download-hint a {
            color: #4a5cf5;
            font-weight: 600;
        }

        body.dark-mode {
            --admin-bg: #0b0d10;
            --admin-card-bg: #14191f;
            --admin-text-primary: #eaeef2;
            --admin-text-secondary: #b0b8c5;
            --admin-text-muted: #6b7a8a;
            --admin-border: #1e242c;
            --admin-shadow-hover: 0 8px 40px rgba(0,0,0,0.4);
        }
        body.dark-mode .admin-sidebar {
            background: #0b0d10;
            border-color: #1e242c;
        }
        body.dark-mode .admin-sidebar .nav-item:hover {
            background: #14191f;
        }
        body.dark-mode .admin-sidebar .nav-item.active {
            background: #4a5cf5;
        }
        body.dark-mode .admin-sidebar .nav-item.home-link {
            background: #14191f;
            border-color: #1e242c;
        }
        body.dark-mode .admin-sidebar .nav-item.home-link:hover {
            background: #4a5cf5;
            color: #ffffff;
        }
        body.dark-mode .admin-sidebar .sidebar-footer .user-info {
            background: #14191f;
        }
        body.dark-mode .admin-header {
            background: #0b0d10;
            border-color: #1e242c;
        }
        body.dark-mode .stat-card .stat-number {
            color: #eaeef2;
        }
        body.dark-mode .recent-item {
            border-color: #1e242c;
        }
        body.dark-mode .status-bar-item .status-track {
            background: #1e242c;
        }
        body.dark-mode .admin-header .header-actions .theme-toggle-btn {
            border-color: #1e242c;
            color: #b0b8c5;
        }
        body.dark-mode .admin-header .header-actions .theme-toggle-btn:hover {
            background: #14191f;
        }
        body.dark-mode .admin-header .header-actions .notification-btn {
            border-color: #1e242c;
            color: #b0b8c5;
        }
        body.dark-mode .admin-header .header-actions .notification-btn:hover {
            background: #14191f;
        }
        body.dark-mode .admin-sidebar .nav-item .badge {
            background: #1e242c;
            color: #6c7aff;
        }
        body.dark-mode .hamburger-btn:hover {
            background: #14191f;
        }
        body.dark-mode .btn-resume {
            background: #1e242c;
            color: #6c7aff;
        }
        body.dark-mode .btn-resume:hover {
            background: #6c7aff;
            color: #fff;
        }
        body.dark-mode .btn-resume.download {
            background: #1e242c;
            color: #4caf50;
        }
        body.dark-mode .btn-resume.download:hover {
            background: #4caf50;
            color: #fff;
        }
        body.dark-mode .notification-dropdown {
            background: #14191f;
            border-color: #1e242c;
        }
        body.dark-mode .notification-dropdown .dropdown-header {
            background: #14191f;
            border-color: #1e242c;
        }
        body.dark-mode .notification-dropdown .dropdown-header h3 {
            color: #eaeef2;
        }
        body.dark-mode .notification-dropdown .notification-item {
            border-color: #1e242c;
        }
        body.dark-mode .notification-dropdown .notification-item:hover {
            background: #0b0d10;
        }
        body.dark-mode .notification-dropdown .notification-item .notif-title {
            color: #eaeef2;
        }
        body.dark-mode .notification-dropdown .notification-item .notif-message {
            color: #b0b8c5;
        }
        body.dark-mode .resume-modal .modal-content {
            background: #14191f;
        }
        body.dark-mode .resume-modal .modal-header {
            border-color: #1e242c;
        }
        body.dark-mode .resume-modal .modal-header h3 {
            color: #eaeef2;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: 99;
        }
        .sidebar-overlay.show {
            display: block;
        }

        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-grid { grid-template-columns: 1fr; }
            .recent-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .admin-header { padding: 0 16px; }
            .admin-content { padding: 16px; }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            .stat-card { padding: 16px; }
            .stat-card .stat-number { font-size: 22px; }
            .hamburger-btn { display: flex !important; }
            .notification-dropdown {
                width: 300px;
                right: -40px;
            }
            .resume-modal .modal-body iframe {
                height: 350px;
            }
        }

        @media (max-width: 480px) {
            .notification-dropdown {
                width: 280px;
                right: -60px;
            }
            .resume-modal .modal-body iframe {
                height: 250px;
            }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <aside class="admin-sidebar" id="adminSidebar">
        <div class="logo">
            HIFI <span>Admin</span>
            <small>Marketing &amp; Technologies</small>
        </div>
        <nav>
            <!-- ===== HOME BUTTON (Go to Website) ===== -->
            <a href="../index.php" class="nav-item home-link">
                <i class="fas fa-home"></i> Home
            </a>
            
            <hr class="sidebar-divider" />

            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="jobs.php" class="nav-item">
                <i class="fas fa-briefcase"></i> Jobs
                <span class="badge"><?php echo $total_jobs; ?></span>
            </a>
            <a href="applications.php" class="nav-item">
                <i class="fas fa-users"></i> Applications
                <span class="badge"><?php echo $pending_applications; ?></span>
            </a>
            <a href="messages.php" class="nav-item">
                <i class="fas fa-envelope"></i> Messages
                <span class="badge"><?php echo $unread_messages; ?></span>
            </a>
            <a href="export-report.php" class="nav-item">
                <i class="fas fa-file-alt"></i> Reports
            </a>
            <a href="#" class="nav-item" onclick="alert('Settings page coming soon!')">
                <i class="fas fa-cog"></i> Settings
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div style="display:flex;align-items:center;gap:16px;">
                <button class="hamburger-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="page-title">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</p>
                </div>
            </div>
            <div class="header-actions">
                <!-- ===== NOTIFICATION BUTTON ===== -->
                <button class="notification-btn" onclick="toggleNotificationDropdown()">
                    <i class="fas fa-bell"></i>
                    <?php if ($admin_notification_count > 0): ?>
                        <span class="dot"><?php echo $admin_notification_count; ?></span>
                    <?php endif; ?>
                </button>

                <!-- ===== NOTIFICATION DROPDOWN ===== -->
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="dropdown-header">
                        <h3>Notifications</h3>
                        <?php if ($admin_notification_count > 0): ?>
                            <a href="?mark_read=1">Mark all read</a>
                        <?php endif; ?>
                    </div>
                    <?php if (count($admin_notifications) > 0): ?>
                        <?php foreach ($admin_notifications as $notif): ?>
                            <div class="notification-item" onclick="markNotificationRead(<?php echo $notif['id']; ?>, '<?php echo $notif['link'] ?? ''; ?>')">
                                <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                <div class="notif-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                <div class="notif-time"><?php echo time_ago($notif['created_at']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            No new notifications
                        </div>
                    <?php endif; ?>
                </div>

                <button class="theme-toggle-btn" onclick="toggleTheme()">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
            </div>
        </header>

        <div class="admin-content">
            <!-- ===== STATS ===== -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-briefcase"></i></div>
                    <div class="stat-number"><?php echo $total_jobs; ?></div>
                    <div class="stat-label">Total Jobs</div>
                    <span class="stat-change up"><i class="fas fa-arrow-up"></i> Active</span>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-number"><?php echo $total_applications; ?></div>
                    <div class="stat-label">Total Applications</div>
                    <span class="stat-change up"><i class="fas fa-arrow-up"></i> Received</span>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?php echo $pending_applications; ?></div>
                    <div class="stat-label">Pending Review</div>
                    <span class="stat-change <?php echo $pending_applications > 0 ? 'up' : 'down'; ?>">
                        <?php echo $pending_applications > 0 ? '<i class="fas fa-arrow-up"></i> Awaiting' : '<i class="fas fa-arrow-down"></i> All reviewed'; ?>
                    </span>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-envelope"></i></div>
                    <div class="stat-number"><?php echo $unread_messages; ?></div>
                    <div class="stat-label">Unread Messages</div>
                    <span class="stat-change <?php echo $unread_messages > 0 ? 'up' : 'down'; ?>">
                        <?php echo $unread_messages > 0 ? '<i class="fas fa-arrow-up"></i> New' : '<i class="fas fa-check"></i> All read'; ?>
                    </span>
                </div>
            </div>

            <!-- ===== CHARTS ===== -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Applications Overview</h3>
                        <a href="applications.php" class="chart-action">View all →</a>
                    </div>
                    <div class="status-bars">
                        <?php
                        $status_colors = ['pending' => 'orange', 'reviewed' => 'blue', 'shortlisted' => 'green', 'rejected' => 'red'];
                        $status_labels = ['pending' => 'Pending', 'reviewed' => 'Reviewed', 'shortlisted' => 'Shortlisted', 'rejected' => 'Rejected'];
                        $total = max($total_applications, 1);
                        foreach ($status_labels as $key => $label):
                            $count = $status_counts[$key] ?? 0;
                            $percentage = round(($count / $total) * 100);
                            $color = $status_colors[$key] ?? 'blue';
                        ?>
                        <div class="status-bar-item">
                            <span class="status-label"><?php echo $label; ?></span>
                            <div class="status-track">
                                <div class="status-fill <?php echo $color; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <span class="status-count"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <a href="jobs.php?action=add" style="display:flex;align-items:center;gap:12px;padding:14px 18px;background:#f8fafc;border-radius:12px;border:1px solid var(--admin-border);transition:var(--admin-transition);">
                            <i class="fas fa-plus-circle" style="color:#4a5cf5;font-size:20px;"></i>
                            <div>
                                <div style="font-weight:600;font-size:14px;color:var(--admin-text-primary);">Post New Job</div>
                                <div style="font-size:12px;color:var(--admin-text-muted);">Create a new job listing</div>
                            </div>
                        </a>
                        <a href="applications.php" style="display:flex;align-items:center;gap:12px;padding:14px 18px;background:#f8fafc;border-radius:12px;border:1px solid var(--admin-border);transition:var(--admin-transition);">
                            <i class="fas fa-users" style="color:#2e7d32;font-size:20px;"></i>
                            <div>
                                <div style="font-weight:600;font-size:14px;color:var(--admin-text-primary);">View Applications</div>
                                <div style="font-size:12px;color:var(--admin-text-muted);">Review pending applications</div>
                            </div>
                        </a>
                        <a href="messages.php" style="display:flex;align-items:center;gap:12px;padding:14px 18px;background:#f8fafc;border-radius:12px;border:1px solid var(--admin-border);transition:var(--admin-transition);">
                            <i class="fas fa-envelope" style="color:#e65100;font-size:20px;"></i>
                            <div>
                                <div style="font-weight:600;font-size:14px;color:var(--admin-text-primary);">View Messages</div>
                                <div style="font-size:12px;color:var(--admin-text-muted);">Check contact form messages</div>
                            </div>
                        </a>
                        <a href="export-report.php" style="display:flex;align-items:center;gap:12px;padding:14px 18px;background:#f8fafc;border-radius:12px;border:1px solid var(--admin-border);transition:var(--admin-transition);">
                            <i class="fas fa-file-export" style="color:#e65100;font-size:20px;"></i>
                            <div>
                                <div style="font-weight:600;font-size:14px;color:var(--admin-text-primary);">Export Reports</div>
                                <div style="font-size:12px;color:var(--admin-text-muted);">Download application data</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ===== RECENT APPLICATIONS ===== -->
            <div class="recent-grid">
                <div class="recent-card">
                    <div class="recent-header">
                        <h3>Recent Applications</h3>
                        <a href="applications.php">View all →</a>
                    </div>
                    <?php if (count($recent_applications) > 0): ?>
                        <?php foreach ($recent_applications as $app): ?>
                            <div class="recent-item">
                                <div class="recent-icon blue"><i class="fas fa-user"></i></div>
                                <div class="recent-content">
                                    <div class="recent-title"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                                    <div class="recent-sub">
                                        Applied for: <?php echo htmlspecialchars($app['job_title'] ?? 'N/A'); ?>
                                        <?php if (!empty($app['resume'])): ?>
                                            <br />
                                            <button onclick="previewResume('../<?php echo $app['resume']; ?>')" class="btn-resume" title="Preview Resume">
                                                <i class="fas fa-eye"></i> Preview
                                            </button>
                                            <a href="../<?php echo $app['resume']; ?>" download class="btn-resume download" title="Download Resume">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <br />
                                            <span style="color:var(--admin-text-muted);font-size:11px;">No resume uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="recent-time"><?php echo time_ago($app['applied_at']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--admin-text-muted);text-align:center;padding:20px 0;">No applications yet</p>
                    <?php endif; ?>
                </div>

                <div class="recent-card">
                    <div class="recent-header">
                        <h3>Recent Jobs</h3>
                        <a href="jobs.php">View all →</a>
                    </div>
                    <?php if (count($recent_jobs) > 0): ?>
                        <?php foreach ($recent_jobs as $job): ?>
                            <div class="recent-item">
                                <div class="recent-icon green"><i class="fas fa-briefcase"></i></div>
                                <div class="recent-content">
                                    <div class="recent-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                    <div class="recent-sub"><?php echo htmlspecialchars($job['location']); ?> · <?php echo $job['type']; ?></div>
                                </div>
                                <div class="recent-time"><?php echo time_ago($job['posted_date']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--admin-text-muted);text-align:center;padding:20px 0;">No jobs posted yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- ===== RESUME PREVIEW MODAL ===== -->
    <div class="resume-modal" id="resumeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-pdf"></i> Resume Preview</h3>
                <button class="close-btn" onclick="closeResumePreview()">&times;</button>
            </div>
            <div class="modal-body">
                <iframe id="resumeFrame" src=""></iframe>
                <div class="download-hint">
                    <i class="fas fa-info-circle"></i> If preview doesn't load, 
                    <a href="#" id="resumeDownloadLink" target="_blank">download the file</a> to view it.
                </div>
            </div>
        </div>
    </div>

    <script>
        // ===== SIDEBAR TOGGLE =====
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

        // ===== THEME TOGGLE =====
        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById('themeIcon');
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
                icon.className = 'fas fa-sun';
            } else {
                localStorage.setItem('theme', 'light');
                icon.className = 'fas fa-moon';
            }
        }

        // ===== NOTIFICATION DROPDOWN =====
        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('show');
        }

        // ===== MARK NOTIFICATION READ =====
        function markNotificationRead(id, link) {
            fetch('ajax/mark-notification-read.php?id=' + id)
                .then(() => {
                    if (link) {
                        window.location.href = link;
                    } else {
                        location.reload();
                    }
                })
                .catch(() => {
                    if (link) {
                        window.location.href = link;
                    } else {
                        location.reload();
                    }
                });
        }

        // ===== RESUME PREVIEW =====
        function previewResume(filePath) {
            const modal = document.getElementById('resumeModal');
            const frame = document.getElementById('resumeFrame');
            const downloadLink = document.getElementById('resumeDownloadLink');
            
            modal.classList.add('show');
            frame.src = filePath;
            downloadLink.href = filePath;
            
            // Close on outside click
            modal.onclick = function(e) {
                if (e.target === modal) {
                    closeResumePreview();
                }
            };
        }

        function closeResumePreview() {
            const modal = document.getElementById('resumeModal');
            const frame = document.getElementById('resumeFrame');
            modal.classList.remove('show');
            frame.src = '';
        }

        // ===== CLOSE ON ESC KEY =====
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeResumePreview();
                document.getElementById('notificationDropdown').classList.remove('show');
            }
        });

        // ===== CLOSE DROPDOWN ON OUTSIDE CLICK =====
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notificationDropdown');
            const btn = document.querySelector('.notification-btn');
            if (dropdown && btn) {
                if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const icon = document.getElementById('themeIcon');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                icon.className = 'fas fa-sun';
            } else {
                icon.className = 'fas fa-moon';
            }
        });
    </script>

</body>
</html>