<?php
// pm-billing.php - PM Invoice Management (Finance Portal Style)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['portal_role']) || ($_SESSION['portal_role'] !== 'pm' && $_SESSION['portal_role'] !== 'admin' && $_SESSION['portal_role'] !== 'super_admin')) {
    header('Location: client-portal.php');
    exit();
}

$userData = $_SESSION['user'] ?? [];
$user_id = $_SESSION['user_id'] ?? 0;

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

// ===== SELECTED CLIENT =====
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$selected_client = null;

if ($selected_client_id > 0) {
    foreach ($clients as $c) {
        if ($c['id'] == $selected_client_id) {
            $selected_client = $c;
            break;
        }
    }
}

// ===== GET INVOICES FOR SELECTED CLIENT =====
$invoices = [];
if ($selected_client_id > 0) {
    $inv_sql = "SELECT * FROM invoices WHERE client_id = ? ORDER BY created_at DESC";
    $inv_stmt = mysqli_prepare($conn, $inv_sql);
    mysqli_stmt_bind_param($inv_stmt, "i", $selected_client_id);
    mysqli_stmt_execute($inv_stmt);
    $inv_result = mysqli_stmt_get_result($inv_stmt);
    while ($row = mysqli_fetch_assoc($inv_result)) {
        $invoices[] = $row;
    }
    mysqli_stmt_close($inv_stmt);
}

// ===== ACTIVE PACKAGE FOR BANNER =====
$active_package = $packages[1] ?? $packages[0] ?? null;
$package_name = $active_package['name'] ?? 'Professional Growth';

// ===== CALCULATE TOTALS =====
$total_paid = 0;
$total_due = 0;
$total_partial = 0;
foreach ($invoices as $inv) {
    if ($inv['status'] === 'Paid') $total_paid += $inv['amount'];
    elseif ($inv['status'] === 'Partially Paid') $total_partial += $inv['amount'];
    else $total_due += $inv['amount'];
}

$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
$current_page = 'pm-billing.php';

