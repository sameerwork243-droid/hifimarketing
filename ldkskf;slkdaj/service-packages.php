<?php
// services-packages.php - COMPLETE FIXED VERSION
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../client-portal/login.php');
    exit();
}

// Allow Admin, PM, AND Super Admin
if (!isset($_SESSION['portal_role']) || 
    ($_SESSION['portal_role'] !== 'admin' && 
     $_SESSION['portal_role'] !== 'pm' && 
     $_SESSION['portal_role'] !== 'super_admin')) {
    header('Location: ../client-portal/client-portal.php');
    exit();
}
$userData = $_SESSION['user'] ?? [];
$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';

// ===== GET ALL CLIENTS =====
$clients_sql = "SELECT c.*, u.username, u.email FROM clients c JOIN users u ON c.user_id = u.id";
$clients_result = mysqli_query($conn, $clients_sql);
$clients = [];
while ($row = mysqli_fetch_assoc($clients_result)) {
    $clients[] = $row;
}

// ===== GET SELECTED CLIENT =====
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// ===== GET PACKAGES =====
if ($selected_client_id > 0) {
    $packages_sql = "SELECT p.*, cp.client_id, cp.assigned_at 
                     FROM packages p 
                     LEFT JOIN client_packages cp ON p.id = cp.package_id AND cp.client_id = ?
                     WHERE p.status = 'active' 
                     ORDER BY p.price ASC";
    $stmt = mysqli_prepare($conn, $packages_sql);
    mysqli_stmt_bind_param($stmt, "i", $selected_client_id);
    mysqli_stmt_execute($stmt);
    $packages_result = mysqli_stmt_get_result($stmt);
} else {
    $packages_sql = "SELECT * FROM packages WHERE status = 'active' ORDER BY price ASC";
    $packages_result = mysqli_query($conn, $packages_sql);
}
$packages = [];
while ($row = mysqli_fetch_assoc($packages_result)) {
    $packages[] = $row;
}

// ===== GET CLIENT'S CURRENT PACKAGE =====
$client_current_package = null;
if ($selected_client_id > 0) {
    $current_sql = "SELECT active_package_id FROM clients WHERE id = ?";
    $stmt = mysqli_prepare($conn, $current_sql);
    mysqli_stmt_bind_param($stmt, "i", $selected_client_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $client_data = mysqli_fetch_assoc($result);
    $client_current_package = $client_data['active_package_id'] ?? null;
}

// ===== CREATE CLIENT PACKAGES TABLE =====
$create_client_packages = "CREATE TABLE IF NOT EXISTS client_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    package_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_client_package (client_id, package_id)
)";
mysqli_query($conn, $create_client_packages);

