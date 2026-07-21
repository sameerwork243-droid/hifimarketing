<?php
// admin-finance-portal.php - Admin & Finance Portal
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if user has admin or finance role
if (!isset($_SESSION['portal_role']) || ($_SESSION['portal_role'] !== 'admin' && $_SESSION['portal_role'] !== 'finance')) {
    header('Location: client-portal.php');
    exit();
}

$userData = $_SESSION['user'] ?? [];
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['portal_role'] ?? 'admin';

// ===== ACTIVE TAB =====
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'admin';

// ===== GET ALL CLIENTS =====
$clients_sql = "SELECT c.*, u.username, u.email FROM clients c JOIN users u ON c.user_id = u.id ORDER BY c.name ASC";
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

// ===== GET ALL INVOICES =====
$invoices_sql = "SELECT i.*, c.name as client_name FROM invoices i JOIN clients c ON i.client_id = c.id ORDER BY i.created_at DESC";
$invoices_result = mysqli_query($conn, $invoices_sql);
$invoices = [];
while ($row = mysqli_fetch_assoc($invoices_result)) {
    $invoices[] = $row;
}

// ===== GET ALL TICKETS =====
$tickets_sql = "SELECT t.*, c.name as client_name FROM support_tickets t JOIN clients c ON t.client_id = c.id ORDER BY t.created_at DESC";
$tickets_result = mysqli_query($conn, $tickets_sql);
$tickets = [];
while ($row = mysqli_fetch_assoc($tickets_result)) {
    $tickets[] = $row;
}

// ===== GET ALL DELIVERABLES =====
$deliverables_sql = "SELECT d.*, c.name as client_name FROM deliverables d JOIN clients c ON d.client_id = c.id ORDER BY d.due_date ASC";
$deliverables_result = mysqli_query($conn, $deliverables_sql);
$deliverables = [];
while ($row = mysqli_fetch_assoc($deliverables_result)) {
    $deliverables[] = $row;
}

// ===== GET ALL CUSTOM TASKS =====
$custom_tasks_sql = "SELECT ct.*, c.name as client_name FROM custom_tasks ct JOIN clients c ON ct.client_id = c.id ORDER BY ct.created_at DESC";
$custom_tasks_result = mysqli_query($conn, $custom_tasks_sql);
$custom_tasks = [];
while ($row = mysqli_fetch_assoc($custom_tasks_result)) {
    $custom_tasks[] = $row;
}

// ===== GET ALL VERBAL TASKS =====
$verbal_tasks_sql = "SELECT vt.*, c.name as client_name FROM verbal_tasks vt JOIN clients c ON vt.client_id = c.id ORDER BY vt.created_at DESC";
$verbal_tasks_result = mysqli_query($conn, $verbal_tasks_sql);
$verbal_tasks = [];
while ($row = mysqli_fetch_assoc($verbal_tasks_result)) {
    $verbal_tasks[] = $row;
}

// ===== CALCULATE TOTALS =====
$total_invoices = count($invoices);
$total_paid = 0;
$total_due = 0;
$total_partial = 0;
foreach ($invoices as $inv) {
    if ($inv['status'] === 'Paid') $total_paid += $inv['amount'];
    elseif ($inv['status'] === 'Partially Paid') $total_partial += $inv['amount'];
    else $total_due += $inv['amount'];
}
$total_revenue = $total_paid + $total_partial;

// ===== ACTIVE PACKAGE FOR BANNER =====
$active_package = $packages[1] ?? $packages[0] ?? null;
$package_name = $active_package['name'] ?? 'Enterprise Plan';

$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
$current_page = 'admin-finance-portal.php';

