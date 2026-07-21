<?php
// tickets.php - PM Tickets & Tasks (Full Chat System)
session_start();
error_reporting(0);
ini_set('display_errors', 0);

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

// ===== GET ALL SUPPORT TICKETS =====
$tickets_sql = "SELECT t.*, c.name as client_name, c.id as client_id 
                FROM support_tickets t 
                JOIN clients c ON t.client_id = c.id 
                ORDER BY t.created_at DESC";
$tickets_result = mysqli_query($conn, $tickets_sql);
$tickets = [];
while ($row = mysqli_fetch_assoc($tickets_result)) {
    $tickets[] = $row;
}

// ===== GET SINGLE TICKET FOR REPLY VIEW =====
$view_ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
$view_ticket = null;

if ($view_ticket_id > 0) {
    $view_sql = "SELECT t.*, c.name as client_name, c.id as client_id 
                 FROM support_tickets t 
                 JOIN clients c ON t.client_id = c.id 
                 WHERE t.id = ?";
    $view_stmt = mysqli_prepare($conn, $view_sql);
    mysqli_stmt_bind_param($view_stmt, "i", $view_ticket_id);
    mysqli_stmt_execute($view_stmt);
    $view_result = mysqli_stmt_get_result($view_stmt);
    $view_ticket = mysqli_fetch_assoc($view_result);
    mysqli_stmt_close($view_stmt);
}

// ===== ACTIVE PACKAGE FOR BANNER =====
$active_package = $packages[1] ?? $packages[0] ?? null;
$package_name = $active_package['name'] ?? 'Professional Growth';

$current_page = 'tickets.php';

