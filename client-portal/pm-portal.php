<?php

// pm-portal.php - Complete PM Portal (FIXED - Brand2Social Working)
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
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'operations';
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

// ===== GET ALL DELIVERABLES =====
$deliverables_sql = "SELECT d.*, c.name as client_name FROM deliverables d JOIN clients c ON d.client_id = c.id ORDER BY d.due_date ASC";
$deliverables_result = mysqli_query($conn, $deliverables_sql);
$deliverables = [];
while ($row = mysqli_fetch_assoc($deliverables_result)) {
    $deliverables[] = $row;
}

// ===== GET ALL SUPPORT TICKETS =====
$tickets_sql = "SELECT t.*, c.name as client_name FROM support_tickets t JOIN clients c ON t.client_id = c.id ORDER BY t.created_at DESC";
$tickets_result = mysqli_query($conn, $tickets_sql);
$tickets = [];
while ($row = mysqli_fetch_assoc($tickets_result)) {
    $tickets[] = $row;
}

// ===== GET ALL CUSTOM TASKS =====
$custom_tasks_sql = "SELECT ct.*, c.name as client_name FROM custom_tasks ct JOIN clients c ON ct.client_id = c.id ORDER BY ct.created_at DESC";
$custom_tasks_result = mysqli_query($conn, $custom_tasks_sql);
$custom_tasks = [];
while ($row = mysqli_fetch_assoc($custom_tasks_result)) {
    $custom_tasks[] = $row;
}

// ===== GET ALL ADDONS =====
$addons_sql = "SELECT a.*, c.name as client_name FROM addons a JOIN clients c ON a.client_id = c.id ORDER BY a.created_at DESC";
$addons_result = mysqli_query($conn, $addons_sql);
$addons = [];
while ($row = mysqli_fetch_assoc($addons_result)) {
    $addons[] = $row;
}

// ===== GET ALL INVOICES =====
$invoices_sql = "SELECT i.*, c.name as client_name FROM invoices i JOIN clients c ON i.client_id = c.id ORDER BY i.created_at DESC";
$invoices_result = mysqli_query($conn, $invoices_sql);
$invoices = [];
while ($row = mysqli_fetch_assoc($invoices_result)) {
    $invoices[] = $row;
}

// ===== GET ALL VERBAL TASKS =====
$verbal_tasks_sql = "SELECT * FROM verbal_tasks ORDER BY created_at DESC";
$verbal_tasks_result = mysqli_query($conn, $verbal_tasks_sql);
$verbal_tasks = [];
while ($row = mysqli_fetch_assoc($verbal_tasks_result)) {
    $verbal_tasks[] = $row;
}

// ===== GET BRAND2SOCIAL ATTACHMENTS FOR SELECTED CLIENT =====
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$pm_attachments = [];
if ($selected_client_id > 0) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'documents'");
    if (mysqli_num_rows($check_table) > 0) {
        $att_sql = "SELECT * FROM documents WHERE client_id = ? AND type = 'brand2social' ORDER BY created_at DESC";
        $att_stmt = mysqli_prepare($conn, $att_sql);
        mysqli_stmt_bind_param($att_stmt, "i", $selected_client_id);
        mysqli_stmt_execute($att_stmt);
        $att_result = mysqli_stmt_get_result($att_stmt);
        while ($row = mysqli_fetch_assoc($att_result)) {
            $pm_attachments[] = $row;
        }
        mysqli_stmt_close($att_stmt);
    }
}

// ===== SOCIAL PROGRESS (for sync page) =====
$social_progress = [];
foreach ($clients as $client) {
    $social_progress[$client['id']] = [
        'postsCompleted' => $client['posts_completed'] ?? 0,
        'storiesCompleted' => $client['stories_completed'] ?? 0,
        'reelsCompleted' => $client['reels_completed'] ?? 0,
        'followersGained' => $client['followers_gained'] ?? 0,
        'totalLikes' => $client['total_likes'] ?? 0,
        'brandMentions' => $client['brand_mentions'] ?? 0
    ];
}

// ===== ACTIVE PACKAGE FOR BANNER =====
$active_package = $packages[1] ?? $packages[0] ?? null;
$package_name = $active_package['name'] ?? 'Professional Growth';

