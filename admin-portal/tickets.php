<?php
// tickets.php - PM Tickets & Tasks (Full Chat System with Real-time)
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

// ===== GET ADMIN PROFILE PICTURE =====
$admin_dp = 'default-admin.png';
$admin_dp_path = __DIR__ . '/dps/' . $user_id . '.jpg';
if (file_exists($admin_dp_path)) {
    $admin_dp = $user_id . '.jpg';
} elseif (file_exists(__DIR__ . '/dps/admin-default.png')) {
    $admin_dp = 'admin-default.png';
}
$admin_dp_url = 'dps/' . $admin_dp;

// ===== CHECK IF avatar COLUMN EXISTS =====
$avatar_column_exists = false;
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'avatar'");
if ($check_column && mysqli_num_rows($check_column) > 0) {
    $avatar_column_exists = true;
}

// ===== GET ALL CLIENTS =====
if ($avatar_column_exists) {
    $clients_sql = "SELECT c.*, u.username, u.email, u.avatar FROM clients c JOIN users u ON c.user_id = u.id ORDER BY c.name ASC";
} else {
    $clients_sql = "SELECT c.*, u.username, u.email FROM clients c JOIN users u ON c.user_id = u.id ORDER BY c.name ASC";
}
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
$filter_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

if ($filter_client_id > 0) {
    $tickets_sql = "SELECT t.*, c.name as client_name, c.id as client_id, u.username 
                    FROM support_tickets t 
                    JOIN clients c ON t.client_id = c.id 
                    JOIN users u ON c.user_id = u.id
                    WHERE t.client_id = ?
                    ORDER BY t.created_at DESC";
    $tickets_stmt = mysqli_prepare($conn, $tickets_sql);
    mysqli_stmt_bind_param($tickets_stmt, "i", $filter_client_id);
    mysqli_stmt_execute($tickets_stmt);
    $tickets_result = mysqli_stmt_get_result($tickets_stmt);
    $tickets = [];
    while ($row = mysqli_fetch_assoc($tickets_result)) {
        $tickets[] = $row;
    }
    mysqli_stmt_close($tickets_stmt);
} else {
    $tickets_sql = "SELECT t.*, c.name as client_name, c.id as client_id, u.username 
                    FROM support_tickets t 
                    JOIN clients c ON t.client_id = c.id 
                    JOIN users u ON c.user_id = u.id
                    ORDER BY t.created_at DESC";
    $tickets_result = mysqli_query($conn, $tickets_sql);
    $tickets = [];
    while ($row = mysqli_fetch_assoc($tickets_result)) {
        $tickets[] = $row;
    }
}

// ===== HELPER FUNCTION: Find Client DP by Username =====
function findClientDPByUsername($username) {
    if (empty($username)) {
        return 'default-client.png';
    }
    
    $base_path = __DIR__ . '/dps/';
    $extensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
    
    // Try username as-is
    foreach ($extensions as $ext) {
        $file_path = $base_path . $username . '.' . $ext;
        if (file_exists($file_path)) {
            return $username . '.' . $ext;
        }
    }
    
    // Try lowercase
    $lower_username = strtolower($username);
    if ($lower_username !== $username) {
        foreach ($extensions as $ext) {
            $file_path = $base_path . $lower_username . '.' . $ext;
            if (file_exists($file_path)) {
                return $lower_username . '.' . $ext;
            }
        }
    }
    
    // Try client_ prefix as fallback
    foreach ($extensions as $ext) {
        $file_path = $base_path . 'client_' . $username . '.' . $ext;
        if (file_exists($file_path)) {
            return 'client_' . $username . '.' . $ext;
        }
    }
    
    return 'default-client.png';
}

// ===== HELPER FUNCTION: Backup Conversation =====
function backupConversation($username, $sender_type, $message, $created_at = null) {
    if (empty($username)) {
        return false;
    }
    
    $backup_dir = __DIR__ . '/../uploads/chat_files/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }
    
    $filename = $backup_dir . $username . '.txt';
    $date = $created_at ? date('d-m-Y', strtotime($created_at)) : date('d-m-Y');
    $time = $created_at ? date('h:i A', strtotime($created_at)) : date('h:i A');
    
    // Clean message for backup
    $clean_message = trim(strip_tags($message));
    if (empty($clean_message)) {
        $clean_message = '[File/Media shared]';
    }
    
    $sender_label = ($sender_type === 'admin') ? 'Admin' : 'Client';
    
    // Check if file exists to determine if we need a header
    $needs_header = !file_exists($filename) || filesize($filename) < 10;
    
    $entry = "";
    
    if ($needs_header) {
        $entry .= "========================================\n";
        $entry .= "CONVERSATION BACKUP - " . strtoupper($username) . "\n";
        $entry .= "STARTED: " . date('d-m-Y H:i:s') . "\n";
        $entry .= "========================================\n\n";
    }
    
    $entry .= "----------------------------------------\n";
    $entry .= "Date: " . $date . "\n";
    $entry .= "Time: " . $time . "\n\n";
    $entry .= $sender_label . ":\n" . $clean_message . "\n";
    $entry .= "----------------------------------------\n\n";
    
    return file_put_contents($filename, $entry, FILE_APPEND | LOCK_EX);
}

// ===== GET SINGLE TICKET FOR REPLY VIEW =====
$view_ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
$view_ticket = null;
$client_dp = 'default-client.png';
$client_username = '';