// ============================================================ 
// ===== AJAX HANDLER =====
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    // ===== ADD INVOICE =====
    if ($_POST['ajax_action'] === 'add_invoice') {
        $client_id = intval($_POST['client_id']);
        $invoice_number = trim($_POST['invoice_number']);
        $amount = floatval($_POST['amount']);
        $issue_date = !empty($_POST['issue_date']) ? trim($_POST['issue_date']) : null;
        $due_date = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;
        $lps = floatval($_POST['lps'] ?? 0);
        $note = trim($_POST['note']);
        $status = trim($_POST['status']);
        
        if ($client_id > 0 && !empty($invoice_number) && $amount > 0) {
            $sql = "INSERT INTO invoices (client_id, invoice_number, amount, issue_date, due_date, lps, note, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            
            if (!$stmt) {
                $response = ['success' => false, 'message' => 'SQL Prepare Error: ' . mysqli_error($conn)];
                echo json_encode($response);
                exit();
            }
            
            mysqli_stmt_bind_param($stmt, "isddsdss", $client_id, $invoice_number, $amount, $issue_date, $due_date, $lps, $note, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Invoice added successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Execute Error: ' . mysqli_stmt_error($stmt)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Please fill all required fields'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== GET INVOICE =====
    if ($_POST['ajax_action'] === 'get_invoice') {
        $invoice_id = intval($_POST['invoice_id']);
        
        if ($invoice_id > 0) {
            $sql = "SELECT * FROM invoices WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $invoice_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $invoice = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($invoice) {
                $response = ['success' => true, 'data' => $invoice];
            } else {
                $response = ['success' => false, 'message' => 'Invoice not found'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid invoice ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== UPDATE INVOICE =====
    if ($_POST['ajax_action'] === 'update_invoice') {
        $invoice_id = intval($_POST['invoice_id']);
        $invoice_number = trim($_POST['invoice_number']);
        $amount = floatval($_POST['amount']);
        $issue_date = !empty($_POST['issue_date']) ? trim($_POST['issue_date']) : null;
        $due_date = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;
        $lps = floatval($_POST['lps'] ?? 0);
        $note = trim($_POST['note']);
        $status = trim($_POST['status']);
        
        if ($invoice_id > 0 && !empty($invoice_number) && $amount > 0) {
            $sql = "UPDATE invoices SET 
                    invoice_number = ?, amount = ?, issue_date = ?, due_date = ?, 
                    lps = ?, note = ?, status = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sddsdssi", $invoice_number, $amount, $issue_date, $due_date, $lps, $note, $status, $invoice_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Invoice updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Update Error: ' . mysqli_stmt_error($stmt)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Please fill all required fields'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== DELETE INVOICE =====
    if ($_POST['ajax_action'] === 'delete_invoice') {
        $invoice_id = intval($_POST['invoice_id']);
        
        if ($invoice_id > 0) {
            $sql = "DELETE FROM invoices WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $invoice_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Invoice deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Delete Error: ' . mysqli_stmt_error($stmt)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid invoice ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== UPDATE STATUS =====
    if ($_POST['ajax_action'] === 'update_invoice_status') {
        $invoice_id = intval($_POST['invoice_id']);
        $status = trim($_POST['status']);
        
        if ($invoice_id > 0 && in_array($status, ['Due', 'Paid', 'Partially Paid'])) {
            $sql = "UPDATE invoices SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            
            if (!$stmt) {
                $response = ['success' => false, 'message' => 'Database prepare failed: ' . mysqli_error($conn)];
                echo json_encode($response);
                exit();
            }
            
            mysqli_stmt_bind_param($stmt, "si", $status, $invoice_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Status updated to ' . $status];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update: ' . mysqli_stmt_error($stmt)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid status or invoice ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== UPLOAD ATTACHMENT =====
    if ($_POST['ajax_action'] === 'upload_attachment') {
        $invoice_id = intval($_POST['invoice_id']);
        
        if ($invoice_id <= 0 || !isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
            $response = ['success' => false, 'message' => 'No file uploaded'];
            echo json_encode($response);
            exit();
        }
        
        $file_name = $_FILES['file']['name'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_size = $_FILES['file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'])) {
            $response = ['success' => false, 'message' => 'File type not allowed'];
            echo json_encode($response);
            exit();
        }
        
        if ($file_size > 5242880) {
            $response = ['success' => false, 'message' => 'File too large (max 5MB)'];
            echo json_encode($response);
            exit();
        }
        
        $new_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file_name);
        $upload_path = __DIR__ . '/uploads/invoices/';
        
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        if (move_uploaded_file($file_tmp, $upload_path . $new_name)) {
            $sql = "UPDATE invoices SET attachment = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $new_name, $invoice_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Attachment uploaded successfully', 'file' => $new_name];
            } else {
                unlink($upload_path . $new_name);
                $response = ['success' => false, 'message' => 'Failed to save attachment'];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Failed to upload file'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== DELETE ATTACHMENT =====
    if ($_POST['ajax_action'] === 'delete_attachment') {
        $invoice_id = intval($_POST['invoice_id']);
        
        if ($invoice_id > 0) {
            $sql = "SELECT attachment FROM invoices WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $invoice_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($row && !empty($row['attachment'])) {
                $file_path = __DIR__ . '/uploads/invoices/' . $row['attachment'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                $del_sql = "UPDATE invoices SET attachment = NULL WHERE id = ?";
                $del_stmt = mysqli_prepare($conn, $del_sql);
                mysqli_stmt_bind_param($del_stmt, "i", $invoice_id);
                if (mysqli_stmt_execute($del_stmt)) {
                    $response = ['success' => true, 'message' => 'Attachment deleted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to delete attachment'];
                }
                mysqli_stmt_close($del_stmt);
            } else {
                $response = ['success' => false, 'message' => 'No attachment found'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid invoice ID'];
        }
        
        echo json_encode($response);
        exit();
    }
}

// ============================================================ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PM Billing | Finance Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" href="../images/fav-icon.png" type="image/png" />
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --primary-light: #d1fae5;
            --bg: #f0fdf4;
            --card-bg: #ffffff;
            --text-primary: #064e3b;
            --text-secondary: #065f46;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --radius: 16px;
            --shadow: 0 2px 12px rgba(0,0,0,0.04);
            --shadow-hover: 0 8px 40px rgba(0,0,0,0.08);
            --transition: 0.3s ease;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
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
            background: var(--primary-light);
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
            background: var(--primary-light);
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
            background: var(--success);
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
            width: 260px;
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
            background: var(--primary-light);
            color: var(--primary);
        }
        .sidebar-badge {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 12px;
            background: var(--primary-light);
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
        .sidebar-nav .nav-label {
            padding: 8px 12px 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
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
            background: var(--primary-light);
            color: var(--primary);
        }
        .sidebar-link.active {
            background: var(--primary-light);
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
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
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

        /* ===== CLIENT SELECTOR ===== */
        .client-selector {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 16px 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: var(--shadow);
        }
        .client-selector label {
            font-weight: 600;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .client-selector select {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-width: 200px;
            cursor: pointer;
        }
        .client-selector select:focus {
            outline: none;
            border-color: var(--primary);
        }
        .client-selector .btn-add {
            margin-left: auto;
            padding: 8px 20px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
        }
        .client-selector .btn-add:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5,150,105,0.3);
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
            padding: 14px 16px;
            transition: var(--transition);
        }
        .stat-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        .stat-card .number {
            font-size: 20px;
            font-weight: 900;
            color: var(--text-primary);
        }
        .stat-card .label {
            font-size: 11px;
            color: var(--text-muted);
        }
        .stat-card .stat-icon {
            float: right;
            font-size: 20px;
            opacity: 0.15;
            color: var(--primary);
        }

        /* ===== CARD ===== */
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

        /* ===== TABLE ===== */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
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
            vertical-align: middle;
        }
        table tr:hover td { background: #f8fafc; }

        /* ===== STATUS BADGE ===== */
        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
            display: inline-block;
            text-transform: uppercase;
        }
        .status-badge.paid { background: #d1fae5; color: #065f46; }
        .status-badge.due { background: #fee2e2; color: #dc2626; }
        .status-badge.partially-paid { background: #fef3c7; color: #92400e; }

        /* ===== BUTTONS ===== */
        .btn-sm {
            padding: 4px 10px;
            border-radius: 6px;
            border: none;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-sm.edit { background: #fef3c7; color: #92400e; }
        .btn-sm.edit:hover { background: #f59e0b; color: #fff; }
        .btn-sm.delete { background: #fee2e2; color: #dc3545; }
        .btn-sm.delete:hover { background: #dc3545; color: #fff; }
        .btn-sm.status { background: #d1fae5; color: #059669; }
        .btn-sm.status:hover { background: #059669; color: #fff; }
        .btn-sm.upload { background: #dbeafe; color: #1e40af; }
        .btn-sm.upload:hover { background: #1e40af; color: #fff; }
        .btn-sm.download { background: #dcfce7; color: #16a34a; }
        .btn-sm.download:hover { background: #16a34a; color: #fff; }

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

        /* ===== MODALS ===== */
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
            max-width: 560px;
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
        .modal input, .modal select, .modal textarea {
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
        .modal input:focus, .modal select:focus, .modal textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5,150,105,0.1);
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
            box-shadow: 0 4px 12px rgba(5,150,105,0.3);
        }
        .modal .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .modal .form-group.full-width {
            grid-column: 1 / -1;
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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .modal .form-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-layout { flex-direction: column; padding: 12px; }
            .banner { flex-direction: column; text-align: center; }
            .banner h2 { font-size: 16px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .client-selector { flex-direction: column; align-items: stretch; }
            .client-selector select { width: 100%; }
            .client-selector .btn-add { margin-left: 0; width: 100%; text-align: center; }
            .modal { padding: 20px; }
            table { font-size: 11px; }
            table th, table td { padding: 6px 8px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .header-actions .user-badge .name { display: none; }
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
            <div class="header-actions">
                <button class="action-btn" onclick="openModal('modal-add-invoice')" title="Add Invoice">
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
            </div>
        </div>
    </header>

    <!-- ===== MAIN LAYOUT ===== -->
    <div class="main-layout">

        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar <?php echo $isCollapsed ? 'collapsed' : ''; ?>" id="mainSidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">F</div>
                <div class="sidebar-brand-text">
                    <h1>Finance Portal</h1>
                    <span>HIFI Marketing</span>
                </div>
            </div>
            <div class="sidebar-toggle">
                <button onclick="toggleSidebar()">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            <div class="sidebar-badge">
                <span>Access</span>
                <span class="role">Finance</span>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-label">Main</div>
                <a href="finance-portal.php" class="sidebar-link">
                    <i class="fas fa-chart-pie"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                
                <div class="nav-label">Billing</div>
                <a href="ledger-summary.php" class="sidebar-link">
                    <i class="fas fa-book"></i>
                    <span class="sidebar-text">Ledger Summary</span>
                </a>
                <a href="invoices-billing.php" class="sidebar-link">
                    <i class="fas fa-file-invoice"></i>
                    <span class="sidebar-text">Invoices & Billing</span>
                </a>
                <a href="pm-billing.php" class="sidebar-link active">
                    <i class="fas fa-user-tie"></i>
                    <span class="sidebar-text">PM Verbal Project Billing</span>
                </a>
                <a href="subscription-packaging.php" class="sidebar-link">
                    <i class="fas fa-boxes"></i>
                    <span class="sidebar-text">Subscription Packaging</span>
                </a>
                
                <div class="nav-label">Reports</div>
                <a href="reports.php" class="sidebar-link">
                    <i class="fas fa-chart-bar"></i>
                    <span class="sidebar-text">Reports</span>
                </a>
                <a href="payments.php" class="sidebar-link">
                    <i class="fas fa-credit-card"></i>
                    <span class="sidebar-text">Payment History</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <div class="sidebar-user-text">
                        <div class="name"><?php echo $userData['name'] ?? 'PM'; ?></div>
                        <div class="role-label">Finance Access</div>
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
                    <h2><i class="fas fa-user-tie"></i> PM Verbal Project Billing</h2>
                    <p>Manage project invoices &bull; <strong><?php echo $package_name; ?></strong></p>
                </div>
                <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:8px;"></i> <?php echo count($invoices); ?> Invoices</span>
            </div>

            <!-- ===== CLIENT SELECTOR ===== -->
            <div class="client-selector">
                <label for="clientSelect"><i class="fas fa-user"></i> Select Client:</label>
                <select id="clientSelect" onchange="window.location.href='pm-billing.php?client_id=' + this.value">
                    <option value="">-- Select Client --</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo $client['name']; ?> (<?php echo $client['username']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($selected_client): ?>
                <span style="font-size:12px;color:var(--text-muted);">
                    <strong><?php echo $selected_client['name']; ?></strong> &bull; <?php echo $selected_client['email']; ?>
                </span>
                <?php endif; ?>
                <button class="btn-add" onclick="openModal('modal-add-invoice')">
                    <i class="fas fa-plus"></i> Add Invoice
                </button>
            </div>

            <?php if ($selected_client): ?>

            <!-- ===== STATS ===== -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle" style="color:#10b981;"></i></div>
                    <div class="number" style="color:#10b981;"><?php echo number_format($total_paid); ?> PKR</div>
                    <div class="label">Total Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-circle" style="color:#dc2626;"></i></div>
                    <div class="number" style="color:#dc2626;"><?php echo number_format($total_due); ?> PKR</div>
                    <div class="label">Total Due</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock" style="color:#f59e0b;"></i></div>
                    <div class="number" style="color:#f59e0b;"><?php echo number_format($total_partial); ?> PKR</div>
                    <div class="label">Partially Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-invoice" style="color:var(--primary);"></i></div>
                    <div class="number"><?php echo count($invoices); ?></div>
                    <div class="label">Total Invoices</div>
                </div>
            </div>

            <!-- ===== INVOICE TABLE ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-invoice" style="color:var(--primary);"></i> Invoice Ledger</h3>
                    <span class="sub"><i class="fas fa-info-circle"></i> Manage invoices</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Amount</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>LPS</th>
                                <th>Status</th>
                                <th style="text-align:center;">Attachment</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($invoices)): ?>
                                <?php foreach ($invoices as $inv): 
                                    $status_class = strtolower(str_replace(' ', '-', $inv['status'] ?? 'due'));
                                    $issue_date = !empty($inv['issue_date']) && $inv['issue_date'] != '0000-00-00' ? date('M d, Y', strtotime($inv['issue_date'])) : 'N/A';
                                    $due_date = !empty($inv['due_date']) && $inv['due_date'] != '0000-00-00' ? date('M d, Y', strtotime($inv['due_date'])) : 'N/A';
                                    $has_attachment = !empty($inv['attachment']);
                                ?>
                                <tr>
                                    <td style="font-weight:700;color:var(--text-primary);font-size:12px;">
                                        <?php echo htmlspecialchars($inv['invoice_number'] ?? 'N/A'); ?>
                                    </td>
                                    <td style="font-weight:700;font-size:13px;">
                                        <?php echo number_format($inv['amount']); ?> PKR
                                    </td>
                                    <td style="font-size:12px;color:var(--text-secondary);"><?php echo $issue_date; ?></td>
                                    <td style="font-size:12px;color:var(--text-secondary);"><?php echo $due_date; ?></td>
                                    <td style="font-size:12px;font-weight:600;color:var(--warning);">
                                        <?php echo !empty($inv['lps']) && $inv['lps'] > 0 ? number_format($inv['lps']) . '%' : '0%'; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $inv['status'] ?? 'Due'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($has_attachment): ?>
                                            <button class="btn-sm download" onclick="downloadAttachment(<?php echo $inv['id']; ?>)" title="Download">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                            <button class="btn-sm delete" onclick="deleteAttachment(<?php echo $inv['id']; ?>)" title="Remove" style="font-size:8px;padding:2px 6px;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-sm upload" onclick="openUploadModal(<?php echo $inv['id']; ?>)" title="Upload Attachment">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;white-space:nowrap;">
                                        <button class="btn-sm status" onclick="openStatusModal(<?php echo $inv['id']; ?>, '<?php echo $inv['status']; ?>')" title="Change Status">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        <button class="btn-sm edit" onclick="editInvoice(<?php echo $inv['id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-sm delete" onclick="deleteInvoice(<?php echo $inv['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);font-size:13px;">No invoices found for this client</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php else: ?>
            <div class="card">
                <div class="empty-state">
                    <i class="fas fa-user-plus"></i>
                    <h3 style="font-size:18px;font-weight:800;color:var(--text-primary);">Select a Client</h3>
                    <p style="font-size:13px;color:var(--text-muted);">Please select a client from the dropdown above to manage their invoices.</p>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ===== MODALS ===== -->

    <!-- Add Invoice Modal -->
    <div class="modal-overlay" id="modal-add-invoice">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-add-invoice')"><i class="fas fa-times"></i></button>
            <h3><i class="fas fa-plus-circle" style="color:var(--primary);"></i> Add Invoice</h3>
            <p class="modal-sub">Create a new invoice for <?php echo $selected_client ? $selected_client['name'] : 'client'; ?></p>
            <form id="addInvoiceForm" onsubmit="addInvoice(event)">
                <input type="hidden" id="add-client-id" value="<?php echo $selected_client_id; ?>">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Invoice Number *</label>
                        <input type="text" id="add-invoice-number" required placeholder="INV-2026-001">
                    </div>
                    <div class="form-group full-width">
                        <label>Amount (PKR) *</label>
                        <input type="number" id="add-amount" required placeholder="25000" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Issue Date</label>
                        <input type="date" id="add-issue-date">
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" id="add-due-date">
                    </div>
                    <div class="form-group">
                        <label>LPS (%)</label>
                        <input type="number" id="add-lps" placeholder="5" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="add-status">
                            <option value="Due">Due</option>
                            <option value="Paid">Paid</option>
                            <option value="Partially Paid">Partially Paid</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Note</label>
                        <textarea id="add-note" rows="2" placeholder="Invoice description..."></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-plus"></i> Create Invoice</button>
            </form>
        </div>
    </div>

    <!-- Edit Invoice Modal -->
    <div class="modal-overlay" id="modal-edit-invoice">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-edit-invoice')"><i class="fas fa-times"></i></button>
            <h3><i class="fas fa-edit" style="color:var(--warning);"></i> Edit Invoice</h3>
            <p class="modal-sub">Update invoice details</p>
            <form id="editInvoiceForm" onsubmit="updateInvoice(event)">
                <input type="hidden" id="edit-invoice-id">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Invoice Number *</label>
                        <input type="text" id="edit-invoice-number" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Amount (PKR) *</label>
                        <input type="number" id="edit-amount" required step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Issue Date</label>
                        <input type="date" id="edit-issue-date">
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" id="edit-due-date">
                    </div>
                    <div class="form-group">
                        <label>LPS (%)</label>
                        <input type="number" id="edit-lps" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="edit-status">
                            <option value="Due">Due</option>
                            <option value="Paid">Paid</option>
                            <option value="Partially Paid">Partially Paid</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Note</label>
                        <textarea id="edit-note" rows="2"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Invoice</button>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal-overlay" id="modal-status">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-status')"><i class="fas fa-times"></i></button>
            <h3><i class="fas fa-exchange-alt" style="color:var(--primary);"></i> Update Status</h3>
            <p class="modal-sub">Change invoice status</p>
            <form id="statusForm" onsubmit="updateStatus(event)">
                <input type="hidden" id="status-invoice-id">
                <label>Status</label>
                <select id="status-select" style="width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                    <option value="Due">Due</option>
                    <option value="Paid">Paid</option>
                    <option value="Partially Paid">Partially Paid</option>
                </select>
                <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Update Status</button>
            </form>
        </div>
    </div>

    <!-- Upload Attachment Modal -->
    <div class="modal-overlay" id="modal-upload">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-upload')"><i class="fas fa-times"></i></button>
            <h3><i class="fas fa-upload" style="color:var(--info);"></i> Upload Attachment</h3>
            <p class="modal-sub">Upload invoice file (PDF, DOC, PNG, JPG)</p>
            <form id="uploadForm" onsubmit="uploadAttachment(event)" enctype="multipart/form-data">
                <input type="hidden" id="upload-invoice-id">
                <label>Select File</label>
                <input type="file" id="upload-file" required accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                <button type="submit" class="btn-submit"><i class="fas fa-upload"></i> Upload</button>
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

        // ===== ADD INVOICE =====
        function addInvoice(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('ajax_action', 'add_invoice');
            formData.append('client_id', document.getElementById('add-client-id').value);
            formData.append('invoice_number', document.getElementById('add-invoice-number').value);
            formData.append('amount', document.getElementById('add-amount').value);
            formData.append('issue_date', document.getElementById('add-issue-date').value || '');
            formData.append('due_date', document.getElementById('add-due-date').value || '');
            formData.append('lps', document.getElementById('add-lps').value || 0);
            formData.append('status', document.getElementById('add-status').value);
            formData.append('note', document.getElementById('add-note').value);
            
            if (!document.getElementById('add-client-id').value || !document.getElementById('add-invoice-number').value || !document.getElementById('add-amount').value) {
                showToast('Please fill all required fields (*)', 'error');
                return;
            }
            
            showToast('Creating invoice...', 'warning');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    closeModal('modal-add-invoice');
                    document.getElementById('addInvoiceForm').reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Network Error: ' + error, 'error');
            });
        }

        // ===== EDIT INVOICE =====
        function editInvoice(invoiceId) {
            const formData = new FormData();
            formData.append('ajax_action', 'get_invoice');
            formData.append('invoice_id', invoiceId);
            
            showToast('Loading...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const inv = data.data;
                    document.getElementById('edit-invoice-id').value = inv.id;
                    document.getElementById('edit-invoice-number').value = inv.invoice_number;
                    document.getElementById('edit-amount').value = inv.amount;
                    document.getElementById('edit-issue-date').value = inv.issue_date || '';
                    document.getElementById('edit-due-date').value = inv.due_date || '';
                    document.getElementById('edit-lps').value = inv.lps || 0;
                    document.getElementById('edit-status').value = inv.status;
                    document.getElementById('edit-note').value = inv.note || '';
                    openModal('modal-edit-invoice');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error loading invoice.', 'error');
            });
        }

        // ===== UPDATE INVOICE =====
        function updateInvoice(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('ajax_action', 'update_invoice');
            formData.append('invoice_id', document.getElementById('edit-invoice-id').value);
            formData.append('invoice_number', document.getElementById('edit-invoice-number').value);
            formData.append('amount', document.getElementById('edit-amount').value);
            formData.append('issue_date', document.getElementById('edit-issue-date').value);
            formData.append('due_date', document.getElementById('edit-due-date').value);
            formData.append('lps', document.getElementById('edit-lps').value || 0);
            formData.append('status', document.getElementById('edit-status').value);
            formData.append('note', document.getElementById('edit-note').value);
            
            showToast('Updating invoice...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    closeModal('modal-edit-invoice');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error updating invoice.', 'error');
            });
        }

        // ===== DELETE INVOICE =====
        function deleteInvoice(invoiceId) {
            if (!confirm('Are you sure you want to delete this invoice?')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_invoice');
            formData.append('invoice_id', invoiceId);
            
            showToast('Deleting...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting invoice.', 'error');
            });
        }

        // ===== OPEN STATUS MODAL =====
        function openStatusModal(invoiceId, currentStatus) {
            document.getElementById('status-invoice-id').value = invoiceId;
            document.getElementById('status-select').value = currentStatus;
            openModal('modal-status');
        }

        // ===== UPDATE STATUS =====
        function updateStatus(e) {
            e.preventDefault();
            
            const invoiceId = document.getElementById('status-invoice-id').value;
            const status = document.getElementById('status-select').value;
            
            if (!invoiceId || !status) {
                showToast('Invalid data', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'update_invoice_status');
            formData.append('invoice_id', invoiceId);
            formData.append('status', status);
            
            showToast('Updating status...', 'warning');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    closeModal('modal-status');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error updating status.', 'error');
            });
        }

        // ===== OPEN UPLOAD MODAL =====
        function openUploadModal(invoiceId) {
            document.getElementById('upload-invoice-id').value = invoiceId;
            document.getElementById('upload-file').value = '';
            openModal('modal-upload');
        }

        // ===== UPLOAD ATTACHMENT =====
        function uploadAttachment(e) {
            e.preventDefault();
            
            const invoiceId = document.getElementById('upload-invoice-id').value;
            const fileInput = document.getElementById('upload-file');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                showToast('Please select a file', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'upload_attachment');
            formData.append('invoice_id', invoiceId);
            formData.append('file', fileInput.files[0]);
            
            showToast('Uploading...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    closeModal('modal-upload');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error uploading file.', 'error');
            });
        }

        // ===== DELETE ATTACHMENT =====
        function deleteAttachment(invoiceId) {
            if (!confirm('Remove attachment?')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_attachment');
            formData.append('invoice_id', invoiceId);
            
            showToast('Deleting...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting attachment.', 'error');
            });
        }

        // ===== DOWNLOAD ATTACHMENT =====
        function downloadAttachment(invoiceId) {
            const formData = new FormData();
            formData.append('ajax_action', 'download_attachment');
            formData.append('invoice_id', invoiceId);
            
            showToast('Downloading...', 'warning');
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'download_invoice.php?file=' + data.file;
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error downloading file.', 'error');
            });
        }

        // Session timeout
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