// ===== AJAX HANDLER =====
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    $conn = $GLOBALS['conn'];
    
    // 1. Update Task Progress
    if ($_POST['ajax_action'] === 'update_task_progress') {
        $task_id = intval($_POST['task_id']);
        $progress = intval($_POST['progress']);
        
        if ($task_id > 0) {
            $sql = "UPDATE custom_tasks SET progress = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $progress, $task_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Progress updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid task ID'];
        }
    }
    
    // 2. Update Deliverable Status
    elseif ($_POST['ajax_action'] === 'update_deliverable_status') {
        $deliverable_id = intval($_POST['deliverable_id']);
        $status = trim($_POST['status']);
        
        if ($deliverable_id > 0 && in_array($status, ['To Do', 'In Progress', 'Done'])) {
            $sql = "UPDATE deliverables SET status = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $status, $deliverable_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Status updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
    }
    
    // 3. Delete Deliverable
    elseif ($_POST['ajax_action'] === 'delete_deliverable') {
        $deliverable_id = intval($_POST['deliverable_id']);
        
        if ($deliverable_id > 0) {
            $sql = "DELETE FROM deliverables WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $deliverable_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Deliverable deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid deliverable ID'];
        }
    }
    
    // 4. Add Deliverable
    elseif ($_POST['ajax_action'] === 'add_deliverable') {
        $client_id = intval($_POST['client_id']);
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $assigned_to = trim($_POST['assigned_to']);
        $due_date = trim($_POST['due_date']);
        
        if ($client_id > 0 && !empty($name)) {
            $sql = "INSERT INTO deliverables (client_id, name, type, assigned_to, due_date, status) VALUES (?, ?, ?, ?, ?, 'To Do')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "issss", $client_id, $name, $type, $assigned_to, $due_date);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Deliverable added successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
    }
    
    // 5. Resolve Ticket
    elseif ($_POST['ajax_action'] === 'resolve_ticket') {
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
    }
    
    // 6. Add Reply Note
    elseif ($_POST['ajax_action'] === 'add_reply_note') {
        $ticket_id = intval($_POST['ticket_id']);
        $note = trim($_POST['note']);
        
        if ($ticket_id > 0) {
            $sql = "UPDATE support_tickets SET admin_notes = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $note, $ticket_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Reply note added successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add note: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid ticket ID'];
        }
    }
    
    // 7. Generate Invoice
    elseif ($_POST['ajax_action'] === 'generate_invoice') {
        $client_id = intval($_POST['client_id']);
        $task_id = intval($_POST['task_id']);
        $amount = floatval($_POST['amount']);
        $description = trim($_POST['description']);
        
        if ($client_id > 0) {
            $invoice_number = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $sql = "INSERT INTO invoices (client_id, invoice_number, amount, note, status) VALUES (?, ?, ?, ?, 'Pending')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isds", $client_id, $invoice_number, $amount, $description);
            if (mysqli_stmt_execute($stmt)) {
                if ($task_id > 0) {
                    $update_sql = "UPDATE verbal_tasks SET invoice_generated = 1, invoice_id = LAST_INSERT_ID() WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($update_stmt, "i", $task_id);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
                $response = ['success' => true, 'message' => 'Invoice generated successfully', 'invoice_number' => $invoice_number];
            } else {
                $response = ['success' => false, 'message' => 'Failed to generate invoice: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid client ID'];
        }
    }
    
    // 8. Add Verbal Task
    elseif ($_POST['ajax_action'] === 'add_verbal_task') {
        $client_id = intval($_POST['client_id']);
        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        
        if ($client_id > 0 && !empty($title)) {
            $sql = "INSERT INTO verbal_tasks (client_id, title, category, description, status) VALUES (?, ?, ?, ?, 'In Progress')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isss", $client_id, $title, $category, $description);
            if (mysqli_stmt_execute($stmt)) {
                $ct_sql = "INSERT INTO custom_tasks (client_id, title, category, description, status) VALUES (?, ?, ?, ?, 'In Progress')";
                $ct_stmt = mysqli_prepare($conn, $ct_sql);
                mysqli_stmt_bind_param($ct_stmt, "isss", $client_id, $title, $category, $description);
                mysqli_stmt_execute($ct_stmt);
                mysqli_stmt_close($ct_stmt);
                $response = ['success' => true, 'message' => 'Verbal task added successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to add verbal task: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
    }
    
    // 9. Update Social Progress
    elseif ($_POST['ajax_action'] === 'update_social_progress') {
        $client_id = intval($_POST['client_id']);
        $posts = intval($_POST['posts']);
        $stories = intval($_POST['stories']);
        $likes = intval($_POST['likes']);
        $followers = intval($_POST['followers']);
        
        if ($client_id > 0) {
            $sql = "UPDATE clients SET posts_completed = ?, stories_completed = ?, total_likes = ?, followers_gained = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiiii", $posts, $stories, $likes, $followers, $client_id);
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
    
    // 10. Upload Brand File (PM Side - FIXED)
    elseif ($_POST['ajax_action'] === 'upload_brand_file') {
        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        
        if ($client_id <= 0) {
            $response = ['success' => false, 'message' => 'Please select a client.'];
            echo json_encode($response);
            exit();
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
            $response = ['success' => false, 'message' => 'No file uploaded.'];
            echo json_encode($response);
            exit();
        }
        
        $file_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, ['pdf', 'csv', 'xlsx', 'xls'])) {
            $response = ['success' => false, 'message' => 'Invalid file type.'];
            echo json_encode($response);
            exit();
        }
        
        $new_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file_name);
        $upload_path = __DIR__ . '/uploads/brand2social/';
        
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        if (!move_uploaded_file($file_tmp, $upload_path . $new_name)) {
            $response = ['success' => false, 'message' => 'Failed to move file.'];
            echo json_encode($response);
            exit();
        }
        
        // ===== CHECK/CREATE TABLE WITH PROPER COLUMNS =====
        $check = mysqli_query($conn, "SHOW TABLES LIKE 'documents'");
        if (mysqli_num_rows($check) == 0) {
            $sql = "CREATE TABLE IF NOT EXISTS `documents` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `client_id` int(11) NOT NULL,
                `file_name` varchar(255) NOT NULL,
                `file_path` varchar(255) NOT NULL,
                `file_size` bigint(20) DEFAULT 0,
                `type` varchar(50) DEFAULT 'brand2social',
                `description` text,
                `uploaded_by` varchar(50) DEFAULT 'PM',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `client_id` (`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            mysqli_query($conn, $sql);
        } else {
            // Check if description column exists
            $check_col = mysqli_query($conn, "SHOW COLUMNS FROM documents LIKE 'description'");
            if (mysqli_num_rows($check_col) == 0) {
                mysqli_query($conn, "ALTER TABLE documents ADD COLUMN description text AFTER type");
            }
            // Check if uploaded_by column exists
            $check_col2 = mysqli_query($conn, "SHOW COLUMNS FROM documents LIKE 'uploaded_by'");
            if (mysqli_num_rows($check_col2) == 0) {
                mysqli_query($conn, "ALTER TABLE documents ADD COLUMN uploaded_by varchar(50) DEFAULT 'PM' AFTER description");
            }
        }
        
        $file_path = 'uploads/brand2social/' . $new_name;
        $type = 'brand2social';
        $uploaded_by = 'PM';
        
        $sql = "INSERT INTO documents (client_id, file_name, file_path, file_size, type, description, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            unlink($upload_path . $new_name);
            $response = ['success' => false, 'message' => 'Database prepare failed: ' . mysqli_error($conn)];
            echo json_encode($response);
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, "issssss", $client_id, $file_name, $file_path, $file_size, $type, $description, $uploaded_by);
        
        if (mysqli_stmt_execute($stmt)) {
            $response = ['success' => true, 'message' => 'File uploaded!'];
        } else {
            unlink($upload_path . $new_name);
            $response = ['success' => false, 'message' => 'Database error: ' . mysqli_stmt_error($stmt)];
        }
        mysqli_stmt_close($stmt);
        
        echo json_encode($response);
        exit();
    }
    
    // 11. Delete Attachment (PM Side)
    elseif ($_POST['ajax_action'] === 'delete_attachment') {
        $doc_id = intval($_POST['doc_id']);
        
        if ($doc_id > 0) {
            // Get file path first
            $get_sql = "SELECT file_path FROM documents WHERE id = ?";
            $get_stmt = mysqli_prepare($conn, $get_sql);
            mysqli_stmt_bind_param($get_stmt, "i", $doc_id);
            mysqli_stmt_execute($get_stmt);
            $get_result = mysqli_stmt_get_result($get_stmt);
            $doc = mysqli_fetch_assoc($get_result);
            mysqli_stmt_close($get_stmt);
            
            if ($doc) {
                // Delete file from server
                $file_path = __DIR__ . '/../' . $doc['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Delete from database
                $del_sql = "DELETE FROM documents WHERE id = ?";
                $del_stmt = mysqli_prepare($conn, $del_sql);
                mysqli_stmt_bind_param($del_stmt, "i", $doc_id);
                if (mysqli_stmt_execute($del_stmt)) {
                    $response = ['success' => true, 'message' => 'Attachment deleted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to delete: ' . mysqli_error($conn)];
                }
                mysqli_stmt_close($del_stmt);
            } else {
                $response = ['success' => false, 'message' => 'Attachment not found'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid attachment ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    echo json_encode($response);
    exit();
}

// ===== GET CLIENT LIST FOR DROPDOWNS =====
$client_options = [];
foreach ($clients as $c) {
    $client_options[$c['id']] = $c['name'] . ' (' . $c['username'] . ')';
}

// ============================================================ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PM Portal | HIFI Marketing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" href="../images/fav-icon.png" type="image/png" />
    <style>
        /* ===== HIFI DASHBOARD COLOR THEME (CLIENT PORTAL STYLE) ===== */
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

        /* Mobile Menu Toggle */
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

        /* Mobile Navigation Overlay */
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

        /* Header actions */
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
        .header-actions .action-btn.pm-btn {
            background: var(--primary);
            color: #fff;
            padding: 6px 16px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 12px;
        }
        .header-actions .action-btn.pm-btn:hover {
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

        /* ===== TASK CARDS ===== */
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
        .status-badge.resolved { background: #e8f5e9; color: #2e7d32; }

        /* ===== TABLE ===== */
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
            .header-actions .action-btn.pm-btn { display: none; }
            .header-actions .action-btn { padding: 4px 8px; font-size: 13px; }
            .modal { padding: 20px; }
            .header-inner { padding: 0 12px; }
            .logo { font-size: 17px; }
            .logo .brand-icon { width: 30px; height: 30px; font-size: 13px; }
            .banner .banner-actions .btn-white { width: 100%; text-align: center; }
            .table-wrap { overflow-x: auto; }
            table { font-size: 12px; }
            table th, table td { padding: 8px 10px; }
        }
        
        @media (max-width: 480px) {
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

        /* ===== RANGE SLIDER ===== */
        input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 3px;
            outline: none;
            transition: opacity 0.2s;
            background: #e9edf2;
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            cursor: pointer;
            background: var(--primary);
            border: 2px solid var(--primary-dark);
        }
        input[type="range"]::-moz-range-thumb {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            cursor: pointer;
            background: var(--primary);
            border: 2px solid var(--primary-dark);
        }

        /* Status select */
        select.status-select {
            border: none;
            background: transparent;
            font-weight: 700;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 9999px;
            cursor: pointer;
        }
        select.status-select option { padding: 4px 8px; }

        /* Attachment styles */
        .attachment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 8px;
            border-left: 3px solid var(--primary);
            transition: var(--transition);
        }
        .attachment-item:hover {
            background: #f0f3ff;
        }
        .attachment-item .file-info {
            flex: 1;
        }
        .attachment-item .file-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--text-primary);
        }
        .attachment-item .file-meta {
            font-size: 11px;
            color: var(--text-muted);
        }
        .attachment-item .file-actions {
            display: flex;
            gap: 6px;
        }
        .attachment-item .file-actions button {
            background: transparent;
            border: none;
            padding: 4px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
        }
        .attachment-item .file-actions .btn-download {
            color: var(--primary);
        }
        .attachment-item .file-actions .btn-download:hover {
            background: #f0f3ff;
        }
        .attachment-item .file-actions .btn-delete {
            color: #dc3545;
        }
        .attachment-item .file-actions .btn-delete:hover {
            background: #fee2e2;
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
            <a href="operations.php" class="nav-link <?php echo $current_page === 'operations.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Operations
            </a>
            <a href="deliverables.php" class="nav-link <?php echo $current_page === 'deliverables.php' ? 'active' : ''; ?>">
                <i class="fas fa-check-square"></i> Deliverables
            </a>
            <a href="tickets.php" class="nav-link <?php echo $current_page === 'tickets.php' ? 'active' : ''; ?>">
                <i class="fas fa-headset"></i> Tickets
            </a>
            <a href="verbal.php" class="nav-link <?php echo $current_page === 'verbal.php' ? 'active' : ''; ?>">
                <i class="fas fa-phone"></i> Verbal
            </a>
            <a href="progress-sync.php" class="nav-link <?php echo $current_page === 'progress-sync.php' ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> Sync
            </a>
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
    <!-- ===== MOBILE NAVIGATION OVERLAY ===== -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay" onclick="closeMobileMenu()"></div>

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
        <a href="operations.php" class="<?php echo $current_page === 'operations.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
            <i class="fas fa-tachometer-alt"></i> Operations Desk
        </a>
        <a href="deliverables.php" class="<?php echo $current_page === 'deliverables.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
            <i class="fas fa-check-square"></i> Manage Deliverables
        </a>
        <a href="tickets.php" class="<?php echo $current_page === 'tickets.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
            <i class="fas fa-headset"></i> Client Tickets & Tasks
        </a>
        <a href="verbal.php" class="<?php echo $current_page === 'verbal.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
            <i class="fas fa-phone"></i> Client Verbal Requests
        </a>
        <a href="progress-sync.php" class="<?php echo $current_page === 'progress-sync.php' ? 'active' : ''; ?>" onclick="closeMobileMenu()">
            <i class="fas fa-sliders-h"></i> Progress Counter Sync
        </a>
        <a href="pm-ad-campaigns.php" class="sidebar-link <?php echo $current_page === 'pm-ad-campaigns.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>
            <span class="sidebar-text">Ad Campaigns</span>
        </a>
        <a href="services-packages.php" class="sidebar-link <?php echo $current_page === 'services-packages.php' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
            <span class="sidebar-text">Service Packages</span>
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
        <a href="operations.php" class="sidebar-link <?php echo $current_page === 'operations.php' ? 'active' : ''; ?>">
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
        <a href="services-packages.php" class="sidebar-link <?php echo $current_page === 'services-packages.php' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
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

            <!-- ===== BRAND2SOCIAL ATTACHMENTS (PM SIDE) ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-paperclip" style="color:var(--primary);"></i> Brand2Social Attachments</h3>
                    <button onclick="openModal('modal-upload-brand')" style="background:var(--primary);color:#fff;border:none;padding:6px 18px;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;transition:var(--transition);">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Upload brand2social analytics files for client to download.</p>
                
                <!-- Client Selector for Attachments -->
                <div style="margin-bottom:12px;">
                    <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Select Client:</label>
                    <select id="attachment-client-select" onchange="window.location.href='?tab=operations&client_id=' + this.value" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['username']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="pm-attachments-list">
                    <?php if (!empty($pm_attachments)): ?>
                        <?php foreach ($pm_attachments as $att): ?>
                        <div class="attachment-item">
                            <div class="file-info">
                                <div class="file-name">
                                    <i class="fas fa-file"></i> <?php echo htmlspecialchars($att['file_name']); ?>
                                </div>
                                <div class="file-meta">
                                    Uploaded: <?php echo date('M d, Y H:i', strtotime($att['created_at'])); ?> &bull; 
                                    Size: <?php echo round($att['file_size'] / 1024, 1); ?> KB
                                    <?php if (!empty($att['description'])): ?>
                                    &bull; <?php echo htmlspecialchars($att['description']); ?>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                                    <i class="fas fa-user"></i> Uploaded by: <?php echo $att['uploaded_by'] ?? 'PM'; ?>
                                </div>
                            </div>
                            <div class="file-actions">
                                <button class="btn-download" onclick="downloadFile(<?php echo $att['id']; ?>)" title="Download">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn-delete" onclick="deleteAttachment(<?php echo $att['id']; ?>)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="padding:20px;text-align:center;color:var(--text-muted);">
                            <i class="fas fa-file" style="font-size:28px;display:block;margin-bottom:6px;opacity:0.3;"></i>
                            <p style="font-size:12px;">No attachments uploaded yet.</p>
                            <p style="font-size:11px;">Select a client and upload brand2social files.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- ===== TAB 1: OPERATIONS DESK ===== -->
            <?php if ($activeTab === 'operations'): ?>

            <!-- PM Creative Desk Info -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-pen-tool" style="color:var(--primary);"></i> Project Manager Creative Desk</h3>
                    <span class="sub">All finance elements are secured and hidden</span>
                </div>
                <p style="font-size:13px;color:var(--text-secondary);">All finance elements, PKR invoicing, and billing registry records are secured and hidden from this interface.</p>
            </div>

            <!-- Ongoing Client Add-on & Custom Tasks -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-tasks" style="color:var(--primary);"></i> Ongoing Client Add-on &amp; Custom Tasks</h3>
                    <span class="sub">Drag the slider to update task completion percentage</span>
                </div>
                <div class="grid-2">
                    <?php if (!empty($custom_tasks)): ?>
                        <?php foreach ($custom_tasks as $task): ?>
                        <div class="task-item" style="border-color: <?php echo $task['status'] === 'Awaiting Quote' ? 'var(--primary)' : 'var(--border)'; ?>;">
                            <div class="task-header">
                                <span class="task-title"><?php echo htmlspecialchars($task['title']); ?></span>
                                <span class="status-badge <?php echo $task['status'] === 'Awaiting Quote' ? 'pending' : 'in-progress'; ?>"><?php echo $task['status']; ?></span>
                            </div>
                            <div class="task-desc"><?php echo htmlspecialchars($task['description']); ?></div>
                            <div class="task-meta">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($task['category']); ?></span>
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($task['client_name'] ?? 'Unknown'); ?></span>
                            </div>
                            <div style="margin-top:8px;">
                                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);">
                                    <span>Progress</span>
                                    <span id="progress-label-<?php echo $task['id']; ?>" style="font-weight:700;color:var(--text-primary);"><?php echo $task['progress']; ?>%</span>
                                </div>
                                <input type="range" min="0" max="100" value="<?php echo $task['progress']; ?>" 
                                       oninput="updateTaskProgress(<?php echo $task['id']; ?>, this.value)"
                                       id="progress-slider-<?php echo $task['id']; ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="grid-column:1/-1;text-align:center;padding:30px;color:var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;"></i>
                            <p style="font-size:13px;">No custom tasks available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Verbal Project Addition -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-phone" style="color:var(--primary);"></i> Verbal Project Addition</h3>
                    <span class="sub">Client verbal requests over Zoom/WhatsApp</span>
                </div>
                <form onsubmit="addVerbalTask(event)" class="grid-2" style="gap:12px;">
                    <div>
                        <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Select Client</label>
                        <select id="verbal-client" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Category</label>
                        <select id="verbal-category" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                            <option value="Software & Web dev">Software & Web dev</option>
                            <option value="Design & Branding">Design & Branding</option>
                            <option value="Marketing & Ads">Marketing & Ads</option>
                            <option value="Content & Copy">Content & Copy</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Task Title</label>
                        <input type="text" id="verbal-title" required placeholder="e.g. Design 10 extra posts" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Verbal Context</label>
                        <input type="text" id="verbal-desc" required placeholder="Client instructions..." style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                    </div>
                    <div style="grid-column:1/-1;">
                        <button type="submit" style="width:100%;padding:10px;background:var(--primary);color:#fff;border:none;border-radius:40px;font-weight:700;font-size:13px;cursor:pointer;transition:var(--transition);">
                            <i class="fas fa-paper-plane"></i> Post to Finance Invoice Pipeline
                        </button>
                    </div>
                </form>
            </div>

            <!-- ===== TAB 2: MANAGE DELIVERABLES ===== -->
            <?php elseif ($activeTab === 'deliverables'): ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle" style="color:var(--primary);"></i> Add Deliverable Target</h3>
                    <span class="sub">Publish to client board</span>
                </div>
                <form onsubmit="addDeliverable(event)" class="grid-2" style="gap:12px;">
                    <div>
                        <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Select Client</label>
                        <select id="dl-client" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Deliverable Title</label>
                        <input type="text" id="dl-name" required placeholder="e.g. Schedule UGC Blog Copy" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Category Type</label>
                        <select id="dl-type" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                            <option value="Elegant Design">Elegant Design</option>
                            <option value="SEO Blog">SEO Blog</option>
                            <option value="Paid Ads">Paid Ads</option>
                            <option value="Engagement">Engagement</option>
                            <option value="Setup">Setup</option>
                            <option value="Development">Development</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Assignee</label>
                        <input type="text" id="dl-assignee" required placeholder="Zack Media" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                    </div>
                    <div>
                        <label style="font-weight:600;font-size:12px;color:var(--text-secondary);display:block;margin-bottom:4px;">Due Date</label>
                        <input type="date" id="dl-date" required style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                    </div>
                    <div style="grid-column:1/-1;">
                        <button type="submit" style="width:100%;padding:10px;background:var(--primary);color:#fff;border:none;border-radius:40px;font-weight:700;font-size:13px;cursor:pointer;transition:var(--transition);">
                            <i class="fas fa-check-circle"></i> Publish Deliverable
                        </button>
                    </div>
                </form>
            </div>

            <!-- Active Timeline Targets Board -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list-check" style="color:var(--primary);"></i> Active Timeline Targets Board</h3>
                    <span class="sub">Track all deliverables</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Assignee</th>
                                <th>Status</th>
                                <th>Client</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($deliverables)): ?>
                                <?php foreach ($deliverables as $deliv): ?>
                                <tr>
                                    <td style="font-weight:700;color:var(--text-primary);"><?php echo htmlspecialchars($deliv['name']); ?></td>
                                    <td><span style="padding:2px 10px;background:#f8fafc;border-radius:12px;font-size:10px;font-weight:600;"><?php echo htmlspecialchars($deliv['type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($deliv['assigned_to']); ?></td>
                                    <td>
                                        <select onchange="updateDeliverableStatus(<?php echo $deliv['id']; ?>, this.value)" 
                                                class="status-select" style="background:<?php echo $deliv['status'] === 'Done' ? '#e8f5e9' : ($deliv['status'] === 'In Progress' ? '#e8edfe' : '#f1f5f9'); ?>;color:<?php echo $deliv['status'] === 'Done' ? '#065f46' : ($deliv['status'] === 'In Progress' ? '#1a3a8a' : '#475569'); ?>;">
                                            <option value="To Do" <?php echo $deliv['status'] === 'To Do' ? 'selected' : ''; ?>>To Do</option>
                                            <option value="In Progress" <?php echo $deliv['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Done" <?php echo $deliv['status'] === 'Done' ? 'selected' : ''; ?>>Done</option>
                                        </select>
                                    </td>
                                    <td style="color:var(--text-muted);font-size:12px;"><?php echo htmlspecialchars($deliv['client_name']); ?></td>
                                    <td style="text-align:right;">
                                        <button onclick="deleteDeliverable(<?php echo $deliv['id']; ?>)" style="background:transparent;border:none;color:#dc3545;cursor:pointer;padding:4px 8px;border-radius:6px;transition:var(--transition);" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">No deliverables available</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===== TAB 3: CLIENT TICKETS & TASKS ===== -->
            <?php elseif ($activeTab === 'tickets'): ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-headset" style="color:var(--primary);"></i> Client Action Requests &amp; Tickets</h3>
                    <span class="sub">Review task scopes and provide response</span>
                </div>
                <?php if (!empty($tickets)): ?>
                    <?php foreach ($tickets as $req): ?>
                    <div style="padding:16px;border:1px solid <?php echo $req['status'] === 'Open' ? '#fee2e2' : '#e8f5e9'; ?>;border-radius:10px;margin-bottom:12px;background:<?php echo $req['status'] === 'Open' ? '#fef2f2' : '#f0fdf4'; ?>;">
                        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                            <div style="flex:1;">
                                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:4px;">
                                    <span class="status-badge <?php echo $req['status'] === 'Open' ? 'open' : 'resolved'; ?>"><?php echo $req['status']; ?></span>
                                    <span style="font-size:10px;font-weight:600;color:var(--text-muted);"><?php echo htmlspecialchars($req['category']); ?></span>
                                    <span style="font-size:10px;color:var(--text-muted);">Priority: <?php echo $req['priority'] ?? 'Medium'; ?></span>
                                    <span style="font-size:10px;color:var(--text-muted);">Client: <?php echo htmlspecialchars($req['client_name']); ?></span>
                                </div>
                                <div style="font-weight:700;color:var(--text-primary);font-size:14px;"><?php echo htmlspecialchars($req['title']); ?></div>
                                <div style="font-size:12px;color:var(--text-secondary);margin-top:3px;"><?php echo htmlspecialchars($req['description']); ?></div>
                                <?php if (!empty($req['admin_notes'])): ?>
                                <div style="margin-top:6px;padding:8px 12px;background:#e8edfe;border-radius:8px;font-size:12px;color:#1a3a8a;">
                                    <i class="fas fa-reply"></i> <?php echo htmlspecialchars($req['admin_notes']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:6px;min-width:120px;">
                                <?php if ($req['status'] === 'Open'): ?>
                                <button onclick="resolveTicket(<?php echo $req['id']; ?>)" style="padding:6px 14px;background:#10b981;color:#fff;border:none;border-radius:40px;font-size:10px;font-weight:600;cursor:pointer;transition:var(--transition);">
                                    <i class="fas fa-check"></i> Resolve
                                </button>
                                <?php else: ?>
                                <span style="padding:6px 14px;background:#f1f5f9;color:var(--text-muted);border-radius:40px;font-size:10px;font-weight:600;text-align:center;">Resolved ✓</span>
                                <?php endif; ?>
                                <button onclick="openNoteModal(<?php echo $req['id']; ?>, '<?php echo addslashes($req['admin_notes']); ?>')" style="padding:6px 14px;background:var(--primary);color:#fff;border:none;border-radius:40px;font-size:10px;font-weight:600;cursor:pointer;transition:var(--transition);">
                                    <i class="fas fa-reply"></i> Reply Note
                                </button>
                            </div>
                        </div>
                        <div style="font-size:10px;color:var(--text-muted);margin-top:6px;">Submitted: <?php echo date('Y-m-d', strtotime($req['created_at'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="text-align:center;padding:30px;color:var(--text-muted);">
                        <i class="fas fa-inbox" style="font-size:30px;display:block;margin-bottom:8px;"></i>
                        <p style="font-size:13px;">No tickets available</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== TAB 4: CLIENT VERBAL REQUESTS ===== -->
            <?php elseif ($activeTab === 'verbal'): ?>

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

            <!-- ===== TAB 5: PROGRESS COUNTER SYNC ===== -->
            <?php elseif ($activeTab === 'sync'): ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-sliders-h" style="color:var(--primary);"></i> Social Progress Counters</h3>
                    <button onclick="syncAllProgress()" style="padding:6px 16px;background:var(--primary);color:#fff;border:none;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;transition:var(--transition);">
                        <i class="fas fa-sync"></i> Sync All
                    </button>
                </div>
                
                <?php foreach ($clients as $client): 
                    $progress = $social_progress[$client['id']] ?? ['postsCompleted' => 0, 'storiesCompleted' => 0, 'totalLikes' => 0, 'followersGained' => 0];
                    $package = null;
                    foreach ($packages as $p) {
                        if ($p['id'] == $client['active_package_id']) {
                            $package = $p;
                            break;
                        }
                    }
                    $posts_limit = $package['posts_limit'] ?? 20;
                    $stories_limit = $package['stories_limit'] ?? 25;
                ?>
                <div style="border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:14px;background:#fafbfc;">
                    <h4 style="font-weight:700;color:var(--text-primary);font-size:14px;margin-bottom:10px;"><?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['username']); ?>)</h4>
                    <div class="grid-2" style="gap:12px;">
                        <div>
                            <label style="font-size:11px;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:2px;">Feed Posts Completed</label>
                            <span style="font-weight:700;font-size:14px;" id="posts-display-<?php echo $client['id']; ?>"><?php echo $progress['postsCompleted']; ?> / <?php echo $posts_limit; ?></span>
                            <input type="range" id="sync-posts-<?php echo $client['id']; ?>" min="0" max="<?php echo $posts_limit; ?>" value="<?php echo $progress['postsCompleted']; ?>" 
                                   oninput="updateProgressDisplay('<?php echo $client['id']; ?>', 'posts', this.value, <?php echo $posts_limit; ?>)">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:2px;">Stories Completed</label>
                            <span style="font-weight:700;font-size:14px;" id="stories-display-<?php echo $client['id']; ?>"><?php echo $progress['storiesCompleted']; ?> / <?php echo $stories_limit; ?></span>
                            <input type="range" id="sync-stories-<?php echo $client['id']; ?>" min="0" max="<?php echo $stories_limit; ?>" value="<?php echo $progress['storiesCompleted']; ?>" 
                                   oninput="updateProgressDisplay('<?php echo $client['id']; ?>', 'stories', this.value, <?php echo $stories_limit; ?>)">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:2px;">Total Likes</label>
                            <span style="font-weight:700;font-size:14px;" id="likes-display-<?php echo $client['id']; ?>"><?php echo number_format($progress['totalLikes']); ?></span>
                            <input type="range" id="sync-likes-<?php echo $client['id']; ?>" min="0" max="20000" value="<?php echo $progress['totalLikes']; ?>" 
                                   oninput="document.getElementById('likes-display-<?php echo $client['id']; ?>').textContent = Number(this.value).toLocaleString()">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:2px;">Followers Gained</label>
                            <span style="font-weight:700;font-size:14px;" id="followers-display-<?php echo $client['id']; ?>"><?php echo number_format($progress['followersGained']); ?></span>
                            <input type="range" id="sync-followers-<?php echo $client['id']; ?>" min="0" max="5000" value="<?php echo $progress['followersGained']; ?>" 
                                   oninput="document.getElementById('followers-display-<?php echo $client['id']; ?>').textContent = Number(this.value).toLocaleString()">
                        </div>
                    </div>
                    <button onclick="saveClientProgress(<?php echo $client['id']; ?>)" style="margin-top:10px;padding:6px 18px;background:var(--primary);color:#fff;border:none;border-radius:40px;font-size:11px;font-weight:600;cursor:pointer;transition:var(--transition);">
                        <i class="fas fa-save"></i> Save Progress
                    </button>
                </div>
                <?php endforeach; ?>
                
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);text-align:center;font-size:11px;color:var(--text-muted);">
                    Changes will be synced instantly to the client dashboard
                </div>
            </div>

            <?php endif; ?>

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

    <!-- Note Modal -->
    <div class="modal-overlay" id="modal-note">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-note')"><i class="fas fa-times"></i></button>
            <h3>Add Reply Note</h3>
            <p class="modal-sub">Provide response commentary to the client.</p>
            <form onsubmit="saveNoteFromModal(event)">
                <input type="hidden" id="note-req-id">
                <label>Your Reply</label>
                <textarea id="note-reply-text" rows="4" required placeholder="Enter your response..."></textarea>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Reply Note</button>
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

    <!-- ===== BRAND UPLOAD MODAL (PM SIDE - FIXED) ===== -->
    <div class="modal-overlay" id="modal-upload-brand">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-upload-brand')"><i class="fas fa-times"></i></button>
            <h3>Upload Brand2Social File</h3>
            <p class="modal-sub">Upload analytics files for client to download.</p>
            <form id="upload-brand-form" onsubmit="uploadBrandFile(event)" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Select Client *</label>
                    <select id="brand-client-id" name="brand-client-id" required style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['username']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Select File (PDF, CSV, XLSX) *</label>
                    <input type="file" id="brand-upload-file" name="brand-upload-file" required accept=".pdf,.csv,.xlsx,.xls" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label>File Description (Optional)</label>
                    <input type="text" id="brand-file-desc" name="brand-file-desc" placeholder="e.g. Monthly Report - June 2024" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-upload"></i> Upload File</button>
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

        // ===== 1. UPDATE TASK PROGRESS =====
        function updateTaskProgress(taskId, value) {
            document.getElementById('progress-label-' + taskId).textContent = value + '%';
            
            const formData = new FormData();
            formData.append('ajax_action', 'update_task_progress');
            formData.append('task_id', taskId);
            formData.append('progress', value);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showToast('Error updating progress: ' + data.message, 'error');
                }
            })
            .catch(error => {});
        }

        // ===== 2. UPDATE DELIVERABLE STATUS =====
        function updateDeliverableStatus(deliverableId, status) {
            const formData = new FormData();
            formData.append('ajax_action', 'update_deliverable_status');
            formData.append('deliverable_id', deliverableId);
            formData.append('status', status);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Status updated to "' + status + '"');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error updating status', 'error');
            });
        }

        // ===== 3. DELETE DELIVERABLE =====
        function deleteDeliverable(deliverableId) {
            if (confirm('Are you sure you want to delete this deliverable?')) {
                const formData = new FormData();
                formData.append('ajax_action', 'delete_deliverable');
                formData.append('deliverable_id', deliverableId);
                
                fetch(window.location.href, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Deliverable deleted successfully');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error deleting deliverable', 'error');
                });
            }
        }

        // ===== 4. ADD DELIVERABLE =====
        function addDeliverable(e) {
            e.preventDefault();
            const client_id = document.getElementById('dl-client').value;
            const name = document.getElementById('dl-name').value;
            const type = document.getElementById('dl-type').value;
            const assignee = document.getElementById('dl-assignee').value;
            const date = document.getElementById('dl-date').value;
            
            if (!name || !assignee || !date) {
                showToast('Please fill all fields', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'add_deliverable');
            formData.append('client_id', client_id);
            formData.append('name', name);
            formData.append('type', type);
            formData.append('assigned_to', assignee);
            formData.append('due_date', date);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Deliverable "' + name + '" published!');
                    document.getElementById('dl-name').value = '';
                    document.getElementById('dl-assignee').value = '';
                    document.getElementById('dl-date').value = '';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error adding deliverable', 'error');
            });
        }

        // ===== 5. RESOLVE TICKET =====
        function resolveTicket(ticketId) {
            if (confirm('Mark this ticket as resolved?')) {
                const formData = new FormData();
                formData.append('ajax_action', 'resolve_ticket');
                formData.append('ticket_id', ticketId);
                
                fetch(window.location.href, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Ticket resolved successfully!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error resolving ticket', 'error');
                });
            }
        }

        // ===== 6. OPEN NOTE MODAL =====
        function openNoteModal(ticketId, currentNote) {
            document.getElementById('note-req-id').value = ticketId;
            document.getElementById('note-reply-text').value = currentNote || '';
            openModal('modal-note');
        }

        // ===== 7. SAVE NOTE FROM MODAL =====
        function saveNoteFromModal(e) {
            e.preventDefault();
            const ticketId = document.getElementById('note-req-id').value;
            const note = document.getElementById('note-reply-text').value;
            
            if (!note.trim()) {
                showToast('Please enter a reply note', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'add_reply_note');
            formData.append('ticket_id', ticketId);
            formData.append('note', note);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Reply note saved!');
                    closeModal('modal-note');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error saving note', 'error');
            });
        }

        // ===== 8. OPEN INVOICE MODAL =====
        function openInvoiceModal(clientId, taskId, title) {
            document.getElementById('invoice-client-id').value = clientId;
            document.getElementById('invoice-task-id').value = taskId;
            document.getElementById('invoice-desc').value = 'Invoice for: ' + title;
            document.getElementById('invoice-amount').value = '';
            openModal('modal-invoice');
        }

        // ===== 9. GENERATE INVOICE FROM MODAL =====
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

        // ===== 10. ADD VERBAL TASK =====
        function addVerbalTask(e) {
            e.preventDefault();
            
            let clientId, category, title, description;
            const modalClient = document.getElementById('modal-verbal-client');
            
            if (modalClient && modalClient.value) {
                clientId = modalClient.value;
                category = document.getElementById('modal-verbal-category').value;
                title = document.getElementById('modal-verbal-title').value;
                description = document.getElementById('modal-verbal-desc').value;
                closeModal('modal-verbal');
            } else {
                clientId = document.getElementById('verbal-client').value;
                category = document.getElementById('verbal-category').value;
                title = document.getElementById('verbal-title').value;
                description = document.getElementById('verbal-desc').value;
            }
            
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
                    if (document.getElementById('verbal-title')) {
                        document.getElementById('verbal-title').value = '';
                        document.getElementById('verbal-desc').value = '';
                    }
                    if (document.getElementById('modal-verbal-title')) {
                        document.getElementById('modal-verbal-title').value = '';
                        document.getElementById('modal-verbal-desc').value = '';
                    }
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error adding verbal task', 'error');
            });
        }

        // ===== 11. UPDATE PROGRESS DISPLAY =====
        function updateProgressDisplay(clientId, type, value, limit) {
            if (type === 'posts') {
                document.getElementById('posts-display-' + clientId).textContent = value + ' / ' + limit;
            } else if (type === 'stories') {
                document.getElementById('stories-display-' + clientId).textContent = value + ' / ' + limit;
            }
        }

        // ===== 12. SAVE CLIENT PROGRESS =====
        function saveClientProgress(clientId) {
            const posts = document.getElementById('sync-posts-' + clientId).value;
            const stories = document.getElementById('sync-stories-' + clientId).value;
            const likes = document.getElementById('sync-likes-' + clientId).value;
            const followers = document.getElementById('sync-followers-' + clientId).value;
            
            const formData = new FormData();
            formData.append('ajax_action', 'update_social_progress');
            formData.append('client_id', clientId);
            formData.append('posts', posts);
            formData.append('stories', stories);
            formData.append('likes', likes);
            formData.append('followers', followers);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Progress synced for client!');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error syncing progress', 'error');
            });
        }

        // ===== 13. SYNC ALL PROGRESS =====
        function syncAllProgress() {
            const buttons = document.querySelectorAll('[onclick^="saveClientProgress"]');
            if (buttons.length === 0) {
                showToast('No clients to sync', 'warning');
                return;
            }
            buttons.forEach(btn => {
                const clientId = btn.getAttribute('onclick').match(/\d+/)[0];
                saveClientProgress(clientId);
            });
            showToast('Syncing all client progress...');
        }

        // ===== 14. UPLOAD BRAND FILE (PM SIDE - FIXED) =====
        function uploadBrandFile(e) {
            e.preventDefault();
            
            // Get values
            const clientId = document.getElementById('brand-client-id').value;
            const fileInput = document.getElementById('brand-upload-file');
            const description = document.getElementById('brand-file-desc').value || '';
            
            // Validate
            if (!clientId) {
                showToast('Please select a client first.', 'error');
                return;
            }
            
            if (!fileInput.files || fileInput.files.length === 0) {
                showToast('Please select a file.', 'error');
                return;
            }
            
            // Check file size (max 10MB)
            if (fileInput.files[0].size > 10485760) {
                showToast('File size too large. Maximum 10MB allowed.', 'error');
                return;
            }
            
            // Check file extension
            const fileName = fileInput.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            const allowedExts = ['pdf', 'csv', 'xlsx', 'xls'];
            if (!allowedExts.includes(fileExt)) {
                showToast('File type not allowed. Please upload PDF, CSV, or Excel files.', 'error');
                return;
            }
            
            // Create FormData
            const formData = new FormData();
            formData.append('ajax_action', 'upload_brand_file');
            formData.append('file', fileInput.files[0]);
            formData.append('client_id', clientId);
            formData.append('description', description);
            
            // Show loading
            showToast('Uploading file...', 'warning');
            
            // Send request
            fetch(window.location.href, { 
                method: 'POST', 
                body: formData 
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('File uploaded successfully!');
                    closeModal('modal-upload-brand');
                    document.getElementById('upload-brand-form').reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                showToast('Error uploading file. Please try again.', 'error');
            });
        }

        // ===== 15. DELETE ATTACHMENT (PM SIDE) =====
        function deleteAttachment(docId) {
            if (!confirm('Are you sure you want to delete this attachment?')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_attachment');
            formData.append('doc_id', docId);
            
            showToast('Deleting...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Attachment deleted successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting attachment.', 'error');
            });
        }

        // ===== 16. DOWNLOAD FILE =====
        function downloadFile(docId) {
            window.location.href = 'download.php?doc_id=' + docId;
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