// ===== AJAX HANDLER =====
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    // ===== PM REPLY TO TICKET =====
    if ($_POST['ajax_action'] === 'pm_reply') {
        $ticket_id = intval($_POST['ticket_id']);
        $reply_text = trim($_POST['reply_text']);
        
        if ($ticket_id > 0 && !empty($reply_text)) {
            // Check if columns exist
            $check_col = mysqli_query($conn, "SHOW COLUMNS FROM support_tickets LIKE 'pm_reply'");
            if (mysqli_num_rows($check_col) == 0) {
                mysqli_query($conn, "ALTER TABLE support_tickets 
                    ADD COLUMN pm_reply text AFTER client_reply_date,
                    ADD COLUMN pm_reply_date datetime DEFAULT NULL AFTER pm_reply");
            }
            
            // Append reply to existing replies
            $sql = "UPDATE support_tickets SET 
                    pm_reply = CONCAT(IFNULL(pm_reply, ''), ?),
                    pm_reply_date = NOW(),
                    status = 'In Progress' 
                    WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            $reply_with_date = "\n\n--- PM Reply (" . date('Y-m-d H:i') . ") ---\n" . $reply_text;
            mysqli_stmt_bind_param($stmt, "si", $reply_with_date, $ticket_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Reply sent successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to send reply: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Please enter a reply'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== RESOLVE TICKET =====
    if ($_POST['ajax_action'] === 'resolve_ticket') {
        $ticket_id = intval($_POST['ticket_id']);
        
        if ($ticket_id > 0) {
            $sql = "UPDATE support_tickets SET status = 'Resolved' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $ticket_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Ticket resolved successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to resolve: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid ticket ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== DELETE TICKET =====
    if ($_POST['ajax_action'] === 'delete_ticket') {
        $ticket_id = intval($_POST['ticket_id']);
        
        if ($ticket_id > 0) {
            $sql = "DELETE FROM support_tickets WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $ticket_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Ticket deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid ticket ID'];
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
    <title>PM Portal | HIFI Marketing - Tickets</title>
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
        .status-badge.in-progress { background: #e8edfe; color: var(--primary); }
        .status-badge.resolved { background: #e8f5e9; color: #2e7d32; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table th { background: #f8fafc; text-align: left; padding: 10px 14px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 1px solid var(--border); }
        table td { padding: 10px 14px; color: var(--text-secondary); border-bottom: 1px solid #f0f2f5; }
        table tr:hover td { background: #f8fafc; }
        table tr:last-child td { border-bottom: none; }
        .modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 16px; }
        .modal-overlay.show { display: flex; }
        .modal { background: var(--card-bg); border-radius: var(--radius); max-width: 560px; width: 100%; padding: 24px 28px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); position: relative; max-height: 90vh; overflow-y: auto; }
        .modal .modal-close { position: absolute; top: 14px; right: 16px; background: transparent; border: none; font-size: 18px; color: var(--text-muted); cursor: pointer; transition: var(--transition); }
        .modal .modal-close:hover { color: var(--text-primary); }
        .modal h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin-bottom: 3px; }
        .modal .modal-sub { font-size: 12px; color: var(--text-muted); margin-bottom: 16px; }
        .modal label { display: block; font-weight: 600; font-size: 12px; color: var(--text-secondary); margin-bottom: 3px; }
        .modal input, .modal select, .modal textarea { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; background: #f8fafc; font-size: 13px; font-family: 'Inter', sans-serif; transition: var(--transition); margin-bottom: 12px; }
        .modal input:focus, .modal select:focus, .modal textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(74,92,245,0.1); }
        .modal .btn-submit { width: 100%; padding: 10px; background: var(--primary); color: #fff; border: none; border-radius: 40px; font-weight: 700; font-size: 13px; cursor: pointer; transition: var(--transition); }
        .modal .btn-submit:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-sm { padding: 4px 10px; border-radius: 6px; border: none; font-size: 11px; font-weight: 600; cursor: pointer; transition: var(--transition); }
        .btn-sm.view { background: #f0f3ff; color: var(--primary); }
        .btn-sm.view:hover { background: var(--primary); color: #fff; }
        .btn-sm.delete { background: #fee2e2; color: #dc3545; }
        .btn-sm.delete:hover { background: #dc3545; color: #fff; }
        .btn-sm.resolve { background: #dcfce7; color: #16a34a; }
        .btn-sm.resolve:hover { background: #16a34a; color: #fff; }
        .toast-container { position: fixed; top: 80px; right: 20px; z-index: 300; display: flex; flex-direction: column; gap: 8px; }
        .toast { background: var(--text-primary); color: #fff; padding: 12px 18px; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; min-width: 260px; animation: slideIn 0.3s ease; }
        .toast.success i { color: #10b981; }
        .toast.error i { color: #ef4444; }
        .toast.warning i { color: #f59e0b; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .security-badge { position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.7); color: #4ade80; padding: 3px 12px; border-radius: 20px; font-size: 8px; font-weight: 700; z-index: 999; backdrop-filter: blur(10px); border: 1px solid rgba(74,222,128,0.2); pointer-events: none; }
        .empty-state { text-align: center; padding: 30px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 36px; color: #d0d7e0; margin-bottom: 8px; }
        .empty-state h4 { font-size: 15px; color: var(--text-primary); margin-bottom: 3px; }
        
        /* ===== TICKET ITEMS ===== */
        .ticket-item {
            padding: 16px 18px;
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 12px;
            transition: var(--transition);
            cursor: pointer;
        }
        .ticket-item:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-hover);
        }
        .ticket-item .ticket-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .ticket-item .ticket-title {
            font-weight: 700;
            font-size: 14px;
            color: var(--text-primary);
        }
        .ticket-item .ticket-desc {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        .ticket-item .ticket-meta {
            display: flex;
            gap: 12px;
            margin-top: 6px;
            font-size: 11px;
            color: var(--text-muted);
            flex-wrap: wrap;
        }
        .ticket-item .ticket-meta i { margin-right: 3px; }
        
        /* ===== TICKET DETAIL VIEW ===== */
        .ticket-detail {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 20px 24px;
            margin-bottom: 18px;
        }
        .ticket-detail .ticket-subject {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-primary);
        }
        .ticket-detail .ticket-info {
            display: flex;
            gap: 16px;
            margin: 8px 0 16px;
            font-size: 12px;
            color: var(--text-muted);
            flex-wrap: wrap;
        }
        .ticket-detail .ticket-info span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .ticket-detail .message-thread {
            margin-top: 16px;
            max-height: 400px;
            overflow-y: auto;
        }
        .ticket-detail .message {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 12px;
            border-left: 4px solid var(--primary);
        }
        .ticket-detail .message.client {
            background: #f0f3ff;
            border-left-color: var(--primary);
        }
        .ticket-detail .message.pm {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }
        .ticket-detail .message .msg-header {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .ticket-detail .message .msg-header .sender {
            font-weight: 700;
            color: var(--text-primary);
        }
        .ticket-detail .message .msg-text {
            font-size: 13px;
            color: var(--text-secondary);
            white-space: pre-wrap;
        }
        .ticket-detail .reply-form textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #f8fafc;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            resize: vertical;
            min-height: 80px;
        }
        .ticket-detail .reply-form textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74,92,245,0.1);
        }
        .ticket-detail .reply-form .btn-send {
            padding: 8px 24px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }
        .ticket-detail .reply-form .btn-send:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .ticket-detail .reply-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
        }
        .ticket-detail .reply-actions button {
            padding: 6px 16px;
            border: none;
            border-radius: 40px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .ticket-detail .reply-actions .btn-resolve {
            background: #dcfce7;
            color: #16a34a;
        }
        .ticket-detail .reply-actions .btn-resolve:hover {
            background: #16a34a;
            color: #fff;
        }
        .ticket-detail .reply-actions .btn-delete {
            background: #fee2e2;
            color: #dc3545;
        }
        .ticket-detail .reply-actions .btn-delete:hover {
            background: #dc3545;
            color: #fff;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            background: #f0f3ff;
            color: var(--primary);
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 14px;
        }
        .btn-back:hover {
            background: var(--primary);
            color: #fff;
        }

        @media (max-width: 1024px) { .grid-2 { grid-template-columns: 1fr; } .grid-3 { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 992px) { .desktop-nav { display: none; } .mobile-menu-toggle { display: flex; } .header-actions .user-badge .name { display: none; } }
        @media (max-width: 768px) {
            .sidebar { display: none; } .main-layout { padding: 12px; flex-direction: column; }
            .banner { padding: 16px 18px; flex-direction: column; text-align: center; }
            .banner h2 { font-size: 16px; } .grid-3 { grid-template-columns: 1fr; }
            .header-actions .action-btn { padding: 4px 8px; font-size: 13px; }
            .modal { padding: 20px; } .header-inner { padding: 0 12px; }
            .logo { font-size: 17px; } .logo .brand-icon { width: 30px; height: 30px; font-size: 13px; }
            .banner .banner-actions .btn-white { width: 100%; text-align: center; }
            .table-wrap { overflow-x: auto; } table { font-size: 12px; } table th, table td { padding: 8px 10px; }
            .ticket-detail { padding: 14px 16px; }
            .ticket-detail .message { padding: 10px 12px; }
            .ticket-item { padding: 12px 14px; }
            .ticket-item .ticket-title { font-size: 13px; }
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
                <div class="brand-icon">P</div>
                HIFI <span>Marketing</span>
            </div>
            <nav class="desktop-nav">
                <a href="operations.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Operations</a>
                <a href="deliverables.php" class="nav-link"><i class="fas fa-check-square"></i> Deliverables</a>
                <a href="tickets.php" class="nav-link active"><i class="fas fa-headset"></i> Tickets</a>
                <a href="verbal.php" class="nav-link"><i class="fas fa-phone"></i> Verbal</a>
                <a href="progress-sync.php" class="nav-link"><i class="fas fa-sliders-h"></i> Sync</a>
            </nav>
            <div class="header-actions">
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
            <a href="tickets.php" class="active" onclick="closeMobileMenu()"><i class="fas fa-headset"></i> Client Tickets & Tasks</a>
            <a href="verbal.php" onclick="closeMobileMenu()"><i class="fas fa-phone"></i> Client Verbal Requests</a>
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
                <a href="operations.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i><span class="sidebar-text">Operations Desk</span></a>
                <a href="deliverables.php" class="sidebar-link"><i class="fas fa-check-square"></i><span class="sidebar-text">Manage Deliverables</span></a>
                <a href="tickets.php" class="sidebar-link active"><i class="fas fa-headset"></i><span class="sidebar-text">Client Tickets & Tasks</span></a>
                <a href="verbal.php" class="sidebar-link"><i class="fas fa-phone"></i><span class="sidebar-text">Client Verbal Requests</span></a>
                <a href="progress-sync.php" class="sidebar-link"><i class="fas fa-sliders-h"></i><span class="sidebar-text">Progress Counter Sync</span></a>
                <a href="pm-ad-campaigns.php" class="sidebar-link"><i class="fas fa-bullhorn"></i><span class="sidebar-text">Ad Campaigns</span></a>
                <a href="service-packages.php" class="sidebar-link"><i class="fas fa-credit-card"></i><span class="sidebar-text">Service Packages</span></a>
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

            <?php if ($view_ticket_id > 0 && $view_ticket): ?>
            
            <!-- ===== TICKET DETAIL VIEW ===== -->
            <button class="btn-back" onclick="window.location.href='tickets.php'">
                <i class="fas fa-arrow-left"></i> Back to Tickets
            </button>

            <div class="ticket-detail">
                <div class="ticket-subject"><?php echo htmlspecialchars($view_ticket['title']); ?></div>
                <div class="ticket-info">
                    <span><i class="fas fa-user"></i> Client: <?php echo htmlspecialchars($view_ticket['client_name']); ?></span>
                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($view_ticket['category'] ?? 'General'); ?></span>
                    <span><i class="fas fa-circle" style="color:<?php echo $view_ticket['status'] === 'Open' ? '#dc3545' : ($view_ticket['status'] === 'In Progress' ? '#4a5cf5' : '#2e7d32'); ?>;font-size:8px;"></i> <?php echo $view_ticket['status']; ?></span>
                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y H:i', strtotime($view_ticket['created_at'])); ?></span>
                </div>

                <div class="message-thread">
                    <!-- Original Message -->
                    <div class="message client">
                        <div class="msg-header">
                            <span class="sender"><i class="fas fa-user"></i> <?php echo htmlspecialchars($view_ticket['client_name']); ?></span>
                            <span><?php echo date('M d, Y H:i', strtotime($view_ticket['created_at'])); ?></span>
                        </div>
                        <div class="msg-text"><?php echo nl2br(htmlspecialchars($view_ticket['description'])); ?></div>
                    </div>

                    <!-- PM Replies -->
                    <?php 
                    $pm_replies = explode("\n\n--- PM Reply", $view_ticket['pm_reply'] ?? '');
                    foreach ($pm_replies as $reply): 
                        if (trim($reply)): 
                    ?>
                    <div class="message pm">
                        <div class="msg-header">
                            <span class="sender"><i class="fas fa-user-tie"></i> PM Reply</span>
                            <span><?php echo date('M d, Y H:i', strtotime($view_ticket['pm_reply_date'] ?? $view_ticket['created_at'])); ?></span>
                        </div>
                        <div class="msg-text"><?php echo nl2br(htmlspecialchars(trim($reply))); ?></div>
                    </div>
                    <?php 
                        endif; 
                    endforeach; 
                    ?>

                    <!-- Client Replies -->
                    <?php 
                    $client_replies = explode("\n\n--- Client Reply", $view_ticket['client_reply'] ?? '');
                    foreach ($client_replies as $reply): 
                        if (trim($reply)): 
                    ?>
                    <div class="message client">
                        <div class="msg-header">
                            <span class="sender"><i class="fas fa-user"></i> <?php echo htmlspecialchars($view_ticket['client_name']); ?> (Reply)</span>
                            <span><?php echo date('M d, Y H:i', strtotime($view_ticket['client_reply_date'] ?? $view_ticket['created_at'])); ?></span>
                        </div>
                        <div class="msg-text"><?php echo nl2br(htmlspecialchars(trim($reply))); ?></div>
                    </div>
                    <?php 
                        endif; 
                    endforeach; 
                    ?>
                </div>

                <!-- Reply Form -->
                <div class="reply-form">
                    <form id="replyForm" onsubmit="submitPMReply(event, <?php echo $view_ticket['id']; ?>)">
                        <label style="font-weight:600;font-size:13px;color:var(--text-secondary);display:block;margin-bottom:6px;">
                            <i class="fas fa-reply"></i> Your Reply
                        </label>
                        <textarea id="reply-text" rows="3" placeholder="Type your reply here..." required></textarea>
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </form>
                </div>

                <!-- Action Buttons -->
                <div class="reply-actions">
                    <?php if ($view_ticket['status'] !== 'Resolved'): ?>
                    <button class="btn-resolve" onclick="resolveTicket(<?php echo $view_ticket['id']; ?>)">
                        <i class="fas fa-check"></i> Mark as Resolved
                    </button>
                    <?php endif; ?>
                    <button class="btn-delete" onclick="deleteTicket(<?php echo $view_ticket['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete Ticket
                    </button>
                </div>
            </div>

            <?php else: ?>

            <!-- ===== TICKETS LIST ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-headset" style="color:var(--primary);"></i> Client Action Requests &amp; Tickets</h3>
                    <span class="sub"><?php echo count($tickets); ?> total tickets</span>
                </div>
                <?php if (!empty($tickets)): ?>
                    <?php foreach ($tickets as $req): ?>
                    <div class="ticket-item" onclick="window.location.href='tickets.php?ticket_id=<?php echo $req['id']; ?>'">
                        <div class="ticket-header">
                            <span class="ticket-title"><?php echo htmlspecialchars($req['title']); ?></span>
                            <span class="status-badge <?php echo $req['status'] === 'Open' ? 'open' : ($req['status'] === 'In Progress' ? 'in-progress' : 'resolved'); ?>">
                                <?php echo $req['status']; ?>
                            </span>
                        </div>
                        <div class="ticket-desc"><?php echo htmlspecialchars(substr($req['description'], 0, 120)) . (strlen($req['description']) > 120 ? '...' : ''); ?></div>
                        <div class="ticket-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($req['client_name']); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo $req['category'] ?? 'General'; ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                            <?php if (!empty($req['pm_reply'])): ?>
                            <span style="color:var(--primary);"><i class="fas fa-reply"></i> PM replied</span>
                            <?php endif; ?>
                            <?php if (!empty($req['client_reply'])): ?>
                            <span style="color:#10b981;"><i class="fas fa-check-circle"></i> Client replied</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No tickets available from clients.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php endif; ?>

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

        // ===== PM REPLY =====
        function submitPMReply(e, ticketId) {
            e.preventDefault();
            const replyText = document.getElementById('reply-text').value;
            
            if (!replyText.trim()) {
                showToast('Please enter a reply', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'pm_reply');
            formData.append('ticket_id', ticketId);
            formData.append('reply_text', replyText);
            
            showToast('Sending reply...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Reply sent successfully!');
                    document.getElementById('reply-text').value = '';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error sending reply.', 'error');
            });
        }

        // ===== RESOLVE TICKET =====
        function resolveTicket(ticketId) {
            if (!confirm('Mark this ticket as resolved?')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'resolve_ticket');
            formData.append('ticket_id', ticketId);
            
            showToast('Resolving...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Ticket resolved successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error resolving ticket.', 'error');
            });
        }

        // ===== DELETE TICKET =====
        function deleteTicket(ticketId) {
            if (!confirm('Are you sure you want to delete this ticket?')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_ticket');
            formData.append('ticket_id', ticketId);
            
            showToast('Deleting...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Ticket deleted successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting ticket.', 'error');
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