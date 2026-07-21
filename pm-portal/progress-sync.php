<?php
// progress-sync.php - FINAL WORKING VERSION
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['portal_role']) || ($_SESSION['portal_role'] !== 'pm' && $_SESSION['portal_role'] !== 'admin')) {
    header('Location: client-portal.php');
    exit();
}

$userData = $_SESSION['user'] ?? [];
$user_id = $_SESSION['user_id'] ?? 0;
$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';

// ===== GET ALL CLIENTS =====
$clients_sql = "SELECT c.*, u.username, u.email FROM clients c JOIN users u ON c.user_id = u.id";
$clients_result = mysqli_query($conn, $clients_sql);
$clients = [];
while ($row = mysqli_fetch_assoc($clients_result)) {
    $clients[] = $row;
}

// ===== GET ALL PACKAGES =====
$packages_sql = "SELECT * FROM packages WHERE status = 'active' ORDER BY price ASC";
$packages_result = mysqli_query($conn, $packages_sql);
$packages = [];
while ($row = mysqli_fetch_assoc($packages_result)) {
    $packages[] = $row;
}

// ===== SOCIAL PROGRESS =====
$social_progress = [];
foreach ($clients as $client) {
    $social_progress[$client['id']] = [
        'postsCompleted' => $client['posts_completed'] ?? 0,
        'storiesCompleted' => $client['stories_completed'] ?? 0,
        'reelsCompleted' => $client['reels_completed'] ?? 0,
        'adsCompleted' => $client['ads_completed'] ?? 0,
        'followersGained' => $client['followers_gained'] ?? 0,
        'totalLikes' => $client['total_likes'] ?? 0,
        'brandMentions' => $client['brand_mentions'] ?? 0
    ];
}

// ===== GET SELECTED CLIENT =====
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// ===== AJAX HANDLER =====
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    $conn = $GLOBALS['conn'];
    
    if ($_POST['ajax_action'] === 'update_social_progress') {
        $client_id = intval($_POST['client_id']);
        $posts = intval($_POST['posts'] ?? 0);
        $stories = intval($_POST['stories'] ?? 0);
        $reels = intval($_POST['reels'] ?? 0);
        $ads = intval($_POST['ads'] ?? 0);
        $likes = intval($_POST['likes'] ?? 0);
        $followers = intval($_POST['followers'] ?? 0);
        
        if ($client_id > 0) {
            $sql = "UPDATE clients SET 
                    posts_completed = ?, 
                    stories_completed = ?, 
                    reels_completed = ?,
                    ads_completed = ?,
                    total_likes = ?, 
                    followers_gained = ?
                    WHERE id = ?";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiiiiii", $posts, $stories, $reels, $ads, $likes, $followers, $client_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Social progress updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid client ID'];
        }
    }
    
    echo json_encode($response);
    exit();
}

// ===== GET SELECTED CLIENT DATA =====
$selected_client = null;
$selected_package = null;

if ($selected_client_id > 0) {
    foreach ($clients as $c) {
        if ($c['id'] == $selected_client_id) {
            $selected_client = $c;
            break;
        }
    }
    
    if ($selected_client) {
        foreach ($packages as $p) {
            if ($p['id'] == $selected_client['active_package_id']) {
                $selected_package = $p;
                break;
            }
        }
    }
}

// ===== PACKAGE LIMITS =====
$posts_limit = $selected_package['posts_limit'] ?? 0;
$stories_limit = $selected_package['stories_limit'] ?? 0;
$reels_limit = $selected_package['reels_limit'] ?? 0;
$ads_limit = $selected_package['ads_limit'] ?? 0;

