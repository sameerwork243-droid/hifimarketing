<?php
// ledger-summary.php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: client-portal/login.php');
    exit();
}

$userData = $_SESSION['user'] ?? [];
$user_id = $_SESSION['user_id'] ?? 0;

// Get client data
$client_id = 0;
if ($user_id > 0) {
    $client_sql = "SELECT * FROM clients WHERE user_id = ?";
    $client_stmt = mysqli_prepare($conn, $client_sql);
    mysqli_stmt_bind_param($client_stmt, "i", $user_id);
    mysqli_stmt_execute($client_stmt);
    $client_result = mysqli_stmt_get_result($client_stmt);
    if ($client_data = mysqli_fetch_assoc($client_result)) {
        $client_id = $client_data['id'];
    }
    mysqli_stmt_close($client_stmt);
}

// Get ledger entries
$ledger_entries = [];
if ($client_id > 0) {
    $ledger_sql = "SELECT * FROM ledger WHERE client_id = ? ORDER BY date DESC LIMIT 50";
    $ledger_stmt = mysqli_prepare($conn, $ledger_sql);
    mysqli_stmt_bind_param($ledger_stmt, "i", $client_id);
    mysqli_stmt_execute($ledger_stmt);
    $ledger_result = mysqli_stmt_get_result($ledger_stmt);
    while ($row = mysqli_fetch_assoc($ledger_result)) {
        $ledger_entries[] = $row;
    }
    mysqli_stmt_close($ledger_stmt);
}

// Get totals
$totals = ['income' => 0, 'expense' => 0, 'balance' => 0];
if ($client_id > 0) {
    $total_sql = "SELECT type, SUM(amount) as total FROM ledger WHERE client_id = ? GROUP BY type";
    $total_stmt = mysqli_prepare($conn, $total_sql);
    mysqli_stmt_bind_param($total_stmt, "i", $client_id);
    mysqli_stmt_execute($total_stmt);
    $total_result = mysqli_stmt_get_result($total_stmt);
    while ($row = mysqli_fetch_assoc($total_result)) {
        if ($row['type'] === 'income') $totals['income'] = $row['total'];
        else $totals['expense'] = $row['total'];
    }
    mysqli_stmt_close($total_stmt);
    $totals['balance'] = $totals['income'] - $totals['expense'];
}

$current_page = 'ledger-summary.php';
$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger Summary | Finance Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="../images/fav-icon.png" type="image/png" />
    <style>
        /* Include all styles from finance-portal.php here */
        /* For brevity, use @import or copy styles */
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

        /* Header - same as finance-portal */
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

        /* Main Layout */
        .main-layout {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
            min-height: calc(100vh - 72px);
        }

        /* Sidebar */
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

        /* Content */
        .content {
            flex: 1;
            min-width: 0;
        }

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

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .ledger-table th {
            text-align: left;
            padding: 10px 12px;
            background: var(--bg);
            font-weight: 700;
            color: var(--text-secondary);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border);
        }
        .ledger-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
        }
        .ledger-table tr:hover {
            background: var(--bg);
        }
        .ledger-table .income {
            color: var(--success);
            font-weight: 700;
        }
        .ledger-table .expense {
            color: var(--danger);
            font-weight: 700;
        }
        .status-badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.completed { background: #d1fae5; color: #059669; }
        .status-badge.pending { background: #fef3c7; color: #d97706; }
        .status-badge.failed { background: #fee2e2; color: #dc2626; }

        .totals-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .total-box {
            padding: 14px 16px;
            border-radius: 10px;
            text-align: center;
        }
        .total-box .number {
            font-size: 22px;
            font-weight: 800;
        }
        .total-box .label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .total-box.income {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
        }
        .total-box.income .number { color: #059669; }
        .total-box.expense {
            background: #fee2e2;
            border: 1px solid #fecaca;
        }
        .total-box.expense .number { color: #dc2626; }
        .total-box.balance {
            background: #dbeafe;
            border: 1px solid #bfdbfe;
        }
        .total-box.balance .number { color: #2563eb; }

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

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-layout { flex-direction: column; }
            .totals-grid { grid-template-columns: 1fr; }
            .ledger-table { font-size: 12px; }
            .ledger-table th, .ledger-table td { padding: 6px 8px; }
        }
        @media (max-width: 480px) {
            .ledger-table { font-size: 11px; }
            .ledger-table th, .ledger-table td { padding: 4px 6px; }
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
                <a href="finance-portal.php" class="sidebar-link">
                    <i class="fas fa-chart-pie"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                
                <div class="nav-label">Billing</div>
                <a href="ledger-summary.php" class="sidebar-link active">
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
                    <h2><i class="fas fa-book"></i> Ledger Summary</h2>
                    <p>Complete financial transaction history</p>
                </div>
                <span class="badge"><i class="fas fa-circle" style="color:#4ade80;font-size:8px;"></i> <?php echo count($ledger_entries); ?> Entries</span>
            </div>

            <!-- ===== TOTALS ===== -->
            <div class="totals-grid">
                <div class="total-box income">
                    <div class="number">$<?php echo number_format($totals['income'], 2); ?></div>
                    <div class="label">Total Income</div>
                </div>
                <div class="total-box expense">
                    <div class="number">$<?php echo number_format($totals['expense'], 2); ?></div>
                    <div class="label">Total Expenses</div>
                </div>
                <div class="total-box balance">
                    <div class="number">$<?php echo number_format($totals['balance'], 2); ?></div>
                    <div class="label">Net Balance</div>
                </div>
            </div>

            <!-- ===== LEDGER TABLE ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list" style="color:var(--primary);"></i> Transaction History</h3>
                    <span class="sub">Recent 50 entries</span>
                </div>
                <?php if (!empty($ledger_entries)): ?>
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ledger_entries as $entry): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($entry['date'])); ?></td>
                            <td><?php echo htmlspecialchars($entry['description']); ?></td>
                            <td><?php echo htmlspecialchars($entry['category'] ?? 'General'); ?></td>
                            <td class="<?php echo $entry['type']; ?>">
                                <?php echo ucfirst($entry['type']); ?>
                            </td>
                            <td class="<?php echo $entry['type']; ?>">
                                $<?php echo number_format($entry['amount'], 2); ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $entry['status']; ?>">
                                    <?php echo ucfirst($entry['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No ledger entries found.</p>
                </div>
                <?php endif; ?>
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