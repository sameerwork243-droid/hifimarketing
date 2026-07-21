<?php
// marketing-reports.php - Client Marketing Reports Page (PUBLIC HOSTING READY)
session_start();
error_reporting(E_ALL);

// ===== FIXED PATH FOR PUBLIC HOSTING =====
require_once dirname(__DIR__) . '/includes/config.php';

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

// ===== GET ACTIVE PACKAGE =====
$packages_sql = "SELECT * FROM packages WHERE status = 'active' ORDER BY price ASC LIMIT 1";
$packages_result = mysqli_query($conn, $packages_sql);
$active_package = mysqli_fetch_assoc($packages_result);
$package_name = $active_package['name'] ?? 'Professional Growth';

// ===== GET AD CAMPAIGNS =====
$campaigns = [];
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'ad_campaigns'");
if (mysqli_num_rows($check_table) > 0) {
    $camp_sql = "SELECT * FROM ad_campaigns WHERE client_id = ? ORDER BY created_at DESC";
    $camp_stmt = mysqli_prepare($conn, $camp_sql);
    mysqli_stmt_bind_param($camp_stmt, "i", $client_id);
    mysqli_stmt_execute($camp_stmt);
    $camp_result = mysqli_stmt_get_result($camp_stmt);
    while ($row = mysqli_fetch_assoc($camp_result)) {
        $campaigns[] = $row;
    }
    mysqli_stmt_close($camp_stmt);
}

// ===== GET CAMPAIGN REPORTS =====
$campaign_reports = [];
if ($client_id > 0) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'campaign_reports'");
    if (mysqli_num_rows($check_table) > 0) {
        $rep_sql = "SELECT * FROM campaign_reports WHERE client_id = ? ORDER BY created_at DESC";
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

// ===== GET BRAND2SOCIAL ATTACHMENTS FOR CLIENT =====
$client_attachments = [];
if ($client_id > 0) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'documents'");
    if (mysqli_num_rows($check_table) > 0) {
        $att_sql = "SELECT * FROM documents WHERE client_id = ? AND type = 'brand2social' ORDER BY created_at DESC";
        $att_stmt = mysqli_prepare($conn, $att_sql);
        mysqli_stmt_bind_param($att_stmt, "i", $client_id);
        mysqli_stmt_execute($att_stmt);
        $att_result = mysqli_stmt_get_result($att_stmt);
        while ($row = mysqli_fetch_assoc($att_result)) {
            $client_attachments[] = $row;
        }
        mysqli_stmt_close($att_stmt);
    }
}

// ===== SAMPLE DATA (if no campaigns exist) =====
$sample_campaigns = [
    [
        'id' => 1,
        'platform' => 'Meta Ads',
        'campaign_name' => 'Summer Sale 2024',
        'status' => 'Active',
        'budget' => 25000,
        'spent' => 18500,
        'impressions' => 112450,
        'clicks' => 3450,
        'conversions' => 245,
        'ctr' => 3.07,
        'roi' => 3.2,
        'created_at' => date('Y-m-d', strtotime('-7 days'))
    ],
    [
        'id' => 2,
        'platform' => 'Google Ads',
        'campaign_name' => 'Search Campaign Q4',
        'status' => 'Active',
        'budget' => 18000,
        'spent' => 12800,
        'impressions' => 78320,
        'clicks' => 2890,
        'conversions' => 187,
        'ctr' => 3.69,
        'roi' => 4.1,
        'created_at' => date('Y-m-d', strtotime('-14 days'))
    ],
    [
        'id' => 3,
        'platform' => 'TikTok Ads',
        'campaign_name' => 'Brand Awareness',
        'status' => 'Paused',
        'budget' => 12000,
        'spent' => 9500,
        'impressions' => 45000,
        'clicks' => 1200,
        'conversions' => 89,
        'ctr' => 2.67,
        'roi' => 2.8,
        'created_at' => date('Y-m-d', strtotime('-21 days'))
    ],
    [
        'id' => 4,
        'platform' => 'Instagram Ads',
        'campaign_name' => 'Influencer Collaboration',
        'status' => 'Active',
        'budget' => 15000,
        'spent' => 11200,
        'impressions' => 89200,
        'clicks' => 2100,
        'conversions' => 156,
        'ctr' => 2.35,
        'roi' => 3.6,
        'created_at' => date('Y-m-d', strtotime('-10 days'))
    ],
    [
        'id' => 5,
        'platform' => 'Snapchat Ads',
        'campaign_name' => 'Gen Z Targeting',
        'status' => 'Draft',
        'budget' => 8000,
        'spent' => 0,
        'impressions' => 0,
        'clicks' => 0,
        'conversions' => 0,
        'ctr' => 0,
        'roi' => 0,
        'created_at' => date('Y-m-d', strtotime('-2 days'))
    ],
    [
        'id' => 6,
        'platform' => 'Facebook Ads',
        'campaign_name' => 'Retargeting Campaign',
        'status' => 'Active',
        'budget' => 20000,
        'spent' => 16500,
        'impressions' => 95600,
        'clicks' => 2800,
        'conversions' => 210,
        'ctr' => 2.93,
        'roi' => 3.8,
        'created_at' => date('Y-m-d', strtotime('-5 days'))
    ]
];

