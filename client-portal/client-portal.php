<?php
// client-portal.php - Complete Client Portal (FIXED - Client Specific Packages)
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['portal_role']) && ($_SESSION['portal_role'] === 'pm' || $_SESSION['portal_role'] === 'admin')) {
    header('Location: pm-portal.php');
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

// ===== GET CLIENT'S ASSIGNED PACKAGES (ONLY CLIENT SPECIFIC) =====
$packages = [];
$client_package_ids = [];

if ($client_id > 0) {
    // Get client's assigned package IDs from client_packages table
    $cp_sql = "SELECT package_id FROM client_packages WHERE client_id = ?";
    $cp_stmt = mysqli_prepare($conn, $cp_sql);
    mysqli_stmt_bind_param($cp_stmt, "i", $client_id);
    mysqli_stmt_execute($cp_stmt);
    $cp_result = mysqli_stmt_get_result($cp_stmt);
    while ($row = mysqli_fetch_assoc($cp_result)) {
        $client_package_ids[] = $row['package_id'];
    }
    mysqli_stmt_close($cp_stmt);
}

// If client has assigned packages, show only those
if (!empty($client_package_ids)) {
    $ids_string = implode(',', $client_package_ids);
    $packages_sql = "SELECT * FROM packages WHERE id IN ($ids_string) AND status = 'active' ORDER BY price ASC";
    $packages_result = mysqli_query($conn, $packages_sql);
    while ($row = mysqli_fetch_assoc($packages_result)) {
        $packages[] = $row;
    }
} else {
    // If no packages assigned, check if client has active_package_id
    if ($client_data && !empty($client_data['active_package_id'])) {
        $pkg_sql = "SELECT * FROM packages WHERE id = ? AND status = 'active'";
        $pkg_stmt = mysqli_prepare($conn, $pkg_sql);
        mysqli_stmt_bind_param($pkg_stmt, "i", $client_data['active_package_id']);
        mysqli_stmt_execute($pkg_stmt);
        $pkg_result = mysqli_stmt_get_result($pkg_stmt);
        while ($row = mysqli_fetch_assoc($pkg_result)) {
            $packages[] = $row;
        }
        mysqli_stmt_close($pkg_stmt);
    }
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

// If active package not found but packages exist, use first package
if (!$active_package && !empty($packages)) {
    $active_package = $packages[0];
}

// ===== SOCIAL PROGRESS =====
$social_progress = [
    'postsCompleted' => $client_data['posts_completed'] ?? 0,
    'storiesCompleted' => $client_data['stories_completed'] ?? 0,
    'reelsCompleted' => $client_data['reels_completed'] ?? 0,
    'adsCompleted' => $client_data['ads_completed'] ?? 0,
    'followersGained' => $client_data['followers_gained'] ?? 0,
    'totalLikes' => $client_data['total_likes'] ?? 0,
    'brandMentions' => $client_data['brand_mentions'] ?? 0
];

// ===== INVOICES =====
$invoices = [];
if ($client_id > 0) {
    $inv_sql = "SELECT * FROM invoices WHERE client_id = ? ORDER BY created_at DESC";
    $inv_stmt = mysqli_prepare($conn, $inv_sql);
    mysqli_stmt_bind_param($inv_stmt, "i", $client_id);
    mysqli_stmt_execute($inv_stmt);
    $inv_result = mysqli_stmt_get_result($inv_stmt);
    while ($row = mysqli_fetch_assoc($inv_result)) {
        $invoices[] = $row;
    }
    mysqli_stmt_close($inv_stmt);
}

// ===== SUPPORT TICKETS =====
$tickets = [];
if ($client_id > 0) {
    $tkt_sql = "SELECT * FROM support_tickets WHERE client_id = ? ORDER BY created_at DESC";
    $tkt_stmt = mysqli_prepare($conn, $tkt_sql);
    mysqli_stmt_bind_param($tkt_stmt, "i", $client_id);
    mysqli_stmt_execute($tkt_stmt);
    $tkt_result = mysqli_stmt_get_result($tkt_stmt);
    while ($row = mysqli_fetch_assoc($tkt_result)) {
        $tickets[] = $row;
    }
    mysqli_stmt_close($tkt_stmt);
}

// ===== DELIVERABLES =====
$deliverables = [];
if ($client_id > 0) {
    $del_sql = "SELECT * FROM deliverables WHERE client_id = ? ORDER BY due_date ASC";
    $del_stmt = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($del_stmt, "i", $client_id);
    mysqli_stmt_execute($del_stmt);
    $del_result = mysqli_stmt_get_result($del_stmt);
    while ($row = mysqli_fetch_assoc($del_result)) {
        $deliverables[] = $row;
    }
    mysqli_stmt_close($del_stmt);
}

// ===== CUSTOM TASKS =====
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

// ===== ADDONS =====
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

// ===== ATTACHMENTS =====
$attachments = [];
if ($client_id > 0) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'documents'");
    if (mysqli_num_rows($check_table) > 0) {
        $att_sql = "SELECT * FROM documents WHERE client_id = ? AND type = 'brand2social' ORDER BY created_at DESC";
        $att_stmt = mysqli_prepare($conn, $att_sql);
        mysqli_stmt_bind_param($att_stmt, "i", $client_id);
        mysqli_stmt_execute($att_stmt);
        $att_result = mysqli_stmt_get_result($att_stmt);
        while ($row = mysqli_fetch_assoc($att_result)) {
            $attachments[] = $row;
        }
        mysqli_stmt_close($att_stmt);
    }
}

// ===== CAMPAIGN REPORTS =====
$campaign_reports = [];
if ($client_id > 0) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'reports'");
    if (mysqli_num_rows($check_table) > 0) {
        $rep_sql = "SELECT * FROM reports WHERE client_id = ? AND type = 'campaign' ORDER BY created_at DESC";
        $rep_stmt = mysqli_prepare($conn, $rep_sql);
        mysqli_stmt_bind_param($rep_stmt, "i", $client_id);
        mysqli_stmt_execute($rep_stmt);
        $rep_result = mysqli_stmt_get_result($rep_stmt);
        while ($row = mysqli_fetch_assoc($rep_result)) {
            $campaign_reports[] = $row;
        }
        mysqli_stmt_close($rep_stmt);
    }
}

