<?php
// billing.php - Client Invoice Ledger with Stripe Integration (FULLY FIXED)
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/config.php';

// ============================================================
// ===== STRIPE CONFIGURATION =====
// ============================================================
define('STRIPE_SECRET_KEY', 'REPLACE_WITH_STRIPE_SECRET_KEY');
define('STRIPE_PUBLISHABLE_KEY', 'REPLACE_WITH_STRIPE_PUBLISHABLE_KEY');

// ============================================================
// ===== STRIPE API FUNCTION =====
// ============================================================
function stripeApiRequest($endpoint, $method = 'POST', $data = []) {
    $url = 'https://api.stripe.com/v1/' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Stripe API Error: ' . $error);
    }
    
    return json_decode($response, true);
}

// ============================================================
// ===== SESSION VALIDATION =====
// ============================================================
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

// ============================================================
// ===== GET CLIENT DATA =====
// ============================================================
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

// ============================================================
// ===== GET INVOICES =====
// ============================================================
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

// ============================================================
// ===== GET ACTIVE PACKAGE =====
// ============================================================
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

$package_name = $active_package['name'] ?? 'No Package';

// ============================================================
// ===== TOTAL CALCULATIONS =====
// ============================================================
$total_paid = 0;
$total_due = 0;
$total_partial = 0;
foreach ($invoices as $inv) {
    if ($inv['status'] === 'Paid') $total_paid += $inv['amount'];
    elseif ($inv['status'] === 'Partially Paid') $total_partial += $inv['amount'];
    else $total_due += $inv['amount'];
}

$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
$current_page = 'billing.php';