// If no campaigns in database, use sample data
if (empty($campaigns)) {
    $campaigns = $sample_campaigns;
}

// ===== CALCULATE SUMMARY STATS =====
$total_budget = 0;
$total_spent = 0;
$total_impressions = 0;
$total_clicks = 0;
$total_conversions = 0;
$active_campaigns = 0;
$paused_campaigns = 0;
$draft_campaigns = 0;

foreach ($campaigns as $camp) {
    $total_budget += $camp['budget'] ?? 0;
    $total_spent += $camp['spent'] ?? 0;
    $total_impressions += $camp['impressions'] ?? 0;
    $total_clicks += $camp['clicks'] ?? 0;
    $total_conversions += $camp['conversions'] ?? 0;
    
    $status = $camp['status'] ?? 'Draft';
    if ($status === 'Active') $active_campaigns++;
    elseif ($status === 'Paused') $paused_campaigns++;
    elseif ($status === 'Draft') $draft_campaigns++;
}

$avg_ctr = $total_impressions > 0 ? round(($total_clicks / $total_impressions) * 100, 2) : 0;
$avg_roi = $total_spent > 0 ? round($total_conversions / ($total_spent / 1000), 1) : 0;

// ===== PLATFORM ICONS =====
$platform_icons = [
    'Meta Ads' => 'fa-facebook',
    'Google Ads' => 'fa-google',
    'TikTok Ads' => 'fa-tiktok',
    'Instagram Ads' => 'fa-instagram',
    'Snapchat Ads' => 'fa-snapchat',
    'Facebook Ads' => 'fa-facebook'
];

$platform_colors = [
    'Meta Ads' => '#1877F2',
    'Google Ads' => '#EA4335',
    'TikTok Ads' => '#000000',
    'Instagram Ads' => '#E4405F',
    'Snapchat Ads' => '#FFFC00',
    'Facebook Ads' => '#1877F2'
];