// ===== OUT OF SCOPE TASKS =====
$out_of_scope_tasks = [
    ['name' => 'Branding Booster (10 custom posts)', 'description' => 'Custom brand style assets, vectors, and typography setups.', 'price' => 15000],
    ['name' => 'Elite Video Production (3 4K reels)', 'description' => 'Premium motion-graphic reel outputs.', 'price' => 30000],
    ['name' => 'Custom Shopify/Wordpress Store Setup', 'description' => 'Full-stack store redesign, bank gateway connections and product integrations.', 'price' => 80000]
];

// ===== VARIABLES FROM ACTIVE PACKAGE =====
$package_name = $active_package['name'] ?? 'No Package';
$package_price = $active_package['price'] ?? 0;
$package_currency = $active_package['currency'] ?? 'PKR';

// Service limits from package
$posts_limit = $active_package['posts_limit'] ?? 0;
$stories_limit = $active_package['stories_limit'] ?? 0;
$reels_limit = $active_package['reels_limit'] ?? 0;
$ads_limit = $active_package['ads_limit'] ?? 0;

// Service toggles from package
$content_calendar = $active_package['content_calendar'] ?? 0;
$hashtag_research = $active_package['hashtag_research'] ?? 0;
$daily_engagement = $active_package['daily_engagement'] ?? 0;
$graphic_designs = $active_package['graphic_designs'] ?? 0;
$monthly_report = $active_package['monthly_report'] ?? 0;
$youtube_seo = $active_package['youtube_seo'] ?? 0;
$fb_ig_ads = $active_package['fb_ig_ads'] ?? 0;
$google_ads = $active_package['google_ads'] ?? 0;
$website_store = $active_package['website_store'] ?? 0;
$pinterest_management = $active_package['pinterest_management'] ?? 0;
$ugc_blogs = $active_package['ugc_blogs'] ?? 0;
$profile_creation = $active_package['profile_creation'] ?? 0;

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';

// ===== COMPLETED SOCIAL RATIO (ONLY FOR ACTIVE SERVICES) =====
$total_completed = 0;
$total_limits = 0;

if ($posts_limit > 0) {
    $total_completed += $social_progress['postsCompleted'];
    $total_limits += $posts_limit;
}
if ($stories_limit > 0) {
    $total_completed += $social_progress['storiesCompleted'];
    $total_limits += $stories_limit;
}
if ($reels_limit > 0) {
    $total_completed += $social_progress['reelsCompleted'];
    $total_limits += $reels_limit;
}
if ($ads_limit > 0) {
    $total_completed += $social_progress['adsCompleted'];
    $total_limits += $ads_limit;
}

$social_ratio = $total_limits > 0 ? round(($total_completed / $total_limits) * 100) : 0;

// ===== AJAX HANDLER =====
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    if ($_POST['ajax_action'] === 'submit_ticket') {
        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $client_id = intval($_POST['client_id']);
        
        if ($client_id > 0 && !empty($title)) {
            $sql = "INSERT INTO support_tickets (client_id, title, category, description, status) VALUES (?, ?, ?, ?, 'Open')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isss", $client_id, $title, $category, $description);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Ticket submitted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to submit ticket: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
    }
    
    elseif ($_POST['ajax_action'] === 'submit_custom_task') {
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
    }
    
    elseif ($_POST['ajax_action'] === 'request_addon') {
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
    }
    
    elseif ($_POST['ajax_action'] === 'switch_package') {
        $package_id = intval($_POST['package_id']);
        $client_id = intval($_POST['client_id']);
        
        if ($client_id > 0 && $package_id > 0) {
            $sql = "UPDATE clients SET active_package_id = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $package_id, $client_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Package switched successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to switch package: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
    }
    
    elseif ($_POST['ajax_action'] === 'settle_invoice') {
        $invoice_id = intval($_POST['invoice_id']);
        
        if ($invoice_id > 0) {
            $sql = "UPDATE invoices SET status = 'Paid' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $invoice_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Invoice settled successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to settle invoice: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid invoice ID'];
        }
    }
    
    elseif ($_POST['ajax_action'] === 'upload_file') {
        $client_id = intval($_POST['client_id']);
        $type = $_POST['type'] ?? 'brand2social';
        
        if ($client_id > 0 && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
            $file_name = $_FILES['file']['name'];
            $file_size = $_FILES['file']['size'];
            $file_tmp = $_FILES['file']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['pdf', 'csv', 'xlsx', 'xls'];
            
            if (in_array($file_ext, $allowed)) {
                $new_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file_name);
                $upload_path = __DIR__ . '/../uploads/brand2social/';
                
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0777, true);
                }
                
                if (move_uploaded_file($file_tmp, $upload_path . $new_name)) {
                    $sql = "INSERT INTO documents (client_id, file_name, file_path, file_size, type) VALUES (?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    $file_path = 'uploads/brand2social/' . $new_name;
                    mysqli_stmt_bind_param($stmt, "issss", $client_id, $file_name, $file_path, $file_size, $type);
                    if (mysqli_stmt_execute($stmt)) {
                        $response = ['success' => true, 'message' => 'File uploaded successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to save file record: ' . mysqli_error($conn)];
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $response = ['success' => false, 'message' => 'Failed to move uploaded file'];
                }
            } else {
                $response = ['success' => false, 'message' => 'File type not allowed'];
            }
        } else {
            $response = ['success' => false, 'message' => 'No file uploaded or client ID missing'];
        }
    }
    
    echo json_encode($response);
    exit();
}