// ===== AJAX HANDLER =====
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    // 1. ADD PACKAGE
    if ($_POST['ajax_action'] === 'add_package') {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $price = floatval($_POST['price']);
        $currency = mysqli_real_escape_string($conn, trim($_POST['currency']));
        $billing_type = mysqli_real_escape_string($conn, trim($_POST['billing_type']));
        $posts_limit = intval($_POST['posts_limit']);
        $stories_limit = intval($_POST['stories_limit']);
        $reels_limit = intval($_POST['reels_limit']);
        $ads_limit = intval($_POST['ads_limit']);
        $client_id = intval($_POST['client_id'] ?? 0);
        
        // Service toggles (1 = checked/enabled, 0 = unchecked/disabled)
        $content_calendar = isset($_POST['content_calendar']) && $_POST['content_calendar'] == 1 ? 1 : 0;
        $hashtag_research = isset($_POST['hashtag_research']) && $_POST['hashtag_research'] == 1 ? 1 : 0;
        $daily_engagement = isset($_POST['daily_engagement']) && $_POST['daily_engagement'] == 1 ? 1 : 0;
        $graphic_designs = isset($_POST['graphic_designs']) && $_POST['graphic_designs'] == 1 ? 1 : 0;
        $monthly_report = isset($_POST['monthly_report']) && $_POST['monthly_report'] == 1 ? 1 : 0;
        $youtube_seo = isset($_POST['youtube_seo']) && $_POST['youtube_seo'] == 1 ? 1 : 0;
        $fb_ig_ads = isset($_POST['fb_ig_ads']) && $_POST['fb_ig_ads'] == 1 ? 1 : 0;
        $google_ads = isset($_POST['google_ads']) && $_POST['google_ads'] == 1 ? 1 : 0;
        $website_store = isset($_POST['website_store']) && $_POST['website_store'] == 1 ? 1 : 0;
        $pinterest_management = isset($_POST['pinterest_management']) && $_POST['pinterest_management'] == 1 ? 1 : 0;
        $ugc_blogs = isset($_POST['ugc_blogs']) && $_POST['ugc_blogs'] == 1 ? 1 : 0;
        $profile_creation = isset($_POST['profile_creation']) && $_POST['profile_creation'] == 1 ? 1 : 0;
        
        if (!empty($name) && $price > 0) {
            $sql = "INSERT INTO packages (
                name, description, price, currency, billing_type, 
                posts_limit, stories_limit, reels_limit, ads_limit,
                content_calendar, hashtag_research, daily_engagement, 
                graphic_designs, monthly_report, youtube_seo, 
                fb_ig_ads, google_ads, website_store, 
                pinterest_management, ugc_blogs, profile_creation, status
            ) VALUES (
                '$name', '$description', $price, '$currency', '$billing_type',
                $posts_limit, $stories_limit, $reels_limit, $ads_limit,
                $content_calendar, $hashtag_research, $daily_engagement,
                $graphic_designs, $monthly_report, $youtube_seo,
                $fb_ig_ads, $google_ads, $website_store,
                $pinterest_management, $ugc_blogs, $profile_creation, 'active'
            )";
            
            if (mysqli_query($conn, $sql)) {
                $package_id = mysqli_insert_id($conn);
                
                if ($client_id > 0) {
                    $check = mysqli_query($conn, "SELECT id FROM client_packages WHERE client_id = $client_id AND package_id = $package_id");
                    if (mysqli_num_rows($check) == 0) {
                        mysqli_query($conn, "INSERT INTO client_packages (client_id, package_id) VALUES ($client_id, $package_id)");
                    }
                    
                    $check_active = mysqli_query($conn, "SELECT active_package_id FROM clients WHERE id = $client_id");
                    $row = mysqli_fetch_assoc($check_active);
                    if (!$row || !$row['active_package_id']) {
                        mysqli_query($conn, "UPDATE clients SET active_package_id = $package_id WHERE id = $client_id");
                    }
                }
                
                $response = ['success' => true, 'message' => 'Package added successfully', 'id' => $package_id];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add package: ' . mysqli_error($conn)];
            }
        } else {
            $response = ['success' => false, 'message' => 'Please fill all required fields'];
        }
    }
    
    // 2. UPDATE PACKAGE
    elseif ($_POST['ajax_action'] === 'update_package') {
        $id = intval($_POST['id']);
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $price = floatval($_POST['price']);
        $currency = mysqli_real_escape_string($conn, trim($_POST['currency']));
        $billing_type = mysqli_real_escape_string($conn, trim($_POST['billing_type']));
        $posts_limit = intval($_POST['posts_limit']);
        $stories_limit = intval($_POST['stories_limit']);
        $reels_limit = intval($_POST['reels_limit']);
        $ads_limit = intval($_POST['ads_limit']);
        $client_id = intval($_POST['client_id'] ?? 0);
        
        $content_calendar = isset($_POST['content_calendar']) && $_POST['content_calendar'] == 1 ? 1 : 0;
        $hashtag_research = isset($_POST['hashtag_research']) && $_POST['hashtag_research'] == 1 ? 1 : 0;
        $daily_engagement = isset($_POST['daily_engagement']) && $_POST['daily_engagement'] == 1 ? 1 : 0;
        $graphic_designs = isset($_POST['graphic_designs']) && $_POST['graphic_designs'] == 1 ? 1 : 0;
        $monthly_report = isset($_POST['monthly_report']) && $_POST['monthly_report'] == 1 ? 1 : 0;
        $youtube_seo = isset($_POST['youtube_seo']) && $_POST['youtube_seo'] == 1 ? 1 : 0;
        $fb_ig_ads = isset($_POST['fb_ig_ads']) && $_POST['fb_ig_ads'] == 1 ? 1 : 0;
        $google_ads = isset($_POST['google_ads']) && $_POST['google_ads'] == 1 ? 1 : 0;
        $website_store = isset($_POST['website_store']) && $_POST['website_store'] == 1 ? 1 : 0;
        $pinterest_management = isset($_POST['pinterest_management']) && $_POST['pinterest_management'] == 1 ? 1 : 0;
        $ugc_blogs = isset($_POST['ugc_blogs']) && $_POST['ugc_blogs'] == 1 ? 1 : 0;
        $profile_creation = isset($_POST['profile_creation']) && $_POST['profile_creation'] == 1 ? 1 : 0;
        
        if ($id > 0 && !empty($name) && $price > 0) {
            $sql = "UPDATE packages SET 
                    name = '$name', 
                    description = '$description', 
                    price = $price, 
                    currency = '$currency', 
                    billing_type = '$billing_type', 
                    posts_limit = $posts_limit, 
                    stories_limit = $stories_limit, 
                    reels_limit = $reels_limit, 
                    ads_limit = $ads_limit,
                    content_calendar = $content_calendar,
                    hashtag_research = $hashtag_research,
                    daily_engagement = $daily_engagement,
                    graphic_designs = $graphic_designs,
                    monthly_report = $monthly_report,
                    youtube_seo = $youtube_seo,
                    fb_ig_ads = $fb_ig_ads,
                    google_ads = $google_ads,
                    website_store = $website_store,
                    pinterest_management = $pinterest_management,
                    ugc_blogs = $ugc_blogs,
                    profile_creation = $profile_creation
                    WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                if ($client_id > 0) {
                    $check = mysqli_query($conn, "SELECT id FROM client_packages WHERE client_id = $client_id AND package_id = $id");
                    if (mysqli_num_rows($check) == 0) {
                        mysqli_query($conn, "INSERT INTO client_packages (client_id, package_id) VALUES ($client_id, $id)");
                    }
                }
                $response = ['success' => true, 'message' => 'Package updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update package: ' . mysqli_error($conn)];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
    }
    
    // 3. DELETE PACKAGE
    elseif ($_POST['ajax_action'] === 'delete_package') {
        $id = intval($_POST['id']);
        if ($id > 0) {
            mysqli_query($conn, "DELETE FROM client_packages WHERE package_id = $id");
            if (mysqli_query($conn, "DELETE FROM packages WHERE id = $id")) {
                $response = ['success' => true, 'message' => 'Package deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete package: ' . mysqli_error($conn)];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid ID'];
        }
    }
    
    // 4. GET PACKAGE DATA
    elseif ($_POST['ajax_action'] === 'get_package') {
        $id = intval($_POST['id']);
        if ($id > 0) {
            $result = mysqli_query($conn, "SELECT * FROM packages WHERE id = $id");
            $package = mysqli_fetch_assoc($result);
            if ($package) {
                $response = ['success' => true, 'data' => $package];
            } else {
                $response = ['success' => false, 'message' => 'Package not found'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid ID'];
        }
    }
    
    // 5. ASSIGN PACKAGE
    elseif ($_POST['ajax_action'] === 'assign_package') {
        $client_id = intval($_POST['client_id']);
        $package_id = intval($_POST['package_id']);
        
        if ($client_id > 0 && $package_id > 0) {
            $check = mysqli_query($conn, "SELECT id FROM client_packages WHERE client_id = $client_id AND package_id = $package_id");
            if (mysqli_num_rows($check) == 0) {
                if (mysqli_query($conn, "INSERT INTO client_packages (client_id, package_id) VALUES ($client_id, $package_id)")) {
                    mysqli_query($conn, "UPDATE clients SET active_package_id = $package_id WHERE id = $client_id");
                    $response = ['success' => true, 'message' => 'Package assigned to client successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to assign package: ' . mysqli_error($conn)];
                }
            } else {
                mysqli_query($conn, "UPDATE clients SET active_package_id = $package_id WHERE id = $client_id");
                $response = ['success' => true, 'message' => 'Active package updated successfully'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
    }
    
    // 6. UNASSIGN PACKAGE
    elseif ($_POST['ajax_action'] === 'unassign_package') {
        $client_id = intval($_POST['client_id']);
        $package_id = intval($_POST['package_id']);
        
        if ($client_id > 0 && $package_id > 0) {
            if (mysqli_query($conn, "DELETE FROM client_packages WHERE client_id = $client_id AND package_id = $package_id")) {
                $check = mysqli_query($conn, "SELECT active_package_id FROM clients WHERE id = $client_id");
                $row = mysqli_fetch_assoc($check);
                if ($row && $row['active_package_id'] == $package_id) {
                    mysqli_query($conn, "UPDATE clients SET active_package_id = NULL WHERE id = $client_id");
                }
                $response = ['success' => true, 'message' => 'Package unassigned successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to unassign package: ' . mysqli_error($conn)];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
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
    <title>Services & Packages | HIFI Marketing</title>
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
        .banner .banner-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .banner .banner-actions .btn-white { background: #fff; color: var(--primary); padding: 6px 16px; border-radius: 40px; font-weight: 700; font-size: 11px; border: none; cursor: pointer; transition: var(--transition); }
        .banner .banner-actions .btn-white:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        
        .client-selector {
            display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
            padding: 14px 18px; background: var(--card-bg); border-radius: var(--radius);
            border: 1px solid var(--border); margin-bottom: 18px;
        }
        .client-selector label { font-weight: 700; font-size: 13px; color: var(--text-secondary); }
        .client-selector select {
            padding: 8px 16px; border: 1px solid var(--border); border-radius: 8px;
            background: #f8fafc; font-size: 13px; font-weight: 500; min-width: 200px;
            cursor: pointer; transition: var(--transition);
        }
        .client-selector select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(74,92,245,0.1); }
        .client-selector .client-info { font-size: 12px; color: var(--text-muted); margin-left: auto; }
        .client-selector .client-info strong { color: var(--text-primary); }
        
        .card {
            background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border);
            padding: 18px 20px; box-shadow: var(--shadow); margin-bottom: 18px;
        }
        .card .card-header {
            display: flex; align-items: center; justify-content: space-between;
            padding-bottom: 10px; border-bottom: 1px solid var(--border);
            margin-bottom: 14px; flex-wrap: wrap; gap: 8px;
        }
        .card .card-header h3 { font-size: 14px; font-weight: 700; color: var(--text-primary); }
        .card .card-header .sub { font-size: 11px; color: var(--text-muted); }
        
        .package-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
        .package-card {
            background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border);
            padding: 18px 20px; transition: var(--transition); position: relative;
        }
        .package-card:hover { box-shadow: var(--shadow-hover); transform: translateY(-2px); }
        .package-card.assigned { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(74,92,245,0.1); }
        .package-card .assigned-badge {
            position: absolute; top: -8px; right: 14px; background: var(--primary);
            color: #fff; font-size: 8px; font-weight: 700; padding: 2px 12px;
            border-radius: 20px; text-transform: uppercase;
        }
        .package-card .active-badge {
            position: absolute; top: -8px; left: 14px; background: #10b981;
            color: #fff; font-size: 8px; font-weight: 700; padding: 2px 12px;
            border-radius: 20px; text-transform: uppercase;
        }
        .package-card .pkg-name { font-size: 17px; font-weight: 800; color: var(--text-primary); }
        .package-card .pkg-price { font-size: 24px; font-weight: 900; color: var(--text-primary); margin: 6px 0; }
        .package-card .pkg-price span { font-size: 13px; font-weight: 500; color: var(--text-muted); }
        .package-card .pkg-desc { font-size: 12px; color: var(--text-secondary); margin-bottom: 10px; }
        
        .package-card ul { list-style: none; margin: 10px 0 14px; }
        .package-card ul li { 
            font-size: 12px; color: var(--text-secondary); padding: 4px 0; 
            display: flex; align-items: center; gap: 8px; 
        }
        .package-card ul li .service-icon { 
            width: 18px; text-align: center; font-size: 14px; 
        }
        .package-card ul li .service-icon.active { color: var(--success); }
        
        .package-card .pkg-actions { display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); flex-wrap: wrap; }
        .package-card .pkg-actions button { flex: 1; padding: 7px 12px; border-radius: 40px; border: none; font-weight: 700; font-size: 11px; cursor: pointer; transition: var(--transition); min-width: 60px; }
        .package-card .pkg-actions .btn-edit { background: var(--primary); color: #fff; }
        .package-card .pkg-actions .btn-edit:hover { background: var(--primary-dark); }
        .package-card .pkg-actions .btn-delete { background: #fee2e2; color: #dc3545; }
        .package-card .pkg-actions .btn-delete:hover { background: #fecaca; }
        .package-card .pkg-actions .btn-assign { background: #10b981; color: #fff; }
        .package-card .pkg-actions .btn-assign:hover { background: #059669; }
        .package-card .pkg-actions .btn-unassign { background: #fef3c7; color: #92400e; }
        .package-card .pkg-actions .btn-unassign:hover { background: #fde68a; }
        
        .modal-overlay {
            position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px); display: none; align-items: center;
            justify-content: center; padding: 16px;
        }
        .modal-overlay.show { display: flex; }
        .modal {
            background: var(--card-bg); border-radius: var(--radius); max-width: 600px;
            width: 100%; padding: 24px 28px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            position: relative; max-height: 90vh; overflow-y: auto;
        }
        .modal .modal-close { position: absolute; top: 14px; right: 16px; background: transparent; border: none; font-size: 18px; color: var(--text-muted); cursor: pointer; transition: var(--transition); }
        .modal .modal-close:hover { color: var(--text-primary); }
        .modal h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin-bottom: 3px; }
        .modal .modal-sub { font-size: 12px; color: var(--text-muted); margin-bottom: 16px; }
        .modal label { display: block; font-weight: 600; font-size: 12px; color: var(--text-secondary); margin-bottom: 3px; }
        .modal input, .modal select, .modal textarea {
            width: 100%; padding: 9px 12px; border: 1px solid var(--border);
            border-radius: 8px; background: #f8fafc; font-size: 13px;
            font-family: 'Inter', sans-serif; transition: var(--transition); margin-bottom: 12px;
        }
        .modal input:focus, .modal select:focus, .modal textarea:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(74,92,245,0.1);
        }
        
        .service-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        .service-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid var(--border);
            transition: var(--transition);
        }
        .service-item:hover { border-color: var(--primary); }
        .service-item .service-label { font-size: 12px; font-weight: 500; color: var(--text-secondary); flex: 1; }
        .service-item .service-count { width: 60px; }
        .service-item .service-count input { padding: 4px 6px; font-size: 12px; width: 100%; text-align: center; border: 1px solid var(--border); border-radius: 4px; background: #fff; }
        .service-item .service-count input:focus { outline: none; border-color: var(--primary); }
        
        .service-toggle {
            position: relative;
            width: 40px;
            height: 22px;
            flex-shrink: 0;
        }
        .service-toggle input { opacity: 0; width: 0; height: 0; }
        .service-toggle .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background: #d1d5db; transition: .3s; border-radius: 22px;
        }
        .service-toggle .slider:before {
            position: absolute; content: ""; height: 16px; width: 16px;
            left: 3px; bottom: 3px; background: white; transition: .3s; border-radius: 50%;
        }
        .service-toggle input:checked + .slider { background: var(--success); }
        .service-toggle input:checked + .slider:before { transform: translateX(18px); }
        .service-toggle .status-label {
            position: absolute; right: -24px; top: 50%; transform: translateY(-50%);
            font-size: 10px; font-weight: 700;
        }
        .service-toggle input:checked + .slider + .status-label { color: var(--success); }
        .service-toggle input:not(:checked) + .slider + .status-label { color: #ef4444; }
        
        .modal .btn-submit {
            width: 100%; padding: 10px; background: var(--primary); color: #fff;
            border: none; border-radius: 40px; font-weight: 700; font-size: 13px;
            cursor: pointer; transition: var(--transition);
        }
        .modal .btn-submit:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .modal .btn-cancel {
            width: 100%; padding: 10px; background: #e9edf2; color: var(--text-secondary);
            border: none; border-radius: 40px; font-weight: 700; font-size: 13px;
            cursor: pointer; transition: var(--transition); margin-top: 6px;
        }
        .modal .btn-cancel:hover { background: #d0d7e0; }
        
        .toast-container {
            position: fixed; top: 80px; right: 20px; z-index: 300;
            display: flex; flex-direction: column; gap: 8px;
        }
        .toast {
            background: var(--text-primary); color: #fff; padding: 12px 18px;
            border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            font-size: 12px; font-weight: 600; display: flex; align-items: center;
            gap: 8px; min-width: 260px; animation: slideIn 0.3s ease;
        }
        .toast.success i { color: #10b981; }
        .toast.error i { color: #ef4444; }
        .toast.warning i { color: #f59e0b; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .security-badge {
            position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.7);
            color: #4ade80; padding: 3px 12px; border-radius: 20px; font-size: 8px;
            font-weight: 700; z-index: 999; backdrop-filter: blur(10px);
            border: 1px solid rgba(74,222,128,0.2); pointer-events: none;
        }
        .back-link {
            display: inline-flex; align-items: center; gap: 8px; color: #fff;
            font-weight: 600; font-size: 13px; transition: var(--transition);
            padding: 4px 12px; border-radius: 8px; background: rgba(255,255,255,0.15);
        }
        .back-link:hover { background: rgba(255,255,255,0.25); }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-layout { padding: 12px; flex-direction: column; }
            .banner { padding: 16px 18px; flex-direction: column; text-align: center; }
            .banner h2 { font-size: 16px; }
            .package-grid { grid-template-columns: 1fr; }
            .client-selector { flex-direction: column; align-items: stretch; }
            .client-selector .client-info { margin-left: 0; text-align: center; }
            .header-actions .user-badge .name { display: none; }
            .modal { padding: 20px; }
            .service-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-inner">
            <div class="logo"><div class="brand-icon">H</div> HIFI <span>Marketing</span></div>
            <div class="header-actions">
                <div class="user-badge">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <span class="name"><?php echo $userData['name'] ?? 'PM'; ?></span>
                    <span class="online"></span>
                </div>
                <a href="login.php?logout=true"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </header>
    <div class="main-layout">
        <aside class="sidebar <?php echo $isCollapsed ? 'collapsed' : ''; ?>" id="mainSidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">P</div>
                <div class="sidebar-brand-text"><h1>SMMA Scale</h1><span>PM Portal</span></div>
            </div>
            <div class="sidebar-toggle"><button onclick="toggleSidebar()"><i class="fas fa-chevron-left"></i></button></div>
            <div class="sidebar-badge"><span>Access</span><span class="role">PM Admin</span></div>
            <nav class="sidebar-nav">
                <a href="index.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i><span class="sidebar-text">Operations Desk</span></a>
                <a href="deliverables.php" class="sidebar-link"><i class="fas fa-check-square"></i><span class="sidebar-text">Manage Deliverables</span></a>
                <a href="tickets.php" class="sidebar-link"><i class="fas fa-headset"></i><span class="sidebar-text">Client Tickets & Tasks</span></a>
                <a href="verbal.php" class="sidebar-link"><i class="fas fa-phone"></i><span class="sidebar-text">Client Verbal Requests</span></a>
                <a href="progress-sync.php" class="sidebar-link"><i class="fas fa-sliders-h"></i><span class="sidebar-text">Progress Counter Sync</span></a>
                <a href="pm-ad-campaigns.php" class="sidebar-link">
                    <i class="fas fa-bullhorn"></i>
                    <span class="sidebar-text">Ad Campaigns</span>
                </a>
                <a href="service-packages.php" class="sidebar-link">
                    <i class="fas fa-boxes"></i>
                    <span class="sidebar-text">Service Packages</span>
                </a>
                
<a href="pm-billing.php" class="sidebar-link <?php echo $current_page === 'services-packages.php' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
            <span class="sidebar-text">pm-billing</span>
        </a>
    
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <div class="sidebar-user-text"><div class="name"><?php echo $userData['name'] ?? 'PM'; ?></div><div class="role-label">Senior Account Director</div></div>
                </div>
                <a href="login.php?logout=true" class="logout-link"><i class="fas fa-sign-out-alt"></i><span class="sidebar-text">Logout</span></a>
            </div>
        </aside>
        <div class="content">
            <div class="banner">
                <div><h2><i class="fas fa-cubes"></i> Services & Packages</h2><p>Manage subscription plans and assign to clients</p></div>
                <div class="banner-actions">
                    <a href="pm-portal.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to PM Portal</a>
                    <button onclick="openAddModal()" class="btn-white"><i class="fas fa-plus-circle"></i> Add New Package</button>
                </div>
            </div>
            <div class="client-selector">
                <label for="clientFilter"><i class="fas fa-user"></i> Select Client:</label>
                <select id="clientFilter" onchange="window.location.href='?client_id=' + this.value">
                    <option value="0">-- All Clients --</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name'] . ' (' . $client['username'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($selected_client_id > 0): ?>
                    <span class="client-info">
                        <i class="fas fa-info-circle" style="color:var(--primary);"></i>
                        Showing packages for <strong><?php foreach ($clients as $c) { if ($c['id'] == $selected_client_id) { echo htmlspecialchars($c['name']); break; } } ?></strong>
                        <?php if ($client_current_package): ?>
                            • Active Package: <strong style="color:#10b981;"><?php foreach ($packages as $p) { if ($p['id'] == $client_current_package) { echo htmlspecialchars($p['name']); break; } } ?></strong>
                        <?php else: ?>
                            • <span style="color:#dc3545;">No active package</span>
                        <?php endif; ?>
                    </span>
                <?php else: ?>
                    <span class="client-info"><i class="fas fa-globe" style="color:var(--primary);"></i> Showing all packages</span>
                <?php endif; ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-credit-card" style="color:var(--primary);"></i> Service Plans</h3>
                    <span class="sub"><?php echo count($packages); ?> packages available</span>
                </div>
                <?php if (!empty($packages)): ?>
                <div class="package-grid">
                    <?php foreach ($packages as $pkg): 
                        $is_assigned = isset($pkg['client_id']) && $pkg['client_id'] == $selected_client_id;
                        $is_active = ($client_current_package && $client_current_package == $pkg['id']);
                    ?>
                    <div class="package-card <?php echo $is_assigned ? 'assigned' : ''; ?>" id="pkg-<?php echo $pkg['id']; ?>">
                        <?php if ($is_active): ?><span class="active-badge"><i class="fas fa-check-circle"></i> Active</span><?php endif; ?>
                        <?php if ($is_assigned): ?><span class="assigned-badge"><i class="fas fa-user-check"></i> Assigned</span><?php endif; ?>
                        <div class="pkg-name"><?php echo htmlspecialchars($pkg['name']); ?></div>
                        <div class="pkg-price"><?php echo number_format($pkg['price']); ?> <span><?php echo $pkg['currency'] ?? 'PKR'; ?>/<?php echo $pkg['billing_type'] ?? 'mo'; ?></span></div>
                        <div class="pkg-desc"><?php echo htmlspecialchars($pkg['description'] ?? 'No description'); ?></div>
                        
                        <!-- ===== ONLY ENABLED SERVICES ===== -->
                        <ul>
                            <?php if ($pkg['posts_limit'] > 0): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> <?php echo $pkg['posts_limit']; ?> Posts</li>
                            <?php endif; ?>
                            <?php if ($pkg['stories_limit'] > 0): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> <?php echo $pkg['stories_limit']; ?> Stories</li>
                            <?php endif; ?>
                            <?php if ($pkg['reels_limit'] > 0): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> <?php echo $pkg['reels_limit']; ?> Reels/Videos</li>
                            <?php endif; ?>
                            <?php if ($pkg['ads_limit'] > 0): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> <?php echo $pkg['ads_limit']; ?> Ads</li>
                            <?php endif; ?>
                            <?php if (($pkg['content_calendar'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> Content Calendar</li>
                            <?php endif; ?>
                            <?php if (($pkg['hashtag_research'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> Hashtag Research</li>
                            <?php endif; ?>
                            <?php if (($pkg['daily_engagement'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> 2hr Daily Engagement</li>
                            <?php endif; ?>
                            <?php if (($pkg['graphic_designs'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> Elegant Graphic Designs</li>
                            <?php endif; ?>
                            <?php if (($pkg['monthly_report'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> Monthly Report</li>
                            <?php endif; ?>
                            <?php if (($pkg['youtube_seo'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> YouTube SEO</li>
                            <?php endif; ?>
                            <?php if (($pkg['fb_ig_ads'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> FB & IG Targeted Ads</li>
                            <?php endif; ?>
                            <?php if (($pkg['google_ads'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> Google Ads</li>
                            <?php endif; ?>
                            <?php if (($pkg['website_store'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> Website/Store Management</li>
                            <?php endif; ?>
                            <?php if (($pkg['pinterest_management'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> Pinterest Management</li>
                            <?php endif; ?>
                            <?php if (($pkg['ugc_blogs'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> 4x UGC Blogs (SEO)</li>
                            <?php endif; ?>
                            <?php if (($pkg['profile_creation'] ?? 0) == 1): ?>
                            <li><span class="service-icon active"><i class="fas fa-check-circle"></i></span> All Platform Profile Creation</li>
                            <?php endif; ?>
                        </ul>
                        
                        <div class="pkg-actions">
                            <button class="btn-edit" onclick="editPackage(<?php echo $pkg['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn-delete" onclick="deletePackage(<?php echo $pkg['id']; ?>)"><i class="fas fa-trash"></i> Delete</button>
                            <?php if ($selected_client_id > 0): ?>
                                <?php if ($is_assigned): ?>
                                    <button class="btn-unassign" onclick="unassignPackage(<?php echo $selected_client_id; ?>, <?php echo $pkg['id']; ?>)"><i class="fas fa-user-minus"></i> Unassign</button>
                                <?php else: ?>
                                    <button class="btn-assign" onclick="assignPackage(<?php echo $selected_client_id; ?>, <?php echo $pkg['id']; ?>)"><i class="fas fa-user-plus"></i> Assign</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state" style="text-align:center;padding:40px;color:var(--text-muted);">
                    <i class="fas fa-cubes" style="font-size:40px;display:block;margin-bottom:12px;opacity:0.3;"></i>
                    <p style="font-size:14px;">No packages available.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== MODAL ===== -->
    <div class="modal-overlay" id="modal-package">
        <div class="modal">
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            <h3 id="modal-title">Add New Package</h3>
            <p class="modal-sub" id="modal-sub">Create a new service plan for clients</p>
            <form id="package-form" onsubmit="submitPackage(event)">
                <input type="hidden" id="pkg-id" value="0">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div><label>Package Name *</label><input type="text" id="pkg-name" required placeholder="e.g. Professional Package"></div>
                    <div><label>Price *</label><input type="number" id="pkg-price" required placeholder="e.g. 999" step="0.01"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div><label>Currency</label><select id="pkg-currency"><option value="PKR">PKR</option><option value="USD">USD</option><option value="AED">AED</option></select></div>
                    <div><label>Billing Type</label><select id="pkg-billing"><option value="Per Month">Per Month</option><option value="Per Year">Per Year</option><option value="One Time">One Time</option></select></div>
                </div>
                <label>Description</label>
                <textarea id="pkg-desc" rows="2" placeholder="Package description..."></textarea>
                
                <h4 style="font-size:13px;font-weight:700;color:var(--text-secondary);margin:8px 0 4px;">📊 Service Limits (Numeric)</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;margin-bottom:12px;">
                    <div><label style="font-size:11px;">Posts</label><input type="number" id="pkg-posts" value="0" placeholder="e.g. 25" style="padding:6px 8px;font-size:12px;"></div>
                    <div><label style="font-size:11px;">Stories</label><input type="number" id="pkg-stories" value="0" placeholder="e.g. 30" style="padding:6px 8px;font-size:12px;"></div>
                    <div><label style="font-size:11px;">Reels</label><input type="number" id="pkg-reels" value="0" placeholder="e.g. 10" style="padding:6px 8px;font-size:12px;"></div>
                    <div><label style="font-size:11px;">Ads</label><input type="number" id="pkg-ads" value="0" placeholder="e.g. 5" style="padding:6px 8px;font-size:12px;"></div>
                </div>
                
                <h4 style="font-size:13px;font-weight:700;color:var(--text-secondary);margin:8px 0 4px;">✅ Services (Toggle On/Off)</h4>
                <div class="service-grid">
                    <div class="service-item"><span class="service-label">Content Calendar</span><label class="service-toggle"><input type="checkbox" id="svc-content-calendar"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">Hashtag Research</span><label class="service-toggle"><input type="checkbox" id="svc-hashtag-research"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">2hr Daily Engagement</span><label class="service-toggle"><input type="checkbox" id="svc-daily-engagement"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">Elegant Graphic Designs</span><label class="service-toggle"><input type="checkbox" id="svc-graphic-designs"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">Monthly Report</span><label class="service-toggle"><input type="checkbox" id="svc-monthly-report"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">YouTube SEO</span><label class="service-toggle"><input type="checkbox" id="svc-youtube-seo"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">FB & IG Targeted Ads</span><label class="service-toggle"><input type="checkbox" id="svc-fb-ig-ads"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">Google Ads</span><label class="service-toggle"><input type="checkbox" id="svc-google-ads"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">Website/Store Management</span><label class="service-toggle"><input type="checkbox" id="svc-website-store"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">Pinterest Management</span><label class="service-toggle"><input type="checkbox" id="svc-pinterest-management"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">4x UGC Blogs (SEO)</span><label class="service-toggle"><input type="checkbox" id="svc-ugc-blogs"><span class="slider"></span><span class="status-label"></span></label></div>
                    <div class="service-item"><span class="service-label">All Platform Profile Creation</span><label class="service-toggle"><input type="checkbox" id="svc-profile-creation"><span class="slider"></span><span class="status-label"></span></label></div>
                </div>
                
                <?php if ($selected_client_id > 0): ?>
                <div style="background:#f0f3ff;padding:10px 14px;border-radius:8px;margin-bottom:12px;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-user-check" style="color:var(--primary);"></i>
                    <span style="font-size:12px;color:var(--text-secondary);">This package will be assigned to: <strong><?php foreach ($clients as $c) { if ($c['id'] == $selected_client_id) { echo htmlspecialchars($c['name']); break; } } ?></strong></span>
                    <input type="hidden" id="pkg-client-id" value="<?php echo $selected_client_id; ?>">
                </div>
                <?php else: ?>
                <div style="background:#fef3c7;padding:10px 14px;border-radius:8px;margin-bottom:12px;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-info-circle" style="color:#f59e0b;"></i>
                    <span style="font-size:12px;color:#92400e;">No client selected. You can assign this package later from the list.</span>
                </div>
                <input type="hidden" id="pkg-client-id" value="0">
                <?php endif; ?>
                
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Package</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <div class="toast-container" id="toast-container"></div>
    <div class="security-badge">🔒 Secure Session • <?php echo $_SERVER['REMOTE_ADDR']; ?></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            sidebar.classList.toggle('collapsed');
            document.cookie = `sidebar_collapsed=${sidebar.classList.contains('collapsed')}; path=/; max-age=31536000`;
        }
        
        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Add New Package';
            document.getElementById('modal-sub').textContent = 'Create a new service plan for clients';
            document.getElementById('pkg-id').value = '0';
            document.getElementById('pkg-name').value = '';
            document.getElementById('pkg-desc').value = '';
            document.getElementById('pkg-currency').value = 'PKR';
            document.getElementById('pkg-billing').value = 'Per Month';
            document.getElementById('pkg-price').value = '';
            document.getElementById('pkg-posts').value = '0';
            document.getElementById('pkg-stories').value = '0';
            document.getElementById('pkg-reels').value = '0';
            document.getElementById('pkg-ads').value = '0';
            
            // ALL CHECKBOXES OFF BY DEFAULT
            document.getElementById('svc-content-calendar').checked = false;
            document.getElementById('svc-hashtag-research').checked = false;
            document.getElementById('svc-daily-engagement').checked = false;
            document.getElementById('svc-graphic-designs').checked = false;
            document.getElementById('svc-monthly-report').checked = false;
            document.getElementById('svc-youtube-seo').checked = false;
            document.getElementById('svc-fb-ig-ads').checked = false;
            document.getElementById('svc-google-ads').checked = false;
            document.getElementById('svc-website-store').checked = false;
            document.getElementById('svc-pinterest-management').checked = false;
            document.getElementById('svc-ugc-blogs').checked = false;
            document.getElementById('svc-profile-creation').checked = false;
            
            document.getElementById('modal-package').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function editPackage(id) {
            document.getElementById('modal-title').textContent = 'Edit Package';
            document.getElementById('modal-sub').textContent = 'Update service plan details';
            
            const formData = new FormData();
            formData.append('ajax_action', 'get_package');
            formData.append('id', id);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const pkg = data.data;
                    document.getElementById('pkg-id').value = pkg.id;
                    document.getElementById('pkg-name').value = pkg.name;
                    document.getElementById('pkg-desc').value = pkg.description || '';
                    document.getElementById('pkg-currency').value = pkg.currency || 'PKR';
                    document.getElementById('pkg-billing').value = pkg.billing_type || 'Per Month';
                    document.getElementById('pkg-price').value = pkg.price;
                    document.getElementById('pkg-posts').value = pkg.posts_limit || 0;
                    document.getElementById('pkg-stories').value = pkg.stories_limit || 0;
                    document.getElementById('pkg-reels').value = pkg.reels_limit || 0;
                    document.getElementById('pkg-ads').value = pkg.ads_limit || 0;
                    
                    // Set checkboxes based on database values (1 = checked)
                    document.getElementById('svc-content-calendar').checked = (pkg.content_calendar == 1);
                    document.getElementById('svc-hashtag-research').checked = (pkg.hashtag_research == 1);
                    document.getElementById('svc-daily-engagement').checked = (pkg.daily_engagement == 1);
                    document.getElementById('svc-graphic-designs').checked = (pkg.graphic_designs == 1);
                    document.getElementById('svc-monthly-report').checked = (pkg.monthly_report == 1);
                    document.getElementById('svc-youtube-seo').checked = (pkg.youtube_seo == 1);
                    document.getElementById('svc-fb-ig-ads').checked = (pkg.fb_ig_ads == 1);
                    document.getElementById('svc-google-ads').checked = (pkg.google_ads == 1);
                    document.getElementById('svc-website-store').checked = (pkg.website_store == 1);
                    document.getElementById('svc-pinterest-management').checked = (pkg.pinterest_management == 1);
                    document.getElementById('svc-ugc-blogs').checked = (pkg.ugc_blogs == 1);
                    document.getElementById('svc-profile-creation').checked = (pkg.profile_creation == 1);
                    
                    // Set client_id if exists
                    if (pkg.client_id) {
                        document.getElementById('pkg-client-id').value = pkg.client_id;
                    }
                    
                    document.getElementById('modal-package').classList.add('show');
                    document.body.style.overflow = 'hidden';
                } else {
                    showToast('Error loading package: ' + data.message, 'error');
                }
            })
            .catch(error => { showToast('Error loading package', 'error'); });
        }
        
        function closeModal() {
            document.getElementById('modal-package').classList.remove('show');
            document.body.style.overflow = '';
        }
        document.querySelector('.modal-overlay').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-triangle-exclamation';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; setTimeout(() => toast.remove(), 300); }, 3500);
        }
        
        function submitPackage(e) {
            e.preventDefault();
            const id = document.getElementById('pkg-id').value;
            const name = document.getElementById('pkg-name').value.trim();
            const description = document.getElementById('pkg-desc').value.trim();
            const currency = document.getElementById('pkg-currency').value;
            const billing_type = document.getElementById('pkg-billing').value;
            const price = document.getElementById('pkg-price').value;
            const posts_limit = document.getElementById('pkg-posts').value || 0;
            const stories_limit = document.getElementById('pkg-stories').value || 0;
            const reels_limit = document.getElementById('pkg-reels').value || 0;
            const ads_limit = document.getElementById('pkg-ads').value || 0;
            const client_id = document.getElementById('pkg-client-id').value || 0;
            
            if (!name || !price || price <= 0) {
                showToast('Please enter a valid name and price', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', id == 0 ? 'add_package' : 'update_package');
            formData.append('id', id);
            formData.append('name', name);
            formData.append('description', description);
            formData.append('currency', currency);
            formData.append('billing_type', billing_type);
            formData.append('price', price);
            formData.append('posts_limit', posts_limit);
            formData.append('stories_limit', stories_limit);
            formData.append('reels_limit', reels_limit);
            formData.append('ads_limit', ads_limit);
            formData.append('client_id', client_id);
            
            // Service toggles - send 1 if checked, 0 if not
            formData.append('content_calendar', document.getElementById('svc-content-calendar').checked ? 1 : 0);
            formData.append('hashtag_research', document.getElementById('svc-hashtag-research').checked ? 1 : 0);
            formData.append('daily_engagement', document.getElementById('svc-daily-engagement').checked ? 1 : 0);
            formData.append('graphic_designs', document.getElementById('svc-graphic-designs').checked ? 1 : 0);
            formData.append('monthly_report', document.getElementById('svc-monthly-report').checked ? 1 : 0);
            formData.append('youtube_seo', document.getElementById('svc-youtube-seo').checked ? 1 : 0);
            formData.append('fb_ig_ads', document.getElementById('svc-fb-ig-ads').checked ? 1 : 0);
            formData.append('google_ads', document.getElementById('svc-google-ads').checked ? 1 : 0);
            formData.append('website_store', document.getElementById('svc-website-store').checked ? 1 : 0);
            formData.append('pinterest_management', document.getElementById('svc-pinterest-management').checked ? 1 : 0);
            formData.append('ugc_blogs', document.getElementById('svc-ugc-blogs').checked ? 1 : 0);
            formData.append('profile_creation', document.getElementById('svc-profile-creation').checked ? 1 : 0);
            
            showToast('Saving package...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    closeModal();
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => { showToast('Error saving package', 'error'); });
        }
        
        function deletePackage(id) {
            if (!confirm('Are you sure you want to delete this package?')) return;
            const formData = new FormData();
            formData.append('ajax_action', 'delete_package');
            formData.append('id', id);
            showToast('Deleting package...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => { showToast('Error deleting package', 'error'); });
        }
        
        function assignPackage(clientId, packageId) {
            if (!confirm('Assign this package to the selected client?')) return;
            const formData = new FormData();
            formData.append('ajax_action', 'assign_package');
            formData.append('client_id', clientId);
            formData.append('package_id', packageId);
            showToast('Assigning package...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => { showToast('Error assigning package', 'error'); });
        }
        
        function unassignPackage(clientId, packageId) {
            if (!confirm('Remove this package from the selected client?')) return;
            const formData = new FormData();
            formData.append('ajax_action', 'unassign_package');
            formData.append('client_id', clientId);
            formData.append('package_id', packageId);
            showToast('Unassigning package...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => { showToast('Error unassigning package', 'error'); });
        }
        
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