// ===== AJAX HANDLER FOR PDF EXPORT =====
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'export_pdf') {
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
            
            // ===== GENERATE HTML REPORT WITH PRINT CSS =====
            header('Content-Type: text/html');
            
            $html_output = '<!DOCTYPE html>
            <html>
            <head>
                <title>Campaign Report - ' . htmlspecialchars($campaign['campaign_name']) . '</title>
                <style>
                    @media print {
                        .no-print { display: none !important; }
                        body { padding: 20px; }
                        .page-break { page-break-after: always; }
                    }
                    body { font-family: Arial, sans-serif; padding: 30px; max-width: 1000px; margin: 0 auto; background: #fff; }
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
                    .no-print:hover { background: #3a4be0; }
                    .header { background: #4a5cf5; color: #fff; padding: 25px; text-align: center; border-radius: 10px; }
                    .header h1 { margin: 0; font-size: 26px; }
                    .header p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }
                    .section { margin-top: 25px; }
                    .section-title { font-size: 18px; font-weight: bold; color: #1a1c26; border-bottom: 3px solid #4a5cf5; padding-bottom: 8px; margin-bottom: 15px; }
                    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 18px; border-radius: 10px; }
                    .info-item { padding: 6px 0; }
                    .info-item .label { font-weight: bold; color: #3d4452; }
                    .info-item .value { color: #1a1c26; }
                    .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 12px; }
                    .metric-box { background: #f8fafc; padding: 14px; border-radius: 10px; text-align: center; }
                    .metric-box .number { font-size: 22px; font-weight: bold; color: #1a1c26; }
                    .metric-box .label { font-size: 12px; color: #8a94a0; }
                    .table { width: 100%; border-collapse: collapse; margin-top: 12px; }
                    .table th { background: #4a5cf5; color: #fff; padding: 10px 14px; text-align: left; font-size: 12px; }
                    .table td { padding: 10px 14px; border-bottom: 1px solid #e9edf2; font-size: 12px; }
                    .table tr:nth-child(even) { background: #f8fafc; }
                    .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e9edf2; text-align: center; font-size: 11px; color: #8a94a0; }
                    .status-badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
                    .status-badge.active { background: #d1fae5; color: #065f46; }
                    .status-badge.paused { background: #fef3c7; color: #92400e; }
                    .status-badge.draft { background: #f1f5f9; color: #475569; }
                    .status-badge.completed { background: #dbeafe; color: #1e40af; }
                    .roi-positive { color: #10b981; }
                    .roi-negative { color: #ef4444; }
                    .client-name { font-weight: bold; }
                    @media (max-width: 768px) {
                        .info-grid { grid-template-columns: 1fr; }
                        .metrics-grid { grid-template-columns: 1fr 1fr; }
                        body { padding: 15px; }
                    }
                    @media print {
                        .no-print { display: none !important; }
                        body { padding: 20px; }
                        .header { background: #4a5cf5 !important; color: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                        .status-badge { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                        .table th { background: #4a5cf5 !important; color: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                        .metric-box { background: #f8fafc !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                        .info-grid { background: #f8fafc !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    }
                </style>
            </head>
            <body>
                <button class="no-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
                <div id="report-content">
                    <div class="header">
                        <h1>📊 Campaign Performance Report</h1>
                        <p>Generated on ' . date('F d, Y H:i:s') . '</p>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Campaign Overview</div>
                        <div class="info-grid">
                            <div class="info-item"><span class="label">Campaign Name:</span> <span class="value">' . htmlspecialchars($campaign['campaign_name']) . '</span></div>
                            <div class="info-item"><span class="label">Platform:</span> <span class="value">' . htmlspecialchars($campaign['platform']) . '</span></div>
                            <div class="info-item"><span class="label">Status:</span> <span class="value"><span class="status-badge ' . strtolower($campaign['status']) . '">' . $campaign['status'] . '</span></span></div>
                            <div class="info-item"><span class="label">Client:</span> <span class="value">' . htmlspecialchars($client['name'] ?? $client['email'] ?? 'N/A') . '</span></div>
                            <div class="info-item"><span class="label">Budget:</span> <span class="value">' . number_format($campaign['budget']) . ' PKR</span></div>
                            <div class="info-item"><span class="label">Start Date:</span> <span class="value">' . $start_date . '</span></div>
                            <div class="info-item"><span class="label">End Date:</span> <span class="value">' . $end_date . '</span></div>
                            <div class="info-item"><span class="label">Target Audience:</span> <span class="value">' . htmlspecialchars($campaign['target_audience'] ?? 'N/A') . '</span></div>
                        </div>
                    </div>
                    
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
                                <div class="number ' . (($campaign['roi'] ?? 0) > 0 ? 'roi-positive' : 'roi-negative') . '">' . ($campaign['roi'] ?? 0) . 'x</div>
                                <div class="label">ROI (Return on Investment)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Cost Metrics</div>
                        <div class="info-grid">
                            <div class="info-item"><span class="label">CPC (Cost Per Click):</span> <span class="value">' . number_format($campaign['cpc'] ?? 0, 2) . ' PKR</span></div>
                            <div class="info-item"><span class="label">CPM (Cost Per 1000 Impressions):</span> <span class="value">' . number_format($campaign['cpm'] ?? 0, 2) . ' PKR</span></div>
                            <div class="info-item"><span class="label">CPA (Cost Per Acquisition):</span> <span class="value">' . number_format($campaign['cpa'] ?? 0, 2) . ' PKR</span></div>
                            <div class="info-item"><span class="label">Budget Utilization:</span> <span class="value">' . ($campaign['budget'] > 0 ? round(($campaign['spent'] / $campaign['budget']) * 100) : 0) . '%</span></div>
                        </div>
                    </div>';
            
            if (!empty($reports)) {
                $html_output .= '<div class="section">
                    <div class="section-title">Historical Reports</div>
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
                $html_output .= '</tbody></table></div>';
            }
            
            if (!empty($campaign['notes'])) {
                $html_output .= '<div class="section">
                    <div class="section-title">Additional Notes</div>
                    <div style="background:#f8fafc;padding:12px 16px;border-radius:8px;font-size:13px;">' . nl2br(htmlspecialchars($campaign['notes'])) . '</div>
                </div>';
            }
            
            $html_output .= '<div class="footer">
                <p>This report is automatically generated by HIFI Marketing Client Portal. For any queries, please contact your account manager.</p>
                <p>Report ID: #' . $campaign_id . ' | Generated: ' . date('Y-m-d H:i:s') . '</p>
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
            echo '<h3>Campaign not found</h3>';
            exit();
        }
    } else {
        echo '<h3>Invalid data</h3>';
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Marketing Reports | HIFI Marketing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="icon" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/../images/fav-icon.png" type="image/png" />
    <style>
        /* ===== HIFI THEME ===== */
        :root {
            --primary: #4a5cf5;
            --primary-dark: #3a4be0;
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }

        /* ===== HEADER ===== */
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
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            font-weight: 900;
        }
        .logo span { color: var(--primary); }

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
        .header-right .nav-link:hover,
        .header-right .nav-link.active {
            background: #f0f3ff;
            color: var(--primary);
        }
        .header-right .user-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px 3px 3px;
            border-radius: 40px;
            background: #f0f3ff;
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

        /* ===== MAIN ===== */
        .main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 16px 40px;
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
            font-size: 20px;
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
        .banner .actions {
            display: flex;
            gap: 8px;
        }
        .banner .actions .btn-white {
            background: #fff;
            color: var(--primary);
            padding: 6px 18px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 12px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }
        .banner .actions .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* ===== STATS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: var(--card);
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
            color: var(--text);
        }
        .stat-card .label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .stat-card .icon {
            float: right;
            font-size: 22px;
            opacity: 0.15;
            color: var(--primary);
        }
        .stat-card .sub-text {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .stat-card .green { color: #10b981; }
        .stat-card .amber { color: #f59e0b; }
        .stat-card .blue { color: var(--primary); }

        /* ===== FILTERS ===== */
        .filter-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            background: var(--card);
            padding: 12px 16px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            align-items: center;
        }
        .filter-bar .filter-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
        }
        .filter-bar .filter-btn {
            padding: 4px 14px;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: transparent;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
        }
        .filter-bar .filter-btn:hover,
        .filter-bar .filter-btn.active {
            background: #f0f3ff;
            border-color: var(--primary);
            color: var(--primary);
        }
        .filter-bar .filter-btn.platform-btn {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .filter-bar .filter-btn.platform-btn i {
            font-size: 12px;
        }

        /* ===== CAMPAIGN CARDS ===== */
        .campaign-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .campaign-card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 18px 20px;
            transition: var(--transition);
            position: relative;
        }
        .campaign-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
            border-color: var(--primary);
        }
        .campaign-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .campaign-card .card-header .platform-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .campaign-card .card-header .platform-info .platform-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 16px;
        }
        .campaign-card .card-header .platform-info .campaign-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--text);
        }
        .campaign-card .card-header .platform-info .platform-name {
            font-size: 10px;
            color: var(--text-muted);
        }
        .campaign-card .card-header .status-badge {
            padding: 2px 12px;
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
            gap: 8px;
            margin: 12px 0;
            padding: 10px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        .campaign-card .metrics .metric-item {
            text-align: center;
        }
        .campaign-card .metrics .metric-item .metric-value {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
        }
        .campaign-card .metrics .metric-item .metric-label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .campaign-card .progress-section {
            margin-top: 10px;
        }
        .campaign-card .progress-section .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: var(--text-muted);
        }
        .campaign-card .progress-section .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }
        .campaign-card .progress-section .progress-bar .fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
            background: var(--primary);
        }

        .campaign-card .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
            font-size: 11px;
        }
        .campaign-card .card-footer .roi {
            font-weight: 700;
            color: #10b981;
        }
        .campaign-card .card-footer .date {
            color: var(--text-muted);
            font-size: 10px;
        }
        .campaign-card .card-footer .btn-export {
            background: transparent;
            border: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 11px;
            cursor: pointer;
            transition: var(--transition);
        }
        .campaign-card .card-footer .btn-export:hover {
            color: var(--primary-dark);
        }

        /* ===== SECTION TITLE ===== */
        .section-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: var(--primary); }
        .section-title .count {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
        }

        /* ===== ATTACHMENT STYLES ===== */
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
            min-width: 0;
        }
        .attachment-item .file-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--text);
        }
        .attachment-item .file-meta {
            font-size: 11px;
            color: var(--text-muted);
        }
        .attachment-item .btn-download {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
            text-decoration: none;
        }
        .attachment-item .btn-download:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 48px;
            color: #d0d7e0;
            margin-bottom: 12px;
            display: block;
        }
        .empty-state h4 {
            font-size: 18px;
            color: var(--text);
            margin-bottom: 4px;
        }
        .empty-state p { font-size: 13px; }

        /* ===== TOAST ===== */
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

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .campaign-grid { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); }
        }

        @media (max-width: 768px) {
            .banner { padding: 16px 18px; flex-direction: column; text-align: center; }
            .banner h2 { font-size: 17px; }
            .banner .actions { width: 100%; justify-content: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stats-grid .stat-card { padding: 12px 14px; }
            .stats-grid .stat-card .number { font-size: 20px; }
            .campaign-grid { grid-template-columns: 1fr; }
            .header-right .nav-link { display: none; }
            .main { padding: 12px 10px 30px; }
            .logo { font-size: 16px; }
            .logo .brand-icon { width: 28px; height: 28px; font-size: 12px; }
            .filter-bar { padding: 10px 12px; gap: 6px; }
            .filter-bar .filter-btn { font-size: 10px; padding: 3px 10px; }
            .campaign-card { padding: 14px 16px; }
            .campaign-card .metrics .metric-item .metric-value { font-size: 14px; }
            .attachment-item { flex-wrap: wrap; gap: 8px; }
            .attachment-item .btn-download { width: 100%; justify-content: center; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .banner h2 { font-size: 15px; }
            .banner p { font-size: 12px; }
            .header-right .user-badge { padding: 2px 8px 2px 2px; font-size: 11px; }
            .header-right .user-badge img { width: 22px; height: 22px; }
            .campaign-card .card-header .platform-info .platform-icon { width: 30px; height: 30px; font-size: 13px; }
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
            <div class="header-right">
                <a href="client-portal.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="client-portal.php?tab=dashboard" class="nav-link"><i class="fas fa-tachometer-alt"></i></a>
                <div class="user-badge">
                    <img src="<?php echo htmlspecialchars($userData['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'); ?>" alt="Avatar">
                    <span><?php echo htmlspecialchars($userData['name'] ?? 'Client'); ?></span>
                </div>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </header>

    <!-- ===== MAIN ===== -->
    <div class="main">

        <!-- ===== BANNER ===== -->
        <div class="banner">
            <div>
                <h2><i class="fas fa-bullhorn"></i> Ad Campaigns &amp; Reports</h2>
                <p>Track all your ad campaigns across <strong>Meta, Google, TikTok, Instagram, Snapchat</strong> and more</p>
            </div>
            <div class="actions">
                <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:7px;"></i> <?php echo $active_campaigns; ?> Active</span>
                <span class="badge"><i class="fas fa-circle" style="color:#f59e0b;font-size:7px;"></i> <?php echo $paused_campaigns; ?> Paused</span>
            </div>
        </div>

        <!-- ===== SUMMARY STATS ===== -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-coins"></i></div>
                <div class="number"><?php echo number_format($total_spent); ?> PKR</div>
                <div class="label">Total Spent</div>
                <div class="sub-text">Budget: <?php echo number_format($total_budget); ?> PKR</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-eye"></i></div>
                <div class="number"><?php echo number_format($total_impressions); ?></div>
                <div class="label">Total Impressions</div>
                <div class="sub-text">CTR: <?php echo $avg_ctr; ?>%</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-mouse-pointer"></i></div>
                <div class="number"><?php echo number_format($total_clicks); ?></div>
                <div class="label">Total Clicks</div>
                <div class="sub-text">Conversions: <?php echo number_format($total_conversions); ?></div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-trend-up"></i></div>
                <div class="number" style="color:#10b981;"><?php echo $avg_roi; ?>x</div>
                <div class="label">Average ROI</div>
                <div class="sub-text"><?php echo count($campaigns); ?> Total Campaigns</div>
            </div>
        </div>

        <!-- ===== FILTERS ===== -->
        <div class="filter-bar">
            <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
            <button class="filter-btn active" onclick="filterCampaigns('all')">All</button>
            <button class="filter-btn" onclick="filterCampaigns('Active')">Active</button>
            <button class="filter-btn" onclick="filterCampaigns('Paused')">Paused</button>
            <button class="filter-btn" onclick="filterCampaigns('Draft')">Draft</button>
            <span style="color:var(--border);margin:0 4px;">|</span>
            <button class="filter-btn platform-btn" onclick="filterByPlatform('all')"><i class="fas fa-globe"></i> All</button>
            <button class="filter-btn platform-btn" onclick="filterByPlatform('Meta Ads')"><i class="fab fa-facebook"></i> Meta</button>
            <button class="filter-btn platform-btn" onclick="filterByPlatform('Google Ads')"><i class="fab fa-google"></i> Google</button>
            <button class="filter-btn platform-btn" onclick="filterByPlatform('TikTok Ads')"><i class="fab fa-tiktok"></i> TikTok</button>
            <button class="filter-btn platform-btn" onclick="filterByPlatform('Instagram Ads')"><i class="fab fa-instagram"></i> Insta</button>
            <button class="filter-btn platform-btn" onclick="filterByPlatform('Snapchat Ads')"><i class="fab fa-snapchat"></i> Snap</button>
        </div>

        <!-- ===== CAMPAIGNS ===== -->
        <div class="section-title">
            <i class="fas fa-bullseye"></i> Ad Campaigns
            <span class="count">(<?php echo count($campaigns); ?> campaigns)</span>
        </div>

        <div class="campaign-grid" id="campaignGrid">
            <?php foreach ($campaigns as $camp): 
                $platform = $camp['platform'] ?? 'Meta Ads';
                $icon = $platform_icons[$platform] ?? 'fa-bullhorn';
                $color = $platform_colors[$platform] ?? '#4a5cf5';
                $status = $camp['status'] ?? 'Draft';
                $status_class = strtolower($status);
                $budget = $camp['budget'] ?? 0;
                $spent = $camp['spent'] ?? 0;
                $progress = $budget > 0 ? round(($spent / $budget) * 100) : 0;
            ?>
            <div class="campaign-card" data-status="<?php echo $status; ?>" data-platform="<?php echo $platform; ?>">
                <div class="card-header">
                    <div class="platform-info">
                        <div class="platform-icon" style="background:<?php echo $color; ?>;">
                            <i class="fab <?php echo $icon; ?>"></i>
                        </div>
                        <div>
                            <div class="campaign-name"><?php echo htmlspecialchars($camp['campaign_name'] ?? 'Untitled'); ?></div>
                            <div class="platform-name"><?php echo $platform; ?></div>
                        </div>
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

                <div class="card-footer">
                    <span>ROI: <span class="roi"><?php echo $camp['roi'] ?? 0; ?>x</span></span>
                    <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($camp['created_at'] ?? 'now')); ?></span>
                    <button onclick="exportCampaignReport(<?php echo $camp['id'] ?? 0; ?>, <?php echo $client_id; ?>)" class="btn-export">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== BRAND2SOCIAL ATTACHMENTS (CLIENT SIDE - DOWNLOAD ONLY) ===== -->
        <div class="section-title">
            <i class="fas fa-paperclip"></i> Brand2Social Attachments
            <span class="count">Files uploaded by your PM</span>
        </div>

        <div class="card" style="margin-bottom:20px;">
            <?php if (!empty($client_attachments)): ?>
                <?php foreach ($client_attachments as $att): ?>
                <div class="attachment-item">
                    <div class="file-info">
                        <div class="file-name">
                            <i class="fas fa-file"></i> <?php echo htmlspecialchars($att['file_name']); ?>
                        </div>
                        <div class="file-meta">
                            Uploaded: <?php echo date('M d, Y H:i', strtotime($att['created_at'])); ?> &bull; 
                            Size: <?php echo round($att['file_size'] / 1024, 1); ?> KB
                            <?php if (!empty($att['description'])): ?>
                            &bull; <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($att['description']); ?>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                            <i class="fas fa-user"></i> Uploaded by: <?php echo htmlspecialchars($att['uploaded_by'] ?? 'PM'); ?>
                        </div>
                    </div>
                    <a href="download.php?doc_id=<?php echo $att['id']; ?>" class="btn-download">
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

        <!-- ===== SUMMARY OVERVIEW ===== -->
        <div class="section-title">
            <i class="fas fa-chart-pie"></i> Campaign Summary
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
            <div style="background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:14px 16px;text-align:center;">
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text-muted);">Active Campaigns</div>
                <div style="font-size:22px;font-weight:900;color:#10b981;"><?php echo $active_campaigns; ?></div>
            </div>
            <div style="background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:14px 16px;text-align:center;">
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text-muted);">Paused Campaigns</div>
                <div style="font-size:22px;font-weight:900;color:#f59e0b;"><?php echo $paused_campaigns; ?></div>
            </div>
            <div style="background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:14px 16px;text-align:center;">
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text-muted);">Draft Campaigns</div>
                <div style="font-size:22px;font-weight:900;color:#94a3b8;"><?php echo $draft_campaigns; ?></div>
            </div>
            <div style="background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:14px 16px;text-align:center;">
                <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--text-muted);">Avg. CTR</div>
                <div style="font-size:22px;font-weight:900;color:var(--primary);"><?php echo $avg_ctr; ?>%</div>
            </div>
        </div>

        <!-- ===== FOOTER ===== -->
        <div style="margin-top:20px;text-align:center;font-size:11px;color:var(--text-muted);border-top:1px solid var(--border);padding-top:16px;">
            <i class="fas fa-sync-alt" style="margin-right:6px;"></i> Reports are updated in real-time. Last sync: <?php echo date('Y-m-d H:i:s'); ?>
        </div>

    </div>

    <!-- ===== TOAST ===== -->
    <div class="toast-container" id="toast-container"></div>

    <script>
        // ===== TOAST =====
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

        // ===== FILTER BY STATUS =====
        function filterCampaigns(status) {
            const cards = document.querySelectorAll('.campaign-card');
            const buttons = document.querySelectorAll('.filter-bar .filter-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            cards.forEach(card => {
                const cardStatus = card.dataset.status;
                if (status === 'all' || cardStatus === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // ===== FILTER BY PLATFORM =====
        function filterByPlatform(platform) {
            const cards = document.querySelectorAll('.campaign-card');
            const buttons = document.querySelectorAll('.filter-bar .platform-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            if (platform !== 'all') {
                event.target.classList.add('active');
            }

            cards.forEach(card => {
                const cardPlatform = card.dataset.platform;
                if (platform === 'all' || cardPlatform === platform) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // ===== EXPORT CAMPAIGN REPORT =====
        function exportCampaignReport(campaignId, clientId) {
            if (!campaignId || campaignId === 0) {
                showToast('Campaign ID not found. Please refresh and try again.', 'error');
                return;
            }
            
            showToast('Generating PDF report...', 'warning');
            
            const formData = new FormData();
            formData.append('ajax_action', 'export_pdf');
            formData.append('campaign_id', campaignId);
            formData.append('client_id', clientId);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.text())
            .then(html => {
                const win = window.open('', '_blank', 'width=1000,height=800,scrollbars=yes');
                if (win) {
                    win.document.write(html);
                    win.document.close();
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