// ============================================================ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Client Portal | HIFI Marketing</title>
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

        /* Desktop Navigation */
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

        /* Mobile Menu Toggle - Hamburger */
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

        /* Mobile Navigation - Full screen overlay */
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

        /* Header right actions */
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
        .header-actions .action-btn.primary {
            background: var(--primary);
            color: #fff;
            padding: 6px 16px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 12px;
        }
        .header-actions .action-btn.primary:hover {
            background: var(--primary-dark);
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

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 16px 18px;
            transition: var(--transition);
        }
        .stat-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        .stat-card .number {
            font-size: 24px;
            font-weight: 900;
            color: var(--text-primary);
        }
        .stat-card .label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .stat-card .stat-icon {
            float: right;
            font-size: 22px;
            opacity: 0.2;
            color: var(--primary);
        }
        .stat-card .progress-bar {
            width: 100%;
            height: 4px;
            background: #e9edf2;
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }
        .stat-card .progress-bar .fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .stat-card .progress-bar .fill.indigo { background: var(--primary); }
        .stat-card .progress-bar .fill.pink { background: #ec4899; }
        .stat-card .progress-bar .fill.emerald { background: #10b981; }
        .stat-card .progress-bar .fill.orange { background: #f59e0b; }

        /* ===== SECTION TITLE ===== */
        .section-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i {
            color: var(--primary);
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

        /* ===== TASK/TICKET CARDS ===== */
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
        .status-badge.paid { background: #e8f5e9; color: #2e7d32; }
        .status-badge.unpaid { background: #fff3e0; color: #e65100; }
        .status-badge.active { background: #e8edfe; color: var(--primary); }

        /* ===== INVOICE TABLE ===== */
        .table-wrap {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        table th {
            background: #f8fafc;
            text-align: left;
            padding: 10px 14px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }
        table td {
            padding: 10px 14px;
            color: var(--text-secondary);
            border-bottom: 1px solid #f0f2f5;
        }
        table tr:hover td { background: #f8fafc; }
        table tr:last-child td { border-bottom: none; }

        /* ===== PACKAGE CARDS ===== */
        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px;
        }
        .package-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 18px 20px;
            transition: var(--transition);
            position: relative;
        }
        .package-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        .package-card.active {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(74,92,245,0.1);
        }
        .package-card .active-badge {
            position: absolute;
            top: -8px;
            right: 14px;
            background: var(--primary);
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            padding: 2px 12px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        .package-card .pkg-name {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-primary);
        }
        .package-card .pkg-price {
            font-size: 22px;
            font-weight: 900;
            color: var(--text-primary);
            margin: 6px 0;
        }
        .package-card .pkg-price span {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
        }
        .package-card ul {
            list-style: none;
            margin: 10px 0 14px;
        }
        .package-card ul li {
            font-size: 12px;
            color: var(--text-secondary);
            padding: 3px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .package-card ul li i {
            font-size: 13px;
        }
        .package-card ul li i.fa-check-circle { color: var(--success); }
        .package-card ul li i.fa-times-circle { color: var(--danger); }
        
        .package-card .btn-switch {
            width: 100%;
            padding: 8px;
            border-radius: 40px;
            border: none;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
        }
        .package-card .btn-switch.active {
            background: #f0f3ff;
            color: var(--primary);
            cursor: default;
        }
        .package-card .btn-switch:not(.active) {
            background: var(--text-primary);
            color: #fff;
        }
        .package-card .btn-switch:not(.active):hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        /* ===== EMPTY STATE ===== */
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
        .empty-state h4 {
            font-size: 15px;
            color: var(--text-primary);
            margin-bottom: 3px;
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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
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
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .package-grid { grid-template-columns: 1fr; }
            .grid-3 { grid-template-columns: 1fr; }
            .header-actions .action-btn.primary { display: none; }
            .header-actions .action-btn { padding: 4px 8px; font-size: 13px; }
            .modal { padding: 20px; }
            .header-inner { padding: 0 12px; }
            .logo { font-size: 17px; }
            .logo .brand-icon { width: 30px; height: 30px; font-size: 13px; }
            .banner .banner-actions .btn-white { width: 100%; text-align: center; }
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .header-actions .action-btn { font-size: 12px; padding: 4px 6px; }
            .header-actions .user-badge { padding: 2px 8px 2px 2px; font-size: 11px; }
            .header-actions .user-badge img { width: 24px; height: 24px; }
            .mobile-nav { width: 280px; }
        }

        /* ===== SECURITY BADGE ===== */
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
        /* Header mein plus icon aur tasks icon hide karein */
.header-actions .action-btn:first-child,
.header-actions .action-btn:nth-child(2) {
    display: none !important;
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
                <a href="client-portal.php" class="nav-link <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="packages.php" class="nav-link <?php echo $activeTab === 'plan' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> Packages
                </a>
                <a href="requests.php" class="nav-link <?php echo $activeTab === 'requests' ? 'active' : ''; ?>">
                    <i class="fas fa-headset"></i> Support
                </a>
                <a href="billing.php" class="nav-link <?php echo $activeTab === 'billing' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i> Billing
                </a>
            </nav>

            <!-- Header Actions -->
            <div class="header-actions">
                <button class="action-btn" onclick="openModal('modal-ticket')" title="Submit Ticket">
                    <i class="fas fa-plus-circle"></i>
                </button>
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
            <a href="client-portal.php" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="packages.php" class="<?php echo $activeTab === 'plan' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-credit-card"></i> Service Packages
            </a>
            
            <a href="client-deliverables.php" class="<?php echo $activeTab === 'deliverables' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-check-square"></i> Deliverables
            </a>
            <a href="requests.php" class="<?php echo $activeTab === 'requests' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-headset"></i> Tasks & Support
            </a>
            <a href="billing.php" class="<?php echo $activeTab === 'billing' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-file-invoice"></i> Billing Ledger
            </a>
            <a href="marketing-reports.php" class="<?php echo $activeTab === 'reports' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
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
                <a href="client-portal.php" class="sidebar-link <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="packages.php" class="sidebar-link <?php echo $activeTab === 'plan' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i>
                    <span class="sidebar-text">Service Packages</span>
                </a>
               
                <a href="client-deliverables.php" class="sidebar-link <?php echo $activeTab === 'deliverables' ? 'active' : ''; ?>">
                    <i class="fas fa-check-square"></i>
                    <span class="sidebar-text">Deliverables</span>
                </a>
                <a href="requests.php" class="sidebar-link <?php echo $activeTab === 'requests' ? 'active' : ''; ?>">
                    <i class="fas fa-headset"></i>
                    <span class="sidebar-text">Tasks & Support</span>
                </a>
                <a href="billing.php" class="sidebar-link <?php echo $activeTab === 'billing' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span class="sidebar-text">Billing Ledger</span>
                </a>
                <a href="reports.php" class="sidebar-link <?php echo $activeTab === 'reports' ? 'active' : ''; ?>">
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
                    <h2><i class="fas fa-shield-alt"></i> Client Workspace</h2>
                    <p>Tracking SMM Contract: <strong><?php echo $package_name; ?></strong> &bull; Monthly Base: <strong><?php echo number_format($package_price); ?> <?php echo $package_currency; ?></strong></p>
                </div>
                <div class="banner-actions">
                    <a href="?tab=plan" class="btn-white"><i class="fas fa-sync-alt"></i> Change Plan</a>
                    <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:8px;"></i> Live Sync</span>
                </div>
            </div>
                                   <!-- ===== BANNER ENDS ===== -->
            
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

            <!-- ===== DYNAMIC STATS - ONLY SHOW ACTIVE SERVICES ===== -->
            <div class="stats-grid">
                <?php if ($posts_limit > 0): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-square"></i></div>
                    <div class="number"><?php echo $social_progress['postsCompleted']; ?></div>
                    <div class="label">Posts Completed / <?php echo $posts_limit; ?></div>
                    <div class="progress-bar">
                        <div class="fill indigo" style="width: <?php echo $posts_limit > 0 ? ($social_progress['postsCompleted'] / $posts_limit) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($stories_limit > 0): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-sparkles"></i></div>
                    <div class="number"><?php echo $social_progress['storiesCompleted']; ?></div>
                    <div class="label">Stories Done / <?php echo $stories_limit; ?></div>
                    <div class="progress-bar">
                        <div class="fill pink" style="width: <?php echo $stories_limit > 0 ? ($social_progress['storiesCompleted'] / $stories_limit) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($reels_limit > 0): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-video"></i></div>
                    <div class="number"><?php echo $social_progress['reelsCompleted']; ?></div>
                    <div class="label">Reels / <?php echo $reels_limit; ?></div>
                    <div class="progress-bar">
                        <div class="fill emerald" style="width: <?php echo $reels_limit > 0 ? ($social_progress['reelsCompleted'] / $reels_limit) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($ads_limit > 0): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-ad"></i></div>
                    <div class="number"><?php echo $social_progress['adsCompleted']; ?></div>
                    <div class="label">Ads / <?php echo $ads_limit; ?></div>
                    <div class="progress-bar">
                        <div class="fill orange" style="width: <?php echo $ads_limit > 0 ? ($social_progress['adsCompleted'] / $ads_limit) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Followers is always shown -->
                <div class="stat-card" style="<?php echo ($posts_limit == 0 && $stories_limit == 0 && $reels_limit == 0 && $ads_limit == 0) ? 'grid-column: 1 / -1;' : ''; ?>">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="number">+<?php echo number_format($social_progress['followersGained']); ?></div>
                    <div class="label">Followers Gained</div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- ===== PAGE SPECIFIC CONTENT ===== -->
            <!-- ============================================================ -->

            <!-- ===== TAB 1: DASHBOARD ===== -->
            <?php if ($activeTab === 'dashboard'): ?>

            <div class="grid-2">
                <!-- Social Growth -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line" style="color:var(--primary);"></i> Social Growth</h3>
                        <span class="sub"><i class="fas fa-circle" style="color:#10b981;font-size:8px;"></i> Live</span>
                    </div>
                    <div class="grid-3" style="gap:10px;">
                        <div style="background:#f8fafc;padding:10px;border-radius:10px;text-align:center;">
                            <div style="font-size:10px;color:var(--text-muted);font-weight:600;">Likes</div>
                            <div style="font-size:18px;font-weight:900;color:var(--text-primary);"><?php echo number_format($social_progress['totalLikes']); ?></div>
                        </div>
                        <div style="background:#f8fafc;padding:10px;border-radius:10px;text-align:center;">
                            <div style="font-size:10px;color:var(--text-muted);font-weight:600;">Mentions</div>
                            <div style="font-size:18px;font-weight:900;color:var(--text-primary);"><?php echo $social_progress['brandMentions']; ?></div>
                        </div>
                        <div style="background:#f8fafc;padding:10px;border-radius:10px;text-align:center;">
                            <div style="font-size:10px;color:var(--text-muted);font-weight:600;">Progress</div>
                            <div style="font-size:18px;font-weight:900;color:var(--primary);"><?php echo $social_ratio; ?>%</div>
                        </div>
                    </div>
                </div>

               <!-- ===== BRAND2SOCIAL ATTACHMENTS (CLIENT SIDE - DOWNLOAD ONLY) ===== -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-paperclip" style="color:var(--primary);"></i> Brand2Social Attachments</h3>
        <span class="sub">Files uploaded by your PM</span>
    </div>
    <?php if (!empty($attachments)): ?>
        <?php foreach ($attachments as $att): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f8fafc;border-radius:10px;margin-bottom:8px;border-left:3px solid var(--primary);transition:var(--transition);">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:13px;color:var(--text-primary);">
                    <i class="fas fa-file"></i> <?php echo htmlspecialchars($att['file_name']); ?>
                </div>
                <div style="font-size:11px;color:var(--text-muted);">
                    Uploaded: <?php echo date('M d, Y H:i', strtotime($att['created_at'])); ?> &bull; 
                    Size: <?php echo round($att['file_size'] / 1024, 1); ?> KB
                    <?php if (!empty($att['description'])): ?>
                    &bull; <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($att['description']); ?>
                    <?php endif; ?>
                </div>
                <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                    <i class="fas fa-user"></i> Uploaded by: <?php echo $att['uploaded_by'] ?? 'PM'; ?>
                </div>
            </div>
            <a href="download.php?doc_id=<?php echo $att['id']; ?>" 
               style="background:var(--primary);color:#fff;border:none;padding:6px 16px;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;transition:var(--transition);display:inline-block;text-decoration:none;flex-shrink:0;"
               onmouseover="this.style.background='var(--primary-dark)'" 
               onmouseout="this.style.background='var(--primary)'">
                <i class="fas fa-download"></i> Download
            </a>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state" style="padding:20px;text-align:center;color:var(--text-muted);">
            <i class="fas fa-file" style="font-size:28px;display:block;margin-bottom:6px;opacity:0.3;"></i>
            <p style="font-size:12px;">No attachments available.</p>
            <p style="font-size:11px;">Your PM will upload brand2social files here.</p>
        </div>
    <?php endif; ?>
</div>

            <!-- Custom Projects -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-briefcase" style="color:var(--primary);"></i> Custom Projects</h3>
                    <button onclick="openModal('modal-custom-task')" style="background:var(--primary);color:#fff;border:none;padding:4px 16px;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;transition:var(--transition);">+ Request</button>
                </div>
                <div class="grid-2">
                    <?php if (!empty($custom_tasks)): ?>
                        <?php foreach ($custom_tasks as $task): ?>
                        <div class="task-item">
                            <div class="task-header">
                                <span class="task-title"><?php echo $task['title']; ?></span>
                                <span class="status-badge <?php echo $task['status'] === 'Awaiting Quote' ? 'pending' : 'in-progress'; ?>"><?php echo $task['status']; ?></span>
                            </div>
                            <div class="task-desc"><?php echo $task['description']; ?></div>
                            <div class="task-meta">
                                <span><i class="fas fa-tag"></i> <?php echo $task['category']; ?></span>
                                <?php if ($task['price'] > 0): ?>
                                <span><i class="fas fa-money-bill"></i> <?php echo number_format($task['price']); ?> PKR</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="grid-column:1/-1;">
                            <i class="fas fa-plus-circle"></i>
                            <p style="font-size:12px;">No custom projects yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== TAB 2: PACKAGES ===== -->
            <?php elseif ($activeTab === 'plan'): ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-credit-card" style="color:var(--primary);"></i> Service Plans</h3>
                    <span class="sub">Your assigned packages</span>
                </div>
                <div class="package-grid">
                    <?php if (!empty($packages)): ?>
                        <?php foreach ($packages as $pkg): 
                            $active = ($active_package && $active_package['id'] == $pkg['id']);
                        ?>
                        <div class="package-card <?php echo $active ? 'active' : ''; ?>">
                            <?php if ($active): ?>
                            <div class="active-badge">Active</div>
                            <?php endif; ?>
                            <div class="pkg-name"><?php echo $pkg['name']; ?></div>
                            <div class="pkg-price"><?php echo number_format($pkg['price']); ?> <span><?php echo $pkg['currency'] ?? 'PKR'; ?>/mo</span></div>
                            <ul>
                                <?php if (($pkg['posts_limit'] ?? 0) > 0): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo $pkg['posts_limit']; ?> posts</li>
                                <?php endif; ?>
                                <?php if (($pkg['stories_limit'] ?? 0) > 0): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo $pkg['stories_limit']; ?> stories</li>
                                <?php endif; ?>
                                <?php if (($pkg['reels_limit'] ?? 0) > 0): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo $pkg['reels_limit']; ?> reels</li>
                                <?php endif; ?>
                                <?php if (($pkg['ads_limit'] ?? 0) > 0): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo $pkg['ads_limit']; ?> ads</li>
                                <?php endif; ?>
                                <?php if (($pkg['content_calendar'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> Content Calendar</li>
                                <?php endif; ?>
                                <?php if (($pkg['hashtag_research'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> Hashtag Research</li>
                                <?php endif; ?>
                                <?php if (($pkg['daily_engagement'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> Daily Engagement</li>
                                <?php endif; ?>
                                <?php if (($pkg['graphic_designs'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> Graphic Designs</li>
                                <?php endif; ?>
                                <?php if (($pkg['monthly_report'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> Monthly Report</li>
                                <?php endif; ?>
                                <?php if (($pkg['youtube_seo'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> YouTube SEO</li>
                                <?php endif; ?>
                                <?php if (($pkg['fb_ig_ads'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> FB & IG Targeted Ads</li>
                                <?php endif; ?>
                                <?php if (($pkg['google_ads'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> Google Ads</li>
                                <?php endif; ?>
                                <?php if (($pkg['website_store'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> Website/Store Management</li>
                                <?php endif; ?>
                                <?php if (($pkg['pinterest_management'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> Pinterest Management</li>
                                <?php endif; ?>
                                <?php if (($pkg['ugc_blogs'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> 4x UGC Blogs (SEO)</li>
                                <?php endif; ?>
                                <?php if (($pkg['profile_creation'] ?? 0) == 1): ?>
                                <li><i class="fas fa-check-circle"></i> All Platform Profile Creation</li>
                                <?php endif; ?>
                            </ul>
                            <button class="btn-switch <?php echo $active ? 'active' : ''; ?>" 
                                    <?php echo $active ? 'disabled' : ''; ?>
                                    onclick="switchPackage(<?php echo $pkg['id']; ?>)">
                                <?php echo $active ? 'Current Package' : 'Switch Package'; ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">
                            <i class="fas fa-cubes" style="font-size:40px;display:block;margin-bottom:12px;opacity:0.3;"></i>
                            <p style="font-size:14px;">No packages assigned to you yet.</p>
                            <p style="font-size:12px;">Please contact your account manager.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== TAB 3: ADDONS ===== -->
            <?php elseif ($activeTab === 'addons'): ?>

            <!-- Out of Scope -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-tools" style="color:var(--primary);"></i> Out-of-Scope Tasks</h3>
                    <span class="sub">Custom projects with separate pricing</span>
                </div>
                <div class="grid-3">
                    <?php foreach ($out_of_scope_tasks as $task): ?>
                    <div style="padding:14px;border:1px solid var(--border);border-radius:12px;transition:var(--transition);" class="hover:shadow-md">
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

            <!-- Custom Projects -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-project-diagram" style="color:var(--primary);"></i> Custom Project Tracker</h3>
                </div>
                <div class="grid-2">
                    <?php if (!empty($custom_tasks)): ?>
                        <?php foreach ($custom_tasks as $task): ?>
                        <div class="task-item">
                            <div class="task-header">
                                <span class="task-title"><?php echo $task['title']; ?></span>
                                <span class="status-badge <?php echo $task['status'] === 'Awaiting Quote' ? 'pending' : 'in-progress'; ?>"><?php echo $task['status']; ?></span>
                            </div>
                            <div class="task-desc"><?php echo $task['description']; ?></div>
                            <div class="task-meta">
                                <span><i class="fas fa-tag"></i> <?php echo $task['category']; ?></span>
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

            <!-- Active Addons -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-layer-group" style="color:var(--primary);"></i> Active Add-ons</h3>
                </div>
                <?php if (!empty($addons)): ?>
                    <?php foreach ($addons as $add): ?>
                    <div style="padding:12px 14px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <div>
                            <div style="font-weight:700;color:var(--text-primary);font-size:13px;"><?php echo $add['name']; ?></div>
                            <div style="font-size:11px;color:var(--text-muted);"><?php echo $add['metrics'] ?? 'In Progress'; ?></div>
                        </div>
                        <span class="status-badge <?php echo $add['status'] === 'In Progress' ? 'in-progress' : 'done'; ?>"><?php echo $add['status']; ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-plus"></i>
                        <p style="font-size:12px;">No active add-ons</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== TAB 4: DELIVERABLES ===== -->
            <?php elseif ($activeTab === 'deliverables'): ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-check-square" style="color:var(--primary);"></i> Deliverable Pipeline</h3>
                    <span class="sub">Track project progress</span>
                </div>
                <div class="grid-3">
                    <?php 
                    $statuses = ['To Do', 'In Progress', 'Done'];
                    $statusColors = ['#f8fafc', '#fff3e0', '#e8f5e9'];
                    foreach ($statuses as $col): 
                    ?>
                    <div style="background:#f8fafc;padding:12px;border-radius:10px;border:1px solid var(--border);">
                        <h4 style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px;border-bottom:1px solid var(--border);padding-bottom:6px;"><?php echo $col; ?></h4>
                        <?php 
                        $found = false;
                        foreach ($deliverables as $deliv): 
                            if ($deliv['status'] === $col): 
                                $found = true;
                        ?>
                        <div style="background:var(--card-bg);padding:10px 12px;border-radius:8px;margin-bottom:6px;border:1px solid var(--border);">
                            <div style="font-weight:700;font-size:12px;color:var(--text-primary);"><?php echo $deliv['name']; ?></div>
                            <div style="font-size:10px;color:var(--text-muted);">Due: <?php echo date('Y-m-d', strtotime($deliv['due_date'])); ?></div>
                            <div style="font-size:10px;color:var(--text-muted);">Agent: <?php echo $deliv['assigned_to'] ?? 'Unassigned'; ?></div>
                        </div>
                        <?php 
                            endif; 
                        endforeach; 
                        if (!$found): 
                        ?>
                        <div style="text-align:center;padding:12px 0;color:var(--text-muted);font-size:11px;">No items</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ===== TAB 5: SUPPORT ===== -->
            <?php elseif ($activeTab === 'requests'): ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-headset" style="color:var(--primary);"></i> Support & Tasks</h3>
                    <button onclick="openModal('modal-ticket')" style="background:var(--primary);color:#fff;border:none;padding:6px 18px;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;transition:var(--transition);"><i class="fas fa-plus"></i> New Ticket</button>
                </div>
                <?php if (!empty($tickets)): ?>
                    <?php foreach ($tickets as $req): ?>
                    <div style="padding:14px 16px;border:1px solid var(--border);border-radius:10px;margin-bottom:10px;transition:var(--transition);" class="hover:border-primary">
                        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                            <div>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:3px;">
                                    <span class="status-badge <?php echo $req['status'] === 'Open' ? 'open' : 'done'; ?>"><?php echo $req['status']; ?></span>
                                    <span style="font-size:10px;font-weight:600;color:var(--text-muted);"><?php echo $req['category'] ?? 'General'; ?></span>
                                    <span style="font-size:10px;color:var(--text-muted);">Priority: <?php echo $req['priority'] ?? 'Medium'; ?></span>
                                </div>
                                <div style="font-weight:700;color:var(--text-primary);font-size:14px;"><?php echo $req['title']; ?></div>
                                <div style="font-size:12px;color:var(--text-secondary);margin-top:3px;"><?php echo $req['description']; ?></div>
                                <?php if (!empty($req['admin_notes'])): ?>
                                <div style="margin-top:6px;padding:8px 12px;background:#f0f3ff;border-radius:8px;font-size:12px;color:var(--primary);">
                                    <i class="fas fa-reply"></i> <?php echo $req['admin_notes']; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:10px;color:var(--text-muted);white-space:nowrap;"><?php echo date('Y-m-d', strtotime($req['created_at'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p style="font-size:12px;">No support tickets</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== TAB 6: BILLING ===== -->
            <?php elseif ($activeTab === 'billing'): ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-invoice" style="color:var(--primary);"></i> Invoice Ledger</h3>
                    <span style="font-size:12px;font-weight:700;color:#10b981;">
                        Total Paid: <?php 
                            $total_paid = 0;
                            foreach ($invoices as $inv) {
                                if ($inv['status'] === 'Paid') $total_paid += $inv['amount'];
                            }
                            echo number_format($total_paid) . ' PKR';
                        ?>
                    </span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Note</th>
                                <th>Status</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($invoices)): ?>
                                <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td style="font-weight:700;color:var(--text-primary);font-size:12px;"><?php echo $inv['invoice_number']; ?></td>
                                    <td style="font-weight:700;font-size:12px;"><?php echo number_format($inv['amount']); ?> PKR</td>
                                    <td style="color:var(--text-secondary);font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo $inv['note'] ?? '-'; ?></td>
                                    <td><span class="status-badge <?php echo $inv['status'] === 'Paid' ? 'paid' : 'unpaid'; ?>"><?php echo $inv['status']; ?></span></td>
                                    <td style="text-align:right;">
                                        <?php if ($inv['status'] === 'Paid'): ?>
                                            <span style="color:#10b981;font-weight:600;font-size:11px;"><i class="fas fa-check-circle"></i> Cleared</span>
                                        <?php else: ?>
                                            <button onclick="settleInvoice(<?php echo $inv['id']; ?>)" style="background:var(--primary);color:#fff;border:none;padding:4px 14px;border-radius:40px;font-size:10px;font-weight:600;cursor:pointer;transition:var(--transition);">Pay Now</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);font-size:13px;">No invoices found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===== TAB 7: REPORTS ===== -->
            <?php elseif ($activeTab === 'reports'): ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar" style="color:var(--primary);"></i> Campaign Reports</h3>
                    <span class="sub">Performance metrics</span>
                </div>

                <?php if (!empty($campaign_reports)): ?>
                    <div class="grid-2">
                        <?php foreach ($campaign_reports as $report): ?>
                        <div style="padding:16px;border:1px solid var(--border);border-radius:10px;background:#f8fafc;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                <span style="font-size:10px;font-weight:700;color:var(--primary);text-transform:uppercase;"><?php echo $report['platform'] ?? 'Meta Ads'; ?></span>
                                <i class="fas fa-trend-up" style="color:#10b981;font-size:16px;"></i>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;text-align:center;">
                                <div>
                                    <div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Spend</div>
                                    <div style="font-weight:800;color:var(--text-primary);font-size:14px;"><?php echo number_format($report['total_spend'] ?? 18500); ?> PKR</div>
                                </div>
                                <div>
                                    <div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Impressions</div>
                                    <div style="font-weight:800;color:var(--text-primary);font-size:14px;"><?php echo number_format($report['impressions'] ?? 112450); ?></div>
                                </div>
                                <div>
                                    <div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Conversions</div>
                                    <div style="font-weight:800;color:var(--text-primary);font-size:14px;"><?php echo number_format($report['conversions'] ?? 245); ?></div>
                                </div>
                            </div>
                            <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:11px;">
                                <span style="color:var(--text-muted);">ROI: <strong style="color:#10b981;"><?php echo $report['roi'] ?? 3.2; ?>x</strong></span>
                                <button onclick="downloadReport('<?php echo $report['id']; ?>')" style="background:transparent;border:none;color:var(--primary);font-weight:600;cursor:pointer;font-size:11px;"><i class="fas fa-download"></i> Export</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="grid-2">
                        <div style="padding:16px;border:1px solid var(--border);border-radius:10px;background:#f8fafc;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                <span style="font-size:10px;font-weight:700;color:var(--primary);text-transform:uppercase;">Meta Ads</span>
                                <i class="fas fa-trend-up" style="color:#10b981;font-size:16px;"></i>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;text-align:center;">
                                <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Spend</div><div style="font-weight:800;color:var(--text-primary);font-size:14px;">18,500 PKR</div></div>
                                <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Impressions</div><div style="font-weight:800;color:var(--text-primary);font-size:14px;">112,450</div></div>
                                <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Conversions</div><div style="font-weight:800;color:var(--text-primary);font-size:14px;">245</div></div>
                            </div>
                            <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:11px;">
                                <span style="color:var(--text-muted);">ROI: <strong style="color:#10b981;">3.2x</strong></span>
                                <button onclick="downloadReport()" style="background:transparent;border:none;color:var(--primary);font-weight:600;cursor:pointer;font-size:11px;"><i class="fas fa-download"></i> Export</button>
                            </div>
                        </div>
                        <div style="padding:16px;border:1px solid var(--border);border-radius:10px;background:#f8fafc;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                <span style="font-size:10px;font-weight:700;color:var(--primary);text-transform:uppercase;">Google Ads</span>
                                <i class="fas fa-trend-up" style="color:#10b981;font-size:16px;"></i>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;text-align:center;">
                                <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Spend</div><div style="font-weight:800;color:var(--text-primary);font-size:14px;">12,800 PKR</div></div>
                                <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Impressions</div><div style="font-weight:800;color:var(--text-primary);font-size:14px;">78,320</div></div>
                                <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Conversions</div><div style="font-weight:800;color:var(--text-primary);font-size:14px;">187</div></div>
                            </div>
                            <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:11px;">
                                <span style="color:var(--text-muted);">ROI: <strong style="color:#10b981;">4.1x</strong></span>
                                <button onclick="downloadReport()" style="background:transparent;border:none;color:var(--primary);font-weight:600;cursor:pointer;font-size:11px;"><i class="fas fa-download"></i> Export</button>
                            </div>
                        </div>
                    </div>
                    <!-- Summary -->
                    <div style="margin-top:14px;padding:14px;background:#f8fafc;border-radius:10px;border:1px solid var(--border);">
                        <h4 style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px;">Summary Overview</h4>
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;text-align:center;">
                            <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Total Spend</div><div style="font-weight:800;font-size:14px;">31,300 PKR</div></div>
                            <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Impressions</div><div style="font-weight:800;font-size:14px;">190,770</div></div>
                            <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Conversions</div><div style="font-weight:800;font-size:14px;">432</div></div>
                            <div><div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Avg. ROI</div><div style="font-weight:800;font-size:14px;color:#10b981;">3.7x</div></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php endif; ?>

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

    <!-- Ticket Modal -->
    <div class="modal-overlay" id="modal-ticket">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-ticket')"><i class="fas fa-times"></i></button>
            <h3>Submit Support Ticket</h3>
            <p class="modal-sub">Choose task assignment or general support</p>
            <div style="display:flex;gap:6px;margin-bottom:14px;">
                <button onclick="switchTicketTab('task')" id="tab-task" style="flex:1;padding:8px;border-radius:8px;border:none;font-weight:600;font-size:11px;cursor:pointer;background:var(--primary);color:#fff;transition:var(--transition);"><i class="fas fa-clipboard-list"></i> Task</button>
                <button onclick="switchTicketTab('support')" id="tab-support" style="flex:1;padding:8px;border-radius:8px;border:none;font-weight:600;font-size:11px;cursor:pointer;background:#e9edf2;color:var(--text-secondary);transition:var(--transition);"><i class="fas fa-life-ring"></i> Support</button>
            </div>
            <div id="task-form">
                <form onsubmit="submitTicket(event, 'Task Assignment')">
                    <label>Subject</label>
                    <input type="text" id="tk-task-title" required placeholder="e.g. Instagram story modification">
                    <label>Details</label>
                    <textarea id="tk-task-desc" rows="3" required placeholder="Explain your task request..."></textarea>
                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send Task</button>
                </form>
            </div>
            <div id="support-form" style="display:none;">
                <form onsubmit="submitTicket(event, 'Support Ticket')">
                    <label>Subject</label>
                    <input type="text" id="tk-support-title" required placeholder="e.g. Billing issue">
                    <label>Priority</label>
                    <select id="tk-support-priority">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                    <label>Details</label>
                    <textarea id="tk-support-desc" rows="3" required placeholder="Describe your issue..."></textarea>
                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send Ticket</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal-overlay" id="modal-upload">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-upload')"><i class="fas fa-times"></i></button>
            <h3>Upload File</h3>
            <p class="modal-sub">Upload brand2social analytics files</p>
            <form id="upload-form" onsubmit="uploadFile(event)" enctype="multipart/form-data">
                <label>Select File (PDF, CSV, XLSX)</label>
                <input type="file" id="upload-file" required accept=".pdf,.csv,.xlsx,.xls">
                <button type="submit" class="btn-submit"><i class="fas fa-upload"></i> Upload</button>
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

        // ===== TICKET TAB =====
        function switchTicketTab(type) {
            const taskTab = document.getElementById('tab-task');
            const supportTab = document.getElementById('tab-support');
            const taskForm = document.getElementById('task-form');
            const supportForm = document.getElementById('support-form');
            if (type === 'task') {
                taskTab.style.background = 'var(--primary)';
                taskTab.style.color = '#fff';
                supportTab.style.background = '#e9edf2';
                supportTab.style.color = 'var(--text-secondary)';
                taskForm.style.display = 'block';
                supportForm.style.display = 'none';
            } else {
                supportTab.style.background = 'var(--primary)';
                supportTab.style.color = '#fff';
                taskTab.style.background = '#e9edf2';
                taskTab.style.color = 'var(--text-secondary)';
                supportForm.style.display = 'block';
                taskForm.style.display = 'none';
            }
        }

        // ===== AJAX FUNCTIONS =====
        function submitTicket(e, category) {
            e.preventDefault();
            let title, description;
            if (category === 'Task Assignment') {
                title = document.getElementById('tk-task-title').value;
                description = document.getElementById('tk-task-desc').value;
            } else {
                title = document.getElementById('tk-support-title').value;
                description = document.getElementById('tk-support-desc').value;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'submit_ticket');
            formData.append('title', title);
            formData.append('category', category);
            formData.append('description', description);
            formData.append('client_id', '<?php echo $client_id; ?>');
            
            showToast('Submitting ticket...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Ticket submitted successfully!');
                    closeModal('modal-ticket');
                    document.getElementById('tk-task-title').value = '';
                    document.getElementById('tk-task-desc').value = '';
                    document.getElementById('tk-support-title').value = '';
                    document.getElementById('tk-support-desc').value = '';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error submitting ticket.', 'error');
            });
        }

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
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
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

        function requestAddon(name) {
            if (!confirm(`Request "${name}"?`)) return;
            const formData = new FormData();
            formData.append('ajax_action', 'request_addon');
            formData.append('name', name);
            formData.append('client_id', '<?php echo $client_id; ?>');
            
            showToast('Requesting add-on...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
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

        function switchPackage(packageId) {
            if (!confirm('Switch to this package?')) return;
            const formData = new FormData();
            formData.append('ajax_action', 'switch_package');
            formData.append('package_id', packageId);
            formData.append('client_id', '<?php echo $client_id; ?>');
            
            showToast('Switching package...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Package switched!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error switching package.', 'error');
            });
        }

        function settleInvoice(invoiceId) {
            if (!confirm('Settle this invoice?')) return;
            const formData = new FormData();
            formData.append('ajax_action', 'settle_invoice');
            formData.append('invoice_id', invoiceId);
            
            showToast('Processing payment...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Invoice settled!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error settling invoice.', 'error');
            });
        }

        function uploadFile(e) {
            e.preventDefault();
            const fileInput = document.getElementById('upload-file');
            if (!fileInput.files || fileInput.files.length === 0) {
                showToast('Please select a file.', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'upload_file');
            formData.append('file', fileInput.files[0]);
            formData.append('client_id', '<?php echo $client_id; ?>');
            formData.append('type', 'brand2social');
            
            showToast('Uploading...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('File uploaded!');
                    closeModal('modal-upload');
                    document.getElementById('upload-form').reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error uploading file.', 'error');
            });
        }

        function downloadFile(fileId) {
            showToast('Downloading file...', 'warning');
        }

        function downloadReport(reportId) {
            showToast('Generating PDF report...', 'warning');
        }

        // ===== SESSION TIMEOUT WARNING =====
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