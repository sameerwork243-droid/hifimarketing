<?php
// client-portal/finance-portal.php - Finance Portal Dashboard
session_start();
error_reporting(0);
ini_set('display_errors', 0);

// Adjust path to config
require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['portal_role'] ?? 'client';
$userData = $_SESSION['user'] ?? [];
$user_id = $_SESSION['user_id'] ?? 0;

// Get client data
$client_id = 0;
$client_data = null;

if ($user_id > 0) {
    $client_sql = "SELECT * FROM clients WHERE user_id = ?";
    $client_stmt = mysqli_prepare($conn, $client_sql);
    mysqli_stmt_bind_param($client_stmt, "i", $user_id);
    mysqli_stmt_execute($client_stmt);
    $client_result = mysqli_stmt_get_result($client_stmt);
    $client_data = mysqli_fetch_assoc($client_result);
    if ($client_data) {
        $client_id = $client_data['id'];
    }
    mysqli_stmt_close($client_stmt);
}

// ===== FINANCE STATISTICS =====
$stats = [
    'total_invoices' => 0,
    'paid_invoices' => 0,
    'unpaid_invoices' => 0,
    'total_revenue' => 0,
    'pending_amount' => 0,
    'subscriptions' => 0
];

// Get invoice stats
if ($client_id > 0) {
    $invoice_sql = "SELECT status, SUM(amount) as total FROM invoices WHERE client_id = ? GROUP BY status";
    $invoice_stmt = mysqli_prepare($conn, $invoice_sql);
    mysqli_stmt_bind_param($invoice_stmt, "i", $client_id);
    mysqli_stmt_execute($invoice_stmt);
    $invoice_result = mysqli_stmt_get_result($invoice_stmt);
    while ($row = mysqli_fetch_assoc($invoice_result)) {
        if ($row['status'] === 'paid') {
            $stats['paid_invoices']++;
            $stats['total_revenue'] += $row['total'];
        } elseif ($row['status'] === 'unpaid' || $row['status'] === 'pending') {
            $stats['unpaid_invoices']++;
            $stats['pending_amount'] += $row['total'];
        }
        $stats['total_invoices']++;
    }
    mysqli_stmt_close($invoice_stmt);
}

// Get subscription count
if ($client_id > 0) {
    $sub_sql = "SELECT COUNT(*) as count FROM client_packages WHERE client_id = ? AND status = 'active'";
    $sub_stmt = mysqli_prepare($conn, $sub_sql);
    mysqli_stmt_bind_param($sub_stmt, "i", $client_id);
    mysqli_stmt_execute($sub_stmt);
    $sub_result = mysqli_stmt_get_result($sub_stmt);
    if ($sub_row = mysqli_fetch_assoc($sub_result)) {
        $stats['subscriptions'] = $sub_row['count'];
    }
    mysqli_stmt_close($sub_stmt);
}

$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
$current_page = 'finance-portal.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Portal | HIFI Marketing</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 18px 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }
        .stat-card .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .stat-card .stat-icon.green { background: #d1fae5; color: #059669; }
        .stat-card .stat-icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-card .stat-icon.yellow { background: #fef3c7; color: #d97706; }
        .stat-card .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
        .stat-card .stat-icon.red { background: #fee2e2; color: #dc2626; }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
        }
        .stat-card .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
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

        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            transition: var(--transition);
            cursor: pointer;
            background: var(--card-bg);
        }
        .quick-link:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            transform: translateX(4px);
        }
        .quick-link .icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .quick-link .icon.green { background: #d1fae5; color: #059669; }
        .quick-link .icon.blue { background: #dbeafe; color: #2563eb; }
        .quick-link .icon.yellow { background: #fef3c7; color: #d97706; }
        .quick-link .icon.purple { background: #ede9fe; color: #7c3aed; }
        .quick-link .info {
            flex: 1;
        }
        .quick-link .info .title {
            font-weight: 700;
            font-size: 13px;
            color: var(--text-primary);
        }
        .quick-link .info .desc {
            font-size: 11px;
            color: var(--text-muted);
        }
        .quick-link .arrow {
            color: var(--text-muted);
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-layout { flex-direction: column; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
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
                <div class="user-badge">
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <span class="name"><?php echo $userData['name'] ?? 'Client'; ?></span>
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
                <a href="finance-portal.php" class="sidebar-link active">
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
                <a href="pm-billing.php" class="sidebar-link">
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
                    <img src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'; ?>" alt="Avatar">
                    <div class="sidebar-user-text">
                        <div class="name"><?php echo $userData['name'] ?? 'Client'; ?></div>
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
                    <h2><i class="fas fa-coins"></i> Finance Dashboard</h2>
                    <p>Manage your billing, invoices, and subscriptions</p>
                </div>
                <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:8px;"></i> Live</span>
            </div>

            <!-- ===== STATS ===== -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-number"><?php echo $stats['total_invoices']; ?></div>
                    <div class="stat-label">Total Invoices</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number"><?php echo $stats['paid_invoices']; ?></div>
                    <div class="stat-label">Paid Invoices</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?php echo $stats['unpaid_invoices']; ?></div>
                    <div class="stat-label">Pending Invoices</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-box"></i></div>
                    <div class="stat-number"><?php echo $stats['subscriptions']; ?></div>
                    <div class="stat-label">Active Subscriptions</div>
                </div>
            </div>

            <!-- ===== QUICK LINKS ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-rocket" style="color:var(--primary);"></i> Quick Actions</h3>
                    <span class="sub">Navigate to finance modules</span>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;">
                    <a href="ledger-summary.php" class="quick-link">
                        <div class="icon green"><i class="fas fa-book"></i></div>
                        <div class="info">
                            <div class="title">Ledger Summary</div>
                            <div class="desc">View complete financial ledger</div>
                        </div>
                        <div class="arrow"><i class="fas fa-chevron-right"></i></div>
                    </a>
                    <a href="invoices-billing.php" class="quick-link">
                        <div class="icon blue"><i class="fas fa-file-invoice"></i></div>
                        <div class="info">
                            <div class="title">Invoices & Billing</div>
                            <div class="desc">Manage all invoices</div>
                        </div>
                        <div class="arrow"><i class="fas fa-chevron-right"></i></div>
                    </a>
                    <a href="pm-billing.php" class="quick-link">
                        <div class="icon yellow"><i class="fas fa-user-tie"></i></div>
                        <div class="info">
                            <div class="title">PM Project Billing</div>
                            <div class="desc">Project manager billing</div>
                        </div>
                        <div class="arrow"><i class="fas fa-chevron-right"></i></div>
                    </a>
                    <a href="subscription-packaging.php" class="quick-link">
                        <div class="icon purple"><i class="fas fa-boxes"></i></div>
                        <div class="info">
                            <div class="title">Subscription Packaging</div>
                            <div class="desc">Manage subscriptions</div>
                        </div>
                        <div class="arrow"><i class="fas fa-chevron-right"></i></div>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000`;
        }
    </script>

</body>
</html>