<?php
// addons.php - Client Addons & Custom Projects
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['portal_role']) && ($_SESSION['portal_role'] === 'pm' || $_SESSION['portal_role'] === 'admin')) {
    header('Location: ../pm-portal/operations.php');
    exit();
}

$userData = $_SESSION['user'] ?? [];
$user_id = $_SESSION['user_id'] ?? 0;

// ===== GET CLIENT DATA =====
$client_id = 0;
$client_data = null;

if ($user_id > 0) {
    $client_sql = "SELECT * FROM clients WHERE user_id = ?";
    $client_stmt = mysqli_prepare($conn, $client_sql);
    if ($client_stmt) {
        mysqli_stmt_bind_param($client_stmt, "i", $user_id);
        mysqli_stmt_execute($client_stmt);
        $client_result = mysqli_stmt_get_result($client_stmt);
        $client_data = mysqli_fetch_assoc($client_result);
        if ($client_data) {
            $client_id = $client_data['id'];
        }
        mysqli_stmt_close($client_stmt);
    }
}

// ===== CREATE CLIENT IF NOT EXISTS =====
if ($client_id == 0 && $user_id > 0) {
    $insert_sql = "INSERT INTO clients (user_id, name, active_package_id) VALUES (?, ?, NULL)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    $name = $userData['name'] ?? 'Client';
    mysqli_stmt_bind_param($insert_stmt, "is", $user_id, $name);
    if (mysqli_stmt_execute($insert_stmt)) {
        $client_id = mysqli_insert_id($conn);
    }
    mysqli_stmt_close($insert_stmt);
}

// ===== GET ADDONS =====
$addons = [];
if ($client_id > 0) {
    $add_sql = "SELECT * FROM addons WHERE client_id = ? ORDER BY created_at DESC";
    $add_stmt = mysqli_prepare($conn, $add_sql);
    mysqli_stmt_bind_param($add_stmt, "i", $client_id);
    mysqli_stmt_execute($add_stmt);
    $add_result = mysqli_stmt_get_result($add_stmt);
    while ($row = mysqli_fetch_assoc($add_result)) {
        $addons[] = $row;
    }
    mysqli_stmt_close($add_stmt);
}

// ===== GET CUSTOM TASKS =====
$custom_tasks = [];
if ($client_id > 0) {
    $ct_sql = "SELECT * FROM custom_tasks WHERE client_id = ? ORDER BY created_at DESC";
    $ct_stmt = mysqli_prepare($conn, $ct_sql);
    mysqli_stmt_bind_param($ct_stmt, "i", $client_id);
    mysqli_stmt_execute($ct_stmt);
    $ct_result = mysqli_stmt_get_result($ct_stmt);
    while ($row = mysqli_fetch_assoc($ct_result)) {
        $custom_tasks[] = $row;
    }
    mysqli_stmt_close($ct_stmt);
}

// ===== GET ACTIVE PACKAGE =====
$active_package = null;
if ($client_data && isset($client_data['active_package_id']) && $client_data['active_package_id'] > 0) {
    $pkg_sql = "SELECT * FROM packages WHERE id = ?";
    $pkg_stmt = mysqli_prepare($conn, $pkg_sql);
    mysqli_stmt_bind_param($pkg_stmt, "i", $client_data['active_package_id']);
    mysqli_stmt_execute($pkg_stmt);
    $pkg_result = mysqli_stmt_get_result($pkg_stmt);
    $active_package = mysqli_fetch_assoc($pkg_result);
    mysqli_stmt_close($pkg_stmt);
}

// ===== PACKAGE NAME FOR BANNER =====
$package_name = $active_package['name'] ?? 'No Package';

// ===== OUT OF SCOPE TASKS =====
$out_of_scope_tasks = [
    ['name' => 'Branding Booster (10 custom posts)', 'description' => 'Custom brand style assets, vectors, and typography setups.', 'price' => 15000],
    ['name' => 'Elite Video Production (3 4K reels)', 'description' => 'Premium motion-graphic reel outputs.', 'price' => 30000],
    ['name' => 'Custom Shopify/Wordpress Store Setup', 'description' => 'Full-stack store redesign, bank gateway connections and product integrations.', 'price' => 80000]
];

