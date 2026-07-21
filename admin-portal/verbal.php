<?php
// verbal.php - Client Verbal Requests
session_start();
error_reporting(0);
ini_set('display_errors', 0);

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

// ===== GET ALL VERBAL TASKS =====
$verbal_tasks_sql = "SELECT * FROM verbal_tasks ORDER BY created_at DESC";
$verbal_tasks_result = mysqli_query($conn, $verbal_tasks_sql);
$verbal_tasks = [];
while ($row = mysqli_fetch_assoc($verbal_tasks_result)) {
    $verbal_tasks[] = $row;
}

// ===== GET ALL CUSTOM TASKS (for PM Verbal Add) =====
$custom_tasks_sql = "SELECT ct.*, c.name as client_name FROM custom_tasks ct JOIN clients c ON ct.client_id = c.id ORDER BY ct.created_at DESC";
$custom_tasks_result = mysqli_query($conn, $custom_tasks_sql);
$custom_tasks = [];
while ($row = mysqli_fetch_assoc($custom_tasks_result)) {
    $custom_tasks[] = $row;
}

// ===== ACTIVE PACKAGE FOR BANNER =====
$active_package = $packages[1] ?? $packages[0] ?? null;
$package_name = $active_package['name'] ?? 'Professional Growth';

$current_page = 'verbal.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PM Portal | HIFI Marketing - Verbal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" href="../images/fav-icon.png" type="image/png" />
    <style>
        /* ===== COPY ALL CSS FROM operations.php HERE ===== */
        :root { --primary: #4a5cf5; --primary-dark: #3a4be0; --bg: #f0f2f5; --card-bg: #ffffff; --text-primary: #1a1c26; --text-secondary: #3d4452; --text-muted: #8a94a0; --border: #e9edf2; --radius: 16px; --shadow: 0 2px 12px rgba(0,0,0,0.04); --shadow-hover: 0 8px 40px rgba(0,0,0,0.08); --transition: 0.3s ease; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-primary); line-height: 1.6; }
        a { text-decoration: none; color: inherit; }
        header { background: rgba(255,255,255,0.98); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); padding: 12px 0; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
        .header-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .logo { font-size: 20px; font-weight: 900; color: var(--text-primary); flex-shrink: 0; display: flex; align-items: center; gap: 8px; }
        .logo span { color: var(--primary); }
        .logo .brand-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; font-size: 16px; }
        .desktop-nav { display: flex; align-items: center; gap: 4px; }
        .desktop-nav .nav-link { font-weight: 600; font-size: 13px; color: var(--text-secondary); padding: 6px 14px; border-radius: 8px; transition: var(--transition); }
        .desktop-nav .nav-link:hover { color: var(--primary); background: #f0f3ff; }
        .desktop-nav .nav-link.active { color: var(--primary); background: #f0f3ff; }
        .mobile-menu-toggle { display: none; background: transparent; border: none; cursor: pointer; padding: 4px; flex-direction: column; gap: 5px; z-index: 110; }
        .mobile-menu-toggle span { display: block; width: 26px; height: 3px; background: var(--text-primary); border-radius: 3px; transition: var(--transition); }
        .mobile-menu-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 6px); }
        .mobile-menu-toggle.active span:nth-child(2) { opacity: 0; }
        .mobile-menu-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -6px); }
        .mobile-nav-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.3); z-index: 150; }
        .mobile-nav-overlay.active { display: block; }
        .mobile-nav { position: fixed; top: 0; right: -320px; width: 300px; height: 100vh; background: var(--card-bg); box-shadow: -4px 0 30px rgba(0,0,0,0.1); z-index: 160; padding: 20px 24px; transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow-y: auto; display: flex; flex-direction: column; }
        .mobile-nav.active { right: 0; }
        .mobile-nav .mobile-header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px; border-bottom: 1px solid var(--border); margin-bottom: 16px; }
        .mobile-nav .mobile-header .logo-small { font-size: 18px; font-weight: 900; color: var(--text-primary); }
        .mobile-nav .mobile-header .logo-small span { color: var(--primary); }
        .mobile-nav .mobile-close { background: transparent; border: none; font-size: 22px; color: var(--text-muted); cursor: pointer; }
        .mobile-nav .mobile-user { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); margin-bottom: 16px; }
        .mobile-nav .mobile-user img { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; }
        .mobile-nav .mobile-user .user-info .name { font-weight: 700; font-size: 15px; color: var(--text-primary); }
        .mobile-nav .mobile-user .user-info .role { font-size: 12px; color: var(--text-muted); }
        .mobile-nav .mobile-user .user-info .role i { color: var(--primary); }
        .mobile-nav .mobile-links { display: flex; flex-direction: column; gap: 4px; flex: 1; }
        .mobile-nav .mobile-links a { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 10px; color: var(--text-secondary); font-weight: 600; font-size: 14px; transition: var(--transition); }
        .mobile-nav .mobile-links a:hover { background: #f0f3ff; color: var(--primary); }
        .mobile-nav .mobile-links a.active { background: #f0f3ff; color: var(--primary); }
        .mobile-nav .mobile-links a i { width: 20px; text-align: center; font-size: 16px; }
        .mobile-nav .mobile-footer { padding-top: 16px; border-top: 1px solid var(--border); margin-top: auto; }
        .mobile-nav .mobile-footer .logout-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 10px; color: #dc3545; font-weight: 600; font-size: 14px; transition: var(--transition); }
        .mobile-nav .mobile-footer .logout-btn:hover { background: #fee2e2; }
        .header-actions { display: flex; align-items: center; gap: 8px; }
        .header-actions .action-btn { background: transparent; border: none; padding: 6px 10px; border-radius: 8px; color: var(--text-secondary); cursor: pointer; transition: var(--transition); font-size: 14px; }
        .header-actions .action-btn:hover { background: #f0f3ff; color: var(--primary); }
        .header-actions .user-badge { display: flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13px; color: var(--text-primary); padding: 4px 10px 4px 4px; border-radius: 40px; background: #f0f3ff; }
        .header-actions .user-badge img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
        .header-actions .user-badge .online { display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; margin-left: 2px; border: 2px solid #fff; }
        .main-layout { display: flex; max-width: 1400px; margin: 0 auto; padding: 20px; gap: 20px; min-height: calc(100vh - 72px); }
        .sidebar { width: 240px; flex-shrink: 0; background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border); padding: 16px 12px; box-shadow: var(--shadow); height: fit-content; position: sticky; top: 88px; transition: var(--transition); }
        .sidebar.collapsed { width: 60px; padding: 16px 8px; }
        .sidebar.collapsed .sidebar-text { display: none; }
        .sidebar.collapsed .sidebar-link { justify-content: center; padding: 10px; }
        .sidebar.collapsed .sidebar-link i { font-size: 18px; margin: 0; }
        .sidebar.collapsed .sidebar-brand-text { display: none; }
        .sidebar.collapsed .sidebar-user-text { display: none; }
        .sidebar.collapsed .sidebar-badge { display: none; }
        .sidebar.collapsed .sidebar-toggle i { transform: rotate(180deg); }
        .sidebar-brand { display: flex; align-items: center; gap: 10px; padding: 8px 12px; margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        .sidebar-brand .brand-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; font-size: 16px; flex-shrink: 0; }
        .sidebar-brand h1 { font-size: 15px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
        .sidebar-brand span { font-size: 9px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .sidebar-toggle { display: flex; justify-content: flex-end; padding: 2px 12px; margin-bottom: 6px; }
        .sidebar-toggle button { background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: var(--transition); }
        .sidebar-toggle button:hover { background: #f0f3ff; color: var(--primary); }
        .sidebar-badge { display: flex; align-items: center; justify-content: space-between; padding: 6px 12px; background: #f0f3ff; border-radius: 8px; margin: 0 4px 12px; font-size: 10px; font-weight: 600; color: var(--text-secondary); }
        .sidebar-badge .role { background: var(--primary); color: #fff; padding: 1px 12px; border-radius: 20px; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .sidebar-nav { display: flex; flex-direction: column; gap: 2px; }
        .sidebar-link { display: flex; align-items: center; gap: 12px; padding: 9px 12px; border-radius: 8px; color: var(--text-secondary); font-weight: 600; font-size: 13px; transition: var(--transition); }
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
        .banner { background: linear-gradient(135deg, #4a5cf5 0%, #6c7aff 100%); border-radius: var(--radius); padding: 20px 24px; color: #fff; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .banner h2 { font-size: 18px; font-weight: 800; }
        .banner p { opacity: 0.85; font-size: 13px; margin-top: 2px; }
        .banner .badge { background: rgba(255,255,255,0.2); padding: 4px 16px; border-radius: 40px; font-weight: 600; font-size: 11px; }
        .banner .banner-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .banner .banner-actions .btn-white { background: #fff; color: var(--primary); padding: 6px 16px; border-radius: 40px; font-weight: 700; font-size: 11px; border: none; cursor: pointer; transition: var(--transition); }
        .banner .banner-actions .btn-white:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border); padding: 18px 20px; box-shadow: var(--shadow); margin-bottom: 18px; }
        .card .card-header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px solid var(--border); margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
        .card .card-header h3 { font-size: 14px; font-weight: 700; color: var(--text-primary); }
        .card .card-header .sub { font-size: 11px; color: var(--text-muted); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .task-item { padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; transition: var(--transition); margin-bottom: 8px; }
        .task-item:hover { border-color: var(--primary); box-shadow: var(--shadow-hover); }
        .task-item .task-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; flex-wrap: wrap; }
        .task-item .task-title { font-weight: 700; font-size: 13px; color: var(--text-primary); }
        .task-item .task-desc { font-size: 12px; color: var(--text-secondary); margin-top: 3px; }
        .task-item .task-meta { display: flex; gap: 10px; margin-top: 6px; font-size: 11px; color: var(--text-muted); flex-wrap: wrap; }
        .task-item .task-meta i { margin-right: 3px; }
        .status-badge { padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; display: inline-block; }
        .status-badge.open { background: #fee2e2; color: #dc3545; }
        .status-badge.pending { background: #fff3e0; color: #e65100; }
        .status-badge.in-progress { background: #e8edfe; color: var(--primary); }
        .status-badge.done { background: #e8f5e9; color: #2e7d32; }
        .status-badge.paid { background: #e8f5e9; color: #2e7d32; }
        .status-badge.unpaid { background: #fff3e0; color: #e65100; }
        .status-badge.resolved { background: #e8f5e9; color: #2e7d32; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table th { background: #f8fafc; text-align: left; padding: 10px 14px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 1px solid var(--border); }
        table td { padding: 10px 14px; color: var(--text-secondary); border-bottom: 1px solid #f0f2f5; }
        table tr:hover td { background: #f8fafc; }
        table tr:last-child td { border-bottom: none; }
        .modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 16px; }
        .modal-overlay.show { display: flex; }
        .modal { background: var(--card-bg); border-radius: var(--radius); max-width: 460px; width: 100%; padding: 24px 28px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); position: relative; max-height: 90vh; overflow-y: auto; }
        .modal .modal-close { position: absolute; top: 14px; right: 16px; background: transparent; border: none; font-size: 18px; color: var(--text-muted); cursor: pointer; transition: var(--transition); }
        .modal .modal-close:hover { color: var(--text-primary); }
        .modal h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin-bottom: 3px; }
        .modal .modal-sub { font-size: 12px; color: var(--text-muted); margin-bottom: 16px; }
        .modal label { display: block; font-weight: 600; font-size: 12px; color: var(--text-secondary); margin-bottom: 3px; }
        .modal input, .modal select, .modal textarea { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; background: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; transition: var(--transition); margin-bottom: 12px; }
        .modal input:focus, .modal select:focus, .modal textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(74,92,245,0.1); }
        .modal .btn-submit { width: 100%; padding: 10px; background: var(--primary); color: #fff; border: none; border-radius: 40px; font-weight: 700; font-size: 13px; cursor: pointer; transition: var(--transition); }
        .modal .btn-submit:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .toast-container { position: fixed; top: 80px; right: 20px; z-index: 300; display: flex; flex-direction: column; gap: 8px; }
        .toast { background: var(--text-primary); color: #fff; padding: 12px 18px; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; min-width: 260px; animation: slideIn 0.3s ease; }
        .toast.success i { color: #10b981; }
        .toast.error i { color: #ef4444; }
        .toast.warning i { color: #f59e0b; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .security-badge { position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.7); color: #4ade80; padding: 3px 12px; border-radius: 20px; font-size: 8px; font-weight: 700; z-index: 999; backdrop-filter: blur(10px); border: 1px solid rgba(74,222,128,0.2); pointer-events: none; }
        input[type="range"] { -webkit-appearance: none; appearance: none; width: 100%; height: 6px; border-radius: 3px; outline: none; transition: opacity 0.2s; background: #e9edf2; }
        input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 16px; height: 16px; border-radius: 50%; cursor: pointer; background: var(--primary); border: 2px solid var(--primary-dark); }
        input[type="range"]::-moz-range-thumb { width: 16px; height: 16px; border-radius: 50%; cursor: pointer; background: var(--primary); border: 2px solid var(--primary-dark); }
        select.status-select { border: none; background: transparent; font-weight: 700; font-size: 10px; padding: 2px 8px; border-radius: 9999px; cursor: pointer; }
        select.status-select option { padding: 4px 8px; }
        .attachment-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f8fafc; border-radius: 10px; margin-bottom: 8px; border-left: 3px solid var(--primary); transition: var(--transition); }
        .attachment-item:hover { background: #f0f3ff; }
        .attachment-item .file-info { flex: 1; }
        .attachment-item .file-name { font-weight: 600; font-size: 13px; color: var(--text-primary); }
        .attachment-item .file-meta { font-size: 11px; color: var(--text-muted); }
        .attachment-item .file-actions { display: flex; gap: 6px; }
        .attachment-item .file-actions button { background: transparent; border: none; padding: 4px 8px; border-radius: 6px; cursor: pointer; transition: var(--transition); font-size: 14px; }
        .attachment-item .file-actions .btn-download { color: var(--primary); }
        .attachment-item .file-actions .btn-download:hover { background: #f0f3ff; }
        .attachment-item .file-actions .btn-delete { color: #dc3545; }
        .attachment-item .file-actions .btn-delete:hover { background: #fee2e2; }
        .empty-state { text-align: center; padding: 30px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 36px; color: #d0d7e0; margin-bottom: 8px; }
        .empty-state h4 { font-size: 15px; color: var(--text-primary); margin-bottom: 3px; }
        @media (max-width: 1024px) { .grid-2 { grid-template-columns: 1fr; } .grid-3 { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 992px) { .desktop-nav { display: none; } .mobile-menu-toggle { display: flex; } .header-actions .user-badge .name { display: none; } }
        @media (max-width: 768px) { .sidebar { display: none; } .main-layout { padding: 12px; flex-direction: column; } .banner { padding: 16px 18px; flex-direction: column; text-align: center; } .banner h2 { font-size: 16px; } .grid-3 { grid-template-columns: 1fr; } .header-actions .action-btn { padding: 4px 8px; font-size: 13px; } .modal { padding: 20px; } .header-inner { padding: 0 12px; } .logo { font-size: 17px; } .logo .brand-icon { width: 30px; height: 30px; font-size: 13px; } .banner .banner-actions .btn-white { width: 100%; text-align: center; } .table-wrap { overflow-x: auto; } table { font-size: 12px; } table th, table td { padding: 8px 10px; } }
        @media (max-width: 480px) { .header-actions .action-btn { font-size: 12px; padding: 4px 6px; } .header-actions .user-badge { padding: 2px 8px 2px 2px; font-size: 11px; } .header-actions .user-badge img { width: 24px; height: 24px; } .mobile-nav { width: 280px; } }
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
                <div class="brand-icon">A</div>
                HIFI <span>Marketing</span>
            </div>
            <nav class="desktop-nav">
                <a href="operations.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Operations</a>
                <a href="deliverables.php" class="nav-link"><i class="fas fa-check-square"></i> Deliverables</a>
                <a href="tickets.php" class="nav-link"><i class="fas fa-headset"></i> Tickets</a>
                <a href="verbal.php" class="nav-link active"><i class="fas fa-phone"></i> Verbal</a>
                <a href="progress-sync.php" class="nav-link"><i class="fas fa-sliders-h"></i> Sync</a>
            </nav>
            <div class="header-actions">
                <button class="action-btn" onclick="openModal('modal-verbal')" title="Add Verbal">
                    <i class="fas fa-plus-circle"></i>
                </button>
                <div class="user-badge">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <span class="name"><?php echo $userData['name'] ?? 'PM'; ?></span>
                    <span class="online"></span>
                </div>
                <a href="logout.php" style="color:#dc3545;font-size:16px;padding:4px 8px;border-radius:8px;transition:var(--transition);" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
                <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- ===== MOBILE NAV ===== -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay" onclick="closeMobileMenu()"></div>
    <nav class="mobile-nav" id="mobileNav">
        <div class="mobile-header">
            <div class="logo-small">HIFI <span>Marketing</span></div>
            <button class="mobile-close" onclick="closeMobileMenu()"><i class="fas fa-times"></i></button>
        </div>
        <div class="mobile-user">
            <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
            <div class="user-info">
                <div class="name"><?php echo $userData['name'] ?? 'PM'; ?></div>
                <div class="role"><i class="fas fa-user-tie"></i> Senior Account Director</div>
            </div>
        </div>
        <div class="mobile-links">
            <a href="operations.php" onclick="closeMobileMenu()"><i class="fas fa-tachometer-alt"></i> Operations Desk</a>
            <a href="deliverables.php" onclick="closeMobileMenu()"><i class="fas fa-check-square"></i> Manage Deliverables</a>
            <a href="tickets.php" onclick="closeMobileMenu()"><i class="fas fa-headset"></i> Client Tickets & Tasks</a>
            <a href="verbal.php" class="active" onclick="closeMobileMenu()"><i class="fas fa-phone"></i> Client Verbal Requests</a>
            <a href="progress-sync.php" onclick="closeMobileMenu()"><i class="fas fa-sliders-h"></i> Progress Counter Sync</a>
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
                <a href="index.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i><span class="sidebar-text">Operations Desk</span></a>
                <a href="deliverables.php" class="sidebar-link"><i class="fas fa-check-square"></i><span class="sidebar-text">Manage Deliverables</span></a>
                <a href="tickets.php" class="sidebar-link"><i class="fas fa-headset"></i><span class="sidebar-text">Client Tickets & Tasks</span></a>
                <a href="verbal.php" class="sidebar-link active"><i class="fas fa-phone"></i><span class="sidebar-text">Client Verbal Requests</span></a>
                <a href="progress-sync.php" class="sidebar-link"><i class="fas fa-sliders-h"></i><span class="sidebar-text">Progress Counter Sync</span></a>
                <a href="pm-ad-campaigns.php" class="sidebar-link"><i class="fas fa-bullhorn"></i><span class="sidebar-text">Ad Campaigns</span></a>
                <a href="service-packages.php" class="sidebar-link"><i class="fas fa-boxes"></i><span class="sidebar-text">Service Packages</span></a>
                <a href="pm-billing.php" class="sidebar-link <?php echo $current_page === 'services-packages.php' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
            <span class="sidebar-text">Billing Management</span>
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
                    <h2><i class="fas fa-shield-alt"></i> PM Workspace</h2>
                    <p>Tracking SMM Contract: <strong><?php echo $package_name; ?></strong> &bull; PM Admin Access</p>
                </div>
                <div class="banner-actions">
                    <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:8px;"></i> Live Sync Active</span>
                </div>
            </div>

            <!-- ===== VERBAL CONTENT ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list" style="color:var(--primary);"></i> Verbal Requests Log</h3>
                    <span class="sub">Monitor verbal tasks and generate invoices</span>
                </div>
                <?php 
                $all_verbal = array_merge(
                    array_filter($custom_tasks, function($t) { return $t['category'] === 'PM Verbal Add'; }),
                    $verbal_tasks
                );
                if (!empty($all_verbal)): 
                    foreach ($all_verbal as $task): 
                ?>
                <div style="padding:14px 16px;border:1px solid var(--border);border-radius:10px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                    <div>
                        <div style="font-weight:700;color:var(--text-primary);font-size:14px;"><?php echo htmlspecialchars($task['title']); ?></div>
                        <div style="font-size:12px;color:var(--text-secondary);"><?php echo htmlspecialchars($task['description'] ?? ''); ?></div>
                        <div style="display:flex;gap:8px;margin-top:4px;flex-wrap:wrap;">
                            <span style="font-size:10px;color:var(--text-muted);">Category: <?php echo htmlspecialchars($task['category'] ?? 'General'); ?></span>
                            <span class="status-badge <?php echo $task['status'] === 'In Progress' ? 'in-progress' : 'done'; ?>"><?php echo $task['status']; ?></span>
                            <?php if (isset($task['invoice_generated']) && $task['invoice_generated']): ?>
                            <span style="font-size:10px;color:#10b981;font-weight:600;"><i class="fas fa-check-circle"></i> Invoice Generated</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!isset($task['invoice_generated']) || !$task['invoice_generated']): ?>
                    <button onclick="openInvoiceModal(<?php echo $task['client_id'] ?? 0; ?>, <?php echo $task['id'] ?? 0; ?>, '<?php echo addslashes($task['title']); ?>')" style="padding:6px 16px;background:var(--primary);color:#fff;border:none;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;transition:var(--transition);">
                        <i class="fas fa-file-invoice"></i> Generate Invoice
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; 
                else: ?>
                <div class="empty-state" style="text-align:center;padding:30px;color:var(--text-muted);">
                    <i class="fas fa-phone" style="font-size:30px;display:block;margin-bottom:8px;"></i>
                    <p style="font-size:13px;">No verbal requests available</p>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ===== MODALS ===== -->
    <!-- Verbal Project Modal -->
    <div class="modal-overlay" id="modal-verbal">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-verbal')"><i class="fas fa-times"></i></button>
            <h3>Add Verbal Project</h3>
            <p class="modal-sub">Client verbal request will be sent to Finance for pricing.</p>
            <form onsubmit="addVerbalTask(event)">
                <label>Select Client</label>
                <select id="modal-verbal-client">
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Category</label>
                <select id="modal-verbal-category">
                    <option value="Software & Web dev">Software & Web dev</option>
                    <option value="Design & Branding">Design & Branding</option>
                    <option value="Marketing & Ads">Marketing & Ads</option>
                    <option value="Content & Copy">Content & Copy</option>
                    <option value="Other">Other</option>
                </select>
                <label>Task Title</label>
                <input type="text" id="modal-verbal-title" required placeholder="e.g. Design 10 extra custom posts">
                <label>Verbal Context</label>
                <textarea id="modal-verbal-desc" rows="3" required placeholder="Client verbal instructions..."></textarea>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Post to Finance</button>
            </form>
        </div>
    </div>

    <!-- Invoice Modal -->
    <div class="modal-overlay" id="modal-invoice">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-invoice')"><i class="fas fa-times"></i></button>
            <h3>Generate Invoice</h3>
            <p class="modal-sub">Generate invoice for verbal task.</p>
            <form onsubmit="generateInvoiceFromModal(event)">
                <input type="hidden" id="invoice-client-id">
                <input type="hidden" id="invoice-task-id">
                <label>Amount (PKR)</label>
                <input type="number" id="invoice-amount" required placeholder="e.g. 25000">
                <label>Description</label>
                <textarea id="invoice-desc" rows="2" required placeholder="Invoice description..."></textarea>
                <button type="submit" class="btn-submit"><i class="fas fa-file-invoice"></i> Generate Invoice</button>
            </form>
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

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-triangle-exclamation';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; setTimeout(() => toast.remove(), 300); }, 3500);
        }

        function addVerbalTask(e) {
            e.preventDefault();
            
            const clientId = document.getElementById('modal-verbal-client').value;
            const category = document.getElementById('modal-verbal-category').value;
            const title = document.getElementById('modal-verbal-title').value;
            const description = document.getElementById('modal-verbal-desc').value;
            
            if (!title.trim()) {
                showToast('Please enter a task title', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'add_verbal_task');
            formData.append('client_id', clientId);
            formData.append('title', title);
            formData.append('category', category);
            formData.append('description', description);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Verbal project "' + title + '" posted!');
                    closeModal('modal-verbal');
                    document.getElementById('modal-verbal-title').value = '';
                    document.getElementById('modal-verbal-desc').value = '';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error adding verbal task', 'error');
            });
        }

        function openInvoiceModal(clientId, taskId, title) {
            document.getElementById('invoice-client-id').value = clientId;
            document.getElementById('invoice-task-id').value = taskId;
            document.getElementById('invoice-desc').value = 'Invoice for: ' + title;
            document.getElementById('invoice-amount').value = '';
            openModal('modal-invoice');
        }

        function generateInvoiceFromModal(e) {
            e.preventDefault();
            const clientId = document.getElementById('invoice-client-id').value;
            const taskId = document.getElementById('invoice-task-id').value;
            const amount = document.getElementById('invoice-amount').value;
            const description = document.getElementById('invoice-desc').value;
            
            if (!amount || amount <= 0) {
                showToast('Please enter a valid amount', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'generate_invoice');
            formData.append('client_id', clientId);
            formData.append('task_id', taskId);
            formData.append('amount', amount);
            formData.append('description', description);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Invoice ' + data.invoice_number + ' generated!');
                    closeModal('modal-invoice');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error generating invoice', 'error');
            });
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