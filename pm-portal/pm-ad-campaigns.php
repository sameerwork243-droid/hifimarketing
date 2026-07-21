<?php
// pm-ad-campaigns.php - PM Ad Campaigns Management (FIXED - NO ERRORS)
session_start();
error_reporting(E_ALL);


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

// ===== GET ALL CLIENTS =====
$clients_sql = "SELECT c.*, u.username, u.email FROM clients c JOIN users u ON c.user_id = u.id ORDER BY c.name ASC";
$clients_result = mysqli_query($conn, $clients_sql);
$clients = [];
while ($row = mysqli_fetch_assoc($clients_result)) {
    $clients[] = $row;
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

// ===== GET CLIENT'S ASSIGNED PACKAGES =====
$client_packages = [];
$client_package_ids = [];

if ($selected_client_id > 0) {
    $cp_sql = "SELECT package_id FROM client_packages WHERE client_id = ?";
    $cp_stmt = mysqli_prepare($conn, $cp_sql);
    if ($cp_stmt) {
        mysqli_stmt_bind_param($cp_stmt, "i", $selected_client_id);
        mysqli_stmt_execute($cp_stmt);
        $cp_result = mysqli_stmt_get_result($cp_stmt);
        while ($row = mysqli_fetch_assoc($cp_result)) {
            $client_package_ids[] = $row['package_id'];
        }
        mysqli_stmt_close($cp_stmt);
    }
}

if (!empty($client_package_ids)) {
    $ids_string = implode(',', $client_package_ids);
    $pkg_sql = "SELECT * FROM packages WHERE id IN ($ids_string) AND status = 'active'";
    $pkg_result = mysqli_query($conn, $pkg_sql);
    while ($row = mysqli_fetch_assoc($pkg_result)) {
        $client_packages[] = $row;
    }
}

// ===== GET AD CAMPAIGNS FOR SELECTED CLIENT =====
$campaigns = [];
if ($selected_client_id > 0) {
    $camp_sql = "SELECT * FROM ad_campaigns WHERE client_id = ? ORDER BY created_at DESC";
    $camp_stmt = mysqli_prepare($conn, $camp_sql);
    if ($camp_stmt) {
        mysqli_stmt_bind_param($camp_stmt, "i", $selected_client_id);
        mysqli_stmt_execute($camp_stmt);
        $camp_result = mysqli_stmt_get_result($camp_stmt);
        while ($row = mysqli_fetch_assoc($camp_result)) {
            $campaigns[] = $row;
        }
        mysqli_stmt_close($camp_stmt);
    }
}

// ===== GET CAMPAIGN REPORTS =====
$campaign_reports = [];
if ($selected_client_id > 0) {
    $rep_sql = "SELECT * FROM campaign_reports WHERE client_id = ? ORDER BY created_at DESC";
    $rep_stmt = mysqli_prepare($conn, $rep_sql);
    if ($rep_stmt) {
        mysqli_stmt_bind_param($rep_stmt, "i", $selected_client_id);
        mysqli_stmt_execute($rep_stmt);
        $rep_result = mysqli_stmt_get_result($rep_stmt);
        while ($row = mysqli_fetch_assoc($rep_result)) {
            $campaign_reports[] = $row;
        }
        mysqli_stmt_close($rep_stmt);
    }
}

// ===== AVAILABLE AD PLATFORMS =====
$ad_platforms = [
    'Meta Ads' => ['icon' => 'fa-facebook', 'color' => '#1877F2'],
    'Google Ads' => ['icon' => 'fa-google', 'color' => '#EA4335'],
    'TikTok Ads' => ['icon' => 'fa-tiktok', 'color' => '#000000'],
    'Instagram Ads' => ['icon' => 'fa-instagram', 'color' => '#E4405F'],
    'Snapchat Ads' => ['icon' => 'fa-snapchat', 'color' => '#FFFC00'],
    'Facebook Ads' => ['icon' => 'fa-facebook', 'color' => '#1877F2'],
    'LinkedIn Ads' => ['icon' => 'fa-linkedin', 'color' => '#0A66C2'],
    'Twitter Ads' => ['icon' => 'fa-twitter', 'color' => '#1DA1F2'],
    'Pinterest Ads' => ['icon' => 'fa-pinterest', 'color' => '#E60023'],
    'YouTube Ads' => ['icon' => 'fa-youtube', 'color' => '#FF0000']
];

// ===== GET PLATFORM CAMPAIGN COUNTS =====
$platform_counts = [];
foreach ($ad_platforms as $platform => $info) {
    $count = 0;
    foreach ($campaigns as $camp) {
        if ($camp['platform'] == $platform) {
            $count++;
        }
    }
    $platform_counts[$platform] = $count;
}

// ===== AJAX HANDLER =====
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];
    
    // ===== ADD CAMPAIGN =====
    if ($_POST['ajax_action'] === 'add_campaign') {
        $client_id = intval($_POST['client_id']);
        $platform = trim($_POST['platform']);
        $campaign_name = trim($_POST['campaign_name']);
        $status = trim($_POST['status']);
        $budget = floatval($_POST['budget']);
        $cpc = floatval($_POST['cpc']);
        $cpm = floatval($_POST['cpm']);
        $cpa = floatval($_POST['cpa']);
        $start_date = trim($_POST['start_date']);
        $end_date = trim($_POST['end_date']);
        $target_audience = trim($_POST['target_audience']);
        $notes = trim($_POST['notes']);
        
        if ($client_id > 0 && !empty($platform) && !empty($campaign_name)) {
            $sql = "INSERT INTO ad_campaigns (client_id, platform, campaign_name, status, budget, cpc, cpm, cpa, start_date, end_date, target_audience, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isssddddssss", 
                    $client_id, $platform, $campaign_name, $status, $budget, 
                    $cpc, $cpm, $cpa, $start_date, $end_date, $target_audience, $notes
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    $response = ['success' => true, 'message' => 'Campaign added successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Database error: ' . mysqli_stmt_error($stmt)];
                }
                mysqli_stmt_close($stmt);
            } else {
                $response = ['success' => false, 'message' => 'Prepare failed: ' . mysqli_error($conn)];
            }
        } else {
            $response = ['success' => false, 'message' => 'Please fill all required fields'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== UPDATE CAMPAIGN =====
    elseif ($_POST['ajax_action'] === 'update_campaign') {
        $campaign_id = intval($_POST['campaign_id']);
        $platform = trim($_POST['platform']);
        $campaign_name = trim($_POST['campaign_name']);
        $status = trim($_POST['status']);
        $budget = floatval($_POST['budget']);
        $spent = floatval($_POST['spent']);
        $impressions = intval($_POST['impressions']);
        $clicks = intval($_POST['clicks']);
        $conversions = intval($_POST['conversions']);
        $cpc = floatval($_POST['cpc']);
        $cpm = floatval($_POST['cpm']);
        $cpa = floatval($_POST['cpa']);
        $start_date = trim($_POST['start_date']);
        $end_date = trim($_POST['end_date']);
        $target_audience = trim($_POST['target_audience']);
        $notes = trim($_POST['notes']);
        
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        $roi = $spent > 0 ? round($conversions / ($spent / 1000), 1) : 0;
        
        if ($campaign_id > 0) {
            $sql = "UPDATE ad_campaigns SET 
                    platform = ?, campaign_name = ?, status = ?, budget = ?, spent = ?, 
                    impressions = ?, clicks = ?, conversions = ?, ctr = ?, roi = ?, 
                    cpc = ?, cpm = ?, cpa = ?, start_date = ?, end_date = ?, 
                    target_audience = ?, notes = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssdddddidddddsssi", 
                $platform, $campaign_name, $status, $budget, $spent, 
                $impressions, $clicks, $conversions, $ctr, $roi, 
                $cpc, $cpm, $cpa, $start_date, $end_date, 
                $target_audience, $notes, $campaign_id
            );
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Campaign updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Database error: ' . mysqli_stmt_error($stmt)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid campaign ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== DELETE CAMPAIGN =====
    elseif ($_POST['ajax_action'] === 'delete_campaign') {
        $campaign_id = intval($_POST['campaign_id']);
        
        if ($campaign_id > 0) {
            $sql = "DELETE FROM ad_campaigns WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $campaign_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'message' => 'Campaign deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Database error: ' . mysqli_stmt_error($stmt)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid campaign ID'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== GENERATE REPORT =====
    elseif ($_POST['ajax_action'] === 'generate_report') {
        $campaign_id = intval($_POST['campaign_id']);
        $client_id = intval($_POST['client_id']);
        $report_date = trim($_POST['report_date']);
        $spent = floatval($_POST['spent']);
        $impressions = intval($_POST['impressions']);
        $clicks = intval($_POST['clicks']);
        $conversions = intval($_POST['conversions']);
        $platform = trim($_POST['platform']);
        
        if ($campaign_id > 0 && $client_id > 0) {
            $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
            $roi = $spent > 0 ? round($conversions / ($spent / 1000), 1) : 0;
            
            $sql = "INSERT INTO campaign_reports (client_id, campaign_id, platform, report_date, spent, impressions, clicks, conversions, ctr, roi) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iissdddddd", 
                $client_id, $campaign_id, $platform, $report_date, 
                $spent, $impressions, $clicks, $conversions, $ctr, $roi
            );
            if (mysqli_stmt_execute($stmt)) {
                $update_sql = "UPDATE ad_campaigns SET spent = ?, impressions = ?, clicks = ?, conversions = ?, ctr = ?, roi = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "ddddddi", $spent, $impressions, $clicks, $conversions, $ctr, $roi, $campaign_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                $response = ['success' => true, 'message' => 'Report generated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Database error: ' . mysqli_stmt_error($stmt)];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
        
        echo json_encode($response);
        exit();
    }
    
    // ===== EXPORT PDF (NO LIBRARY - HTML PRINT) =====
    elseif ($_POST['ajax_action'] === 'export_pdf') {
        $campaign_id = intval($_POST['campaign_id']);
        $client_id = intval($_POST['client_id']);
        
        if ($campaign_id > 0 && $client_id > 0) {
            // Get campaign details
            $camp_sql = "SELECT * FROM ad_campaigns WHERE id = ? AND client_id = ?";
            $camp_stmt = mysqli_prepare($conn, $camp_sql);
            mysqli_stmt_bind_param($camp_stmt, "ii", $campaign_id, $client_id);
            mysqli_stmt_execute($camp_stmt);
            $camp_result = mysqli_stmt_get_result($camp_stmt);
            $campaign = mysqli_fetch_assoc($camp_result);
            mysqli_stmt_close($camp_stmt);
            
            // Get client details
            $client_sql = "SELECT * FROM clients WHERE id = ?";
            $client_stmt = mysqli_prepare($conn, $client_sql);
            mysqli_stmt_bind_param($client_stmt, "i", $client_id);
            mysqli_stmt_execute($client_stmt);
            $client_result = mysqli_stmt_get_result($client_stmt);
            $client = mysqli_fetch_assoc($client_result);
            mysqli_stmt_close($client_stmt);
            
            // Get reports for this campaign
            $rep_sql = "SELECT * FROM campaign_reports WHERE campaign_id = ? ORDER BY report_date DESC";
            $rep_stmt = mysqli_prepare($conn, $rep_sql);
            mysqli_stmt_bind_param($rep_stmt, "i", $campaign_id);
            mysqli_stmt_execute($rep_stmt);
            $rep_result = mysqli_stmt_get_result($rep_stmt);
            $reports = [];
            while ($row = mysqli_fetch_assoc($rep_result)) {
                $reports[] = $row;
            }
            mysqli_stmt_close($rep_stmt);
            
            if ($campaign) {
                // Format dates properly
                $start_date = 'N/A';
                if (!empty($campaign['start_date']) && $campaign['start_date'] != '0000-00-00') {
                    $start_date = date('F d, Y', strtotime($campaign['start_date']));
                }
                $end_date = 'N/A';
                if (!empty($campaign['end_date']) && $campaign['end_date'] != '0000-00-00') {
                    $end_date = date('F d, Y', strtotime($campaign['end_date']));
                }
                
                // ===== GENERATE HTML REPORT WITH PRINT CSS (FIXED) =====
                header('Content-Type: text/html');
                
                $html_output = '<!DOCTYPE html>
                <html>
                <head>
                    <title>Campaign Report - ' . htmlspecialchars($campaign['campaign_name']) . '</title>
                    <style>
                        /* ===== PRINT CSS - HIDE SIDEBAR & HEADER ===== */
                        @media print {
                            body { 
                                padding: 20px; 
                                margin: 0; 
                                background: #fff;
                            }
                            .no-print { 
                                display: none !important; 
                            }
                            /* Hide sidebar and other UI elements */
                            header, .sidebar, .main-layout, .desktop-nav, .mobile-menu-toggle,
                            .header-actions, .banner-actions, .btn-white, .badge,
                            .client-selector, .stats-grid, .platform-grid, .card-actions,
                            .modal-overlay, .toast-container, .security-badge {
                                display: none !important;
                            }
                            .content {
                                margin: 0 !important;
                                padding: 0 !important;
                                width: 100% !important;
                                max-width: 100% !important;
                            }
                            .main-layout {
                                display: block !important;
                                padding: 0 !important;
                            }
                            .banner {
                                display: none !important;
                            }
                            .page-break { 
                                page-break-after: always; 
                            }
                            .campaign-grid {
                                display: block !important;
                            }
                            .campaign-card {
                                page-break-inside: avoid;
                            }
                            .report-container {
                                padding: 20px;
                                max-width: 1000px;
                                margin: 0 auto;
                            }
                        }
                        
                        /* ===== SCREEN STYLES ===== */
                        body { 
                            font-family: Arial, sans-serif; 
                            padding: 30px; 
                            max-width: 1000px; 
                            margin: 0 auto; 
                            background: #f0f2f5;
                        }
                        .no-print { 
                            background: #4a5cf5; 
                            color: #fff; 
                            padding: 12px 24px; 
                            border: none; 
                            border-radius: 40px; 
                            font-size: 14px; 
                            font-weight: bold; 
                            cursor: pointer; 
                            margin-bottom: 20px;
                            display: inline-block;
                        }
                        .no-print:hover { 
                            background: #3a4be0; 
                        }
                        
                        /* ===== REPORT CONTAINER ===== */
                        .report-container {
                            background: #ffffff;
                            border-radius: 16px;
                            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
                            padding: 30px;
                            border: 1px solid #e9edf2;
                        }
                        
                        /* ===== HEADER ===== */
                        .header { 
                            background: linear-gradient(135deg, #4a5cf5 0%, #3a4be0 100%);
                            color: #fff; 
                            padding: 30px; 
                            text-align: center; 
                            border-radius: 12px;
                            margin-bottom: 25px;
                        }
                        .header h1 { 
                            margin: 0; 
                            font-size: 24px; 
                            font-weight: 800;
                        }
                        .header h1 i {
                            font-style: normal;
                        }
                        .header p { 
                            margin: 8px 0 0; 
                            opacity: 0.9; 
                            font-size: 13px; 
                        }
                        .header .report-id {
                            display: inline-block;
                            background: rgba(255,255,255,0.15);
                            padding: 3px 16px;
                            border-radius: 20px;
                            font-size: 11px;
                            margin-top: 8px;
                        }
                        
                        /* ===== SECTIONS ===== */
                        .section { 
                            margin-top: 25px; 
                        }
                        .section-title { 
                            font-size: 16px; 
                            font-weight: 700; 
                            color: #1a1c26; 
                            border-bottom: 3px solid #4a5cf5; 
                            padding-bottom: 8px; 
                            margin-bottom: 15px; 
                        }
                        
                        /* ===== INFO GRID ===== */
                        .info-grid { 
                            display: grid; 
                            grid-template-columns: 1fr 1fr; 
                            gap: 10px; 
                            background: #f8fafc; 
                            padding: 16px 20px; 
                            border-radius: 10px; 
                            border: 1px solid #e9edf2;
                        }
                        .info-item { 
                            padding: 4px 0; 
                        }
                        .info-item .label { 
                            font-weight: 600; 
                            color: #3d4452; 
                            font-size: 12px;
                        }
                        .info-item .value { 
                            color: #1a1c26; 
                            font-weight: 500;
                            font-size: 13px;
                        }
                        .info-item .value .status-badge {
                            display: inline-block;
                            padding: 2px 12px;
                            border-radius: 20px;
                            font-size: 11px;
                            font-weight: 600;
                        }
                        .status-badge.Draft {
                            background: #f1f5f9;
                            color: #475569;
                        }
                        .status-badge.Active {
                            background: #d1fae5;
                            color: #065f46;
                        }
                        .status-badge.Paused {
                            background: #fef3c7;
                            color: #92400e;
                        }
                        .status-badge.Completed {
                            background: #dbeafe;
                            color: #1e40af;
                        }
                        
                        /* ===== METRICS GRID ===== */
                        .metrics-grid { 
                            display: grid; 
                            grid-template-columns: repeat(3, 1fr); 
                            gap: 12px; 
                            margin-top: 12px; 
                        }
                        .metric-box { 
                            background: #f8fafc; 
                            padding: 16px; 
                            border-radius: 10px; 
                            text-align: center; 
                            border: 1px solid #e9edf2;
                        }
                        .metric-box .number { 
                            font-size: 24px; 
                            font-weight: 800; 
                            color: #1a1c26; 
                        }
                        .metric-box .label { 
                            font-size: 11px; 
                            color: #8a94a0; 
                            margin-top: 3px;
                        }
                        
                        /* ===== COST METRICS ===== */
                        .cost-grid {
                            display: grid;
                            grid-template-columns: 1fr 1fr 1fr 1fr;
                            gap: 12px;
                            margin-top: 12px;
                        }
                        .cost-box {
                            background: #f8fafc;
                            padding: 14px 16px;
                            border-radius: 10px;
                            text-align: center;
                            border: 1px solid #e9edf2;
                        }
                        .cost-box .number {
                            font-size: 18px;
                            font-weight: 700;
                            color: #1a1c26;
                        }
                        .cost-box .label {
                            font-size: 10px;
                            color: #8a94a0;
                            margin-top: 2px;
                        }
                        
                        /* ===== TABLE ===== */
                        .table-wrap { 
                            overflow-x: auto; 
                            margin-top: 12px;
                        }
                        .table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            font-size: 12px; 
                        }
                        .table th { 
                            background: #4a5cf5; 
                            color: #fff; 
                            padding: 10px 14px; 
                            text-align: left; 
                            font-size: 11px;
                            font-weight: 600;
                        }
                        .table td { 
                            padding: 10px 14px; 
                            border-bottom: 1px solid #e9edf2; 
                            font-size: 12px; 
                            color: #3d4452;
                        }
                        .table tr:nth-child(even) { 
                            background: #f8fafc; 
                        }
                        .table tr:hover td { 
                            background: #f0f3ff; 
                        }
                        
                        /* ===== NOTES ===== */
                        .notes-box {
                            background: #f8fafc;
                            padding: 12px 16px;
                            border-radius: 8px;
                            border: 1px solid #e9edf2;
                            font-size: 13px;
                            color: #3d4452;
                        }
                        
                        /* ===== FOOTER ===== */
                        .footer { 
                            margin-top: 30px; 
                            padding-top: 15px; 
                            border-top: 1px solid #e9edf2; 
                            text-align: center; 
                            font-size: 11px; 
                            color: #8a94a0; 
                        }
                        
                        /* ===== RESPONSIVE ===== */
                        @media (max-width: 768px) {
                            .info-grid { 
                                grid-template-columns: 1fr; 
                            }
                            .metrics-grid { 
                                grid-template-columns: 1fr 1fr; 
                            }
                            .cost-grid {
                                grid-template-columns: 1fr 1fr;
                            }
                            body { 
                                padding: 15px; 
                            }
                            .report-container {
                                padding: 15px;
                            }
                            .header {
                                padding: 20px;
                            }
                            .header h1 {
                                font-size: 18px;
                            }
                        }
                        @media (max-width: 480px) {
                            .metrics-grid { 
                                grid-template-columns: 1fr; 
                            }
                            .cost-grid {
                                grid-template-columns: 1fr;
                            }
                        }
                        
                        /* ===== PRINT OVERRIDES ===== */
                        @media print {
                            .report-container {
                                box-shadow: none !important;
                                border: none !important;
                                padding: 10px !important;
                                border-radius: 0 !important;
                            }
                            .header {
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                                background: linear-gradient(135deg, #4a5cf5 0%, #3a4be0 100%) !important;
                            }
                            .table th {
                                background: #4a5cf5 !important;
                                color: #fff !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            .status-badge {
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            .metric-box {
                                background: #f8fafc !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            .cost-box {
                                background: #f8fafc !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            .info-grid {
                                background: #f8fafc !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            .notes-box {
                                background: #f8fafc !important;
                                -webkit-print-color-adjust: exact !important;
                                print-color-adjust: exact !important;
                            }
                            .no-print {
                                display: none !important;
                            }
                            body {
                                background: #fff !important;
                                padding: 0 !important;
                                margin: 0 !important;
                            }
                        }
                    </style>
                </head>
                <body>
                    <button class="no-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
                    
                    <div class="report-container">
                        <!-- ===== HEADER ===== -->
                        <div class="header">
                            <h1>📊 Campaign Performance Report</h1>
                            <p>Generated on ' . date('F d, Y H:i:s') . '</p>
                            <span class="report-id">Report #' . str_pad($campaign_id, 5, '0', STR_PAD_LEFT) . '</span>
                        </div>
                        
                        <!-- ===== CAMPAIGN OVERVIEW ===== -->
                        <div class="section">
                            <div class="section-title">Campaign Overview</div>
                            <div class="info-grid">
                                <div class="info-item"><span class="label">Campaign Name:</span> <span class="value">' . htmlspecialchars($campaign['campaign_name']) . '</span></div>
                                <div class="info-item"><span class="label">Platform:</span> <span class="value">' . htmlspecialchars($campaign['platform']) . '</span></div>
                                <div class="info-item"><span class="label">Status:</span> <span class="value"><span class="status-badge ' . $campaign['status'] . '">' . $campaign['status'] . '</span></span></div>
                                <div class="info-item"><span class="label">Client:</span> <span class="value">' . htmlspecialchars($client['email'] ?? $client['name'] ?? 'N/A') . '</span></div>
                                <div class="info-item"><span class="label">Budget:</span> <span class="value">' . number_format($campaign['budget']) . ' PKR</span></div>
                                <div class="info-item"><span class="label">Start Date:</span> <span class="value">' . $start_date . '</span></div>
                                <div class="info-item"><span class="label">End Date:</span> <span class="value">' . $end_date . '</span></div>
                                <div class="info-item"><span class="label">Target Audience:</span> <span class="value">' . htmlspecialchars($campaign['target_audience'] ?? 'N/A') . '</span></div>
                            </div>
                        </div>
                        
                        <!-- ===== PERFORMANCE METRICS ===== -->
                        <div class="section">
                            <div class="section-title">Performance Metrics</div>
                            <div class="metrics-grid">
                                <div class="metric-box">
                                    <div class="number">' . number_format($campaign['spent'] ?? 0) . ' PKR</div>
                                    <div class="label">Total Spent</div>
                                </div>
                                <div class="metric-box">
                                    <div class="number">' . number_format($campaign['impressions'] ?? 0) . '</div>
                                    <div class="label">Impressions</div>
                                </div>
                                <div class="metric-box">
                                    <div class="number">' . number_format($campaign['clicks'] ?? 0) . '</div>
                                    <div class="label">Clicks</div>
                                </div>
                                <div class="metric-box">
                                    <div class="number">' . number_format($campaign['conversions'] ?? 0) . '</div>
                                    <div class="label">Conversions</div>
                                </div>
                                <div class="metric-box">
                                    <div class="number">' . ($campaign['ctr'] ?? 0) . '%</div>
                                    <div class="label">CTR (Click-Through Rate)</div>
                                </div>
                                <div class="metric-box">
                                    <div class="number" style="color:' . (($campaign['roi'] ?? 0) > 0 ? '#10b981' : '#94a3b8') . ';">' . ($campaign['roi'] ?? 0) . 'x</div>
                                    <div class="label">ROI (Return on Investment)</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ===== COST METRICS ===== -->
                        <div class="section">
                            <div class="section-title">Cost Metrics</div>
                            <div class="cost-grid">
                                <div class="cost-box">
                                    <div class="number">' . number_format($campaign['cpc'] ?? 0, 2) . ' PKR</div>
                                    <div class="label">CPC (Cost Per Click)</div>
                                </div>
                                <div class="cost-box">
                                    <div class="number">' . number_format($campaign['cpm'] ?? 0, 2) . ' PKR</div>
                                    <div class="label">CPM (Cost Per 1000 Impressions)</div>
                                </div>
                                <div class="cost-box">
                                    <div class="number">' . number_format($campaign['cpa'] ?? 0, 2) . ' PKR</div>
                                    <div class="label">CPA (Cost Per Acquisition)</div>
                                </div>
                                <div class="cost-box">
                                    <div class="number">' . ($campaign['budget'] > 0 ? round(($campaign['spent'] / $campaign['budget']) * 100) : 0) . '%</div>
                                    <div class="label">Budget Utilization</div>
                                </div>
                            </div>
                        </div>';
                
                if (!empty($reports)) {
                    $html_output .= '<div class="section">
                        <div class="section-title">Historical Reports</div>
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Spent (PKR)</th>
                                        <th>Impressions</th>
                                        <th>Clicks</th>
                                        <th>Conversions</th>
                                        <th>CTR</th>
                                        <th>ROI</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    foreach ($reports as $report) {
                        $html_output .= '<tr>
                            <td>' . date('Y-m-d', strtotime($report['report_date'])) . '</td>
                            <td>' . number_format($report['spent']) . '</td>
                            <td>' . number_format($report['impressions']) . '</td>
                            <td>' . number_format($report['clicks']) . '</td>
                            <td>' . number_format($report['conversions']) . '</td>
                            <td>' . $report['ctr'] . '%</td>
                            <td>' . $report['roi'] . 'x</td>
                        </tr>';
                    }
                    $html_output .= '</tbody></table></div></div>';
                }
                
                if (!empty($campaign['notes'])) {
                    $html_output .= '<div class="section">
                        <div class="section-title">Additional Notes</div>
                        <div class="notes-box">' . nl2br(htmlspecialchars($campaign['notes'])) . '</div>
                    </div>';
                }
                
                $html_output .= '<div class="footer">
                    <p>This report is automatically generated by HIFI Marketing PM Portal.</p>
                    <p>For any queries, please contact your account manager.</p>
                    <p style="margin-top:4px;font-size:10px;">Report ID: #' . str_pad($campaign_id, 5, '0', STR_PAD_LEFT) . ' | Generated: ' . date('Y-m-d H:i:s') . '</p>
                </div>
                    </div>
                    
                    <div class="no-print" style="text-align:center;margin-top:20px;">
                        <button onclick="window.print()" style="background:#4a5cf5;color:#fff;padding:12px 30px;border:none;border-radius:40px;font-size:14px;font-weight:bold;cursor:pointer;">🖨️ Print / Save as PDF</button>
                    </div>
                </body>
                </html>';
                
                echo $html_output;
                exit();
            } else {
                $response = ['success' => false, 'message' => 'Campaign not found'];
                echo json_encode($response);
                exit();
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
            echo json_encode($response);
            exit();
        }
    }
}

// ===== GET CLIENT PACKAGE INFO =====
$client_package_names = [];
foreach ($client_packages as $pkg) {
    $client_package_names[] = $pkg['name'];
}
$package_names_str = !empty($client_package_names) ? implode(', ', $client_package_names) : 'No packages assigned';

// ============================================================ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Ad Campaigns Management | PM Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" href="../images/fav-icon.png" type="image/png" />
    <style>
        :root {
            --primary: #4a5cf5;
            --primary-dark:  #3a4be0;
            --pm: #0b78f5;
            --pm-dark: #0649d9;
            --pm-light: #c7e1fe;
            --bg: #f0f2f5;
            --card: #ffffff;
            --text: #1a1c26;
            --text-secondary: #3d4452;
            --text-muted: #8a94a0;
            --border: #e9edf2;
            --radius: 16px;
            --shadow: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.08);
            --transition: 0.25s ease;
            --success: #10b981;
            --danger: #ef4444;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        a { text-decoration: none; color: inherit; }

        header {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 10px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 8px rgba(0,0,0,0.04);
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 16px;
            gap: 10px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 18px;
            font-weight: 900;
            color: var(--text);
            flex-shrink: 0;
        }
        .logo .brand-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--pm), var(--pm-dark));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            font-weight: 900;
        }
        .logo span { color: var(--pm); }

        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header-right .nav-link {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            transition: var(--transition);
        }
        .header-right .nav-link:hover {
            background: var(--pm-light);
            color: var(--pm);
        }
        .header-right .user-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px 3px 3px;
            border-radius: 40px;
            background: var(--pm-light);
            font-weight: 600;
            font-size: 12px;
            color: var(--text);
        }
        .header-right .user-badge img {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            object-fit: cover;
        }
        .header-right .btn-logout {
            color: #dc3545;
            padding: 6px 8px;
            border-radius: 6px;
            transition: var(--transition);
            font-size: 15px;
        }
        .header-right .btn-logout:hover {
            background: #fee2e2;
        }

        .main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 16px;
        }

        .client-selector {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
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
            transition: var(--transition);
        }
        .client-selector select:focus {
            outline: none;
            border-color: var(--pm);
            box-shadow: 0 0 0 3px rgba(245,158,11,0.1);
        }
        .client-selector .client-info {
            font-size: 12px;
            color: var(--text-muted);
        }
        .client-selector .client-info strong {
            color: var(--text);
        }

        .banner {
            background: linear-gradient(135deg, var(--pm), var(--pm-dark));
            border-radius: var(--radius);
            padding: 16px 20px;
            color: #fff;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .banner h2 {
            font-size: 17px;
            font-weight: 800;
        }
        .banner p {
            opacity: 0.85;
            font-size: 12px;
            margin-top: 2px;
        }
        .banner .badge {
            background: rgba(255,255,255,0.2);
            padding: 3px 14px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 16px;
        }
        .stat-card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 14px 16px;
            transition: var(--transition);
        }
        .stat-card:hover {
            box-shadow: var(--shadow-hover);
        }
        .stat-card .number {
            font-size: 22px;
            font-weight: 900;
            color: var(--text);
        }
        .stat-card .label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .stat-card .icon {
            float: right;
            font-size: 20px;
            opacity: 0.15;
            color: var(--pm);
        }

        .platform-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .platform-card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 14px 16px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }
        .platform-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: var(--pm);
        }
        .platform-card .platform-icon {
            font-size: 24px;
            margin-bottom: 4px;
        }
        .platform-card .platform-name {
            font-weight: 600;
            font-size: 12px;
            color: var(--text);
        }
        .platform-card .campaign-count {
            font-size: 10px;
            color: var(--text-muted);
        }
        .platform-card .add-btn {
            margin-top: 6px;
            padding: 2px 12px;
            border-radius: 20px;
            border: none;
            background: var(--pm);
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .platform-card .add-btn:hover {
            background: var(--pm-dark);
        }

        .campaign-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }
        .campaign-card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 16px 18px;
            transition: var(--transition);
            position: relative;
        }
        .campaign-card:hover {
            box-shadow: var(--shadow-hover);
        }
        .campaign-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .campaign-card .card-header .campaign-info .campaign-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--text);
        }
        .campaign-card .card-header .campaign-info .platform-tag {
            font-size: 10px;
            color: var(--text-muted);
        }
        .campaign-card .card-header .status-badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .campaign-card .card-header .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        .campaign-card .card-header .status-badge.paused {
            background: #fef3c7;
            color: #92400e;
        }
        .campaign-card .card-header .status-badge.draft {
            background: #f1f5f9;
            color: #475569;
        }
        .campaign-card .card-header .status-badge.completed {
            background: #dbeafe;
            color: #1e40af;
        }

        .campaign-card .metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            padding: 10px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            margin: 8px 0;
        }
        .campaign-card .metrics .metric-item {
            text-align: center;
        }
        .campaign-card .metrics .metric-item .metric-value {
            font-size: 15px;
            font-weight: 800;
            color: var(--text);
        }
        .campaign-card .metrics .metric-item .metric-label {
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .campaign-card .progress-section {
            margin-top: 8px;
        }
        .campaign-card .progress-section .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: var(--text-muted);
        }
        .campaign-card .progress-section .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 3px;
        }
        .campaign-card .progress-section .progress-bar .fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
            background: var(--pm);
        }

        .campaign-card .card-actions {
            display: flex;
            gap: 6px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
        }
        .campaign-card .card-actions .btn-action {
            padding: 4px 12px;
            border-radius: 20px;
            border: none;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .campaign-card .card-actions .btn-action.edit {
            background: #f0f3ff;
            color: var(--primary);
        }
        .campaign-card .card-actions .btn-action.edit:hover {
            background: var(--primary);
            color: #fff;
        }
        .campaign-card .card-actions .btn-action.report {
            background: var(--pm-light);
            color: var(--pm);
        }
        .campaign-card .card-actions .btn-action.report:hover {
            background: var(--pm);
            color: #fff;
        }
        .campaign-card .card-actions .btn-action.export-pdf {
            background: #fef3c7;
            color: #92400e;
        }
        .campaign-card .card-actions .btn-action.export-pdf:hover {
            background: var(--pm);
            color: #fff;
        }
        .campaign-card .card-actions .btn-action.delete {
            background: #fee2e2;
            color: #dc3545;
        }
        .campaign-card .card-actions .btn-action.delete:hover {
            background: #dc3545;
            color: #fff;
        }

        .btn-pm {
            background: var(--pm);
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-pm:hover {
            background: var(--pm-dark);
            transform: translateY(-1px);
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
            background: var(--card);
            border-radius: var(--radius);
            max-width: 560px;
            width: 100%;
            padding: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal .modal-close {
            position: absolute;
            top: 12px;
            right: 14px;
            background: transparent;
            border: none;
            font-size: 18px;
            color: var(--text-muted);
            cursor: pointer;
        }
        .modal .modal-close:hover { color: var(--text); }
        .modal h3 {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 2px;
        }
        .modal .modal-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 14px;
        }
        .modal .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .modal .form-group {
            margin-bottom: 8px;
        }
        .modal .form-group.full-width {
            grid-column: 1 / -1;
        }
        .modal .form-group label {
            display: block;
            font-weight: 600;
            font-size: 11px;
            color: var(--text-secondary);
            margin-bottom: 2px;
        }
        .modal .form-group input,
        .modal .form-group select,
        .modal .form-group textarea {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: #f8fafc;
            font-size: 12px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }
        .modal .form-group input:focus,
        .modal .form-group select:focus,
        .modal .form-group textarea:focus {
            outline: none;
            border-color: var(--pm);
            box-shadow: 0 0 0 3px rgba(245,158,11,0.1);
        }
        .modal .btn-submit {
            width: 100%;
            padding: 10px;
            background: var(--pm);
            color: #fff;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
        }
        .modal .btn-submit:hover {
            background: var(--pm-dark);
            transform: translateY(-1px);
        }

        .toast-container {
            position: fixed;
            top: 76px;
            right: 16px;
            z-index: 300;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .toast {
            background: var(--text);
            color: #fff;
            padding: 10px 16px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 220px;
            animation: slideIn 0.3s ease;
        }
        .toast.success i { color: #10b981; }
        .toast.error i { color: #ef4444; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 36px;
            display: block;
            margin-bottom: 8px;
            opacity: 0.3;
        }
        .empty-state p {
            font-size: 12px;
        }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stats-grid .stat-card { padding: 12px 14px; }
            .stats-grid .stat-card .number { font-size: 18px; }
            .campaign-grid { grid-template-columns: 1fr; }
            .platform-grid { grid-template-columns: repeat(3, 1fr); }
            .modal .form-grid { grid-template-columns: 1fr; }
            .banner { flex-direction: column; text-align: center; }
            .client-selector { flex-direction: column; align-items: stretch; }
            .client-selector select { width: 100%; }
            .main { padding: 10px; }
        }
        @media (max-width: 480px) {
            .platform-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header>
        <div class="header-inner">
            <div class="logo">
                <div class="brand-icon">P</div>
                HIFI <span>Marketing</span>
            </div>
            <div class="header-right">
                <a href="index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to PM</a>
                <div class="user-badge">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <span><?php echo $userData['name'] ?? 'PM'; ?></span>
                </div>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </header>

    <div class="main">

        <div class="client-selector">
            <label for="clientSelect"><i class="fas fa-user"></i> Select Client:</label>
            <select id="clientSelect" onchange="window.location.href='?client_id=' + this.value">
                <option value="">-- Select Client --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                        <?php echo $client['name']; ?> (<?php echo $client['username']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($selected_client): ?>
            <span class="client-info">
                <strong><?php echo $selected_client['name']; ?></strong>
                &bull; Email: <?php echo $selected_client['email']; ?>
                &bull; Packages: <?php echo $package_names_str; ?>
            </span>
            <?php endif; ?>
        </div>

        <?php if ($selected_client): ?>

        <div class="banner">
            <div>
                <h2><i class="fas fa-bullhorn"></i> Ad Campaigns: <?php echo $selected_client['name']; ?></h2>
                <p>Manage campaigns across all ad platforms &bull; <?php echo count($campaigns); ?> total campaigns</p>
            </div>
            <div>
                <button class="badge" onclick="openModal('modal-add-campaign')" style="background:rgba(255,255,255,0.3);border:none;color:#fff;cursor:pointer;">
                    <i class="fas fa-plus"></i> New Campaign
                </button>
            </div>
        </div>

        <?php 
        $total_budget = 0;
        $total_spent = 0;
        $total_impressions = 0;
        $total_conversions = 0;
        $active_count = 0;
        foreach ($campaigns as $camp) {
            $total_budget += $camp['budget'] ?? 0;
            $total_spent += $camp['spent'] ?? 0;
            $total_impressions += $camp['impressions'] ?? 0;
            $total_conversions += $camp['conversions'] ?? 0;
            if (($camp['status'] ?? '') == 'Active') $active_count++;
        }
        $avg_roi = $total_spent > 0 ? round($total_conversions / ($total_spent / 1000), 1) : 0;
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-coins"></i></div>
                <div class="number"><?php echo number_format($total_spent); ?> PKR</div>
                <div class="label">Total Spent (Budget: <?php echo number_format($total_budget); ?> PKR)</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-eye"></i></div>
                <div class="number"><?php echo number_format($total_impressions); ?></div>
                <div class="label">Total Impressions</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="number"><?php echo number_format($total_conversions); ?></div>
                <div class="label">Total Conversions</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-trend-up"></i></div>
                <div class="number" style="color:<?php echo $avg_roi > 0 ? '#10b981' : '#94a3b8'; ?>;"><?php echo $avg_roi; ?>x</div>
                <div class="label">Avg ROI (<?php echo $active_count; ?> Active)</div>
            </div>
        </div>

        <div class="platform-grid">
            <?php foreach ($ad_platforms as $platform => $info): 
                $count = $platform_counts[$platform] ?? 0;
            ?>
            <div class="platform-card">
                <div class="platform-icon" style="color:<?php echo $info['color']; ?>;">
                    <i class="fab <?php echo $info['icon']; ?>"></i>
                </div>
                <div class="platform-name"><?php echo $platform; ?></div>
                <div class="campaign-count"><?php echo $count; ?> campaigns</div>
                <button class="add-btn" onclick="openModalWithPlatform('<?php echo $platform; ?>')">
                    <i class="fas fa-plus"></i> Add
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($campaigns)): ?>
        <div class="campaign-grid">
            <?php foreach ($campaigns as $camp): 
                $platform = $camp['platform'] ?? 'Meta Ads';
                $status = $camp['status'] ?? 'Draft';
                $status_class = strtolower($status);
                $budget = $camp['budget'] ?? 0;
                $spent = $camp['spent'] ?? 0;
                $progress = $budget > 0 ? round(($spent / $budget) * 100) : 0;
                $platform_icon = $ad_platforms[$platform]['icon'] ?? 'fa-bullhorn';
                $platform_color = $ad_platforms[$platform]['color'] ?? '#4a5cf5';
            ?>
            <div class="campaign-card">
                <div class="card-header">
                    <div class="campaign-info">
                        <div class="campaign-name">
                            <i class="fab <?php echo $platform_icon; ?>" style="color:<?php echo $platform_color; ?>;"></i>
                            <?php echo htmlspecialchars($camp['campaign_name']); ?>
                        </div>
                        <div class="platform-tag"><?php echo $platform; ?></div>
                    </div>
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span>
                </div>

                <div class="metrics">
                    <div class="metric-item">
                        <div class="metric-value"><?php echo number_format($spent); ?> PKR</div>
                        <div class="metric-label">Spent</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-value"><?php echo number_format($camp['impressions'] ?? 0); ?></div>
                        <div class="metric-label">Impressions</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-value"><?php echo number_format($camp['conversions'] ?? 0); ?></div>
                        <div class="metric-label">Conversions</div>
                    </div>
                </div>

                <div class="progress-section">
                    <div class="progress-info">
                        <span>Budget: <?php echo number_format($budget); ?> PKR</span>
                        <span><?php echo $progress; ?>% used</span>
                    </div>
                    <div class="progress-bar">
                        <div class="fill" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                </div>

                <div class="card-actions">
                    <button class="btn-action edit" onclick="editCampaign(<?php echo $camp['id']; ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-action report" onclick="openReportModal(<?php echo $camp['id']; ?>, <?php echo $selected_client_id; ?>, '<?php echo $platform; ?>')">
                        <i class="fas fa-file-alt"></i> Report
                    </button>
                    <button class="btn-action export-pdf" onclick="exportPDF(<?php echo $camp['id']; ?>, <?php echo $selected_client_id; ?>)">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button class="btn-action delete" onclick="deleteCampaign(<?php echo $camp['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <span style="font-size:9px;color:var(--text-muted);margin-left:auto;">
                        ROI: <strong style="color:<?php echo ($camp['roi'] ?? 0) > 0 ? '#10b981' : '#94a3b8'; ?>;"><?php echo $camp['roi'] ?? 0; ?>x</strong>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card" style="background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:30px;">
            <div class="empty-state">
                <i class="fas fa-bullhorn"></i>
                <p>No campaigns found for this client.</p>
                <button onclick="openModal('modal-add-campaign')" class="btn-pm" style="margin-top:10px;">
                    <i class="fas fa-plus"></i> Create First Campaign
                </button>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="card" style="background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:40px;text-align:center;">
            <i class="fas fa-user-plus" style="font-size:48px;color:var(--text-muted);opacity:0.3;display:block;margin-bottom:12px;"></i>
            <h3 style="font-size:18px;font-weight:800;color:var(--text);">Select a Client</h3>
            <p style="font-size:13px;color:var(--text-muted);">Please select a client from the dropdown above to manage their ad campaigns.</p>
        </div>
        <?php endif; ?>

    </div>

    <!-- ===== MODALS ===== -->

    <!-- Add Campaign Modal -->
    <div class="modal-overlay" id="modal-add-campaign">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-add-campaign')"><i class="fas fa-times"></i></button>
            <h3>Create New Campaign</h3>
            <p class="modal-sub">Add a new ad campaign for <?php echo $selected_client ? $selected_client['name'] : 'client'; ?></p>
            <form id="addCampaignForm" onsubmit="addCampaign(event)">
                <input type="hidden" id="campaign-client-id" value="<?php echo $selected_client_id; ?>">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Platform *</label>
                        <select id="campaign-platform" required>
                            <option value="">Select Platform</option>
                            <?php foreach ($ad_platforms as $platform => $info): ?>
                                <option value="<?php echo $platform; ?>"><?php echo $platform; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Campaign Name *</label>
                        <input type="text" id="campaign-name" required placeholder="e.g. Summer Sale 2024">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="campaign-status">
                            <option value="Draft">Draft</option>
                            <option value="Active">Active</option>
                            <option value="Paused">Paused</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Budget (PKR) *</label>
                        <input type="number" id="campaign-budget" required placeholder="25000">
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" id="campaign-start-date">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" id="campaign-end-date">
                    </div>
                    <div class="form-group">
                        <label>CPC (Cost Per Click)</label>
                        <input type="number" step="0.01" id="campaign-cpc" placeholder="0.50">
                    </div>
                    <div class="form-group">
                        <label>CPM (Cost Per 1000 Impressions)</label>
                        <input type="number" step="0.01" id="campaign-cpm" placeholder="5.00">
                    </div>
                    <div class="form-group">
                        <label>CPA (Cost Per Acquisition)</label>
                        <input type="number" step="0.01" id="campaign-cpa" placeholder="15.00">
                    </div>
                    <div class="form-group">
                        <label>Target Audience</label>
                        <input type="text" id="campaign-target" placeholder="e.g. 18-35, Pakistan">
                    </div>
                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea id="campaign-notes" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-plus"></i> Create Campaign</button>
            </form>
        </div>
    </div>

    <!-- Edit Campaign Modal -->
    <div class="modal-overlay" id="modal-edit-campaign">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-edit-campaign')"><i class="fas fa-times"></i></button>
            <h3>Edit Campaign</h3>
            <p class="modal-sub">Update campaign details and metrics</p>
            <form id="editCampaignForm" onsubmit="updateCampaign(event)">
                <input type="hidden" id="edit-campaign-id">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Platform *</label>
                        <select id="edit-campaign-platform" required>
                            <?php foreach ($ad_platforms as $platform => $info): ?>
                                <option value="<?php echo $platform; ?>"><?php echo $platform; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Campaign Name *</label>
                        <input type="text" id="edit-campaign-name" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="edit-campaign-status">
                            <option value="Draft">Draft</option>
                            <option value="Active">Active</option>
                            <option value="Paused">Paused</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Budget (PKR) *</label>
                        <input type="number" id="edit-campaign-budget" required>
                    </div>
                    <div class="form-group">
                        <label>Spent (PKR)</label>
                        <input type="number" id="edit-campaign-spent" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Impressions</label>
                        <input type="number" id="edit-campaign-impressions">
                    </div>
                    <div class="form-group">
                        <label>Clicks</label>
                        <input type="number" id="edit-campaign-clicks">
                    </div>
                    <div class="form-group">
                        <label>Conversions</label>
                        <input type="number" id="edit-campaign-conversions">
                    </div>
                    <div class="form-group">
                        <label>CPC</label>
                        <input type="number" step="0.01" id="edit-campaign-cpc">
                    </div>
                    <div class="form-group">
                        <label>CPM</label>
                        <input type="number" step="0.01" id="edit-campaign-cpm">
                    </div>
                    <div class="form-group">
                        <label>CPA</label>
                        <input type="number" step="0.01" id="edit-campaign-cpa">
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" id="edit-campaign-start-date">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" id="edit-campaign-end-date">
                    </div>
                    <div class="form-group">
                        <label>Target Audience</label>
                        <input type="text" id="edit-campaign-target">
                    </div>
                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea id="edit-campaign-notes" rows="2"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Campaign</button>
            </form>
        </div>
    </div>

    <!-- Generate Report Modal -->
    <div class="modal-overlay" id="modal-report">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-report')"><i class="fas fa-times"></i></button>
            <h3>Generate Campaign Report</h3>
            <p class="modal-sub">Create a detailed performance report</p>
            <form id="reportForm" onsubmit="generateReport(event)">
                <input type="hidden" id="report-campaign-id">
                <input type="hidden" id="report-client-id" value="<?php echo $selected_client_id; ?>">
                <input type="hidden" id="report-platform">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Report Date *</label>
                        <input type="date" id="report-date" required>
                    </div>
                    <div class="form-group">
                        <label>Spent (PKR) *</label>
                        <input type="number" id="report-spent" step="0.01" required placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Impressions *</label>
                        <input type="number" id="report-impressions" required placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Clicks *</label>
                        <input type="number" id="report-clicks" required placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Conversions *</label>
                        <input type="number" id="report-conversions" required placeholder="0">
                    </div>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-file-alt"></i> Generate Report</button>
            </form>
        </div>
    </div>

    <!-- ===== TOAST ===== -->
    <div class="toast-container" id="toast-container"></div>

    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
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

        function openModalWithPlatform(platform) {
            document.getElementById('campaign-platform').value = platform;
            openModal('modal-add-campaign');
        }

        // ===== ADD CAMPAIGN =====
        function addCampaign(e) {
            e.preventDefault();
            
            const clientId = document.getElementById('campaign-client-id').value;
            const platform = document.getElementById('campaign-platform').value;
            const campaignName = document.getElementById('campaign-name').value;
            const budget = document.getElementById('campaign-budget').value;
            
            if (!clientId || !platform || !campaignName || !budget) {
                showToast('Please fill all required fields (*)', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'add_campaign');
            formData.append('client_id', clientId);
            formData.append('platform', platform);
            formData.append('campaign_name', campaignName);
            formData.append('status', document.getElementById('campaign-status').value);
            formData.append('budget', budget);
            formData.append('cpc', document.getElementById('campaign-cpc').value || 0);
            formData.append('cpm', document.getElementById('campaign-cpm').value || 0);
            formData.append('cpa', document.getElementById('campaign-cpa').value || 0);
            formData.append('start_date', document.getElementById('campaign-start-date').value);
            formData.append('end_date', document.getElementById('campaign-end-date').value);
            formData.append('target_audience', document.getElementById('campaign-target').value);
            formData.append('notes', document.getElementById('campaign-notes').value);
            
            showToast('Creating campaign...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Campaign created successfully!');
                    closeModal('modal-add-campaign');
                    document.getElementById('addCampaignForm').reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error creating campaign: ' + error, 'error');
            });
        }

        // ===== EDIT CAMPAIGN =====
        function editCampaign(campaignId) {
            document.getElementById('edit-campaign-id').value = campaignId;
            openModal('modal-edit-campaign');
            showToast('Please fill in the campaign details manually.', 'warning');
        }

        // ===== UPDATE CAMPAIGN =====
        function updateCampaign(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('ajax_action', 'update_campaign');
            formData.append('campaign_id', document.getElementById('edit-campaign-id').value);
            formData.append('platform', document.getElementById('edit-campaign-platform').value);
            formData.append('campaign_name', document.getElementById('edit-campaign-name').value);
            formData.append('status', document.getElementById('edit-campaign-status').value);
            formData.append('budget', document.getElementById('edit-campaign-budget').value);
            formData.append('spent', document.getElementById('edit-campaign-spent').value || 0);
            formData.append('impressions', document.getElementById('edit-campaign-impressions').value || 0);
            formData.append('clicks', document.getElementById('edit-campaign-clicks').value || 0);
            formData.append('conversions', document.getElementById('edit-campaign-conversions').value || 0);
            formData.append('cpc', document.getElementById('edit-campaign-cpc').value || 0);
            formData.append('cpm', document.getElementById('edit-campaign-cpm').value || 0);
            formData.append('cpa', document.getElementById('edit-campaign-cpa').value || 0);
            formData.append('start_date', document.getElementById('edit-campaign-start-date').value);
            formData.append('end_date', document.getElementById('edit-campaign-end-date').value);
            formData.append('target_audience', document.getElementById('edit-campaign-target').value);
            formData.append('notes', document.getElementById('edit-campaign-notes').value);
            
            showToast('Updating campaign...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Campaign updated successfully!');
                    closeModal('modal-edit-campaign');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error updating campaign.', 'error');
            });
        }

        // ===== DELETE CAMPAIGN =====
        function deleteCampaign(campaignId) {
            if (!confirm('Are you sure you want to delete this campaign?')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_campaign');
            formData.append('campaign_id', campaignId);
            
            showToast('Deleting campaign...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Campaign deleted successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting campaign.', 'error');
            });
        }

        // ===== OPEN REPORT MODAL =====
        function openReportModal(campaignId, clientId, platform) {
            document.getElementById('report-campaign-id').value = campaignId;
            document.getElementById('report-client-id').value = clientId;
            document.getElementById('report-platform').value = platform;
            
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('report-date').value = today;
            
            document.getElementById('report-spent').value = '';
            document.getElementById('report-impressions').value = '';
            document.getElementById('report-clicks').value = '';
            document.getElementById('report-conversions').value = '';
            
            openModal('modal-report');
        }

        // ===== GENERATE REPORT =====
        function generateReport(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('ajax_action', 'generate_report');
            formData.append('campaign_id', document.getElementById('report-campaign-id').value);
            formData.append('client_id', document.getElementById('report-client-id').value);
            formData.append('platform', document.getElementById('report-platform').value);
            formData.append('report_date', document.getElementById('report-date').value);
            formData.append('spent', document.getElementById('report-spent').value);
            formData.append('impressions', document.getElementById('report-impressions').value);
            formData.append('clicks', document.getElementById('report-clicks').value);
            formData.append('conversions', document.getElementById('report-conversions').value);
            
            showToast('Generating report...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Report generated successfully!');
                    closeModal('modal-report');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error generating report.', 'error');
            });
        }

        // ===== EXPORT PDF =====
        function exportPDF(campaignId, clientId) {
            showToast('Generating PDF report...', 'warning');
            
            const formData = new FormData();
            formData.append('ajax_action', 'export_pdf');
            formData.append('campaign_id', campaignId);
            formData.append('client_id', clientId);
            
            // Open in new window for print
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.text())
            .then(html => {
                // Open in new window
                const win = window.open('', '_blank', 'width=1000,height=800,scrollbars=yes');
                if (win) {
                    win.document.write(html);
                    win.document.close();
                    // Auto print after load
                    win.onload = function() {
                        setTimeout(function() {
                            win.print();
                        }, 500);
                    };
                } else {
                    showToast('Please allow popups for this site.', 'error');
                }
            })
            .catch(error => {
                showToast('Error generating PDF: ' + error, 'error');
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