// ===== PACKAGE SERVICES =====
$package_services = [];
if ($selected_package) {
    $package_services = [
        ['key' => 'content_calendar', 'label' => 'Content Calendar', 'value' => $selected_package['content_calendar'] ?? 0],
        ['key' => 'hashtag_research', 'label' => 'Hashtag Research', 'value' => $selected_package['hashtag_research'] ?? 0],
        ['key' => 'daily_engagement', 'label' => 'Daily Engagement', 'value' => $selected_package['daily_engagement'] ?? 0],
        ['key' => 'graphic_designs', 'label' => 'Elegant Graphic Designs', 'value' => $selected_package['graphic_designs'] ?? 0],
        ['key' => 'monthly_report', 'label' => 'Monthly Report', 'value' => $selected_package['monthly_report'] ?? 0],
        ['key' => 'youtube_seo', 'label' => 'YouTube SEO', 'value' => $selected_package['youtube_seo'] ?? 0],
        ['key' => 'fb_ig_ads', 'label' => 'FB & IG Targeted Ads', 'value' => $selected_package['fb_ig_ads'] ?? 0],
        ['key' => 'google_ads', 'label' => 'Google Ads', 'value' => $selected_package['google_ads'] ?? 0],
        ['key' => 'website_store', 'label' => 'Website/Store Management', 'value' => $selected_package['website_store'] ?? 0],
        ['key' => 'pinterest_management', 'label' => 'Pinterest Management', 'value' => $selected_package['pinterest_management'] ?? 0],
        ['key' => 'ugc_blogs', 'label' => '4x UGC Blogs (SEO)', 'value' => $selected_package['ugc_blogs'] ?? 0],
        ['key' => 'profile_creation', 'label' => 'All Platform Profile Creation', 'value' => $selected_package['profile_creation'] ?? 0]
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Progress Sync | HIFI Marketing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" href="../images/fav-icon.png" type="image/png" />
    <style>
        :root {
            --primary: #4a5cf5;
            --primary-dark: #3a4be0;
            --bg: #f0f2f5;
            --card-bg: #ffffff;
            --text-primary: #1a1c26;
            --text-secondary: #3d4452;
            --text-muted: #8a94a0;
            --border: #e9edf2;
            --radius: 16px;
            --shadow: 0 2px 12px rgba(0,0,0,0.04);
            --shadow-hover: 0 8px 40px rgba(0,0,0,0.08);
            --transition: 0.3s ease;
            --success: #10b981;
            --danger: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-primary); line-height: 1.6; }
        a { text-decoration: none; color: inherit; }

        header {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .logo { font-size: 20px; font-weight: 900; color: var(--text-primary); flex-shrink: 0; display: flex; align-items: center; gap: 8px; }
        .logo span { color: var(--primary); }
        .logo .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 900; font-size: 16px;
        }
        .header-actions { display: flex; align-items: center; gap: 8px; }
        .header-actions .user-badge {
            display: flex; align-items: center; gap: 6px;
            font-weight: 600; font-size: 13px; color: var(--text-primary);
            padding: 4px 10px 4px 4px; border-radius: 40px; background: #f0f3ff;
        }
        .header-actions .user-badge img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
        .header-actions .user-badge .online { display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; margin-left: 2px; border: 2px solid #fff; }
        .header-actions a { color: #dc3545; font-size: 16px; padding: 4px 8px; border-radius: 8px; transition: var(--transition); }
        .header-actions a:hover { background: #fee2e2; }
        .header-actions .back-btn {
            background: transparent; border: 1px solid var(--border); padding: 6px 14px;
            border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--text-secondary);
            cursor: pointer; transition: var(--transition);
        }
        .header-actions .back-btn:hover { background: #f0f3ff; color: var(--primary); }

        .main-layout { display: flex; max-width: 1400px; margin: 0 auto; padding: 20px; gap: 20px; min-height: calc(100vh - 72px); }

        .sidebar {
            width: 240px; flex-shrink: 0; background: var(--card-bg); border-radius: var(--radius);
            border: 1px solid var(--border); padding: 16px 12px; box-shadow: var(--shadow);
            height: fit-content; position: sticky; top: 88px; transition: var(--transition);
        }
        .sidebar.collapsed { width: 60px; padding: 16px 8px; }
        .sidebar.collapsed .sidebar-text { display: none; }
        .sidebar.collapsed .sidebar-link { justify-content: center; padding: 10px; }
        .sidebar.collapsed .sidebar-link i { font-size: 18px; margin: 0; }
        .sidebar.collapsed .sidebar-brand-text { display: none; }
        .sidebar.collapsed .sidebar-user-text { display: none; }
        .sidebar.collapsed .sidebar-badge { display: none; }
        .sidebar.collapsed .sidebar-toggle i { transform: rotate(180deg); }

        .sidebar-brand {
            display: flex; align-items: center; gap: 10px; padding: 8px 12px;
            margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 12px;
        }
        .sidebar-brand .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 900; font-size: 16px; flex-shrink: 0;
        }
        .sidebar-brand h1 { font-size: 15px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
        .sidebar-brand span { font-size: 9px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .sidebar-toggle { display: flex; justify-content: flex-end; padding: 2px 12px; margin-bottom: 6px; }
        .sidebar-toggle button { background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: var(--transition); }
        .sidebar-toggle button:hover { background: #f0f3ff; color: var(--primary); }
        .sidebar-badge {
            display: flex; align-items: center; justify-content: space-between;
            padding: 6px 12px; background: #f0f3ff; border-radius: 8px;
            margin: 0 4px 12px; font-size: 10px; font-weight: 600; color: var(--text-secondary);
        }
        .sidebar-badge .role { background: var(--primary); color: #fff; padding: 1px 12px; border-radius: 20px; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .sidebar-nav { display: flex; flex-direction: column; gap: 2px; }
        .sidebar-link {
            display: flex; align-items: center; gap: 12px; padding: 9px 12px;
            border-radius: 8px; color: var(--text-secondary); font-weight: 600; font-size: 13px;
            transition: var(--transition);
        }
        .sidebar-link i { width: 20px; text-align: center; font-size: 15px; flex-shrink: 0; }
        .sidebar-link:hover { background: #f0f3ff; color: var(--primary); }
        .sidebar-link.active { background: #f0f3ff; color: var(--primary); }
        .sidebar-footer { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); }
        .sidebar-footer .user-info { display: flex; align-items: center; gap: 10px; padding: 4px 8px; }
        .sidebar-footer .user-info img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        .sidebar-footer .user-info .name { font-weight: 600; font-size: 12px; color: var(--text-primary); }
        .sidebar-footer .user-info .role-label { font-size: 9px; color: var(--text-muted); }
        .sidebar-footer .logout-link { display: flex; align-items: center; gap: 8px; padding: 6px 12px; margin-top: 6px; color: #dc3545; font-weight: 600; font-size: 12px; border-radius: 8px; transition: var(--transition); }
        .sidebar-footer .logout-link:hover { background: #fee2e2; }

        .content { flex: 1; min-width: 0; }

        .banner {
            background: linear-gradient(135deg, #4a5cf5 0%, #6c7aff 100%);
            border-radius: var(--radius); padding: 20px 24px; color: #fff;
            margin-bottom: 20px; display: flex; justify-content: space-between;
            align-items: center; flex-wrap: wrap; gap: 12px;
        }
        .banner h2 { font-size: 18px; font-weight: 800; }
        .banner p { opacity: 0.85; font-size: 13px; margin-top: 2px; }
        .banner .badge { background: rgba(255,255,255,0.2); padding: 4px 16px; border-radius: 40px; font-weight: 600; font-size: 11px; }

        .client-selector {
            padding: 14px 18px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid var(--border);
            margin-bottom: 18px;
        }
        .client-selector label {
            font-weight: 600;
            font-size: 13px;
            color: var(--text-secondary);
            display: block;
            margin-bottom: 6px;
        }
        .client-selector select {
            width: 100%;
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
            font-size: 13px;
            cursor: pointer;
        }
        .client-selector select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74,92,245,0.1);
        }

        .progress-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 24px;
            box-shadow: var(--shadow);
        }
        .progress-card .client-header {
            font-weight: 700;
            font-size: 16px;
            color: var(--text-primary);
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
        }
        .progress-card .client-header .pkg-badge {
            font-size: 11px;
            background: var(--primary);
            color: #fff;
            padding: 2px 14px;
            border-radius: 12px;
            margin-left: 8px;
        }
        .progress-card .client-header .client-email {
            font-weight: 400;
            font-size: 12px;
            color: var(--text-muted);
        }

        .service-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px 30px;
        }

        .service-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f2f5;
        }
        .service-item:last-child {
            border-bottom: none;
        }
        .service-item .service-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        .service-item .service-left .icon {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }
        .service-item .service-left .icon.active { color: var(--success); }
        .service-item .service-left .icon.inactive { color: var(--danger); }
        .service-item .service-left .name {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .service-item .service-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .service-item .service-right .progress-text {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            min-width: 70px;
            text-align: right;
        }
        .service-item .service-right .progress-text .pct {
            font-size: 11px;
            font-weight: 400;
            color: var(--text-muted);
        }
        .service-item .service-right input[type="range"] {
            width: 120px;
            height: 5px;
            -webkit-appearance: none;
            appearance: none;
            background: #e9edf2;
            border-radius: 3px;
            outline: none;
        }
        .service-item .service-right input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            cursor: pointer;
            background: var(--primary);
            border: 2px solid var(--primary-dark);
        }
        .service-item .service-right input[type="range"]::-moz-range-thumb {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            cursor: pointer;
            background: var(--primary);
            border: 2px solid var(--primary-dark);
        }
        .service-item .service-right .status-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 12px;
            border-radius: 20px;
            min-width: 65px;
            text-align: center;
        }
        .service-item .service-right .status-badge.active {
            background: #e8f5e9;
            color: var(--success);
        }
        .service-item .service-right .status-badge.inactive {
            background: #fee2e2;
            color: var(--danger);
        }

        .save-btn {
            margin-top: 20px;
            padding: 12px 24px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 40px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
        }
        .save-btn:hover {
            background: var(--primary-dark);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 50px;
            display: block;
            margin-bottom: 12px;
            opacity: 0.3;
        }
        .empty-state .title {
            font-size: 17px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        .empty-state p {
            font-size: 14px;
            margin-top: 4px;
        }

        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 300;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .toast {
            background: var(--text-primary);
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 280px;
            animation: slideIn 0.3s ease;
        }
        .toast.success i { color: #10b981; }
        .toast.error i { color: #ef4444; }
        .toast.warning i { color: #f59e0b; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .security-badge {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: #4ade80;
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
            z-index: 999;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(74,222,128,0.2);
            pointer-events: none;
        }

        .sync-footer {
            margin-top: 20px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-layout { padding: 12px; flex-direction: column; }
            .banner { padding: 16px 18px; flex-direction: column; text-align: center; }
            .service-grid { grid-template-columns: 1fr; }
            .service-item { flex-wrap: wrap; }
            .service-item .service-right { width: 100%; margin-top: 6px; }
            .service-item .service-right input[type="range"] { flex: 1; }
            .header-actions .user-badge .name { display: none; }
            .client-selector select { font-size: 12px; }
            .progress-card { padding: 16px; }
        }
        @media (max-width: 480px) {
            .header-actions .user-badge { padding: 2px 8px 2px 2px; font-size: 11px; }
            .header-actions .user-badge img { width: 24px; height: 24px; }
            .service-item .service-right input[type="range"] { width: 80px; }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header>
        <div class="header-inner">
            <div class="logo">
                <div class="brand-icon">P</div>
                HIFI <span>Marketing</span>
            </div>
            <div class="header-actions">
                <a href="pm-portal.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to PM Portal</a>
                <div class="user-badge">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <span class="name"><?php echo $userData['name'] ?? 'PM'; ?></span>
                    <span class="online"></span>
                </div>
                <a href="login.php?logout=true"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </header>

    <!-- ===== MAIN LAYOUT ===== -->
    <div class="main-layout">

       <!-- ===== SIDEBAR ===== -->
<aside class="sidebar <?php echo $isCollapsed ? 'collapsed' : ''; ?>" id="mainSidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">P</div>
        <div class="sidebar-brand-text">
            <h1>SMMA Scale</h1>
            <span>PM Portal</span>
        </div>
    </div>
    <div class="sidebar-toggle">
        <button onclick="toggleSidebar()">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    <div class="sidebar-badge">
        <span>Access</span>
        <span class="role">PM Admin</span>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php" class="sidebar-link <?php echo $current_page === 'operations.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span class="sidebar-text">Operations Desk</span>
        </a>
        <a href="deliverables.php" class="sidebar-link <?php echo $current_page === 'deliverables.php' ? 'active' : ''; ?>">
            <i class="fas fa-check-square"></i>
            <span class="sidebar-text">Manage Deliverables</span>
        </a>
        <a href="tickets.php" class="sidebar-link <?php echo $current_page === 'tickets.php' ? 'active' : ''; ?>">
            <i class="fas fa-headset"></i>
            <span class="sidebar-text">Client Tickets & Tasks</span>
        </a>
        <a href="verbal.php" class="sidebar-link <?php echo $current_page === 'verbal.php' ? 'active' : ''; ?>">
            <i class="fas fa-phone"></i>
            <span class="sidebar-text">Client Verbal Requests</span>
        </a>
        <a href="progress-sync.php" class="sidebar-link <?php echo $current_page === 'progress-sync.php' ? 'active' : ''; ?>">
            <i class="fas fa-sliders-h"></i>
            <span class="sidebar-text">Progress Counter Sync</span>
        </a>
        <a href="pm-ad-campaigns.php" class="sidebar-link <?php echo $current_page === 'pm-ad-campaigns.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>
            <span class="sidebar-text">Ad Campaigns</span>
        </a>
        <a href="service-packages.php" class="sidebar-link <?php echo $current_page === 'service-packages.php' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i>
            <span class="sidebar-text">Service Packages</span>
        </a>
        
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
            <div class="sidebar-user-text">
                <div class="name"><?php echo $userData['name'] ?? 'PM'; ?></div>
                <div class="role-label">Senior Account Director</div>
            </div>
        </div>
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</aside>
        <!-- ===== CONTENT ===== -->
        <div class="content">

            <!-- ===== BANNER ===== -->
            <div class="banner">
                <div>
                    <h2><i class="fas fa-sliders-h"></i> All Service Progress Dashboard</h2>
                    <p>Track and update client social media progress in real-time</p>
                </div>
                <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:8px;"></i> Live Sync Active</span>
            </div>

            <!-- ===== CLIENT SELECTOR ===== -->
            <div class="client-selector">
                <label for="sync-client-selector"><i class="fas fa-user"></i> Select Client:</label>
                <select id="sync-client-selector" onchange="window.location.href='?client_id=' + this.value">
                    <option value="0">-- Select a Client --</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo ($selected_client_id == $client['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name'] . ' (' . $client['username'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selected_client && $selected_package): 
                $progress = $social_progress[$selected_client['id']] ?? [
                    'postsCompleted' => 0, 'storiesCompleted' => 0, 'reelsCompleted' => 0,
                    'adsCompleted' => 0, 'totalLikes' => 0, 'followersGained' => 0
                ];
            ?>
            
            <div class="progress-card">
                <div class="client-header">
                    <i class="fas fa-user-circle" style="color:var(--primary);"></i>
                    <?php echo htmlspecialchars($selected_client['name']); ?>
                    <span class="client-email">(<?php echo $selected_client['username']; ?>)</span>
                    <span class="pkg-badge"><?php echo htmlspecialchars($selected_package['name']); ?></span>
                </div>

                <div class="service-grid">
                    
                    <!-- ===== 1. FEED POSTS ===== -->
                    <?php if ($posts_limit > 0): 
                        $pct = round(($progress['postsCompleted'] / $posts_limit) * 100);
                    ?>
                    <div class="service-item">
                        <div class="service-left">
                            <span class="icon active"><i class="fas fa-check-circle"></i></span>
                            <span class="name">Feed Posts Completed</span>
                        </div>
                        <div class="service-right">
                            <span class="progress-text" id="posts-display-<?php echo $selected_client['id']; ?>">
                                <?php echo $progress['postsCompleted']; ?>/<?php echo $posts_limit; ?>
                                <span class="pct">(<?php echo $pct; ?>%)</span>
                            </span>
                            <input type="range" id="sync-posts-<?php echo $selected_client['id']; ?>" 
                                   min="0" max="<?php echo $posts_limit; ?>" 
                                   value="<?php echo $progress['postsCompleted']; ?>" 
                                   oninput="updateProgressDisplay('<?php echo $selected_client['id']; ?>', 'posts', this.value, <?php echo $posts_limit; ?>)">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ===== 2. STORIES ===== -->
                    <?php if ($stories_limit > 0): 
                        $pct = round(($progress['storiesCompleted'] / $stories_limit) * 100);
                    ?>
                    <div class="service-item">
                        <div class="service-left">
                            <span class="icon active"><i class="fas fa-check-circle"></i></span>
                            <span class="name">Stories Completed</span>
                        </div>
                        <div class="service-right">
                            <span class="progress-text" id="stories-display-<?php echo $selected_client['id']; ?>">
                                <?php echo $progress['storiesCompleted']; ?>/<?php echo $stories_limit; ?>
                                <span class="pct">(<?php echo $pct; ?>%)</span>
                            </span>
                            <input type="range" id="sync-stories-<?php echo $selected_client['id']; ?>" 
                                   min="0" max="<?php echo $stories_limit; ?>" 
                                   value="<?php echo $progress['storiesCompleted']; ?>" 
                                   oninput="updateProgressDisplay('<?php echo $selected_client['id']; ?>', 'stories', this.value, <?php echo $stories_limit; ?>)">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ===== 3. REELS & VIDEOS ===== -->
                    <?php if ($reels_limit > 0): 
                        $pct = round(($progress['reelsCompleted'] / $reels_limit) * 100);
                    ?>
                    <div class="service-item">
                        <div class="service-left">
                            <span class="icon active"><i class="fas fa-check-circle"></i></span>
                            <span class="name">Reels &amp; Videos</span>
                        </div>
                        <div class="service-right">
                            <span class="progress-text" id="reels-display-<?php echo $selected_client['id']; ?>">
                                <?php echo $progress['reelsCompleted']; ?>/<?php echo $reels_limit; ?>
                                <span class="pct">(<?php echo $pct; ?>%)</span>
                            </span>
                            <input type="range" id="sync-reels-<?php echo $selected_client['id']; ?>" 
                                   min="0" max="<?php echo $reels_limit; ?>" 
                                   value="<?php echo $progress['reelsCompleted']; ?>" 
                                   oninput="updateProgressDisplay('<?php echo $selected_client['id']; ?>', 'reels', this.value, <?php echo $reels_limit; ?>)">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ===== 4. ADS PROGRESS ===== -->
                    <?php if ($ads_limit > 0): 
                        $ads_pct = round(($progress['adsCompleted'] / $ads_limit) * 100);
                    ?>
                    <div class="service-item">
                        <div class="service-left">
                            <span class="icon active"><i class="fas fa-check-circle"></i></span>
                            <span class="name">Ads Progress</span>
                        </div>
                        <div class="service-right">
                            <span class="progress-text" id="ads-display-<?php echo $selected_client['id']; ?>">
                                <?php echo $progress['adsCompleted']; ?>/<?php echo $ads_limit; ?>
                                <span class="pct">(<?php echo $ads_pct; ?>%)</span>
                            </span>
                            <input type="range" id="sync-ads-<?php echo $selected_client['id']; ?>" 
                                   min="0" max="<?php echo $ads_limit; ?>" 
                                   value="<?php echo $progress['adsCompleted']; ?>" 
                                   oninput="updateAdsDisplay('<?php echo $selected_client['id']; ?>', this.value, <?php echo $ads_limit; ?>)">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ===== 5. TOTAL LIKES ===== -->
                    <div class="service-item">
                        <div class="service-left">
                            <span class="icon active"><i class="fas fa-check-circle"></i></span>
                            <span class="name">Total Likes</span>
                        </div>
                        <div class="service-right">
                            <span class="progress-text" id="likes-display-<?php echo $selected_client['id']; ?>">
                                <?php echo number_format($progress['totalLikes']); ?>
                            </span>
                            <input type="range" id="sync-likes-<?php echo $selected_client['id']; ?>" 
                                   min="0" max="50000" value="<?php echo $progress['totalLikes']; ?>" 
                                   oninput="document.getElementById('likes-display-<?php echo $selected_client['id']; ?>').textContent = Number(this.value).toLocaleString()">
                        </div>
                    </div>
                    
                    <!-- ===== 6. FOLLOWERS GAINED ===== -->
                    <div class="service-item">
                        <div class="service-left">
                            <span class="icon active"><i class="fas fa-check-circle"></i></span>
                            <span class="name">Followers Gained</span>
                        </div>
                        <div class="service-right">
                            <span class="progress-text" id="followers-display-<?php echo $selected_client['id']; ?>">
                                <?php echo number_format($progress['followersGained']); ?>
                            </span>
                            <input type="range" id="sync-followers-<?php echo $selected_client['id']; ?>" 
                                   min="0" max="10000" value="<?php echo $progress['followersGained']; ?>" 
                                   oninput="document.getElementById('followers-display-<?php echo $selected_client['id']; ?>').textContent = Number(this.value).toLocaleString()">
                        </div>
                    </div>
                    
                    <!-- ===== 7. ALL SERVICES FROM PACKAGE ===== -->
                    <?php foreach ($package_services as $service): 
                        $is_active = ($service['value'] == 1);
                    ?>
                    <div class="service-item">
                        <div class="service-left">
                            <span class="icon <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                <i class="fas <?php echo $is_active ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            </span>
                            <span class="name"><?php echo $service['label']; ?></span>
                        </div>
                        <div class="service-right">
                            <span class="status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                </div>

                <button class="save-btn" onclick="saveClientProgress(<?php echo $selected_client['id']; ?>)">
                    <i class="fas fa-save"></i> Save Progress for <?php echo htmlspecialchars($selected_client['name']); ?>
                </button>
            </div>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p class="title">No Client Selected</p>
                    <p>Please select a client from the dropdown above to view their progress.</p>
                </div>
            <?php endif; ?>
            
            <div class="sync-footer">
                <i class="fas fa-info-circle"></i> Changes will be synced instantly to the client dashboard
            </div>

        </div>
    </div>

    <!-- ===== TOAST CONTAINER ===== -->
    <div class="toast-container" id="toast-container"></div>

    <!-- ===== SECURITY BADGE ===== -->
    <div class="security-badge">🔒 Secure Session • <?php echo $_SERVER['REMOTE_ADDR']; ?></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000`;
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-triangle-exclamation';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; setTimeout(() => toast.remove(), 300); }, 3500);
        }

        function updateProgressDisplay(clientId, type, value, limit) {
            const pct = Math.round((value / limit) * 100);
            const displayId = type + '-display-' + clientId;
            const el = document.getElementById(displayId);
            if (el) {
                el.textContent = value + '/' + limit + ' (' + pct + '%)';
            }
        }

        function updateAdsDisplay(clientId, value, limit) {
            const pct = Math.round((value / limit) * 100);
            const el = document.getElementById('ads-display-' + clientId);
            if (el) {
                el.textContent = value + '/' + limit + ' (' + pct + '%)';
            }
        }

        function saveClientProgress(clientId) {
            const posts = document.getElementById('sync-posts-' + clientId)?.value || 0;
            const stories = document.getElementById('sync-stories-' + clientId)?.value || 0;
            const reels = document.getElementById('sync-reels-' + clientId)?.value || 0;
            const ads = document.getElementById('sync-ads-' + clientId)?.value || 0;
            const likes = document.getElementById('sync-likes-' + clientId)?.value || 0;
            const followers = document.getElementById('sync-followers-' + clientId)?.value || 0;
            
            const formData = new FormData();
            formData.append('ajax_action', 'update_social_progress');
            formData.append('client_id', clientId);
            formData.append('posts', posts);
            formData.append('stories', stories);
            formData.append('reels', reels);
            formData.append('ads', ads);
            formData.append('likes', likes);
            formData.append('followers', followers);
            
            showToast('Saving progress...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Progress saved successfully!');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error saving progress', 'error');
            });
        }
    </script>
</body>
</html>