$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
$current_page = 'addons.php';

// ===== AJAX HANDLER =====
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    if ($_POST['ajax_action'] === 'request_addon') {
        $name = trim($_POST['name']);
        $client_id = intval($_POST['client_id']);
        
        if ($client_id > 0 && !empty($name)) {
            $sql = "INSERT INTO addons (client_id, name, status, metrics) VALUES (?, ?, 'Pending', 'Requested')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "is", $client_id, $name);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Add-on requested successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to request add-on: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    if ($_POST['ajax_action'] === 'submit_custom_task') {
        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $client_id = intval($_POST['client_id']);
        
        if ($client_id > 0 && !empty($title)) {
            $sql = "INSERT INTO custom_tasks (client_id, title, category, description, status) VALUES (?, ?, ?, ?, 'Awaiting Quote')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isss", $client_id, $title, $category, $description);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Custom task submitted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to submit task: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
        
        echo json_encode($response);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Addons | Client Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" href="../images/fav-icon.png" type="image/png" />
    <style>
        /* ===== HIFI DASHBOARD COLOR THEME ===== */
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
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            line-height: 1.6;
        }
        a { text-decoration: none; color: inherit; }

        /* ===== HEADER ===== */
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
        .logo { 
            font-size: 20px; 
            font-weight: 900; 
            color: var(--text-primary); 
            flex-shrink: 0; 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo span { color: var(--primary); }
        .logo .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 900;
            font-size: 16px;
        }

        .desktop-nav {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .desktop-nav .nav-link {
            font-weight: 600;
            font-size: 13px;
            color: var(--text-secondary);
            padding: 6px 14px;
            border-radius: 8px;
            transition: var(--transition);
        }
        .desktop-nav .nav-link:hover {
            color: var(--primary);
            background: #f0f3ff;
        }
        .desktop-nav .nav-link.active {
            color: var(--primary);
            background: #f0f3ff;
        }

        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
            flex-direction: column;
            gap: 5px;
            z-index: 110;
        }
        .mobile-menu-toggle span {
            display: block;
            width: 26px;
            height: 3px;
            background: var(--text-primary);
            border-radius: 3px;
            transition: var(--transition);
        }
        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 6px);
        }
        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -6px);
        }

        .mobile-nav-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.3);
            z-index: 150;
        }
        .mobile-nav-overlay.active { display: block; }

        .mobile-nav {
            position: fixed;
            top: 0;
            right: -320px;
            width: 300px;
            height: 100vh;
            background: var(--card-bg);
            box-shadow: -4px 0 30px rgba(0,0,0,0.1);
            z-index: 160;
            padding: 20px 24px;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .mobile-nav.active {
            right: 0;
        }

        .mobile-nav .mobile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
        }
        .mobile-nav .mobile-header .logo-small {
            font-size: 18px;
            font-weight: 900;
            color: var(--text-primary);
        }
        .mobile-nav .mobile-header .logo-small span { color: var(--primary); }
        .mobile-nav .mobile-close {
            background: transparent;
            border: none;
            font-size: 22px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .mobile-nav .mobile-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
        }
        .mobile-nav .mobile-user img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
        }
        .mobile-nav .mobile-user .user-info .name {
            font-weight: 700;
            font-size: 15px;
            color: var(--text-primary);
        }
        .mobile-nav .mobile-user .user-info .role {
            font-size: 12px;
            color: var(--text-muted);
        }
        .mobile-nav .mobile-user .user-info .role i {
            color: var(--primary);
        }

        .mobile-nav .mobile-links {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }
        .mobile-nav .mobile-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 10px;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
        }
        .mobile-nav .mobile-links a:hover {
            background: #f0f3ff;
            color: var(--primary);
        }
        .mobile-nav .mobile-links a.active {
            background: #f0f3ff;
            color: var(--primary);
        }
        .mobile-nav .mobile-links a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .mobile-nav .mobile-footer {
            padding-top: 16px;
            border-top: 1px solid var(--border);
            margin-top: auto;
        }
        .mobile-nav .mobile-footer .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 10px;
            color: #dc3545;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
        }
        .mobile-nav .mobile-footer .logout-btn:hover {
            background: #fee2e2;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header-actions .action-btn {
            background: transparent;
            border: none;
            padding: 6px 10px;
            border-radius: 8px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
        }
        .header-actions .action-btn:hover {
            background: #f0f3ff;
            color: var(--primary);
        }
        .header-actions .user-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-primary);
            padding: 4px 10px 4px 4px;
            border-radius: 40px;
            background: #f0f3ff;
        }
        .header-actions .user-badge img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
        }
        .header-actions .user-badge .online {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            margin-left: 2px;
            border: 2px solid #fff;
        }

        /* ===== MAIN LAYOUT ===== */
        .main-layout {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
            min-height: calc(100vh - 72px);
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 240px;
            flex-shrink: 0;
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 16px 12px;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 88px;
            transition: var(--transition);
        }
        .sidebar.collapsed {
            width: 60px;
            padding: 16px 8px;
        }
        .sidebar.collapsed .sidebar-text { display: none; }
        .sidebar.collapsed .sidebar-link { justify-content: center; padding: 10px; }
        .sidebar.collapsed .sidebar-link i { font-size: 18px; margin: 0; }
        .sidebar.collapsed .sidebar-brand-text { display: none; }
        .sidebar.collapsed .sidebar-user-text { display: none; }
        .sidebar.collapsed .sidebar-badge { display: none; }
        .sidebar.collapsed .sidebar-toggle i { transform: rotate(180deg); }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }
        .sidebar-brand .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 900;
            font-size: 16px;
            flex-shrink: 0;
        }
        .sidebar-brand h1 {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
        }
        .sidebar-brand span {
            font-size: 9px;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-toggle {
            display: flex;
            justify-content: flex-end;
            padding: 2px 12px;
            margin-bottom: 6px;
        }
        .sidebar-toggle button {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: var(--transition);
        }
        .sidebar-toggle button:hover {
            background: #f0f3ff;
            color: var(--primary);
        }

        .sidebar-badge {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 12px;
            background: #f0f3ff;
            border-radius: 8px;
            margin: 0 4px 12px;
            font-size: 10px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        .sidebar-badge .role {
            background: var(--primary);
            color: #fff;
            padding: 1px 12px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
        }
        .sidebar-link i {
            width: 20px;
            text-align: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        .sidebar-link:hover {
            background: #f0f3ff;
            color: var(--primary);
        }
        .sidebar-link.active {
            background: #f0f3ff;
            color: var(--primary);
        }

        .sidebar-footer {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }
        .sidebar-footer .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 8px;
        }
        .sidebar-footer .user-info img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        .sidebar-footer .user-info .name {
            font-weight: 600;
            font-size: 12px;
            color: var(--text-primary);
        }
        .sidebar-footer .user-info .role-label {
            font-size: 9px;
            color: var(--text-muted);
        }
        .sidebar-footer .logout-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            margin-top: 6px;
            color: #dc3545;
            font-weight: 600;
            font-size: 12px;
            border-radius: 8px;
            transition: var(--transition);
        }
        .sidebar-footer .logout-link:hover {
            background: #fee2e2;
        }

        /* ===== CONTENT AREA ===== */
        .content {
            flex: 1;
            min-width: 0;
        }

        /* ===== BANNER ===== */
        .banner {
            background: linear-gradient(135deg, #4a5cf5 0%, #6c7aff 100%);
            border-radius: var(--radius);
            padding: 20px 24px;
            color: #fff;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .banner h2 {
            font-size: 18px;
            font-weight: 800;
        }
        .banner p {
            opacity: 0.85;
            font-size: 13px;
            margin-top: 2px;
        }
        .banner .badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 16px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 11px;
        }
        .banner .banner-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .banner .banner-actions .btn-white {
            background: #fff;
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 11px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        .banner .banner-actions .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* ===== CARDS ===== */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 18px 20px;
            box-shadow: var(--shadow);
            margin-bottom: 18px;
        }
        .card .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .card .card-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .card .card-header .sub {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* ===== GRID LAYOUTS ===== */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }

        /* ===== TASK ITEMS ===== */
        .task-item {
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            transition: var(--transition);
            margin-bottom: 8px;
        }
        .task-item:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-hover);
        }
        .task-item .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            flex-wrap: wrap;
        }
        .task-item .task-title {
            font-weight: 700;
            font-size: 13px;
            color: var(--text-primary);
        }
        .task-item .task-desc {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 3px;
        }
        .task-item .task-meta {
            display: flex;
            gap: 10px;
            margin-top: 6px;
            font-size: 11px;
            color: var(--text-muted);
            flex-wrap: wrap;
        }
        .task-item .task-meta i { margin-right: 3px; }

        .status-badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.open { background: #fee2e2; color: #dc3545; }
        .status-badge.pending { background: #fff3e0; color: #e65100; }
        .status-badge.in-progress { background: #e8edfe; color: var(--primary); }
        .status-badge.done { background: #e8f5e9; color: #2e7d32; }

        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 36px;
            color: #d0d7e0;
            margin-bottom: 8px;
        }

        /* ===== MODAL ===== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 200;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-overlay.show { display: flex; }
        .modal {
            background: var(--card-bg);
            border-radius: var(--radius);
            max-width: 460px;
            width: 100%;
            padding: 24px 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal .modal-close {
            position: absolute;
            top: 14px;
            right: 16px;
            background: transparent;
            border: none;
            font-size: 18px;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
        }
        .modal .modal-close:hover { color: var(--text-primary); }
        .modal h3 {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 3px;
        }
        .modal .modal-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        .modal label {
            display: block;
            font-weight: 600;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 3px;
        }
        .modal input,
        .modal select,
        .modal textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #f8fafc;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            margin-bottom: 12px;
        }
        .modal input:focus,
        .modal select:focus,
        .modal textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74,92,245,0.1);
        }
        .modal .btn-submit {
            width: 100%;
            padding: 10px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
        }
        .modal .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* ===== TOAST ===== */
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
            padding: 12px 18px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 260px;
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
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
            z-index: 999;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(74,222,128,0.2);
            pointer-events: none;
        }

        @media (max-width: 1024px) {
            .grid-2 { grid-template-columns: 1fr; }
            .grid-3 { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 992px) {
            .desktop-nav { display: none; }
            .mobile-menu-toggle { display: flex; }
            .header-actions .user-badge .name { display: none; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-layout { padding: 12px; flex-direction: column; }
            .banner { padding: 16px 18px; flex-direction: column; text-align: center; }
            .banner h2 { font-size: 16px; }
            .grid-3 { grid-template-columns: 1fr; }
            .header-actions .action-btn { padding: 4px 8px; font-size: 13px; }
            .header-inner { padding: 0 12px; }
            .logo { font-size: 17px; }
            .logo .brand-icon { width: 30px; height: 30px; font-size: 13px; }
            .banner .banner-actions .btn-white { width: 100%; text-align: center; }
            .modal { padding: 20px; }
        }
        @media (max-width: 480px) {
            .header-actions .action-btn { font-size: 12px; padding: 4px 6px; }
            .header-actions .user-badge { padding: 2px 8px 2px 2px; font-size: 11px; }
            .header-actions .user-badge img { width: 24px; height: 24px; }
            .mobile-nav { width: 280px; }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header>
        <div class="header-inner">
            <div class="logo">
                <div class="brand-icon">H</div>
                HIFI <span>Marketing</span>
            </div>

            <!-- Desktop Navigation -->
            <nav class="desktop-nav">
                <a href="client-portal.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="packages.php" class="nav-link"><i class="fas fa-credit-card"></i> Packages</a>
                <a href="addons.php" class="nav-link active"><i class="fas fa-layer-group"></i> Addons</a>
                <a href="requests.php" class="nav-link"><i class="fas fa-headset"></i> Support</a>
                <a href="billing.php" class="nav-link"><i class="fas fa-file-invoice"></i> Billing</a>
            </nav>

            <!-- Header Actions -->
            <div class="header-actions">
                <button class="action-btn" onclick="openModal('modal-custom-task')" title="Custom Request">
                    <i class="fas fa-tasks"></i>
                </button>
                <div class="user-badge">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <span class="name"><?php echo $userData['name'] ?? 'Client'; ?></span>
                    <span class="online"></span>
                </div>
                <a href="logout.php" style="color:#dc3545;font-size:16px;padding:4px 8px;border-radius:8px;transition:var(--transition);" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-sign-out-alt"></i>
                </a>

                <!-- Hamburger Menu Toggle -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- ===== MOBILE NAVIGATION OVERLAY ===== -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay" onclick="closeMobileMenu()"></div>

    <!-- ===== MOBILE NAVIGATION ===== -->
    <nav class="mobile-nav" id="mobileNav">
        <div class="mobile-header">
            <div class="logo-small">HIFI <span>Marketing</span></div>
            <button class="mobile-close" onclick="closeMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="mobile-user">
            <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
            <div class="user-info">
                <div class="name"><?php echo $userData['name'] ?? 'Client'; ?></div>
                <div class="role"><i class="fas fa-user-tie"></i> SMM Account Owner</div>
            </div>
        </div>

        <div class="mobile-links">
            <a href="client-portal.php" class="<?php echo $current_page === 'client-portal.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="packages.php" class="<?php echo $current_page === 'packages.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-credit-card"></i> Service Packages
            </a>
            <a href="addons.php" class="<?php echo $current_page === 'addons.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-layer-group"></i> Addons & Custom
            </a>
            <a href="client-deliverables.php" class="<?php echo $current_page === 'client-deliverables.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-check-square"></i> Deliverables
            </a>
            <a href="requests.php" class="<?php echo $current_page === 'requests.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-headset"></i> Tasks & Support
            </a>
            <a href="billing.php" class="<?php echo $current_page === 'billing.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-file-invoice"></i> Billing Ledger
            </a>
            <a href="reports.php" class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-chart-bar"></i> Marketing Reports
            </a>
        </div>

        <div class="mobile-footer">
            <a href="logout.php" class="logout-btn" onclick="closeMobileMenu()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <!-- ===== MAIN LAYOUT ===== -->
    <div class="main-layout">

        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar <?php echo $isCollapsed ? 'collapsed' : ''; ?>" id="mainSidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">S</div>
                <div class="sidebar-brand-text">
                    <h1>SMMA Scale</h1>
                    <span>Client Portal</span>
                </div>
            </div>

            <div class="sidebar-toggle">
                <button onclick="toggleSidebar()">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <div class="sidebar-badge">
                <span>Access</span>
                <span class="role">Client</span>
            </div>

            <nav class="sidebar-nav">
                <a href="client-portal.php" class="sidebar-link <?php echo $current_page === 'client-portal.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="packages.php" class="sidebar-link <?php echo $current_page === 'packages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i>
                    <span class="sidebar-text">Service Packages</span>
                </a>
                <a href="addons.php" class="sidebar-link <?php echo $current_page === 'addons.php' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i>
                    <span class="sidebar-text">Addons & Custom</span>
                </a>
                <a href="client-deliverables.php" class="sidebar-link <?php echo $current_page === 'client-deliverables.php' ? 'active' : ''; ?>">
                    <i class="fas fa-check-square"></i>
                    <span class="sidebar-text">Deliverables</span>
                </a>
                <a href="requests.php" class="sidebar-link <?php echo $current_page === 'requests.php' ? 'active' : ''; ?>">
                    <i class="fas fa-headset"></i>
                    <span class="sidebar-text">Tasks & Support</span>
                </a>
                <a href="billing.php" class="sidebar-link <?php echo $current_page === 'billing.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span class="sidebar-text">Billing Ledger</span>
                </a>
                <a href="reports.php" class="sidebar-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span class="sidebar-text">Marketing Reports</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <div class="sidebar-user-text">
                        <div class="name"><?php echo $userData['name'] ?? 'Client'; ?></div>
                        <div class="role-label">SMM Account Owner</div>
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
                    <h2><i class="fas fa-layer-group"></i> Addons & Custom Projects</h2>
                    <p>Request additional services &bull; Active Package: <strong><?php echo $package_name; ?></strong></p>
                </div>
                <div class="banner-actions">
                    <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:8px;"></i> <?php echo count($addons); ?> Addons</span>
                </div>
            </div>

            <!-- ===== CLIENT ID DISPLAY ===== -->
            <?php if (!empty($client_data['client_code'])): ?>
            <div style="background:var(--card-bg);border-radius:var(--radius);border:1px solid var(--border);padding:14px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;box-shadow:var(--shadow);">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="background:linear-gradient(135deg, var(--primary), var(--primary-dark));width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <div style="font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">Client Identification</div>
                        <div style="font-weight:900;font-size:22px;color:var(--text-primary);letter-spacing:1px;font-family:monospace;">
                            <?php echo htmlspecialchars($client_data['client_code']); ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                    <span style="font-size:12px;color:var(--text-muted);">
                        <i class="fas fa-user" style="color:var(--primary);width:16px;"></i> 
                        <?php echo htmlspecialchars($client_data['name'] ?? ''); ?>
                    </span>
                    <span style="font-size:12px;color:var(--text-muted);">
                        <i class="fas fa-calendar-alt" style="color:var(--primary);width:16px;"></i> 
                        Since <?php echo date('M d, Y', strtotime($client_data['created_at'] ?? 'now')); ?>
                    </span>
                    <button onclick="copyClientId()" style="background:#f0f3ff;border:none;padding:5px 16px;border-radius:40px;font-size:11px;font-weight:700;color:var(--primary);cursor:pointer;transition:var(--transition);">
                        <i class="fas fa-copy"></i> Copy ID
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== OUT OF SCOPE TASKS ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-tools" style="color:var(--primary);"></i> Out-of-Scope Tasks</h3>
                    <span class="sub">Custom projects with separate pricing</span>
                </div>
                <div class="grid-3">
                    <?php foreach ($out_of_scope_tasks as $task): ?>
                    <div style="padding:14px;border:1px solid var(--border);border-radius:12px;transition:var(--transition);">
                        <h4 style="font-weight:700;font-size:13px;color:var(--text-primary);"><?php echo $task['name']; ?></h4>
                        <p style="font-size:12px;color:var(--text-secondary);margin:4px 0;"><?php echo $task['description']; ?></p>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
                            <span style="font-weight:800;color:var(--primary);"><?php echo number_format($task['price']); ?> PKR</span>
                            <button onclick="requestAddon('<?php echo $task['name']; ?>')" style="background:var(--primary);color:#fff;border:none;padding:5px 16px;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;transition:var(--transition);">Add</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ===== CUSTOM PROJECTS ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-project-diagram" style="color:var(--primary);"></i> Custom Project Tracker</h3>
                </div>
                <div class="grid-2">
                    <?php if (!empty($custom_tasks)): ?>
                        <?php foreach ($custom_tasks as $task): ?>
                        <div class="task-item">
                            <div class="task-header">
                                <span class="task-title"><?php echo htmlspecialchars($task['title']); ?></span>
                                <span class="status-badge <?php echo $task['status'] === 'Awaiting Quote' ? 'pending' : 'in-progress'; ?>"><?php echo htmlspecialchars($task['status']); ?></span>
                            </div>
                            <div class="task-desc"><?php echo htmlspecialchars($task['description']); ?></div>
                            <div class="task-meta">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($task['category']); ?></span>
                                <?php if ($task['price'] > 0): ?>
                                <span><i class="fas fa-money-bill"></i> <?php echo number_format($task['price']); ?> PKR</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="grid-column:1/-1;">
                            <i class="fas fa-folder-open"></i>
                            <p style="font-size:12px;">No custom projects</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== ACTIVE ADDONS ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-layer-group" style="color:var(--primary);"></i> Active Add-ons</h3>
                </div>
                <?php if (!empty($addons)): ?>
                    <?php foreach ($addons as $add): ?>
                    <div style="padding:12px 14px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <div>
                            <div style="font-weight:700;color:var(--text-primary);font-size:13px;"><?php echo htmlspecialchars($add['name']); ?></div>
                            <div style="font-size:11px;color:var(--text-muted);"><?php echo $add['metrics'] ?? 'In Progress'; ?></div>
                        </div>
                        <span class="status-badge <?php echo $add['status'] === 'In Progress' ? 'in-progress' : 'done'; ?>"><?php echo htmlspecialchars($add['status']); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-plus"></i>
                        <p style="font-size:12px;">No active add-ons</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ===== MODALS ===== -->
    <!-- Custom Task Modal -->
    <div class="modal-overlay" id="modal-custom-task">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-custom-task')"><i class="fas fa-times"></i></button>
            <h3>Request Custom Project</h3>
            <p class="modal-sub">Submit for graphic design, web, or merchandise</p>
            <form id="form-custom-task" onsubmit="submitCustomTask(event)">
                <label>Project Category</label>
                <select id="ct-category">
                    <option value="Software Development">Software / Web</option>
                    <option value="Merchandise Printing">Merchandise</option>
                    <option value="Branding">Branding</option>
                    <option value="Video Production">Video Production</option>
                </select>
                <label>Project Title</label>
                <input type="text" id="ct-title" required placeholder="e.g. Mug design for 50 units">
                <label>Details</label>
                <textarea id="ct-desc" rows="3" placeholder="Describe your project..." required></textarea>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Request Quote</button>
            </form>
        </div>
    </div>

    <!-- ===== TOAST CONTAINER ===== -->
    <div class="toast-container" id="toast-container"></div>

    <!-- ===== SECURITY BADGE ===== -->
    <div class="security-badge">🔒 Secure Session • <?php echo $_SERVER['REMOTE_ADDR']; ?></div>

    <script>
        // ===== SIDEBAR TOGGLE =====
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000`;
        }

        // ===== MOBILE MENU =====
        function toggleMobileMenu() {
            const nav = document.getElementById('mobileNav');
            const overlay = document.getElementById('mobileNavOverlay');
            const toggle = document.getElementById('mobileMenuToggle');
            nav.classList.toggle('active');
            overlay.classList.toggle('active');
            toggle.classList.toggle('active');
            document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
        }

        function closeMobileMenu() {
            const nav = document.getElementById('mobileNav');
            const overlay = document.getElementById('mobileNavOverlay');
            const toggle = document.getElementById('mobileMenuToggle');
            nav.classList.remove('active');
            overlay.classList.remove('active');
            toggle.classList.remove('active');
            document.body.style.overflow = '';
        }

        // ===== MODAL FUNCTIONS =====
        function openModal(id) {
            document.getElementById(id).classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
            document.body.style.overflow = '';
        }
        document.querySelectorAll('.modal-overlay').forEach(el => {
            el.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        });

        // ===== TOAST =====
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-triangle-exclamation';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; setTimeout(() => toast.remove(), 300); }, 3500);
        }

        // ===== COPY CLIENT ID =====
        function copyClientId() {
            const clientId = '<?php echo $client_data['client_code'] ?? ''; ?>';
            if (clientId) {
                navigator.clipboard.writeText(clientId).then(() => {
                    showToast('Client ID copied to clipboard!');
                }).catch(() => {
                    const input = document.createElement('input');
                    input.value = clientId;
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    document.body.removeChild(input);
                    showToast('Client ID copied to clipboard!');
                });
            }
        }

        // ===== REQUEST ADDON =====
        function requestAddon(name) {
            if (!confirm(`Request "${name}"?`)) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'request_addon');
            formData.append('name', name);
            formData.append('client_id', '<?php echo $client_id; ?>');
            
            showToast('Requesting add-on...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Add-on requested!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error requesting add-on.', 'error');
            });
        }

        // ===== SUBMIT CUSTOM TASK =====
        function submitCustomTask(e) {
            e.preventDefault();
            const title = document.getElementById('ct-title').value;
            const category = document.getElementById('ct-category').value;
            const description = document.getElementById('ct-desc').value;
            
            const formData = new FormData();
            formData.append('ajax_action', 'submit_custom_task');
            formData.append('title', title);
            formData.append('category', category);
            formData.append('description', description);
            formData.append('client_id', '<?php echo $client_id; ?>');
            
            showToast('Submitting request...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Custom task submitted!');
                    closeModal('modal-custom-task');
                    document.getElementById('form-custom-task').reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error submitting task.', 'error');
            });
        }

        // ===== SESSION TIMEOUT =====
        let sessionTimeout;
        function resetSessionTimer() {
            clearTimeout(sessionTimeout);
            sessionTimeout = setTimeout(function() {
                showToast('Session expiring soon. Please save your work.', 'warning');
            }, 1500000);
        }
        document.addEventListener('click', resetSessionTimer);
        document.addEventListener('keydown', resetSessionTimer);
        resetSessionTimer();
    </script>
</body>
</html>