if ($view_ticket_id > 0) {
    if ($avatar_column_exists) {
        $view_sql = "SELECT t.*, c.name as client_name, c.id as client_id, u.username, u.avatar 
                     FROM support_tickets t 
                     JOIN clients c ON t.client_id = c.id 
                     JOIN users u ON c.user_id = u.id
                     WHERE t.id = ?";
    } else {
        $view_sql = "SELECT t.*, c.name as client_name, c.id as client_id, u.username 
                     FROM support_tickets t 
                     JOIN clients c ON t.client_id = c.id 
                     JOIN users u ON c.user_id = u.id
                     WHERE t.id = ?";
    }
    $view_stmt = mysqli_prepare($conn, $view_sql);
    mysqli_stmt_bind_param($view_stmt, "i", $view_ticket_id);
    mysqli_stmt_execute($view_stmt);
    $view_result = mysqli_stmt_get_result($view_stmt);
    $view_ticket = mysqli_fetch_assoc($view_result);
    
    if ($view_ticket) {
        $client_username = $view_ticket['username'] ?? '';
        // Get client DP by username
        $client_dp = findClientDPByUsername($client_username);
    }
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
    
    // ===== PM REPLY TO TICKET (Legacy Support) =====
    if ($_POST['ajax_action'] === 'pm_reply') {
        $ticket_id = intval($_POST['ticket_id']);
        $reply_text = trim($_POST['reply_text']);
        
        if ($ticket_id > 0 && !empty($reply_text)) {
            $check_col = mysqli_query($conn, "SHOW COLUMNS FROM support_tickets LIKE 'pm_reply'");
            if (mysqli_num_rows($check_col) == 0) {
                mysqli_query($conn, "ALTER TABLE support_tickets 
                    ADD COLUMN pm_reply text AFTER client_reply_date,
                    ADD COLUMN pm_reply_date datetime DEFAULT NULL AFTER pm_reply");
            }
            
            $sql = "UPDATE support_tickets SET 
                    pm_reply = CONCAT(IFNULL(pm_reply, ''), ?),
                    pm_reply_date = NOW(),
                    status = 'In Progress' 
                    WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            $reply_with_date = "\n\n--- PM Reply (" . date('Y-m-d H:i') . ") ---\n" . $reply_text;
            mysqli_stmt_bind_param($stmt, "si", $reply_with_date, $ticket_id);
            if (mysqli_stmt_execute($stmt)) {
                // Backup conversation
                $username_query = "SELECT u.username FROM support_tickets t JOIN clients c ON t.client_id = c.id JOIN users u ON c.user_id = u.id WHERE t.id = ?";
                $user_stmt = mysqli_prepare($conn, $username_query);
                mysqli_stmt_bind_param($user_stmt, "i", $ticket_id);
                mysqli_stmt_execute($user_stmt);
                $user_result = mysqli_stmt_get_result($user_stmt);
                $user_row = mysqli_fetch_assoc($user_result);
                if ($user_row) {
                    backupConversation($user_row['username'], 'admin', $reply_text);
                }
                mysqli_stmt_close($user_stmt);
                
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
    
    // ===== GET MESSAGES (AJAX Polling) =====
    if ($_POST['ajax_action'] === 'get_messages') {
        $ticket_id = intval($_POST['ticket_id']);
        $last_id = isset($_POST['last_id']) ? intval($_POST['last_id']) : 0;
        
        if ($ticket_id > 0) {
            $create_table = "CREATE TABLE IF NOT EXISTS chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NOT NULL,
                sender_type ENUM('admin', 'client') NOT NULL,
                sender_id INT NOT NULL,
                message_type ENUM('text', 'file', 'voice') DEFAULT 'text',
                message TEXT,
                file_path VARCHAR(255),
                file_name VARCHAR(255),
                file_size INT,
                mime_type VARCHAR(100),
                is_deleted TINYINT(1) DEFAULT 0,
                deleted_for VARCHAR(255) DEFAULT NULL,
                deleted_by INT DEFAULT NULL,
                deleted_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ticket_id (ticket_id),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            mysqli_query($conn, $create_table);
            
            $sql = "SELECT * FROM chat_messages WHERE ticket_id = ? AND id > ? AND is_deleted = 0 ORDER BY created_at ASC";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $ticket_id, $last_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $messages = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Check if deleted for this user
                $user_id_str = $user_id . ',';
                if ($row['deleted_for'] && strpos($row['deleted_for'], $user_id_str) !== false) {
                    continue; // Skip messages deleted for this user
                }
                $messages[] = $row;
            }
            mysqli_stmt_close($stmt);
            
            $response = ['success' => true, 'messages' => $messages];
        } else {
            $response = ['success' => false, 'message' => 'Invalid ticket ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== SEND CHAT MESSAGE =====
    if ($_POST['ajax_action'] === 'send_message') {
        $ticket_id = intval($_POST['ticket_id']);
        $message = trim($_POST['message'] ?? '');
        $message_type = $_POST['message_type'] ?? 'text';
        
        if ($ticket_id > 0 && (!empty($message) || $message_type !== 'text')) {
            $create_table = "CREATE TABLE IF NOT EXISTS chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NOT NULL,
                sender_type ENUM('admin', 'client') NOT NULL,
                sender_id INT NOT NULL,
                message_type ENUM('text', 'file', 'voice') DEFAULT 'text',
                message TEXT,
                file_path VARCHAR(255),
                file_name VARCHAR(255),
                file_size INT,
                mime_type VARCHAR(100),
                is_deleted TINYINT(1) DEFAULT 0,
                deleted_for VARCHAR(255) DEFAULT NULL,
                deleted_by INT DEFAULT NULL,
                deleted_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ticket_id (ticket_id),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            mysqli_query($conn, $create_table);
            
            $update_sql = "UPDATE support_tickets SET status = 'In Progress' WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $ticket_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            
            $sql = "INSERT INTO chat_messages (ticket_id, sender_type, sender_id, message_type, message) 
                    VALUES (?, 'admin', ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiss", $ticket_id, $user_id, $message_type, $message);
            
            if (mysqli_stmt_execute($stmt)) {
                $message_id = mysqli_insert_id($conn);
                
                // Get client username for backup
                $username_query = "SELECT u.username FROM support_tickets t JOIN clients c ON t.client_id = c.id JOIN users u ON c.user_id = u.id WHERE t.id = ?";
                $user_stmt = mysqli_prepare($conn, $username_query);
                mysqli_stmt_bind_param($user_stmt, "i", $ticket_id);
                mysqli_stmt_execute($user_stmt);
                $user_result = mysqli_stmt_get_result($user_stmt);
                $user_row = mysqli_fetch_assoc($user_result);
                if ($user_row) {
                    // Backup the message with timestamp
                    $created_at = date('Y-m-d H:i:s');
                    backupConversation($user_row['username'], 'admin', $message, $created_at);
                }
                mysqli_stmt_close($user_stmt);
                
                $response = ['success' => true, 'message' => 'Message sent', 'message_id' => $message_id];
            } else {
                $response = ['success' => false, 'message' => 'Failed to send message: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== DELETE INDIVIDUAL MESSAGE =====
    if ($_POST['ajax_action'] === 'delete_message') {
        $message_id = intval($_POST['message_id']);
        $ticket_id = intval($_POST['ticket_id']);
        $delete_type = $_POST['delete_type'] ?? 'for_me';
        
        if ($message_id > 0 && $ticket_id > 0) {
            if ($delete_type === 'for_everyone') {
                $sql = "UPDATE chat_messages SET 
                        is_deleted = 1, 
                        deleted_by = ?, 
                        deleted_at = NOW(),
                        message = 'This message was deleted'
                        WHERE id = ? AND ticket_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iii", $user_id, $message_id, $ticket_id);
            } else {
                // Delete for me - store who deleted it
                $sql = "UPDATE chat_messages SET 
                        deleted_for = CONCAT(IFNULL(deleted_for, ''), ?, ','),
                        deleted_by = ?,
                        deleted_at = NOW()
                        WHERE id = ? AND ticket_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                $user_id_str = $user_id . ',';
                mysqli_stmt_bind_param($stmt, "siii", $user_id_str, $user_id, $message_id, $ticket_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Message deleted successfully', 'message_id' => $message_id];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete message: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid message ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== CLEAR ENTIRE CHAT =====
    if ($_POST['ajax_action'] === 'clear_chat') {
        $ticket_id = intval($_POST['ticket_id']);
        
        if ($ticket_id > 0) {
            // Get all file paths to delete
            $get_files_sql = "SELECT file_path FROM chat_messages WHERE ticket_id = ? AND file_path IS NOT NULL";
            $get_files_stmt = mysqli_prepare($conn, $get_files_sql);
            mysqli_stmt_bind_param($get_files_stmt, "i", $ticket_id);
            mysqli_stmt_execute($get_files_stmt);
            $files_result = mysqli_stmt_get_result($get_files_stmt);
            
            while ($file_row = mysqli_fetch_assoc($files_result)) {
                $file_path = __DIR__ . '/../' . $file_row['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            mysqli_stmt_close($get_files_stmt);
            
            // Delete all messages for this ticket
            $delete_sql = "DELETE FROM chat_messages WHERE ticket_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $ticket_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $response = ['success' => true, 'message' => 'Chat cleared successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to clear chat: ' . mysqli_error($conn)];
            }
            mysqli_stmt_close($delete_stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid ticket ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== FILE UPLOAD =====
    if ($_POST['ajax_action'] === 'upload_file') {
        $ticket_id = intval($_POST['ticket_id']);
        
        if ($ticket_id > 0 && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/chat_files/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file = $_FILES['file'];
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $file_path = 'uploads/chat_files/' . $file_name;
            $full_path = $upload_dir . $file_name;
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 
                             'application/pdf', 'application/msword', 
                             'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                             'application/zip', 'application/x-zip-compressed',
                             'text/plain', 'audio/mpeg', 'audio/wav', 'audio/ogg'];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $response = ['success' => false, 'message' => 'File type not allowed'];
                echo json_encode($response);
                exit();
            }
            
            if ($file['size'] > 20 * 1024 * 1024) {
                $response = ['success' => false, 'message' => 'File too large (max 20MB)'];
                echo json_encode($response);
                exit();
            }
            
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                $create_table = "CREATE TABLE IF NOT EXISTS chat_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ticket_id INT NOT NULL,
                    sender_type ENUM('admin', 'client') NOT NULL,
                    sender_id INT NOT NULL,
                    message_type ENUM('text', 'file', 'voice') DEFAULT 'text',
                    message TEXT,
                    file_path VARCHAR(255),
                    file_name VARCHAR(255),
                    file_size INT,
                    mime_type VARCHAR(100),
                    is_deleted TINYINT(1) DEFAULT 0,
                    deleted_for VARCHAR(255) DEFAULT NULL,
                    deleted_by INT DEFAULT NULL,
                    deleted_at DATETIME DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ticket_id (ticket_id),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                mysqli_query($conn, $create_table);
                
                $update_sql = "UPDATE support_tickets SET status = 'In Progress' WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "i", $ticket_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                $sql = "INSERT INTO chat_messages (ticket_id, sender_type, sender_id, message_type, 
                        message, file_path, file_name, file_size, mime_type) 
                        VALUES (?, 'admin', ?, 'file', ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                $message = "File uploaded: " . $file['name'];
                $file_size = $file['size'];
                mysqli_stmt_bind_param($stmt, "iisssis", $ticket_id, $user_id, $message, $file_path, $file_name, $file_size, $mime_type);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Get client username for backup
                    $username_query = "SELECT u.username FROM support_tickets t JOIN clients c ON t.client_id = c.id JOIN users u ON c.user_id = u.id WHERE t.id = ?";
                    $user_stmt = mysqli_prepare($conn, $username_query);
                    mysqli_stmt_bind_param($user_stmt, "i", $ticket_id);
                    mysqli_stmt_execute($user_stmt);
                    $user_result = mysqli_stmt_get_result($user_stmt);
                    $user_row = mysqli_fetch_assoc($user_result);
                    if ($user_row) {
                        backupConversation($user_row['username'], 'admin', '[File uploaded: ' . $file['name'] . ']', date('Y-m-d H:i:s'));
                    }
                    mysqli_stmt_close($user_stmt);
                    
                    $response = ['success' => true, 'message' => 'File uploaded successfully', 'file_path' => $file_path];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to save file info: ' . mysqli_error($conn)];
                }
                mysqli_stmt_close($stmt);
            } else {
                $response = ['success' => false, 'message' => 'Failed to upload file'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid request'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== VOICE UPLOAD =====
    if ($_POST['ajax_action'] === 'upload_voice') {
        $ticket_id = intval($_POST['ticket_id']);
        
        if ($ticket_id > 0 && isset($_FILES['voice']) && $_FILES['voice']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/voice_messages/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file = $_FILES['voice'];
            $file_name = time() . '_voice_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $file_path = 'admin-portal/voice_messages/' . $file_name;
            $full_path = $upload_dir . $file_name;
            
            // Validate file type - support multiple audio formats
            $allowed_types = ['audio/webm', 'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            // Also accept video/webm as it's common for voice recording
            if ($mime_type === 'video/webm') {
                $mime_type = 'audio/webm';
            }
            
            if (!in_array($mime_type, $allowed_types)) {
                $response = ['success' => false, 'message' => 'Voice format not supported. Please use webm, mp3, wav, ogg, or mp4.'];
                echo json_encode($response);
                exit();
            }
            
            if ($file['size'] > 10 * 1024 * 1024) {
                $response = ['success' => false, 'message' => 'Voice message too large (max 10MB)'];
                echo json_encode($response);
                exit();
            }
            
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                $create_table = "CREATE TABLE IF NOT EXISTS chat_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ticket_id INT NOT NULL,
                    sender_type ENUM('admin', 'client') NOT NULL,
                    sender_id INT NOT NULL,
                    message_type ENUM('text', 'file', 'voice') DEFAULT 'text',
                    message TEXT,
                    file_path VARCHAR(255),
                    file_name VARCHAR(255),
                    file_size INT,
                    mime_type VARCHAR(100),
                    is_deleted TINYINT(1) DEFAULT 0,
                    deleted_for VARCHAR(255) DEFAULT NULL,
                    deleted_by INT DEFAULT NULL,
                    deleted_at DATETIME DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ticket_id (ticket_id),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                mysqli_query($conn, $create_table);
                
                $update_sql = "UPDATE support_tickets SET status = 'In Progress' WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "i", $ticket_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                $sql = "INSERT INTO chat_messages (ticket_id, sender_type, sender_id, message_type, 
                        message, file_path, file_name, file_size, mime_type) 
                        VALUES (?, 'admin', ?, 'voice', ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                $message = "Voice message";
                $file_size = $file['size'];
                mysqli_stmt_bind_param($stmt, "iisssis", $ticket_id, $user_id, $message, $file_path, $file_name, $file_size, $mime_type);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Get client username for backup
                    $username_query = "SELECT u.username FROM support_tickets t JOIN clients c ON t.client_id = c.id JOIN users u ON c.user_id = u.id WHERE t.id = ?";
                    $user_stmt = mysqli_prepare($conn, $username_query);
                    mysqli_stmt_bind_param($user_stmt, "i", $ticket_id);
                    mysqli_stmt_execute($user_stmt);
                    $user_result = mysqli_stmt_get_result($user_stmt);
                    $user_row = mysqli_fetch_assoc($user_result);
                    if ($user_row) {
                        backupConversation($user_row['username'], 'admin', '[Voice message]', date('Y-m-d H:i:s'));
                    }
                    mysqli_stmt_close($user_stmt);
                    
                    $response = ['success' => true, 'message' => 'Voice message uploaded', 'file_path' => $file_path];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to save voice message: ' . mysqli_error($conn)];
                }
                mysqli_stmt_close($stmt);
            } else {
                $response = ['success' => false, 'message' => 'Failed to upload voice'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid request'];
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
                // Get client username for backup
                $username_query = "SELECT u.username FROM support_tickets t JOIN clients c ON t.client_id = c.id JOIN users u ON c.user_id = u.id WHERE t.id = ?";
                $user_stmt = mysqli_prepare($conn, $username_query);
                mysqli_stmt_bind_param($user_stmt, "i", $ticket_id);
                mysqli_stmt_execute($user_stmt);
                $user_result = mysqli_stmt_get_result($user_stmt);
                $user_row = mysqli_fetch_assoc($user_result);
                if ($user_row) {
                    backupConversation($user_row['username'], 'admin', '[Ticket Resolved]', date('Y-m-d H:i:s'));
                }
                mysqli_stmt_close($user_stmt);
                
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
            // Get all file paths to delete
            $get_files_sql = "SELECT file_path FROM chat_messages WHERE ticket_id = ? AND file_path IS NOT NULL";
            $get_files_stmt = mysqli_prepare($conn, $get_files_sql);
            mysqli_stmt_bind_param($get_files_stmt, "i", $ticket_id);
            mysqli_stmt_execute($get_files_stmt);
            $files_result = mysqli_stmt_get_result($get_files_stmt);
            
            while ($file_row = mysqli_fetch_assoc($files_result)) {
                $file_path = __DIR__ . '/../' . $file_row['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            mysqli_stmt_close($get_files_stmt);
            
            $del_messages = "DELETE FROM chat_messages WHERE ticket_id = ?";
            $stmt_messages = mysqli_prepare($conn, $del_messages);
            mysqli_stmt_bind_param($stmt_messages, "i", $ticket_id);
            mysqli_stmt_execute($stmt_messages);
            mysqli_stmt_close($stmt_messages);
            
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
    <title>Admin Portal | HIFI Marketing - Tickets</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" href="../images/fav-icon.png" type="image/png" />
    <style>
        /* ===== ROOT VARIABLES ===== */
        :root {
            --primary: #4a5cf5;
            --primary-dark: #3a4be0;
            --primary-light: #e8edfe;
            --primary-gradient: linear-gradient(135deg, #4a5cf5 0%, #6c7aff 100%);
            --bg: #f0f4f8;
            --card-bg: #ffffff;
            --text-primary: #1a1c2e;
            --text-secondary: #4a5568;
            --text-muted: #a0aec0;
            --border: #e2e8f0;
            --border-light: #f0f4f8;
            --radius: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.06);
            --shadow-lg: 0 8px 40px rgba(0,0,0,0.08);
            --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }

        /* ===== SCROLLBAR STYLING ===== */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 20px; }
        ::-webkit-scrollbar-thumb:hover { background: #a0aec0; }

        /* ===== HEADER ===== */
        header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .logo {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-primary);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo span { color: var(--primary); }
        .logo .brand-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-gradient);
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
            padding: 8px 16px;
            border-radius: 8px;
            transition: var(--transition);
        }
        .desktop-nav .nav-link:hover {
            color: var(--primary);
            background: var(--primary-light);
        }
        .desktop-nav .nav-link.active {
            color: var(--primary);
            background: var(--primary-light);
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
        .mobile-menu-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 6px); }
        .mobile-menu-toggle.active span:nth-child(2) { opacity: 0; }
        .mobile-menu-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -6px); }
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
        .mobile-nav .mobile-user .user-info .role i { color: var(--primary); }
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
            background: var(--primary-light);
            color: var(--primary);
        }
        .mobile-nav .mobile-links a.active {
            background: var(--primary-light);
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
        .mobile-nav .mobile-footer .logout-btn:hover { background: #fee2e2; }

        /* ===== HEADER ACTIONS ===== */
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
        .header-actions .action-btn:first-child,
        .header-actions .action-btn:nth-child(2) {
            display: none !important;
        }

        /* ===== MAIN LAYOUT ===== */
        .main-layout {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
            gap: 24px;
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
            box-shadow: var(--shadow-sm);
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
            background: var(--primary-gradient);
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
        .sidebar-footer .logout-link:hover { background: #fee2e2; }

        /* ===== CONTENT ===== */
        .content { flex: 1; min-width: 0; }

        /* ===== BANNER ===== */
        .banner {
            background: var(--primary-gradient);
            border-radius: var(--radius);
            padding: 20px 24px;
            color: #fff;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: var(--shadow-md);
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
            backdrop-filter: blur(4px);
        }
        .banner .banner-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .banner .banner-actions .btn-white {
            background: #fff;
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 12px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        .banner .banner-actions .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        /* ===== CARDS ===== */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 18px 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 18px;
            transition: var(--transition);
        }
        .card:hover { box-shadow: var(--shadow-md); }
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

        /* ===== STATUS BADGE ===== */
        .status-badge {
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.open { background: #fee2e2; color: #dc3545; }
        .status-badge.in-progress { background: var(--primary-light); color: var(--primary); }
        .status-badge.resolved { background: #d1fae5; color: #065f46; }

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
            box-shadow: var(--shadow-md);
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

        /* ===== CLIENT SELECTOR ===== */
        .client-selector { margin-bottom: 16px; }
        .client-selector select {
            width: 100%;
            max-width: 400px;
            padding: 10px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: var(--card-bg);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            cursor: pointer;
            transition: var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%238a94a0' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }
        .client-selector select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74,92,245,0.1);
        }
        .client-selector select option { padding: 8px; }

        /* ===== MODERN CHAT UI ===== */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 580px;
            border-radius: var(--radius);
            background: #f7fafc;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .chat-header {
            background: var(--card-bg);
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }
        .chat-header .chat-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
            flex-shrink: 0;
        }
        .chat-header .chat-info { flex: 1; }
        .chat-header .chat-title {
            font-weight: 700;
            font-size: 15px;
            color: var(--text-primary);
        }
        .chat-header .chat-status {
            font-size: 12px;
            color: var(--text-muted);
        }
        .chat-header .chat-status.online { color: var(--success); }
        .chat-header .back-btn {
            color: var(--text-secondary);
            background: var(--primary-light);
            border: none;
            font-size: 16px;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 8px;
            transition: var(--transition);
        }
        .chat-header .back-btn:hover {
            background: var(--primary);
            color: #fff;
        }
        .chat-header .header-actions {
            display: flex;
            gap: 6px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: #f7fafc;
        }

        .date-divider {
            text-align: center;
            font-size: 11px;
            color: var(--text-muted);
            padding: 8px 0;
            margin: 4px 0;
        }
        .date-divider span {
            background: var(--card-bg);
            padding: 4px 16px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            font-weight: 500;
        }

        .message-wrapper {
            display: flex;
            flex-direction: column;
            margin-bottom: 4px;
            position: relative;
            animation: fadeIn 0.2s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-wrapper.admin { align-self: flex-end; align-items: flex-end; }
        .message-wrapper.client { align-self: flex-start; align-items: flex-start; }

        .message-wrapper .message-bubble {
            max-width: 72%;
            padding: 8px 14px 8px 14px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.5;
            position: relative;
            word-wrap: break-word;
            box-shadow: var(--shadow-sm);
            display: flex;
            gap: 10px;
            align-items: flex-start;
            transition: var(--transition);
        }
        .message-wrapper .message-bubble:hover { box-shadow: var(--shadow-md); }

        .message-wrapper.admin .message-bubble {
            background: var(--primary);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .message-wrapper.admin .message-bubble .msg-time {
            color: rgba(255,255,255,0.7);
        }
        .message-wrapper.admin .message-bubble .msg-file a {
            color: #fff;
        }
        .message-wrapper.admin .message-bubble .msg-file {
            background: rgba(255,255,255,0.1);
        }

        .message-wrapper.client .message-bubble {
            background: var(--card-bg);
            color: var(--text-primary);
            border-bottom-left-radius: 4px;
            border: 1px solid var(--border-light);
        }
        .message-wrapper.client .message-bubble .msg-time {
            color: var(--text-muted);
        }

        .message-wrapper .message-bubble .msg-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            margin-top: 2px;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .message-wrapper.admin .message-bubble .msg-avatar {
            border-color: rgba(255,255,255,0.3);
        }
        .message-wrapper.client .message-bubble .msg-avatar {
            border-color: var(--border);
        }

        .message-wrapper .message-bubble .msg-content { flex: 1; min-width: 0; }
        .message-wrapper .message-bubble .msg-time {
            font-size: 10px;
            margin-top: 4px;
            text-align: right;
            display: flex;
            align-items: center;
            gap: 4px;
            justify-content: flex-end;
        }

        .message-wrapper .message-bubble .msg-sender {
            font-size: 10px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 2px;
        }
        .message-wrapper.admin .message-bubble .msg-sender {
            color: rgba(255,255,255,0.8);
        }

        .message-wrapper .message-bubble .msg-file {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            background: rgba(0,0,0,0.04);
            border-radius: 8px;
            margin-top: 4px;
        }
        .message-wrapper.client .message-bubble .msg-file {
            background: var(--bg);
        }
        .message-wrapper .message-bubble .msg-file a {
            font-weight: 600;
            font-size: 12px;
            text-decoration: underline;
            transition: var(--transition);
        }
        .message-wrapper.admin .message-bubble .msg-file a {
            color: #fff;
        }
        .message-wrapper.client .message-bubble .msg-file a {
            color: var(--primary);
        }
        .message-wrapper .message-bubble .msg-file a:hover { opacity: 0.8; }

        .message-wrapper .message-bubble .msg-image {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 4px;
            cursor: pointer;
            display: block;
            transition: var(--transition);
        }
        .message-wrapper .message-bubble .msg-image:hover {
            opacity: 0.9;
            transform: scale(1.01);
        }

        .message-wrapper .message-bubble .voice-player { margin-top: 4px; }
        .message-wrapper .message-bubble .voice-player audio {
            width: 170px;
            height: 34px;
            border-radius: 20px;
        }

        .message-wrapper .message-bubble .msg-deleted {
            font-style: italic;
            color: var(--text-muted);
            opacity: 0.7;
        }
        .message-wrapper.admin .message-bubble .msg-deleted {
            color: rgba(255,255,255,0.7);
        }

        .message-wrapper .message-bubble .msg-actions {
            display: none;
            position: absolute;
            bottom: 2px;
            right: 2px;
            gap: 2px;
            background: rgba(0,0,0,0.06);
            border-radius: 6px;
            padding: 2px;
            backdrop-filter: blur(4px);
        }
        .message-wrapper:hover .message-bubble .msg-actions { display: flex; }
        .message-wrapper .message-bubble .msg-actions .msg-action-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 11px;
            cursor: pointer;
            padding: 4px 6px;
            border-radius: 4px;
            transition: var(--transition);
        }
        .message-wrapper .message-bubble .msg-actions .msg-action-btn:hover {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }
        .message-wrapper.admin .message-bubble .msg-actions .msg-action-btn {
            color: rgba(255,255,255,0.7);
        }
        .message-wrapper.admin .message-bubble .msg-actions .msg-action-btn:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        .typing-indicator {
            display: none;
            align-self: flex-start;
            padding: 8px 16px;
            background: var(--card-bg);
            border-radius: 14px;
            border-bottom-left-radius: 4px;
            font-size: 12px;
            color: var(--text-muted);
            box-shadow: var(--shadow-sm);
            gap: 4px;
            border: 1px solid var(--border-light);
        }
        .typing-indicator.active { display: flex; }
        .typing-indicator .dot {
            width: 6px;
            height: 6px;
            background: var(--text-muted);
            border-radius: 50%;
            animation: typingDot 1.4s infinite;
        }
        .typing-indicator .dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator .dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingDot {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-6px); }
        }

        .chat-input-area {
            padding: 10px 16px;
            background: var(--card-bg);
            border-top: 1px solid var(--border);
            display: flex;
            gap: 8px;
            align-items: flex-end;
            flex-shrink: 0;
        }
        .chat-input-area .input-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            background: var(--bg);
            border-radius: 24px;
            padding: 4px 14px;
            gap: 6px;
            border: 2px solid transparent;
            transition: var(--transition);
        }
        .chat-input-area .input-wrapper:focus-within {
            border-color: var(--primary);
            background: var(--card-bg);
            box-shadow: 0 0 0 4px rgba(74,92,245,0.08);
        }
        .chat-input-area .input-wrapper textarea {
            flex: 1;
            border: none;
            background: transparent;
            padding: 8px 0;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            resize: none;
            min-height: 36px;
            max-height: 80px;
            outline: none;
            color: var(--text-primary);
        }
        .chat-input-area .input-wrapper .attach-btn,
        .chat-input-area .input-wrapper .voice-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            padding: 6px 6px;
            transition: var(--transition);
            border-radius: 50%;
        }
        .chat-input-area .input-wrapper .attach-btn:hover,
        .chat-input-area .input-wrapper .voice-btn:hover {
            color: var(--primary);
            background: var(--primary-light);
        }
        .chat-input-area .send-btn {
            background: var(--primary);
            color: #fff;
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(74,92,245,0.3);
        }
        .chat-input-area .send-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
            box-shadow: 0 4px 16px rgba(74,92,245,0.4);
        }
        .chat-input-area .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .chat-input-area .send-btn.recording {
            background: #dc3545;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220,53,69,0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(220,53,69,0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220,53,69,0); }
        }
        .hidden-file-input { display: none; }

        .btn-sm {
            padding: 4px 12px;
            border-radius: 6px;
            border: none;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-sm.view {
            background: var(--primary-light);
            color: var(--primary);
        }
        .btn-sm.view:hover {
            background: var(--primary);
            color: #fff;
        }
        .btn-sm.delete {
            background: #fee2e2;
            color: #dc3545;
        }
        .btn-sm.delete:hover {
            background: #dc3545;
            color: #fff;
        }
        .btn-sm.resolve {
            background: #d1fae5;
            color: #065f46;
        }
        .btn-sm.resolve:hover {
            background: #065f46;
            color: #fff;
        }
        .btn-sm.clear-chat {
            background: #fef3c7;
            color: #92400e;
        }
        .btn-sm.clear-chat:hover {
            background: #92400e;
            color: #fff;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            background: var(--primary-light);
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
            max-width: 480px;
            width: 100%;
            padding: 24px 28px;
            box-shadow: var(--shadow-lg);
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
            background: var(--bg);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            margin-bottom: 12px;
        }
        .modal input:focus, .modal select:focus, .modal textarea:focus {
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

        .delete-options {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        .delete-options button {
            flex: 1;
            min-width: 100px;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
        }
        .delete-options .btn-for-me {
            background: var(--primary-light);
            color: var(--primary);
        }
        .delete-options .btn-for-me:hover {
            background: var(--primary);
            color: #fff;
        }
        .delete-options .btn-for-everyone {
            background: #fee2e2;
            color: #dc3545;
        }
        .delete-options .btn-for-everyone:hover {
            background: #dc3545;
            color: #fff;
        }
        .delete-options .btn-cancel {
            background: var(--bg);
            color: var(--text-secondary);
        }
        .delete-options .btn-cancel:hover {
            background: #e2e8f0;
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
            box-shadow: var(--shadow-lg);
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 260px;
            animation: slideIn 0.3s ease;
        }
        .toast.success i { color: var(--success); }
        .toast.error i { color: var(--danger); }
        .toast.warning i { color: var(--warning); }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 40px;
            color: #d0d7e0;
            margin-bottom: 10px;
            display: block;
        }
        .empty-state h4 {
            font-size: 15px;
            color: var(--text-primary);
            margin-bottom: 3px;
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

        @media (max-width: 1200px) {
            .chat-container { height: 520px; }
        }

        @media (max-width: 992px) {
            .desktop-nav { display: none; }
            .mobile-menu-toggle { display: flex; }
            .header-actions .user-badge .name { display: none; }
            .main-layout { padding: 16px; gap: 16px; }
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-layout { padding: 12px; flex-direction: column; }
            .banner {
                padding: 16px 18px;
                flex-direction: column;
                text-align: center;
            }
            .banner h2 { font-size: 16px; }
            .header-actions .action-btn { padding: 4px 8px; font-size: 13px; }
            .modal { padding: 20px; }
            .header-inner { padding: 0 12px; }
            .logo { font-size: 17px; }
            .logo .brand-icon { width: 30px; height: 30px; font-size: 13px; }
            .chat-container { height: 440px; border-radius: 12px; }
            .chat-messages { padding: 12px 14px 6px; }
            .message-wrapper .message-bubble { max-width: 88%; }
            .message-wrapper .message-bubble .msg-image { max-width: 150px; }
            .message-wrapper .message-bubble .voice-player audio { width: 140px; }
            .banner .banner-actions .btn-white { width: 100%; text-align: center; }
            .chat-header { padding: 10px 14px; }
            .chat-input-area { padding: 8px 12px; }
            .client-selector select { max-width: 100%; }
            .delete-options button { min-width: 80px; font-size: 11px; padding: 6px; }
            .ticket-item { padding: 12px 14px; }
            .ticket-item .ticket-title { font-size: 13px; }
            .card { padding: 14px 16px; }
        }

        @media (max-width: 480px) {
            .header-actions .action-btn { font-size: 12px; padding: 4px 6px; }
            .header-actions .user-badge { padding: 2px 8px 2px 2px; font-size: 11px; }
            .header-actions .user-badge img { width: 24px; height: 24px; }
            .mobile-nav { width: 280px; }
            .chat-container { height: 380px; }
            .chat-messages .message-wrapper .message-bubble .msg-avatar { width: 24px; height: 24px; }
            .delete-options { flex-direction: column; }
            .delete-options button { min-width: auto; }
            .message-wrapper .message-bubble { padding: 6px 10px; font-size: 12px; }
            .message-wrapper .message-bubble .msg-time { font-size: 9px; }
            .chat-input-area .input-wrapper { padding: 2px 10px; }
            .chat-input-area .send-btn { width: 36px; height: 36px; font-size: 14px; }
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
                <a href="tickets.php" class="nav-link active"><i class="fas fa-headset"></i> Tickets</a>
                <a href="verbal.php" class="nav-link"><i class="fas fa-phone"></i> Verbal</a>
                <a href="progress-sync.php" class="nav-link"><i class="fas fa-sliders-h"></i> Sync</a>
            </nav>
            <div class="header-actions">
                <div class="user-badge">
                    <img src="<?php echo $admin_dp_url; ?>" alt="Avatar" onerror="this.src='dps/default-admin.png'">
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
            <img src="<?php echo $admin_dp_url; ?>" alt="Avatar" onerror="this.src='dps/default-admin.png'">
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
                <span class="role">Admin</span>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i><span class="sidebar-text">Operations Desk</span></a>
                <a href="deliverables.php" class="sidebar-link"><i class="fas fa-check-square"></i><span class="sidebar-text">Manage Deliverables</span></a>
                <a href="tickets.php" class="sidebar-link active"><i class="fas fa-headset"></i><span class="sidebar-text">Client Tickets & Tasks</span></a>
                <a href="verbal.php" class="sidebar-link"><i class="fas fa-phone"></i><span class="sidebar-text">Client Verbal Requests</span></a>
                <a href="progress-sync.php" class="sidebar-link"><i class="fas fa-sliders-h"></i><span class="sidebar-text">Progress Counter Sync</span></a>
                <a href="pm-ad-campaigns.php" class="sidebar-link"><i class="fas fa-bullhorn"></i><span class="sidebar-text">Ad Campaigns</span></a>
                <a href="service-packages.php" class="sidebar-link"><i class="fas fa-boxes"></i><span class="sidebar-text">Service Packages</span></a>
                <a href="pm-billing.php" class="sidebar-link"><i class="fas fa-credit-card"></i><span class="sidebar-text">Billing Management</span></a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <img src="<?php echo $admin_dp_url; ?>" alt="Avatar" onerror="this.src='dps/default-admin.png'">
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
            
            <!-- ===== TICKET DETAIL VIEW WITH CHAT ===== -->
            <button class="btn-back" onclick="window.location.href='tickets.php<?php echo $filter_client_id > 0 ? '?client_id=' . $filter_client_id : ''; ?>'">
                <i class="fas fa-arrow-left"></i> Back to Tickets
            </button>

            <div class="card" style="padding:0;overflow:hidden;">
                <div class="chat-container" id="chatContainer">
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <button class="back-btn" onclick="window.location.href='tickets.php<?php echo $filter_client_id > 0 ? '?client_id=' . $filter_client_id : ''; ?>'">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <img src="dps/<?php echo $client_dp; ?>" class="chat-avatar" alt="<?php echo htmlspecialchars($view_ticket['client_name']); ?>" onerror="this.src='dps/default-client.png'">
                        <div class="chat-info">
                            <div class="chat-title"><?php echo htmlspecialchars($view_ticket['client_name']); ?></div>
                            <div class="chat-status <?php echo $view_ticket['status'] !== 'Resolved' ? 'online' : ''; ?>">
                                <?php echo $view_ticket['status'] !== 'Resolved' ? '● Online' : '● Resolved'; ?>
                            </div>
                        </div>
                        <div class="header-actions" style="display:flex;gap:6px;">
                            <?php if ($view_ticket['status'] !== 'Resolved'): ?>
                            <button class="btn-sm resolve" onclick="resolveTicket(<?php echo $view_ticket['id']; ?>)">
                                <i class="fas fa-check"></i> Resolve
                            </button>
                            <?php endif; ?>
                            <button class="btn-sm clear-chat" onclick="clearChat(<?php echo $view_ticket['id']; ?>)">
                                <i class="fas fa-eraser"></i> Clear
                            </button>
                            <button class="btn-sm delete" onclick="deleteTicket(<?php echo $view_ticket['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Messages -->
                    <div class="chat-messages" id="chatMessages">
                        <div id="messageContainer"></div>
                        <div class="typing-indicator" id="typingIndicator">
                            <span>Client is typing</span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                    </div>
                    
                    <!-- Chat Input -->
                    <?php if ($view_ticket['status'] !== 'Resolved'): ?>
                    <div class="chat-input-area">
                        <div class="input-wrapper">
                            <button class="attach-btn" onclick="document.getElementById('fileInput').click()" title="Attach file">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="file" id="fileInput" class="hidden-file-input" onchange="handleFileUpload(this, <?php echo $view_ticket['id']; ?>)">
                            <textarea id="chatInput" rows="1" placeholder="Type a message..." onkeydown="handleKeyPress(event, <?php echo $view_ticket['id']; ?>)" oninput="autoResize(this)"></textarea>
                            <button class="voice-btn" id="voiceBtn" onclick="toggleRecording(<?php echo $view_ticket['id']; ?>)" title="Voice message">
                                <i class="fas fa-microphone"></i>
                            </button>
                        </div>
                        <button class="send-btn" id="sendBtn" onclick="sendMessage(<?php echo $view_ticket['id']; ?>)">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <?php else: ?>
                    <div style="background:#d1fae5;padding:12px 16px;border-top:1px solid var(--border);text-align:center;color:#065f46;font-size:13px;font-weight:600;">
                        <i class="fas fa-check-circle"></i> This ticket has been <strong>Resolved</strong>. No further messages allowed.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>

            <!-- ===== CLIENT SELECTOR ===== -->
            <div class="client-selector">
                <select id="clientFilter" onchange="filterByClient(this.value)">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>" <?php echo $filter_client_id == $client['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ===== TICKETS LIST ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-headset" style="color:var(--primary);"></i> Client Action Requests &amp; Tickets</h3>
                    <span class="sub"><?php echo count($tickets); ?> total tickets</span>
                </div>
                <?php if (!empty($tickets)): ?>
                    <?php foreach ($tickets as $req): ?>
                    <div class="ticket-item" onclick="window.location.href='tickets.php?ticket_id=<?php echo $req['id']; ?><?php echo $filter_client_id > 0 ? '&client_id=' . $filter_client_id : ''; ?>'">
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
                            <span style="color:var(--success);"><i class="fas fa-check-circle"></i> Client replied</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No tickets available. <?php echo $filter_client_id > 0 ? 'Try selecting a different client.' : ''; ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php endif; ?>

        </div>
    </div>

    <!-- ===== DELETE MESSAGE MODAL ===== -->
    <div class="modal-overlay" id="deleteMessageModal">
        <div class="modal" style="max-width:400px;">
            <button class="modal-close" onclick="closeModal('deleteMessageModal')"><i class="fas fa-times"></i></button>
            <h3 style="font-size:16px;">Delete Message</h3>
            <p class="modal-sub" style="margin-top:4px;">Choose how you want to delete this message.</p>
            <div class="delete-options">
                <button class="btn-for-me" onclick="confirmDeleteMessage('for_me')">
                    <i class="fas fa-user"></i> Delete for me
                </button>
                <button class="btn-for-everyone" onclick="confirmDeleteMessage('for_everyone')">
                    <i class="fas fa-users"></i> Delete for everyone
                </button>
                <button class="btn-cancel" onclick="closeModal('deleteMessageModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- ===== TOAST CONTAINER ===== -->
    <div class="toast-container" id="toast-container"></div>

    <!-- ===== SECURITY BADGE ===== -->
    <div class="security-badge">🔒 Secure Session • <?php echo $_SERVER['REMOTE_ADDR']; ?></div>

    <script>
        // =============================================
        // SIDEBAR & MOBILE FUNCTIONS
        // =============================================
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

        // =============================================
        // MODAL FUNCTIONS
        // =============================================
        let deleteMessageId = null;
        let deleteTicketId = null;

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

        // =============================================
        // CLIENT FILTER
        // =============================================
        function filterByClient(clientId) {
            if (clientId) {
                window.location.href = 'tickets.php?client_id=' + clientId;
            } else {
                window.location.href = 'tickets.php';
            }
        }

        // =============================================
        // TOAST NOTIFICATIONS
        // =============================================
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-triangle-exclamation';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => { 
                toast.style.opacity = '0'; 
                toast.style.transform = 'translateX(100%)'; 
                setTimeout(() => toast.remove(), 300); 
            }, 3500);
        }

        // =============================================
        // RESOLVE TICKET
        // =============================================
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

        // =============================================
        // DELETE TICKET
        // =============================================
        function deleteTicket(ticketId) {
            if (!confirm('Are you sure you want to delete this ticket? All messages and files will be permanently deleted.')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_ticket');
            formData.append('ticket_id', ticketId);
            
            showToast('Deleting ticket...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Ticket deleted successfully!');
                    setTimeout(() => window.location.href = 'tickets.php<?php echo $filter_client_id > 0 ? '?client_id=' . $filter_client_id : ''; ?>', 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting ticket.', 'error');
            });
        }

        // =============================================
        // CLEAR CHAT
        // =============================================
        function clearChat(ticketId) {
            if (!confirm('Are you sure you want to clear all messages in this chat? This action cannot be undone.')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'clear_chat');
            formData.append('ticket_id', ticketId);
            
            showToast('Clearing chat...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Chat cleared successfully!');
                    document.getElementById('messageContainer').innerHTML = '';
                    const emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state';
                    emptyMsg.style.padding = '30px 20px';
                    emptyMsg.innerHTML = '<i class="fas fa-comment-slash"></i><p>No messages. Start the conversation!</p>';
                    document.getElementById('messageContainer').appendChild(emptyMsg);
                    lastMessageId = 0;
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error clearing chat.', 'error');
            });
        }

                // =============================================
        // CHAT FUNCTIONS
        // =============================================
        let lastMessageId = 0;
        let pollInterval = null;
        let isRecording = false;
        let mediaRecorder = null;
        let audioChunks = [];
        const adminDp = '<?php echo $admin_dp_url; ?>';
        const clientDp = 'dps/<?php echo $client_dp; ?>';

        <?php if ($view_ticket_id > 0 && $view_ticket): ?>
        // Load messages
        function loadMessages() {
            const formData = new FormData();
            formData.append('ajax_action', 'get_messages');
            formData.append('ticket_id', <?php echo $view_ticket['id']; ?>);
            formData.append('last_id', lastMessageId);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages && data.messages.length > 0) {
                    const container = document.getElementById('messageContainer');
                    const emptyState = container.querySelector('.empty-state');
                    if (emptyState) emptyState.remove();
                    
                    let lastId = lastMessageId;
                    data.messages.forEach(msg => {
                        appendMessage(msg);
                        if (msg.id > lastId) lastId = msg.id;
                    });
                    lastMessageId = lastId;
                    scrollToBottom();
                }
            })
            .catch(error => console.error('Error loading messages:', error));
        }

        // Format date divider
        function formatDateDivider(dateStr) {
            const date = new Date(dateStr);
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            
            if (date.toDateString() === today.toDateString()) return 'Today';
            if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
            return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
        }

        // Get date string for comparison
        function getDateString(dateStr) {
            const date = new Date(dateStr);
            return date.toDateString();
        }

        // Append message to chat
        function appendMessage(msg) {
            const container = document.getElementById('messageContainer');
            
            // Get the date string for this message
            const msgDate = getDateString(msg.created_at);
            
            // Check if we already have a date divider for this date
            let existingDivider = null;
            let lastDivider = null;
            
            // Check all existing dividers
            const dividers = container.querySelectorAll('.date-divider');
            for (let div of dividers) {
                if (div.dataset.date === msgDate) {
                    existingDivider = div;
                    break;
                }
                lastDivider = div;
            }
            
            // If no divider exists for this date, add one
            if (!existingDivider) {
                // Remove any trailing empty dividers
                if (lastDivider && !lastDivider.nextElementSibling) {
                    // Check if there are any message wrappers after this divider
                    let hasMessages = false;
                    let next = lastDivider.nextElementSibling;
                    while (next) {
                        if (next.classList.contains('message-wrapper')) {
                            hasMessages = true;
                            break;
                        }
                        next = next.nextElementSibling;
                    }
                    if (!hasMessages) {
                        lastDivider.remove();
                    }
                }
                
                const divider = document.createElement('div');
                divider.className = 'date-divider';
                divider.dataset.date = msgDate;
                divider.innerHTML = `<span>${formatDateDivider(msg.created_at)}</span>`;
                container.appendChild(divider);
            }
            
            const wrapper = document.createElement('div');
            wrapper.className = `message-wrapper ${msg.sender_type}`;
            wrapper.id = `msg-${msg.id}`;
            
            const bubble = document.createElement('div');
            bubble.className = `message-bubble ${msg.sender_type}`;
            
            let content = '';
            const dp = msg.sender_type === 'admin' ? adminDp : clientDp;
            
            // Check if message is deleted for this user
            if (msg.is_deleted == 1) {
                content = `
                    <img src="${dp}" class="msg-avatar" alt="Avatar" onerror="this.src='dps/default-${msg.sender_type}.png'">
                    <div class="msg-content">
                        <div class="msg-deleted"><i class="fas fa-info-circle"></i> This message was deleted</div>
                        <div class="msg-time">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                    </div>
                `;
                bubble.innerHTML = content;
                wrapper.appendChild(bubble);
                container.appendChild(wrapper);
                return;
            }
            
            // Check if deleted for this user specifically
            const userIdStr = '<?php echo $user_id; ?>,';
            if (msg.deleted_for && msg.deleted_for.includes(userIdStr) && msg.sender_type !== 'admin') {
                content = `
                    <img src="${dp}" class="msg-avatar" alt="Avatar" onerror="this.src='dps/default-${msg.sender_type}.png'">
                    <div class="msg-content">
                        <div class="msg-deleted"><i class="fas fa-info-circle"></i> This message was deleted</div>
                        <div class="msg-time">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                    </div>
                `;
                bubble.innerHTML = content;
                wrapper.appendChild(bubble);
                container.appendChild(wrapper);
                return;
            }
            
            content += `<img src="${dp}" class="msg-avatar" alt="Avatar" onerror="this.src='dps/default-${msg.sender_type}.png'">`;
            content += `<div class="msg-content">`;
            
            // Message content
            if (msg.message_type === 'text') {
                content += `<div>${escapeHtml(msg.message)}</div>`;
            } else if (msg.message_type === 'file') {
                const fileUrl = `../${msg.file_path}`;
                const isImage = msg.mime_type && msg.mime_type.startsWith('image/');
                
                if (isImage) {
                    content += `
                        <div><strong>📎 ${escapeHtml(msg.file_name)}</strong></div>
                        <img src="${fileUrl}" class="msg-image" onclick="window.open('${fileUrl}')" alt="${escapeHtml(msg.file_name)}">
                    `;
                } else {
                    const icon = getFileIcon(msg.mime_type);
                    content += `
                        <div><strong>📎 ${escapeHtml(msg.file_name)}</strong></div>
                        <div class="msg-file">
                            <i class="fas ${icon}"></i>
                            <a href="${fileUrl}" target="_blank" download>Download (${formatFileSize(msg.file_size)})</a>
                        </div>
                    `;
                }
            } else if (msg.message_type === 'voice') {
                const voiceUrl = `../${msg.file_path}`;
                content += `
                    <div>🎤 Voice Message</div>
                    <div class="voice-player">
                        <audio controls>
                            <source src="${voiceUrl}" type="${msg.mime_type || 'audio/webm'}">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                `;
            }
            
            const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            content += `<div class="msg-time">${time}</div>`;
            content += `</div>`;
            
            // Delete button - Admin can delete any message
            content += `
                <div class="msg-actions">
                    <button class="msg-action-btn" onclick="openDeleteModal(${msg.id}, <?php echo $view_ticket['id']; ?>)" title="Delete message">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            bubble.innerHTML = content;
            wrapper.appendChild(bubble);
            container.appendChild(wrapper);
        }

        // Open delete modal
        function openDeleteModal(messageId, ticketId) {
            deleteMessageId = messageId;
            deleteTicketId = ticketId;
            openModal('deleteMessageModal');
        }

        // Confirm delete message
        function confirmDeleteMessage(deleteType) {
            if (!deleteMessageId || !deleteTicketId) return;
            
            closeModal('deleteMessageModal');
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_message');
            formData.append('message_id', deleteMessageId);
            formData.append('ticket_id', deleteTicketId);
            formData.append('delete_type', deleteType);
            
            showToast('Deleting message...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Message deleted successfully');
                    const msgElement = document.getElementById(`msg-${deleteMessageId}`);
                    if (msgElement) {
                        msgElement.style.transition = 'all 0.3s ease';
                        msgElement.style.opacity = '0';
                        msgElement.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            msgElement.remove();
                            cleanDateDividers();
                            lastMessageId = 0;
                            document.getElementById('messageContainer').innerHTML = '';
                            const emptyMsg = document.createElement('div');
                            emptyMsg.className = 'empty-state';
                            emptyMsg.style.padding = '30px 20px';
                            emptyMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i><p>Loading messages...</p>';
                            document.getElementById('messageContainer').appendChild(emptyMsg);
                            loadMessages();
                        }, 300);
                    } else {
                        lastMessageId = 0;
                        document.getElementById('messageContainer').innerHTML = '';
                        const emptyMsg = document.createElement('div');
                        emptyMsg.className = 'empty-state';
                        emptyMsg.style.padding = '30px 20px';
                        emptyMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i><p>Loading messages...</p>';
                        document.getElementById('messageContainer').appendChild(emptyMsg);
                        loadMessages();
                    }
                    deleteMessageId = null;
                    deleteTicketId = null;
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting message.', 'error');
            });
        }

        // Clean up empty date dividers
        function cleanDateDividers() {
            const container = document.getElementById('messageContainer');
            const dividers = container.querySelectorAll('.date-divider');
            dividers.forEach(divider => {
                let nextSibling = divider.nextElementSibling;
                let hasMessages = false;
                while (nextSibling) {
                    if (nextSibling.classList.contains('message-wrapper')) {
                        hasMessages = true;
                        break;
                    }
                    if (nextSibling.classList.contains('date-divider')) break;
                    nextSibling = nextSibling.nextElementSibling;
                }
                if (!hasMessages) {
                    divider.remove();
                }
            });
        }

        // Helper functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getFileIcon(mimeType) {
            if (!mimeType) return 'fa-file';
            if (mimeType.startsWith('image/')) return 'fa-file-image';
            if (mimeType === 'application/pdf') return 'fa-file-pdf';
            if (mimeType.includes('word')) return 'fa-file-word';
            if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'fa-file-excel';
            if (mimeType.includes('zip') || mimeType.includes('compressed')) return 'fa-file-archive';
            if (mimeType.startsWith('text/')) return 'fa-file-alt';
            return 'fa-file';
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        // Send message
        function sendMessage(ticketId) {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'send_message');
            formData.append('ticket_id', ticketId);
            formData.append('message', message);
            formData.append('message_type', 'text');
            
            input.value = '';
            input.style.height = 'auto';
            document.getElementById('sendBtn').disabled = true;
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    lastMessageId = 0;
                    document.getElementById('messageContainer').innerHTML = '';
                    const emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state';
                    emptyMsg.style.padding = '30px 20px';
                    emptyMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i><p>Loading messages...</p>';
                    document.getElementById('messageContainer').appendChild(emptyMsg);
                    loadMessages();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error sending message.', 'error');
            })
            .finally(() => {
                document.getElementById('sendBtn').disabled = false;
                input.focus();
            });
        }

        function handleKeyPress(event, ticketId) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage(ticketId);
            }
        }

        function autoResize(element) {
            element.style.height = 'auto';
            element.style.height = Math.min(element.scrollHeight, 80) + 'px';
        }

        function scrollToBottom() {
            const container = document.getElementById('chatMessages');
            container.scrollTop = container.scrollHeight;
        }

        // =============================================
        // FILE UPLOAD
        // =============================================
        function handleFileUpload(input, ticketId) {
            const files = input.files;
            if (!files || files.length === 0) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'upload_file');
            formData.append('ticket_id', ticketId);
            formData.append('file', files[0]);
            
            showToast('Uploading file...', 'warning');
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('File uploaded successfully!');
                    lastMessageId = 0;
                    document.getElementById('messageContainer').innerHTML = '';
                    const emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state';
                    emptyMsg.style.padding = '30px 20px';
                    emptyMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i><p>Loading messages...</p>';
                    document.getElementById('messageContainer').appendChild(emptyMsg);
                    loadMessages();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error uploading file.', 'error');
            });
            
            input.value = '';
        }

        // =============================================
        // VOICE RECORDING - FIXED
        // =============================================
        function toggleRecording(ticketId) {
            if (isRecording) {
                stopRecording(ticketId);
            } else {
                startRecording(ticketId);
            }
        }

        function startRecording(ticketId) {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                showToast('Voice recording not supported in this browser.', 'error');
                return;
            }
            
            navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                // Check for supported MIME types
                let mimeType = 'audio/webm';
                const supportedTypes = ['audio/webm', 'audio/webm;codecs=opus', 'audio/ogg;codecs=opus', 'audio/mp4'];
                for (let type of supportedTypes) {
                    if (MediaRecorder.isTypeSupported(type)) {
                        mimeType = type;
                        break;
                    }
                }
                
                mediaRecorder = new MediaRecorder(stream, { mimeType: mimeType });
                audioChunks = [];
                
                mediaRecorder.ondataavailable = event => {
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                };
                
                mediaRecorder.onstop = () => {
                    try {
                        const audioBlob = new Blob(audioChunks, { type: mimeType });
                        if (audioBlob.size > 0) {
                            uploadVoice(audioBlob, ticketId);
                        } else {
                            showToast('No audio recorded. Please try again.', 'error');
                        }
                    } catch (error) {
                        showToast('Error processing voice recording: ' + error.message, 'error');
                    }
                    stream.getTracks().forEach(track => track.stop());
                };
                
                mediaRecorder.onerror = (event) => {
                    showToast('Recording error. Please try again.', 'error');
                    console.error('MediaRecorder error:', event);
                };
                
                // Start recording with time slices for better handling
                mediaRecorder.start(1000);
                isRecording = true;
                
                const voiceBtn = document.getElementById('voiceBtn');
                voiceBtn.innerHTML = '<i class="fas fa-stop"></i>';
                voiceBtn.style.color = '#dc3545';
                
                const sendBtn = document.getElementById('sendBtn');
                sendBtn.classList.add('recording');
                sendBtn.disabled = true;
                
                showToast('Recording... Click stop when done.', 'warning');
            })
            .catch(error => {
                showToast('Error accessing microphone: ' + error.message, 'error');
                console.error('Microphone access error:', error);
            });
        }

        function stopRecording(ticketId) {
            if (mediaRecorder && isRecording) {
                try {
                    mediaRecorder.stop();
                    isRecording = false;
                    
                    const voiceBtn = document.getElementById('voiceBtn');
                    voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
                    voiceBtn.style.color = '';
                    
                    const sendBtn = document.getElementById('sendBtn');
                    sendBtn.classList.remove('recording');
                    sendBtn.disabled = false;
                } catch (error) {
                    showToast('Error stopping recording: ' + error.message, 'error');
                    isRecording = false;
                }
            }
        }

        function uploadVoice(audioBlob, ticketId) {
            const formData = new FormData();
            formData.append('ajax_action', 'upload_voice');
            formData.append('ticket_id', ticketId);
            
            // Determine file extension based on blob type
            let extension = 'webm';
            if (audioBlob.type.includes('ogg')) extension = 'ogg';
            else if (audioBlob.type.includes('mp4')) extension = 'mp4';
            
            formData.append('voice', audioBlob, 'voice_message.' + extension);
            
            showToast('Uploading voice message...', 'warning');
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Voice message sent!');
                    lastMessageId = 0;
                    document.getElementById('messageContainer').innerHTML = '';
                    const emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state';
                    emptyMsg.style.padding = '30px 20px';
                    emptyMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i><p>Loading messages...</p>';
                    document.getElementById('messageContainer').appendChild(emptyMsg);
                    loadMessages();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error uploading voice: ' + error.message, 'error');
            });
        }

        // =============================================
        // POLLING
        // =============================================
        function startPolling() {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(() => {
                loadMessages();
            }, 3000);
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        // =============================================
        // INITIALIZE
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($view_ticket_id > 0 && $view_ticket): ?>
            const container = document.getElementById('messageContainer');
            const emptyMsg = document.createElement('div');
            emptyMsg.className = 'empty-state';
            emptyMsg.style.padding = '30px 20px';
            emptyMsg.innerHTML = '<i class="fas fa-comment"></i><p>Loading messages...</p>';
            container.appendChild(emptyMsg);
            
            loadMessages();
            startPolling();
            document.getElementById('chatInput').focus();
            
            window.addEventListener('beforeunload', function() {
                stopPolling();
                if (isRecording) {
                    stopRecording(<?php echo $view_ticket['id']; ?>);
                }
            });
            <?php endif; ?>
        });
        <?php endif; ?>
        // =============================================
        // SESSION TIMER
        // =============================================
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