// ============================================================
// ===== AJAX HANDLER =====
// ============================================================
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    // ===== CREATE STRIPE CHECKOUT SESSION =====
    if ($_POST['ajax_action'] === 'create_stripe_session') {
        $invoice_id = intval($_POST['invoice_id']);
        $amount = floatval($_POST['amount']);
        $currency = $_POST['currency'] ?? 'pkr';
        $email = $_SESSION['user']['email'] ?? '';
        $payment_method = $_POST['payment_method'] ?? 'card';
        
        if ($invoice_id > 0 && $amount > 0) {
            $get_sql = "SELECT * FROM invoices WHERE id = ? AND client_id = ?";
            $get_stmt = mysqli_prepare($conn, $get_sql);
            mysqli_stmt_bind_param($get_stmt, "ii", $invoice_id, $client_id);
            mysqli_stmt_execute($get_stmt);
            $get_result = mysqli_stmt_get_result($get_stmt);
            $invoice = mysqli_fetch_assoc($get_result);
            mysqli_stmt_close($get_stmt);
            
            if ($invoice) {
                try {
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'];
                    $base_path = dirname($_SERVER['PHP_SELF']);
                    
                    $session_data = [
                        'payment_method_types' => ['card'],
                        'line_items' => [[
                            'price_data' => [
                                'currency' => $currency,
                                'product_data' => [
                                    'name' => 'Invoice #' . $invoice['invoice_number'],
                                    'description' => 'Payment for invoice ' . $invoice['invoice_number'],
                                ],
                                'unit_amount' => intval($amount * 100),
                            ],
                            'quantity' => 1,
                        ]],
                        'mode' => 'payment',
                        'success_url' => $protocol . $host . $base_path . '/billing.php?payment_success=true&session_id={CHECKOUT_SESSION_ID}&invoice_id=' . $invoice_id . '&invoice_number=' . $invoice['invoice_number'] . '&paid_amount=' . $amount . '&currency=' . $currency . '&total_amount=' . $invoice['amount'],
                        'cancel_url' => $protocol . $host . $base_path . '/billing.php?payment_cancelled=true',
                        'client_reference_id' => $invoice_id,
                        'customer_email' => $email,
                        'metadata' => [
                            'invoice_id' => $invoice_id,
                            'client_id' => $client_id,
                            'invoice_number' => $invoice['invoice_number'],
                            'paid_amount' => $amount,
                            'currency' => $currency,
                            'payment_method' => $payment_method,
                            'total_amount' => $invoice['amount']
                        ],
                    ];
                    
                    $result = stripeApiRequest('checkout/sessions', 'POST', $session_data);
                    
                    if (isset($result['url'])) {
                        $response = ['success' => true, 'session_url' => $result['url'], 'session_id' => $result['id']];
                    } else {
                        $response = ['success' => false, 'message' => $result['error']['message'] ?? 'Failed to create session'];
                    }
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => 'Stripe Error: ' . $e->getMessage()];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invoice not found'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== GET INVOICE DETAILS =====
    if ($_POST['ajax_action'] === 'get_invoice_details') {
        $invoice_id = intval($_POST['invoice_id']);
        
        if ($invoice_id > 0) {
            $sql = "SELECT * FROM invoices WHERE id = ? AND client_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $invoice_id, $client_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $invoice = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($invoice) {
                $invoice['issue_date_formatted'] = !empty($invoice['issue_date']) && $invoice['issue_date'] != '0000-00-00' ? date('M d, Y', strtotime($invoice['issue_date'])) : 'N/A';
                $invoice['due_date_formatted'] = !empty($invoice['due_date']) && $invoice['due_date'] != '0000-00-00' ? date('M d, Y', strtotime($invoice['due_date'])) : 'N/A';
                $invoice['created_at_formatted'] = date('M d, Y H:i', strtotime($invoice['created_at']));
                $invoice['paid_date_formatted'] = !empty($invoice['paid_date']) && $invoice['paid_date'] != '0000-00-00' ? date('M d, Y', strtotime($invoice['paid_date'])) : 'Not paid yet';
                
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
    
    // ===== CHECK PAYMENT STATUS =====
    if ($_POST['ajax_action'] === 'check_payment_status') {
        $session_id = trim($_POST['session_id']);
        
        if (!empty($session_id)) {
            try {
                $result = stripeApiRequest('checkout/sessions/' . $session_id, 'GET');
                
                if (isset($result['payment_status'])) {
                    $response = [
                        'success' => true,
                        'paid' => ($result['payment_status'] === 'paid'),
                        'message' => $result['payment_status'] === 'paid' ? 'Payment successful!' : 'Payment not completed.'
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'Could not retrieve session'];
                }
            } catch (Exception $e) {
                $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid session ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== GENERATE PDF =====
    if ($_POST['ajax_action'] === 'generate_pdf') {
        $invoice_id = intval($_POST['invoice_id']);
        
        if ($invoice_id > 0) {
            $sql = "SELECT * FROM invoices WHERE id = ? AND client_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $invoice_id, $client_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $invoice = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($invoice) {
                $client_sql = "SELECT * FROM clients WHERE id = ?";
                $client_stmt = mysqli_prepare($conn, $client_sql);
                mysqli_stmt_bind_param($client_stmt, "i", $client_id);
                mysqli_stmt_execute($client_stmt);
                $client_result = mysqli_stmt_get_result($client_stmt);
                $client = mysqli_fetch_assoc($client_result);
                mysqli_stmt_close($client_stmt);
                
                $response = [
                    'success' => true,
                    'data' => [
                        'invoice' => $invoice,
                        'client' => $client,
                        'company' => [
                            'name' => 'HIFI Marketing',
                            'year' => date('Y')
                        ]
                    ]
                ];
            } else {
                $response = ['success' => false, 'message' => 'Invoice not found'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid invoice ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== DOWNLOAD ATTACHMENT =====
    if ($_POST['ajax_action'] === 'download_attachment') {
        $invoice_id = intval($_POST['invoice_id']);
        
        if ($invoice_id > 0) {
            $sql = "SELECT attachment FROM invoices WHERE id = ? AND client_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $invoice_id, $client_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($row && !empty($row['attachment'])) {
                $file_name = $row['attachment'];
                $base_path = dirname(__DIR__);
                
                $paths_to_check = [
                    $base_path . '/admin-portal/uploads/invoices/' . $file_name,
                    __DIR__ . '/uploads/invoices/' . $file_name,
                    $base_path . '/uploads/invoices/' . $file_name,
                ];
                
                $found_path = null;
                $found = false;
                foreach ($paths_to_check as $path) {
                    $path = str_replace('\\', '/', $path);
                    if (file_exists($path)) {
                        $found_path = $path;
                        $found = true;
                        break;
                    }
                }
                
                if ($found) {
                    $response = ['success' => true, 'file' => $file_name];
                } else {
                    $response = ['success' => false, 'message' => 'File not found on server.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'No attachment found'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid invoice ID'];
        }
        
        echo json_encode($response);
        exit();
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
    <title>Billing | Client Portal</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            line-height: 1.6;
        }
        a { text-decoration: none; color: inherit; }

        .amount-display {
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            color: var(--text-muted);
            font-weight: 700;
        }
        .amount-display.show {
            font-family: inherit;
            letter-spacing: 0;
            color: var(--text-primary);
            font-weight: 900;
        }

        .stat-number .amount-display {
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            color: var(--text-muted);
            font-weight: 700;
        }
        .stat-number .amount-display.show {
            font-family: inherit;
            letter-spacing: 0;
            color: inherit;
            font-weight: 900;
        }

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

        .main-layout {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
            min-height: calc(100vh - 72px);
        }

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

        .content {
            flex: 1;
            min-width: 0;
        }

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
            align-items: center;
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
        .stat-card .stat-icon {
            float: right;
            font-size: 20px;
            opacity: 0.15;
            color: var(--primary);
        }
        .stat-card .stat-number {
            font-size: 20px;
            font-weight: 900;
            color: var(--text-primary);
        }
        .stat-card .stat-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .table-wrap {
            overflow-x: auto;
        }
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
            vertical-align: middle;
        }
        table tr:hover td { background: #f8fafc; }
        table tr:last-child td { border-bottom: none; }

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
            max-width: 520px;
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
        .modal input, .modal select {
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
        .modal input:focus, .modal select:focus {
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
        .modal .btn-stripe {
            width: 100%;
            padding: 12px;
            background: #635bff;
            color: #fff;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .modal .btn-stripe:hover {
            background: #4a3dff;
            transform: translateY(-2px);
        }
        .modal .btn-stripe:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .modal .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }
        .modal .detail-row:last-child {
            border-bottom: none;
        }
        .modal .detail-label {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 12px;
        }
        .modal .detail-value {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 13px;
        }
        .modal .detail-value.paid { color: var(--success); }
        .modal .detail-value.due { color: var(--danger); }
        .modal .detail-value.partial { color: var(--warning); }

        .btn-sm {
            padding: 4px 10px;
            border-radius: 6px;
            border: none;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-sm.pay { background: #d1fae5; color: #065f46; }
        .btn-sm.pay:hover { background: #065f46; color: #fff; }
        .btn-sm.download { background: #e8edfe; color: var(--primary); }
        .btn-sm.download:hover { background: var(--primary); color: #fff; }
        .btn-sm.view { background: #f0f3ff; color: var(--primary); }
        .btn-sm.view:hover { background: var(--primary); color: #fff; }
        .btn-sm.pdf { background: #dcfce7; color: #16a34a; }
        .btn-sm.pdf:hover { background: #16a34a; color: #fff; }

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

        /* ===== CONFIRMATION POPUP ===== */
        .confirmation-overlay {
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .confirmation-overlay.show { display: flex; }
        .confirmation-box {
            background: #fff;
            border-radius: 24px;
            max-width: 440px;
            width: 100%;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: popIn 0.4s ease;
        }
        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .confirmation-box .icon {
            width: 80px;
            height: 80px;
            background: #d1fae5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .confirmation-box .icon i {
            font-size: 40px;
            color: #065f46;
        }
        .confirmation-box h2 {
            font-size: 22px;
            font-weight: 800;
            color: #065f46;
            margin-bottom: 8px;
        }
        .confirmation-box p {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 6px;
            line-height: 1.6;
        }
        .confirmation-box .small-note {
            font-size: 12px;
            color: var(--text-muted);
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 12px 0 20px;
        }
        .confirmation-box .small-note i {
            color: var(--warning);
        }
        .confirmation-box .btn-done {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
        }
        .confirmation-box .btn-done:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* ===== PAYMENT METHOD STYLES ===== */
        .payment-method-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            background: #fff;
            transition: var(--transition);
            text-align: center;
            justify-content: center;
        }
        .payment-method-option:hover {
            border-color: var(--primary);
            background: #f0f3ff;
        }
        .payment-method-option.active {
            border-color: var(--primary);
            background: #f0f3ff;
            box-shadow: 0 0 0 2px rgba(74,92,245,0.1);
        }
        .payment-method-option input[type="radio"] {
            display: none;
        }
        .payment-method-option i {
            font-size: 16px;
            color: var(--primary);
        }
        .payment-method-option span {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-primary);
        }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
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
            .header-actions .action-btn { padding: 4px 8px; font-size: 13px; }
            .header-inner { padding: 0 12px; }
            .logo { font-size: 17px; }
            .logo .brand-icon { width: 30px; height: 30px; font-size: 13px; }
            .banner .banner-actions .btn-white { width: 100%; text-align: center; }
            table { font-size: 11px; }
            table th, table td { padding: 6px 8px; }
            .modal { max-width: 100%; margin: 10px; padding: 16px 18px; }
            .confirmation-box { padding: 28px 20px; }
            .payment-method-option { padding: 6px 8px; }
            .payment-method-option span { font-size: 10px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
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

            <nav class="desktop-nav">
                <a href="client-portal.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="packages.php" class="nav-link"><i class="fas fa-credit-card"></i> Packages</a>
                
                <a href="requests.php" class="nav-link"><i class="fas fa-headset"></i> Support</a>
                <a href="billing.php" class="nav-link active"><i class="fas fa-file-invoice"></i> Billing</a>
            </nav>

            <div class="header-actions">
                <div class="user-badge">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <span class="name"><?php echo $userData['name'] ?? 'Client'; ?></span>
                    <span class="online"></span>
                </div>
                <a href="logout.php" style="color:#dc3545;font-size:16px;padding:4px 8px;border-radius:8px;transition:var(--transition);" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-sign-out-alt"></i>
                </a>

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
            <a href="client-portal.php" onclick="closeMobileMenu()"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="packages.php" onclick="closeMobileMenu()"><i class="fas fa-credit-card"></i> Service Packages</a>
            <a href="client-deliverables.php" onclick="closeMobileMenu()"><i class="fas fa-check-square"></i> Deliverables</a>
            <a href="requests.php" onclick="closeMobileMenu()"><i class="fas fa-headset"></i> Tasks & Support</a>
            <a href="billing.php" class="active" onclick="closeMobileMenu()"><i class="fas fa-file-invoice"></i> Billing Ledger</a>
            <a href="reports.php" onclick="closeMobileMenu()"><i class="fas fa-chart-bar"></i> Marketing Reports</a>
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
                <a href="client-portal.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i><span class="sidebar-text">Dashboard</span></a>
                <a href="packages.php" class="sidebar-link"><i class="fas fa-credit-card"></i><span class="sidebar-text">Service Packages</span></a>
                <a href="client-deliverables.php" class="sidebar-link"><i class="fas fa-check-square"></i><span class="sidebar-text">Deliverables</span></a>
                <a href="requests.php" class="sidebar-link"><i class="fas fa-headset"></i><span class="sidebar-text">Tasks & Support</span></a>
                <a href="billing.php" class="sidebar-link active"><i class="fas fa-file-invoice"></i><span class="sidebar-text">Billing Ledger</span></a>
                <a href="reports.php" class="sidebar-link"><i class="fas fa-chart-bar"></i><span class="sidebar-text">Marketing Reports</span></a>
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
                    <h2><i class="fas fa-file-invoice"></i> Invoice Ledger</h2>
                    <p>Track your billing history &bull; Active Package: <strong><?php echo $package_name; ?></strong></p>
                </div>
                <div class="banner-actions">
                    <button onclick="toggleAllAmounts()" class="btn-white" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-eye" id="toggleIcon"></i> <span id="toggleText">Show Amounts</span>
                    </button>
                    <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:8px;"></i> <?php echo count($invoices); ?> Invoices</span>
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

            <!-- ===== STATS ===== -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle" style="color:#10b981;"></i></div>
                    <div class="stat-number" style="color:#10b981;">
                        <span class="amount-display" data-amount="<?php echo $total_paid; ?>">••••••••</span>
                    </div>
                    <div class="stat-label">Total Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-circle" style="color:#dc2626;"></i></div>
                    <div class="stat-number" style="color:#dc2626;">
                        <span class="amount-display" data-amount="<?php echo $total_due; ?>">••••••••</span>
                    </div>
                    <div class="stat-label">Total Due</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock" style="color:#f59e0b;"></i></div>
                    <div class="stat-number" style="color:#f59e0b;">
                        <span class="amount-display" data-amount="<?php echo $total_partial; ?>">••••••••</span>
                    </div>
                    <div class="stat-label">Partially Paid</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-invoice" style="color:var(--primary);"></i></div>
                    <div class="stat-number"><?php echo count($invoices); ?></div>
                    <div class="stat-label">Total Invoices</div>
                </div>
            </div>

            <!-- ===== INVOICE TABLE ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-invoice" style="color:var(--primary);"></i> Invoice Ledger</h3>
                    <span style="font-size:12px;font-weight:700;color:var(--text-muted);">
                        <i class="fas fa-info-circle"></i> Click on invoice for details
                    </span>
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
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($invoices)): ?>
                                <?php foreach ($invoices as $inv): 
                                    $status_class = strtolower(str_replace(' ', '-', $inv['status'] ?? 'due'));
                                    $issue_date = !empty($inv['issue_date']) && $inv['issue_date'] != '0000-00-00' ? date('M d, Y', strtotime($inv['issue_date'])) : 'N/A';
                                    $due_date = !empty($inv['due_date']) && $inv['due_date'] != '0000-00-00' ? date('M d, Y', strtotime($inv['due_date'])) : 'N/A';
                                    $has_attachment = !empty($inv['attachment']);
                                    $is_paid = ($inv['status'] === 'Paid');
                                    $is_partial = ($inv['status'] === 'Partially Paid');
                                ?>
                                <tr>
                                    <td style="font-weight:700;color:var(--text-primary);font-size:12px;">
                                        <?php echo htmlspecialchars($inv['invoice_number'] ?? 'N/A'); ?>
                                    </td>
                                    <td style="font-weight:700;font-size:13px;">
                                        <span class="amount-display" data-amount="<?php echo $inv['amount']; ?>">••••••••</span>
                                        <?php if ($is_partial && isset($inv['paid_amount']) && $inv['paid_amount'] > 0): ?>
                                        <br><span style="font-size:9px;color:var(--text-muted);">Paid: <span class="amount-display" data-amount="<?php echo $inv['paid_amount']; ?>">••••••••</span></span>
                                        <?php endif; ?>
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
                                            <button class="btn-sm download" onclick="downloadAttachment(<?php echo $inv['id']; ?>)" title="Download Invoice">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);font-size:11px;">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;white-space:nowrap;">
                                        <button class="btn-sm view" onclick="viewInvoice(<?php echo $inv['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($is_paid): ?>
                                            <span style="color:#10b981;font-weight:600;font-size:11px;margin-left:4px;"><i class="fas fa-check-circle"></i></span>
                                        <?php else: ?>
                                            <button class="btn-sm pay" onclick="openStripePayModal(<?php echo $inv['id']; ?>, <?php echo $inv['amount']; ?>, '<?php echo addslashes($inv['invoice_number']); ?>')">
                                                <i class="fas fa-credit-card"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);font-size:13px;">No invoices found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- ===== CONFIRMATION POPUP ===== -->
    <div class="confirmation-overlay" id="confirmationPopup">
        <div class="confirmation-box">
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Payment Successful!</h2>
            <p>Your payment has been processed successfully.</p>
            
            <!-- Invoice Details -->
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin:12px 0;text-align:left;border:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #e9edf2;">
                    <span style="font-size:12px;color:var(--text-muted);">Invoice #</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text-primary);" id="confirmation-invoice">INV-2024-001</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #e9edf2;">
                    <span style="font-size:12px;color:var(--text-muted);">Total Amount</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text-primary);" id="confirmation-total">0 PKR</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #e9edf2;">
                    <span style="font-size:12px;color:var(--text-muted);">Amount Paid</span>
                    <span style="font-size:12px;font-weight:700;color:#10b981;" id="confirmation-paid">0 PKR</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #e9edf2;">
                    <span style="font-size:12px;color:var(--text-muted);">Remaining</span>
                    <span style="font-size:12px;font-weight:700;color:#f59e0b;" id="confirmation-remaining">0 PKR</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;">
                    <span style="font-size:12px;color:var(--text-muted);">Payment Date</span>
                    <span style="font-size:12px;font-weight:700;color:var(--text-primary);" id="confirmation-date">-</span>
                </div>
            </div>
            
            <div class="small-note">
                <i class="fas fa-clock"></i>
                <strong>Pending PM Approval</strong><br>
                <span style="font-size:11px;">Your payment is received. PM will verify and mark invoice as paid.</span>
            </div>
            <button class="btn-done" onclick="closeConfirmation()">
                <i class="fas fa-check"></i> Done
            </button>
        </div>
    </div>

    <!-- ===== MODALS ===== -->

    <!-- View Invoice Modal -->
    <div class="modal-overlay" id="modal-view-invoice">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-view-invoice')"><i class="fas fa-times"></i></button>
            <h3><i class="fas fa-file-invoice" style="color:var(--primary);"></i> Invoice Details</h3>
            <p class="modal-sub" id="view-invoice-number">Invoice #</p>
            <div id="invoice-details-content">
                <div style="text-align:center;padding:20px;color:var(--text-muted);">
                    <i class="fas fa-spinner fa-spin" style="font-size:24px;"></i>
                    <p style="margin-top:8px;">Loading invoice details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== STRIPE PAY MODAL ===== -->
    <div class="modal-overlay" id="modal-stripe-pay">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-stripe-pay')"><i class="fas fa-times"></i></button>
            <h3><i class="fas fa-credit-card"></i> Pay with Stripe</h3>
            <p class="modal-sub">Secure payment via Stripe</p>
            
            <div style="background:#f8fafc;border-radius:10px;padding:16px;margin-bottom:16px;">
                <div class="detail-row">
                    <span class="detail-label">Invoice #</span>
                    <span class="detail-value" id="stripe-invoice-number">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount</span>
                    <span class="detail-value" id="stripe-total-amount" style="color:var(--text-muted);font-size:14px;">-</span>
                </div>
                <div class="detail-row" style="border-bottom: none; padding-bottom: 0; flex-wrap:wrap; gap:8px;">
                    <span class="detail-label">Amount to Pay</span>
                    <span class="detail-value" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <input type="number" id="stripe-pay-amount" 
                               value="0"
                               min="0" step="100" 
                               oninput="updatePayAmount(this.value)"
                               style="width:150px;padding:6px 10px;border:2px solid var(--border);border-radius:6px;font-size:14px;font-weight:700;text-align:right;color:var(--primary);">
                        <select id="stripe-currency" onchange="updatePayAmount(document.getElementById('stripe-pay-amount').value)" style="padding:6px 10px;border:2px solid var(--border);border-radius:6px;font-size:13px;font-weight:600;background:#fff;cursor:pointer;">
                            <option value="pkr">PKR</option>
                            <option value="usd">USD</option>
                            <option value="eur">EUR</option>
                            <option value="gbp">GBP</option>
                            <option value="aed">AED</option>
                            <option value="sar">SAR</option>
                            <option value="inr">INR</option>
                            <option value="cad">CAD</option>
                            <option value="aud">AUD</option>
                            <option value="jpy">JPY</option>
                            <option value="cny">CNY</option>
                        </select>
                    </span>
                </div>
            </div>
            
            <!-- ===== PAYMENT METHODS ===== -->
            <div style="margin-bottom:16px;">
                <div style="font-size:12px;font-weight:700;color:var(--text-primary);margin-bottom:8px;">Payment Method:</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;">
                    <label class="payment-method-option active" onclick="selectPaymentMethod('card', this)">
                        <input type="radio" name="payment_method" value="card" checked>
                        <i class="fas fa-credit-card"></i>
                        <span>Card</span>
                    </label>
                    <label class="payment-method-option" onclick="selectPaymentMethod('bank_transfer', this)">
                        <input type="radio" name="payment_method" value="bank_transfer">
                        <i class="fas fa-university"></i>
                        <span>Bank</span>
                    </label>
                    <label class="payment-method-option" onclick="selectPaymentMethod('jazzcash', this)">
                        <input type="radio" name="payment_method" value="jazzcash">
                        <i class="fas fa-mobile-alt"></i>
                        <span>JazzCash</span>
                    </label>
                    <label class="payment-method-option" onclick="selectPaymentMethod('easypaisa', this)">
                        <input type="radio" name="payment_method" value="easypaisa">
                        <i class="fas fa-mobile-alt"></i>
                        <span>EasyPaisa</span>
                    </label>
                    <label class="payment-method-option" onclick="selectPaymentMethod('other', this)" style="grid-column:span 1;">
                        <input type="radio" name="payment_method" value="other">
                        <i class="fas fa-ellipsis-h"></i>
                        <span>Other</span>
                    </label>
                </div>
            </div>
            
            <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;margin-bottom:16px;border:1px solid #bbf7d0;">
                <div style="display:flex;align-items:center;gap:8px;color:#065f46;font-size:11px;">
                    <i class="fas fa-lock"></i>
                    <span>Secure payment powered by Stripe</span>
                </div>
            </div>
            
            <button class="btn-stripe" onclick="proceedToStripe()" id="stripePayBtn">
                <i class="fas fa-credit-card"></i> Pay <span id="stripe-pay-text">0</span> <span id="stripe-currency-text">PKR</span>
                <svg width="40" height="16" viewBox="0 0 40 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="0.5" y="0.5" width="39" height="15" rx="2.5" fill="white" stroke="#D1D5DB"/>
                    <path d="M6.5 4.5H8.5V11.5H6.5V4.5Z" fill="#6772E5"/>
                    <path d="M17.5 4.5H19.5V11.5H17.5V4.5Z" fill="#6772E5"/>
                    <circle cx="22" cy="8" r="4" fill="#6772E5"/>
                    <circle cx="30" cy="8" r="4" fill="#6772E5"/>
                </svg>
            </button>
            
            <p style="text-align:center;font-size:9px;color:var(--text-muted);margin-top:10px;">
                <i class="fas fa-info-circle"></i> Partial payment allowed. Remaining balance will be updated.
            </p>
        </div>
    </div>

    <!-- ===== TOAST CONTAINER ===== -->
    <div class="toast-container" id="toast-container"></div>

    <!-- ===== SECURITY BADGE ===== -->
    <div class="security-badge">🔒 Secure Session • <?php echo $_SERVER['REMOTE_ADDR']; ?></div>

    <script>
        // ============================================================
        // ===== VARIABLES =====
        // ============================================================
        let amountsVisible = false;
        let stripeInvoiceId = null;
        let stripeTotalAmount = 0;
        let stripeInvoiceNumber = '';
        let selectedPaymentMethod = 'card';

        // ============================================================
        // ===== SIDEBAR & MOBILE MENU =====
        // ============================================================
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

        // ============================================================
        // ===== MODAL FUNCTIONS =====
        // ============================================================
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

        // ============================================================
        // ===== CONFIRMATION POPUP =====
        // ============================================================
        function showConfirmation(invoiceNumber, paidAmount, totalAmount, currency) {
            const paid = parseFloat(paidAmount) || 0;
            const total = parseFloat(totalAmount) || 0;
            const remaining = total - paid;
            const curr = currency || 'PKR';
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            const timeStr = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            
            document.getElementById('confirmation-invoice').textContent = '#' + invoiceNumber;
            document.getElementById('confirmation-total').textContent = total.toLocaleString() + ' ' + curr;
            document.getElementById('confirmation-paid').textContent = paid.toLocaleString() + ' ' + curr;
            document.getElementById('confirmation-remaining').textContent = remaining.toLocaleString() + ' ' + curr;
            document.getElementById('confirmation-date').textContent = dateStr + ' at ' + timeStr;
            
            document.getElementById('confirmationPopup').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeConfirmation() {
            document.getElementById('confirmationPopup').classList.remove('show');
            document.body.style.overflow = '';
            location.reload();
        }

        // ============================================================
        // ===== CHECK PAYMENT STATUS =====
        // ============================================================
        <?php if (isset($_GET['payment_success']) && isset($_GET['session_id']) && isset($_GET['invoice_id'])): ?>
        window.onload = function() {
            const sessionId = '<?php echo $_GET['session_id']; ?>';
            const invoiceId = '<?php echo $_GET['invoice_id']; ?>';
            const invoiceNumber = '<?php echo $_GET['invoice_number'] ?? ''; ?>';
            const paidAmount = '<?php echo $_GET['paid_amount'] ?? 0; ?>';
            const currency = '<?php echo $_GET['currency'] ?? 'PKR'; ?>';
            const totalAmount = '<?php echo $_GET['total_amount'] ?? 0; ?>';
            
            const formData = new FormData();
            formData.append('ajax_action', 'check_payment_status');
            formData.append('session_id', sessionId);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.paid) {
                    setTimeout(function() {
                        showConfirmation(
                            invoiceNumber || 'INV-' + invoiceId,
                            paidAmount,
                            totalAmount,
                            currency.toUpperCase()
                        );
                    }, 500);
                } else if (data.success && !data.paid) {
                    showToast('Payment not completed. Please try again.', 'warning');
                } else {
                    showToast(data.message || 'Error checking payment status', 'error');
                }
            })
            .catch(error => {
                showToast('Error checking payment status', 'error');
            });
        };
        <?php endif; ?>

        // ============================================================
        // ===== TOAST =====
        // ============================================================
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-triangle-exclamation';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; setTimeout(() => toast.remove(), 300); }, 3500);
        }

        // ============================================================
        // ===== COPY CLIENT ID =====
        // ============================================================
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

        // ============================================================
        // ===== TOGGLE ALL AMOUNTS =====
        // ============================================================
        function toggleAllAmounts() {
            amountsVisible = !amountsVisible;
            
            const toggleText = document.getElementById('toggleText');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (amountsVisible) {
                toggleText.textContent = 'Hide Amounts';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                toggleText.textContent = 'Show Amounts';
                toggleIcon.className = 'fas fa-eye';
            }
            
            const amountDisplays = document.querySelectorAll('.amount-display');
            
            amountDisplays.forEach(function(element) {
                const amount = element.getAttribute('data-amount');
                if (amount !== null && amount !== '') {
                    if (amountsVisible) {
                        element.textContent = parseFloat(amount).toLocaleString() + ' PKR';
                        element.classList.add('show');
                    } else {
                        element.textContent = '••••••••';
                        element.classList.remove('show');
                    }
                }
            });
        }

        // ============================================================
        // ===== VIEW INVOICE =====
        // ============================================================
        function viewInvoice(invoiceId) {
            const modal = document.getElementById('modal-view-invoice');
            const content = document.getElementById('invoice-details-content');
            
            content.innerHTML = `
                <div style="text-align:center;padding:20px;color:var(--text-muted);">
                    <i class="fas fa-spinner fa-spin" style="font-size:24px;"></i>
                    <p style="margin-top:8px;">Loading invoice details...</p>
                </div>
            `;
            
            openModal('modal-view-invoice');
            
            const formData = new FormData();
            formData.append('ajax_action', 'get_invoice_details');
            formData.append('invoice_id', invoiceId);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const inv = data.data;
                    const statusClass = inv.status.toLowerCase().replace(' ', '-');
                    const colorClass = statusClass === 'paid' ? 'paid' : statusClass === 'due' ? 'due' : 'partial';
                    
                    document.getElementById('view-invoice-number').textContent = 'Invoice #' + inv.invoice_number;
                    
                    content.innerHTML = `
                        <div style="background:#f8fafc;border-radius:10px;padding:16px;margin-bottom:14px;">
                            <div class="detail-row">
                                <span class="detail-label">Invoice Number</span>
                                <span class="detail-value">${inv.invoice_number}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Amount</span>
                                <span class="detail-value amount-display" data-amount="${inv.amount}">••••••••</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status</span>
                                <span class="detail-value ${colorClass}">${inv.status}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Issue Date</span>
                                <span class="detail-value">${inv.issue_date_formatted}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Due Date</span>
                                <span class="detail-value">${inv.due_date_formatted}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">LPS</span>
                                <span class="detail-value">${inv.lps || 0}%</span>
                            </div>
                            ${inv.paid_amount > 0 ? `
                            <div class="detail-row">
                                <span class="detail-label">Paid Amount</span>
                                <span class="detail-value amount-display" data-amount="${inv.paid_amount}">••••••••</span>
                            </div>
                            ` : ''}
                            <div class="detail-row">
                                <span class="detail-label">Created</span>
                                <span class="detail-value">${inv.created_at_formatted}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Paid Date</span>
                                <span class="detail-value">${inv.paid_date_formatted}</span>
                            </div>
                            ${inv.note ? `
                            <div class="detail-row" style="flex-direction:column;align-items:flex-start;gap:4px;padding:8px 0;">
                                <span class="detail-label">Note</span>
                                <span class="detail-value" style="font-weight:400;font-size:12px;">${inv.note}</span>
                            </div>
                            ` : ''}
                            ${inv.attachment ? `
                            <div class="detail-row">
                                <span class="detail-label">Attachment</span>
                                <span class="detail-value">
                                    <button class="btn-sm download" onclick="downloadAttachment(${inv.id})">
                                        <i class="fas fa-file-pdf"></i> Download
                                    </button>
                                </span>
                            </div>
                            ` : ''}
                            <div class="detail-row">
                                <span class="detail-label">PDF Invoice</span>
                                <span class="detail-value">
                                    <button class="btn-sm pdf" onclick="downloadPDF(${inv.id})" style="background:#4a5cf5;color:#fff;padding:6px 14px;">
                                        <i class="fas fa-file-pdf"></i> Download PDF
                                    </button>
                                </span>
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            ${inv.status !== 'Paid' ? `
                            <button class="btn-stripe" onclick="closeModal('modal-view-invoice');openStripePayModal(${inv.id}, ${inv.amount}, '${inv.invoice_number}')" style="padding:10px 20px;font-size:12px;width:auto;">
                                <i class="fas fa-credit-card"></i> Pay Now
                            </button>
                            ` : ''}
                            <button class="btn-sm view" onclick="closeModal('modal-view-invoice')" style="padding:8px 16px;background:#e5e7eb;color:var(--text-secondary);">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    `;
                    
                    setTimeout(function() {
                        const modalAmounts = document.querySelectorAll('#invoice-details-content .amount-display');
                        if (amountsVisible) {
                            modalAmounts.forEach(function(el) {
                                const amount = el.getAttribute('data-amount');
                                if (amount !== null && amount !== '') {
                                    el.textContent = parseFloat(amount).toLocaleString() + ' PKR';
                                    el.classList.add('show');
                                }
                            });
                        }
                    }, 100);
                    
                } else {
                    content.innerHTML = `
                        <div style="text-align:center;padding:20px;color:var(--danger);">
                            <i class="fas fa-exclamation-circle" style="font-size:24px;"></i>
                            <p style="margin-top:8px;">${data.message || 'Failed to load invoice details'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                content.innerHTML = `
                    <div style="text-align:center;padding:20px;color:var(--danger);">
                        <i class="fas fa-exclamation-circle" style="font-size:24px;"></i>
                        <p style="margin-top:8px;">Error loading invoice details</p>
                    </div>
                `;
            });
        }

        // ============================================================
        // ===== SELECT PAYMENT METHOD =====
        // ============================================================
        function selectPaymentMethod(method, element) {
            selectedPaymentMethod = method;
            
            document.querySelectorAll('.payment-method-option').forEach(el => {
                el.classList.remove('active');
                el.style.borderColor = 'var(--border)';
                el.style.background = '#fff';
            });
            
            element.classList.add('active');
            element.style.borderColor = 'var(--primary)';
            element.style.background = '#f0f3ff';
            
            const btn = document.getElementById('stripePayBtn');
            const icons = {
                'card': 'fa-credit-card',
                'bank_transfer': 'fa-university',
                'jazzcash': 'fa-mobile-alt',
                'easypaisa': 'fa-mobile-alt',
                'other': 'fa-ellipsis-h'
            };
            const icon = icons[method] || 'fa-credit-card';
            const currentText = btn.innerHTML;
            const newText = currentText.replace(/fa-[a-z-]+/g, icon);
            btn.innerHTML = newText;
        }

        // ============================================================
        // ===== UPDATE PAY AMOUNT (REAL-TIME) =====
        // ============================================================
        function updatePayAmount(value) {
            const totalAmount = stripeTotalAmount || 0;
            const payText = document.getElementById('stripe-pay-text');
            const payBtn = document.getElementById('stripePayBtn');
            const payInput = document.getElementById('stripe-pay-amount');
            const currencySelect = document.getElementById('stripe-currency');
            const currencyText = document.getElementById('stripe-currency-text');
            const currentCurrency = currencySelect.options[currencySelect.selectedIndex].text;
            
            let amount = parseFloat(value);
            
            if (isNaN(amount) || amount <= 0) {
                amount = 0;
                payInput.value = 0;
            }
            
            if (totalAmount > 0 && amount > totalAmount) {
                amount = totalAmount;
                payInput.value = totalAmount;
                showToast('Amount cannot exceed total invoice amount', 'warning');
            }
            
            currencyText.textContent = currentCurrency;
            payText.textContent = amount.toLocaleString();
            
            if (amount == 0) {
                payBtn.innerHTML = '<i class="fas fa-credit-card"></i> Enter Amount';
                payBtn.disabled = true;
            } else {
                const currentIcon = payBtn.innerHTML.match(/fa-[a-z-]+/)[0] || 'fa-credit-card';
                payBtn.innerHTML = `<i class="${currentIcon}"></i> Pay ${amount.toLocaleString()} ${currentCurrency} <svg width="40" height="16" viewBox="0 0 40 16" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.5" y="0.5" width="39" height="15" rx="2.5" fill="white" stroke="#D1D5DB"/><path d="M6.5 4.5H8.5V11.5H6.5V4.5Z" fill="#6772E5"/><path d="M17.5 4.5H19.5V11.5H17.5V4.5Z" fill="#6772E5"/><circle cx="22" cy="8" r="4" fill="#6772E5"/><circle cx="30" cy="8" r="4" fill="#6772E5"/></svg>`;
                payBtn.disabled = false;
            }
        }

        // ============================================================
        // ===== OPEN STRIPE PAY MODAL =====
        // ============================================================
        function openStripePayModal(invoiceId, amount, invoiceNumber) {
            stripeInvoiceId = invoiceId;
            stripeTotalAmount = amount;
            stripeInvoiceNumber = invoiceNumber || 'INV-' + String(invoiceId).padStart(5, '0');
            
            document.getElementById('stripe-invoice-number').textContent = stripeInvoiceNumber;
            document.getElementById('stripe-total-amount').textContent = amount.toLocaleString() + ' PKR';
            
            const payInput = document.getElementById('stripe-pay-amount');
            payInput.max = amount;
            payInput.value = amount;
            payInput.placeholder = 'Enter amount';
            
            document.getElementById('stripe-currency').value = 'pkr';
            
            selectedPaymentMethod = 'card';
            document.querySelectorAll('.payment-method-option').forEach(el => {
                el.classList.remove('active');
                el.style.borderColor = 'var(--border)';
                el.style.background = '#fff';
            });
            const firstOption = document.querySelector('.payment-method-option');
            if (firstOption) {
                firstOption.classList.add('active');
                firstOption.style.borderColor = 'var(--primary)';
                firstOption.style.background = '#f0f3ff';
            }
            
            updatePayAmount(amount);
            openModal('modal-stripe-pay');
        }

        // ============================================================
        // ===== PROCEED TO STRIPE =====
        // ============================================================
        function proceedToStripe() {
            const btn = document.getElementById('stripePayBtn');
            const payInput = document.getElementById('stripe-pay-amount');
            const currencySelect = document.getElementById('stripe-currency');
            
            let amount = parseFloat(payInput.value);
            const currency = currencySelect.value;
            
            if (isNaN(amount) || amount <= 0) {
                showToast('Please enter a valid amount', 'error');
                return;
            }
            
            if (amount > stripeTotalAmount) {
                showToast('Amount cannot exceed total invoice amount', 'error');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            if (!stripeInvoiceId) {
                showToast('Invalid invoice', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-credit-card"></i> Pay with Stripe';
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'create_stripe_session');
            formData.append('invoice_id', stripeInvoiceId);
            formData.append('amount', amount);
            formData.append('currency', currency);
            formData.append('payment_method', selectedPaymentMethod);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.session_url) {
                    sessionStorage.setItem('stripe_session_id', data.session_id);
                    sessionStorage.setItem('stripe_invoice_id', stripeInvoiceId);
                    sessionStorage.setItem('stripe_invoice_number', stripeInvoiceNumber);
                    sessionStorage.setItem('stripe_paid_amount', amount);
                    sessionStorage.setItem('stripe_currency', currency);
                    
                    window.location.href = data.session_url;
                } else {
                    showToast(data.message || 'Failed to create payment session', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-credit-card"></i> Pay with Stripe';
                }
            })
            .catch(error => {
                showToast('Error: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-credit-card"></i> Pay with Stripe';
            });
        }

        // ============================================================
        // ===== CHECK FOR PAYMENT CANCELLED =====
        // ============================================================
        <?php if (isset($_GET['payment_cancelled'])): ?>
        window.onload = function() {
            showToast('Payment was cancelled', 'warning');
        };
        <?php endif; ?>

        // ============================================================
        // ===== DOWNLOAD PDF =====
        // ============================================================
        function downloadPDF(invoiceId) {
            showToast('Generating PDF...', 'warning');
            
            const formData = new FormData();
            formData.append('ajax_action', 'generate_pdf');
            formData.append('invoice_id', invoiceId);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const inv = data.data.invoice;
                    const client = data.data.client;
                    const company = data.data.company;
                    printInvoice(inv, client, company);
                } else {
                    showToast(data.message || 'Failed to generate PDF', 'error');
                }
            })
            .catch(error => {
                showToast('Error generating PDF', 'error');
            });
        }

        // ============================================================
        // ===== PRINT INVOICE =====
        // ============================================================
        function printInvoice(invoice, client, company) {
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            const issueDate = invoice.issue_date && invoice.issue_date !== '0000-00-00' ? new Date(invoice.issue_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
            const dueDate = invoice.due_date && invoice.due_date !== '0000-00-00' ? new Date(invoice.due_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
            const paidDate = invoice.paid_date && invoice.paid_date !== '0000-00-00' ? new Date(invoice.paid_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'Not paid yet';
            
            const statusColor = {
                'Paid': '#10b981',
                'Due': '#ef4444',
                'Partially Paid': '#f59e0b',
                'Pending': '#f59e0b'
            }[invoice.status] || '#6b7280';
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Invoice ${invoice.invoice_number}</title>
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { 
                            font-family: Arial, sans-serif; 
                            padding: 40px; 
                            background: #ffffff;
                            color: #1a1c26;
                        }
                        .invoice-container {
                            max-width: 800px;
                            margin: 0 auto;
                            border: 1px solid #e5e7eb;
                            border-radius: 12px;
                            padding: 40px;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                        }
                        .header { 
                            display: flex;
                            justify-content: space-between;
                            align-items: flex-start;
                            border-bottom: 3px solid #4a5cf5;
                            padding-bottom: 20px;
                            margin-bottom: 30px;
                        }
                        .header .company h1 { 
                            color: #4a5cf5; 
                            font-size: 28px;
                            margin: 0;
                        }
                        .header .company p { 
                            color: #6b7280; 
                            margin: 5px 0 0 0;
                            font-size: 14px;
                        }
                        .header .invoice-title {
                            text-align: right;
                        }
                        .header .invoice-title h2 {
                            color: #1a1c26;
                            font-size: 22px;
                            margin: 0;
                        }
                        .header .invoice-title p {
                            color: #6b7280;
                            font-size: 14px;
                            margin: 5px 0 0 0;
                        }
                        .client-info {
                            background: #f8fafc;
                            padding: 16px 20px;
                            border-radius: 8px;
                            margin-bottom: 24px;
                        }
                        .client-info .label {
                            color: #6b7280;
                            font-size: 12px;
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }
                        .client-info .name {
                            font-size: 18px;
                            font-weight: 700;
                            color: #1a1c26;
                            margin-top: 2px;
                        }
                        .client-info .email {
                            color: #6b7280;
                            font-size: 14px;
                        }
                        .details-grid {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 12px;
                            margin-bottom: 24px;
                        }
                        .details-grid .item {
                            padding: 10px 14px;
                            background: #f8fafc;
                            border-radius: 6px;
                        }
                        .details-grid .item .label {
                            color: #6b7280;
                            font-size: 11px;
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }
                        .details-grid .item .value {
                            font-size: 15px;
                            font-weight: 700;
                            color: #1a1c26;
                            margin-top: 2px;
                        }
                        .amount-section {
                            background: #f0f3ff;
                            padding: 20px 24px;
                            border-radius: 8px;
                            margin: 20px 0;
                            border-left: 4px solid #4a5cf5;
                        }
                        .amount-section .amount {
                            font-size: 32px;
                            font-weight: 900;
                            color: #4a5cf5;
                        }
                        .amount-section .label {
                            color: #6b7280;
                            font-size: 14px;
                        }
                        .status-badge {
                            display: inline-block;
                            padding: 4px 16px;
                            border-radius: 20px;
                            font-size: 12px;
                            font-weight: 700;
                            background: ${statusColor}22;
                            color: ${statusColor};
                            border: 1px solid ${statusColor}44;
                        }
                        .note-section {
                            margin-top: 20px;
                            padding: 16px 20px;
                            background: #f8fafc;
                            border-radius: 8px;
                        }
                        .note-section .label {
                            color: #6b7280;
                            font-size: 11px;
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }
                        .note-section .note {
                            color: #3d4452;
                            font-size: 14px;
                            margin-top: 4px;
                        }
                        .footer {
                            margin-top: 30px;
                            text-align: center;
                            color: #8a94a0;
                            font-size: 12px;
                            border-top: 1px solid #e5e7eb;
                            padding-top: 20px;
                        }
                        .footer .thankyou {
                            font-size: 16px;
                            font-weight: 700;
                            color: #4a5cf5;
                            margin-bottom: 4px;
                        }
                        @media print {
                            body { padding: 20px; }
                            .invoice-container { box-shadow: none; border: 1px solid #e5e7eb; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="invoice-container">
                        <div class="header">
                            <div class="company">
                                <h1>${company.name}</h1>
                                <p>Digital Marketing Agency</p>
                            </div>
                            <div class="invoice-title">
                                <h2>INVOICE</h2>
                                <p>${invoice.invoice_number}</p>
                            </div>
                        </div>
                        
                        <div class="client-info">
                            <div class="label">Billed To</div>
                            <div class="name">${client.name || 'Client'}</div>
                            <div class="email">${client.email || ''}</div>
                        </div>
                        
                        <div class="details-grid">
                            <div class="item">
                                <div class="label">Invoice Date</div>
                                <div class="value">${issueDate}</div>
                            </div>
                            <div class="item">
                                <div class="label">Due Date</div>
                                <div class="value">${dueDate}</div>
                            </div>
                            <div class="item">
                                <div class="label">Status</div>
                                <div class="value"><span class="status-badge">${invoice.status}</span></div>
                            </div>
                            <div class="item">
                                <div class="label">LPS</div>
                                <div class="value">${invoice.lps || 0}%</div>
                            </div>
                            ${invoice.paid_amount > 0 ? `
                            <div class="item">
                                <div class="label">Paid Amount</div>
                                <div class="value">${parseFloat(invoice.paid_amount).toLocaleString()} PKR</div>
                            </div>
                            ` : ''}
                            <div class="item">
                                <div class="label">Payment Date</div>
                                <div class="value">${paidDate}</div>
                            </div>
                        </div>
                        
                        <div class="amount-section">
                            <div class="label">Total Amount</div>
                            <div class="amount">${parseFloat(invoice.amount).toLocaleString()} PKR</div>
                        </div>
                        
                        ${invoice.note ? `
                        <div class="note-section">
                            <div class="label">Note</div>
                            <div class="note">${invoice.note}</div>
                        </div>
                        ` : ''}
                        
                        <div class="footer">
                            <div class="thankyou">Thank you for your business!</div>
                            <p>${company.name} - ${company.year}</p>
                            <p style="margin-top:4px;font-size:11px;">This is a system-generated invoice. For any queries, please contact support.</p>
                        </div>
                    </div>
                    
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                            }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `);
            
            printWindow.document.close();
        }

        // ============================================================
        // ===== DOWNLOAD ATTACHMENT =====
        // ============================================================
        function downloadAttachment(invoiceId) {
            const formData = new FormData();
            formData.append('ajax_action', 'download_attachment');
            formData.append('invoice_id', invoiceId);
            
            showToast('Preparing download...', 'warning');
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.file) {
                    const file = encodeURIComponent(data.file);
                    const downloadUrl = '../admin-portal/direct_download.php?file=' + file;
                    window.location.href = downloadUrl;
                } else {
                    showToast(data.message || 'File not available', 'error');
                }
            })
            .catch(error => {
                showToast('Error downloading file', 'error');
            });
        }

        // ============================================================
        // ===== SESSION TIMEOUT =====
        // ============================================================
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