// ============================================================ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin & Finance Portal | HIFI Marketing</title>
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
            --warning: #f59e0b;
            --admin-color: #4a5cf5;
            --finance-color: #10b981;
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
            gap: 4px;
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

        /* ===== TAB NAVIGATION ===== */
        .tab-nav {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 8px 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            box-shadow: var(--shadow);
        }
        .tab-nav .tab-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
            background: transparent;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-nav .tab-btn:hover {
            background: #f0f3ff;
            color: var(--primary);
        }
        .tab-nav .tab-btn.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(74,92,245,0.25);
        }
        .tab-nav .tab-btn.admin-tab.active {
            background: var(--admin-color);
        }
        .tab-nav .tab-btn.finance-tab.active {
            background: var(--finance-color);
        }
        .tab-nav .tab-btn i {
            font-size: 14px;
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
        .mobile-nav.active { right: 0; }

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

        /* ===== CONTENT ===== */
        .content {
            flex: 1;
            min-width: 0;
        }

        /* ===== BANNER ===== */
        .banner {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
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

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }

        .status-badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.open { background: #fee2e2; color: #dc3545; }
        .status-badge.resolved { background: #e8f5e9; color: #2e7d32; }
        .status-badge.paid { background: #d1fae5; color: #065f46; }
        .status-badge.due { background: #fee2e2; color: #dc2626; }
        .status-badge.partially-paid { background: #fef3c7; color: #92400e; }
        .status-badge.active { background: #e8edfe; color: var(--primary); }

        .stat-box {
            background: #f8fafc;
            padding: 14px 16px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid var(--border);
        }
        .stat-box .number {
            font-size: 24px;
            font-weight: 900;
            color: var(--text-primary);
        }
        .stat-box .label {
            font-size: 11px;
            color: var(--text-muted);
        }

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

        /* ===== TABLE ===== */
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table th {
            background: #f8fafc;
            text-align: left;
            padding: 10px 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border);
        }
        table td {
            padding: 10px 12px;
            color: var(--text-secondary);
            border-bottom: 1px solid #f0f2f5;
        }
        table tr:hover td { background: #f8fafc; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .grid-2 { grid-template-columns: 1fr; }
            .grid-3 { grid-template-columns: 1fr 1fr; }
            .grid-4 { grid-template-columns: 1fr 1fr; }
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
            .grid-4 { grid-template-columns: 1fr; }
            .tab-nav { flex-direction: column; }
            .tab-nav .tab-btn { justify-content: center; }
            .header-actions .action-btn { padding: 4px 8px; font-size: 13px; }
            .modal { padding: 20px; }
            .header-inner { padding: 0 12px; }
            .logo { font-size: 17px; }
            .logo .brand-icon { width: 30px; height: 30px; font-size: 13px; }
            .banner .banner-actions .btn-white { width: 100%; text-align: center; }
            table { font-size: 11px; }
            table th, table td { padding: 6px 8px; }
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
                <div class="brand-icon">A</div>
                HIFI <span>Marketing</span>
            </div>

            <!-- Desktop Navigation -->
            <nav class="desktop-nav">
                <a href="admin-finance-portal.php" class="nav-link active"><i class="fas fa-shield-alt"></i> Admin</a>
                <a href="pm-portal.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> PM</a>
                <a href="client-portal.php" class="nav-link"><i class="fas fa-user"></i> Client</a>
            </nav>

            <!-- Header Actions -->
            <div class="header-actions">
                <div class="user-badge">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <span class="name"><?php echo $userData['name'] ?? 'Admin'; ?></span>
                    <span class="online"></span>
                </div>
                <a href="logout.php" style="color:#dc3545;font-size:16px;padding:4px 8px;border-radius:8px;transition:var(--transition);" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-sign-out-alt"></i>
                </a>

                <!-- Hamburger Menu Toggle -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
                    <span></span><span></span><span></span>
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
            <button class="mobile-close" onclick="closeMobileMenu()"><i class="fas fa-times"></i></button>
        </div>

        <div class="mobile-user">
            <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
            <div class="user-info">
                <div class="name"><?php echo $userData['name'] ?? 'Admin'; ?></div>
                <div class="role"><i class="fas fa-user-tie"></i> <?php echo ucfirst($user_role); ?></div>
            </div>
        </div>

        <div class="mobile-links">
            <a href="?tab=admin" class="<?php echo $activeTab === 'admin' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-shield-alt"></i> Admin Dashboard
            </a>
            <a href="?tab=finance" class="<?php echo $activeTab === 'finance' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
                <i class="fas fa-coins"></i> Finance Dashboard
            </a>
            <a href="pm-portal.php" onclick="closeMobileMenu()"><i class="fas fa-tachometer-alt"></i> PM Portal</a>
            <a href="client-portal.php" onclick="closeMobileMenu()"><i class="fas fa-user"></i> Client Portal</a>
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
                <div class="brand-icon">A</div>
                <div class="sidebar-brand-text">
                    <h1>SMMA Scale</h1>
                    <span>Admin Portal</span>
                </div>
            </div>

            <div class="sidebar-toggle">
                <button onclick="toggleSidebar()">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <div class="sidebar-badge">
                <span>Access</span>
                <span class="role"><?php echo ucfirst($user_role); ?></span>
            </div>

            <nav class="sidebar-nav">
                <!-- Admin Section -->
                <a href="?tab=admin" class="sidebar-link <?php echo $activeTab === 'admin' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span class="sidebar-text">Admin Dashboard</span>
                </a>
                
                <!-- Finance Section -->
                <a href="?tab=finance" class="sidebar-link <?php echo $activeTab === 'finance' ? 'active' : ''; ?>">
                    <i class="fas fa-coins"></i>
                    <span class="sidebar-text">Finance Dashboard</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <div class="sidebar-user-text">
                        <div class="name"><?php echo $userData['name'] ?? 'Admin'; ?></div>
                        <div class="role-label"><?php echo ucfirst($user_role); ?></div>
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

            <!-- ===== TAB NAVIGATION ===== -->
            <div class="tab-nav">
                <button class="tab-btn admin-tab <?php echo $activeTab === 'admin' ? 'active' : ''; ?>" onclick="window.location.href='?tab=admin'">
                    <i class="fas fa-shield-alt"></i> Admin Dashboard
                </button>
                <button class="tab-btn finance-tab <?php echo $activeTab === 'finance' ? 'active' : ''; ?>" onclick="window.location.href='?tab=finance'">
                    <i class="fas fa-coins"></i> Finance Dashboard
                </button>
            </div>

            <!-- ===== BANNER ===== -->
            <div class="banner">
                <div>
                    <h2><i class="fas fa-shield-alt"></i> <?php echo ucfirst($user_role); ?> Workspace</h2>
                    <p>Manage operations, finances, and client relationships</p>
                </div>
                <div class="banner-actions">
                    <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:8px;"></i> <?php echo count($clients); ?> Clients</span>
                    <span class="badge"><i class="fas fa-file-invoice"></i> <?php echo $total_invoices; ?> Invoices</span>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- ===== ADMIN TAB ===== -->
            <?php if ($activeTab === 'admin'): ?>

            <!-- Admin Stats -->
            <div class="grid-4">
                <div class="stat-box">
                    <div class="number"><?php echo count($clients); ?></div>
                    <div class="label">Total Clients</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo count($packages); ?></div>
                    <div class="label">Active Packages</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo count($tickets); ?></div>
                    <div class="label">Support Tickets</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo count($deliverables); ?></div>
                    <div class="label">Deliverables</div>
                </div>
            </div>

            <!-- ===== Configure Retainers ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-handshake" style="color:var(--primary);"></i> Configure Retainers</h3>
                    <span class="sub">Manage client retainers and recurring packages</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Package</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($clients)): ?>
                                <?php foreach ($clients as $client): 
                                    $client_pkg = null;
                                    foreach ($packages as $pkg) {
                                        if ($pkg['id'] == $client['active_package_id']) {
                                            $client_pkg = $pkg;
                                            break;
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                                    <td><?php echo $client_pkg ? htmlspecialchars($client_pkg['name']) : 'No Package'; ?></td>
                                    <td><?php echo $client_pkg ? number_format($client_pkg['price']) . ' PKR' : 'N/A'; ?></td>
                                    <td><span class="status-badge active">Active</span></td>
                                    <td style="text-align:right;">
                                        <button onclick="editRetainer(<?php echo $client['id']; ?>)" style="background:var(--primary);color:#fff;border:none;padding:4px 14px;border-radius:20px;font-size:10px;font-weight:600;cursor:pointer;">Edit</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">No clients found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===== All Project Scopes ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-project-diagram" style="color:var(--primary);"></i> All Project Scopes</h3>
                    <span class="sub">View all client projects and scopes</span>
                </div>
                <div class="grid-2">
                    <?php if (!empty($custom_tasks)): ?>
                        <?php foreach (array_slice($custom_tasks, 0, 4) as $task): ?>
                        <div style="padding:12px 14px;border:1px solid var(--border);border-radius:10px;">
                            <div style="font-weight:700;font-size:13px;color:var(--text-primary);"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div style="font-size:11px;color:var(--text-muted);">Client: <?php echo htmlspecialchars($task['client_name'] ?? 'N/A'); ?></div>
                            <div style="font-size:11px;color:var(--text-muted);">Category: <?php echo htmlspecialchars($task['category']); ?></div>
                            <div style="margin-top:4px;font-size:10px;color:var(--text-muted);">Status: <?php echo $task['status']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="grid-column:1/-1;">
                            <i class="fas fa-inbox"></i>
                            <p>No project scopes available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== Global Deliverables ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-check-square" style="color:var(--primary);"></i> Global Deliverables</h3>
                    <span class="sub">Track all deliverables across clients</span>
                </div>
                <div class="grid-3">
                    <?php 
                    $statuses = ['To Do', 'In Progress', 'Done'];
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
                            <div style="font-weight:700;font-size:12px;color:var(--text-primary);"><?php echo htmlspecialchars($deliv['name']); ?></div>
                            <div style="font-size:10px;color:var(--text-muted);">Client: <?php echo htmlspecialchars($deliv['client_name']); ?></div>
                            <div style="font-size:10px;color:var(--text-muted);">Due: <?php echo date('Y-m-d', strtotime($deliv['due_date'])); ?></div>
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

            <!-- ===== Resolve Tickets ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-headset" style="color:var(--primary);"></i> Resolve Tickets</h3>
                    <button onclick="window.location.href='tickets.php'" style="background:var(--primary);color:#fff;border:none;padding:4px 16px;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;">View All</button>
                </div>
                <?php if (!empty($tickets)): ?>
                    <?php foreach (array_slice($tickets, 0, 3) as $ticket): ?>
                    <div style="padding:12px 14px;border:1px solid <?php echo $ticket['status'] === 'Open' ? '#fee2e2' : '#e8f5e9'; ?>;border-radius:10px;margin-bottom:8px;">
                        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;">
                            <span style="font-weight:700;font-size:13px;"><?php echo htmlspecialchars($ticket['title']); ?></span>
                            <span class="status-badge <?php echo $ticket['status'] === 'Open' ? 'open' : 'resolved'; ?>"><?php echo $ticket['status']; ?></span>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);">Client: <?php echo htmlspecialchars($ticket['client_name']); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i><p>No tickets</p></div>
                <?php endif; ?>
            </div>

            <!-- ===== Sync Metrics Live ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-sliders-h" style="color:var(--primary);"></i> Sync Metrics Live</h3>
                    <span class="sub">Real-time client progress metrics</span>
                </div>
                <div class="grid-2">
                    <?php foreach (array_slice($clients, 0, 4) as $client): ?>
                    <div style="padding:12px 14px;border:1px solid var(--border);border-radius:10px;">
                        <div style="font-weight:700;font-size:13px;"><?php echo htmlspecialchars($client['name']); ?></div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:6px;font-size:11px;color:var(--text-muted);">
                            <span>Posts: <?php echo $client['posts_completed'] ?? 0; ?></span>
                            <span>Stories: <?php echo $client['stories_completed'] ?? 0; ?></span>
                            <span>Followers: +<?php echo $client['followers_gained'] ?? 0; ?></span>
                            <span>Likes: <?php echo $client['total_likes'] ?? 0; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- ===== FINANCE TAB ===== -->
            <?php elseif ($activeTab === 'finance'): ?>

            <!-- Finance Stats -->
            <div class="grid-4">
                <div class="stat-box">
                    <div class="number" style="color:#10b981;"><?php echo number_format($total_revenue); ?> PKR</div>
                    <div class="label">Total Revenue</div>
                </div>
                <div class="stat-box">
                    <div class="number" style="color:#dc2626;"><?php echo number_format($total_due); ?> PKR</div>
                    <div class="label">Total Due</div>
                </div>
                <div class="stat-box">
                    <div class="number" style="color:#f59e0b;"><?php echo number_format($total_partial); ?> PKR</div>
                    <div class="label">Partially Paid</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo count($verbal_tasks); ?></div>
                    <div class="label">Verbal Tasks</div>
                </div>
            </div>

            <!-- ===== Ledger Summary ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-book" style="color:#10b981;"></i> Ledger Summary</h3>
                    <span class="sub">Financial overview of all clients</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Total Invoices</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($clients)): ?>
                                <?php foreach ($clients as $client): 
                                    $client_invoices = array_filter($invoices, function($inv) use ($client) {
                                        return $inv['client_id'] == $client['id'];
                                    });
                                    $c_total = 0;
                                    $c_paid = 0;
                                    $c_due = 0;
                                    foreach ($client_invoices as $inv) {
                                        $c_total += $inv['amount'];
                                        if ($inv['status'] === 'Paid') $c_paid += $inv['amount'];
                                        else $c_due += $inv['amount'];
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                                    <td><?php echo count($client_invoices); ?></td>
                                    <td style="color:#10b981;"><?php echo number_format($c_paid); ?> PKR</td>
                                    <td style="color:#dc2626;"><?php echo number_format($c_due); ?> PKR</td>
                                    <td style="text-align:right;">
                                        <button onclick="viewClientLedger(<?php echo $client['id']; ?>)" style="background:#f0f3ff;color:var(--primary);border:none;padding:4px 14px;border-radius:20px;font-size:10px;font-weight:600;cursor:pointer;">View</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">No clients found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===== Invoices & Billing ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-invoice" style="color:#10b981;"></i> Invoices &amp; Billing</h3>
                    <button onclick="window.location.href='pm-billing.php'" style="background:#10b981;color:#fff;border:none;padding:4px 16px;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;">Manage Invoices</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th style="text-align:right;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($invoices)): ?>
                                <?php foreach (array_slice($invoices, 0, 5) as $inv): ?>
                                <tr>
                                    <td style="font-weight:700;"><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($inv['client_name']); ?></td>
                                    <td><?php echo number_format($inv['amount']); ?> PKR</td>
                                    <td><span class="status-badge <?php echo strtolower($inv['status']); ?>"><?php echo $inv['status']; ?></span></td>
                                    <td style="text-align:right;font-size:11px;color:var(--text-muted);"><?php echo date('M d, Y', strtotime($inv['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">No invoices found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===== PM Verbal Project Billing ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-phone" style="color:#10b981;"></i> PM Verbal Project Billing</h3>
                    <span class="sub">Track verbal project billing</span>
                </div>
                <?php if (!empty($verbal_tasks)): ?>
                    <?php foreach (array_slice($verbal_tasks, 0, 4) as $task): ?>
                    <div style="padding:12px 14px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <div>
                            <div style="font-weight:700;font-size:13px;"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div style="font-size:11px;color:var(--text-muted);">Client: <?php echo htmlspecialchars($task['client_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <span class="status-badge <?php echo isset($task['invoice_generated']) && $task['invoice_generated'] ? 'paid' : 'due'; ?>">
                                <?php echo isset($task['invoice_generated']) && $task['invoice_generated'] ? 'Invoiced' : 'Pending'; ?>
                            </span>
                            <button onclick="generateVerbalInvoice(<?php echo $task['id']; ?>)" style="background:#10b981;color:#fff;border:none;padding:4px 14px;border-radius:20px;font-size:10px;font-weight:600;cursor:pointer;">
                                <i class="fas fa-file-invoice"></i> Generate
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-phone"></i><p>No verbal tasks found</p></div>
                <?php endif; ?>
            </div>

            <!-- ===== Subscription Packaging ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-boxes" style="color:#10b981;"></i> Subscription Packaging</h3>
                    <span class="sub">Manage client subscription packages</span>
                </div>
                <div class="grid-3">
                    <?php if (!empty($packages)): ?>
                        <?php foreach ($packages as $pkg): ?>
                        <div style="padding:14px;border:1px solid var(--border);border-radius:10px;text-align:center;">
                            <div style="font-weight:800;font-size:15px;color:var(--text-primary);"><?php echo htmlspecialchars($pkg['name']); ?></div>
                            <div style="font-size:18px;font-weight:900;color:var(--primary);"><?php echo number_format($pkg['price']); ?> PKR</div>
                            <div style="font-size:11px;color:var(--text-muted);"><?php echo $pkg['posts_limit'] ?? 0; ?> posts / month</div>
                            <div style="font-size:11px;color:var(--text-muted);"><?php echo $pkg['stories_limit'] ?? 0; ?> stories / month</div>
                            <button onclick="editPackage(<?php echo $pkg['id']; ?>)" style="margin-top:8px;padding:4px 16px;background:var(--primary);color:#fff;border:none;border-radius:20px;font-size:10px;font-weight:600;cursor:pointer;">Edit Package</button>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="grid-column:1/-1;"><i class="fas fa-box"></i><p>No packages available</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php endif; ?>

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

        // ===== ADMIN FUNCTIONS =====
        function editRetainer(clientId) {
            showToast('Retainer editor coming soon for client #' + clientId, 'warning');
        }

        function editPackage(packageId) {
            showToast('Package editor coming soon for package #' + packageId, 'warning');
        }

        // ===== FINANCE FUNCTIONS =====
        function viewClientLedger(clientId) {
            window.location.href = 'pm-billing.php?client_id=' + clientId;
        }

        function generateVerbalInvoice(taskId) {
            showToast('Generating invoice for verbal task #' + taskId, 'warning');
            setTimeout(() => {
                window.location.href = 'pm-billing.php?verbal_task_id=' + taskId;
            }, 1500);
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