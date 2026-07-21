<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

// ===== GET UNREAD MESSAGES COUNT =====
$msg_query = "SELECT COUNT(*) as total FROM messages WHERE status = 'unread'";
$msg_result = mysqli_query($conn, $msg_query);
$unread_messages = mysqli_fetch_assoc($msg_result)['total'];

// ===== FETCH DATA =====
$jobs_query = "SELECT COUNT(*) as total FROM jobs WHERE is_active = 1";
$jobs_result = mysqli_query($conn, $jobs_query);
$total_jobs = mysqli_fetch_assoc($jobs_result)['total'];

$apps_query = "SELECT COUNT(*) as total FROM applications";
$apps_result = mysqli_query($conn, $apps_query);
$total_applications = mysqli_fetch_assoc($apps_result)['total'];

$pending_query = "SELECT COUNT(*) as total FROM applications WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_applications = mysqli_fetch_assoc($pending_result)['total'];

$status_query = "SELECT status, COUNT(*) as count FROM applications GROUP BY status";
$status_result = mysqli_query($conn, $status_query);
$status_data = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row['count'];
}

// ===== QUERY - FETCHES ALL DATA (including phone, address, resume) =====
$recent_query = "SELECT a.*, j.title as job_title FROM applications a 
                 LEFT JOIN jobs j ON a.job_id = j.id 
                 ORDER BY a.applied_at DESC LIMIT 10";
$recent_result = mysqli_query($conn, $recent_query);
$recent_apps = mysqli_fetch_all($recent_result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Export Reports | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        :root {
            --admin-sidebar-width: 260px;
            --admin-primary: #4a5cf5;
            --admin-bg: #f0f2f5;
            --admin-card-bg: #ffffff;
            --admin-text-primary: #1a1c26;
            --admin-text-secondary: #3d4452;
            --admin-text-muted: #8a94a0;
            --admin-border: #e9edf2;
            --admin-radius: 16px;
            --admin-transition: 0.3s ease;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--admin-bg);
            color: var(--admin-text-primary);
            display: flex;
            min-height: 100vh;
            line-height: 1.6;
        }
        a { text-decoration: none; color: inherit; }

        .admin-sidebar {
            width: var(--admin-sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #ffffff;
            border-right: 1px solid var(--admin-border);
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        .admin-sidebar .logo {
            font-size: 22px;
            font-weight: 900;
            color: var(--admin-text-primary);
            padding-bottom: 24px;
            border-bottom: 1px solid var(--admin-border);
            margin-bottom: 24px;
        }
        .admin-sidebar .logo span { color: var(--admin-primary); }
        .admin-sidebar .logo small {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: var(--admin-text-muted);
        }
        .admin-sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--admin-text-secondary);
            font-weight: 600;
            font-size: 14px;
            transition: var(--admin-transition);
            cursor: pointer;
            margin-bottom: 4px;
        }
        .admin-sidebar .nav-item i {
            width: 20px;
            font-size: 16px;
            color: var(--admin-text-muted);
        }
        .admin-sidebar .nav-item:hover {
            background: #f8fafc;
            color: var(--admin-primary);
        }
        .admin-sidebar .nav-item:hover i { color: var(--admin-primary); }
        .admin-sidebar .nav-item.active {
            background: var(--admin-primary);
            color: #ffffff;
        }
        .admin-sidebar .nav-item.active i { color: #ffffff; }
        .admin-sidebar .nav-item .badge {
            margin-left: auto;
            background: #f0f3ff;
            color: var(--admin-primary);
            font-size: 11px;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .admin-sidebar .nav-item.active .badge {
            background: rgba(255,255,255,0.2);
            color: #ffffff;
        }
        .admin-sidebar .nav-item.home-link {
            background: #f0f3ff;
            border: 1px solid #e9edf2;
            margin-bottom: 12px;
        }
        .admin-sidebar .nav-item.home-link:hover {
            background: #4a5cf5;
            color: #ffffff;
        }
        .admin-sidebar .nav-item.home-link:hover i {
            color: #ffffff;
        }
        .admin-sidebar .nav-item.home-link i {
            color: #4a5cf5;
        }
        .admin-sidebar .sidebar-divider {
            border: none;
            border-top: 1px solid var(--admin-border);
            margin: 8px 0 12px;
        }
        .admin-sidebar .sidebar-footer {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--admin-border);
        }
        .admin-sidebar .sidebar-footer .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            background: #f8fafc;
        }
        .admin-sidebar .sidebar-footer .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--admin-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        .admin-sidebar .sidebar-footer .user-info .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--admin-text-primary);
        }
        .admin-sidebar .sidebar-footer .user-info .user-role {
            font-size: 12px;
            color: var(--admin-text-muted);
        }
        .admin-sidebar .sidebar-footer .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 12px;
            color: #dc3545;
            font-weight: 600;
            font-size: 14px;
            transition: var(--admin-transition);
            margin-top: 8px;
            cursor: pointer;
        }
        .admin-sidebar .sidebar-footer .logout-btn:hover {
            background: #fee2e2;
        }

        .admin-main {
            margin-left: var(--admin-sidebar-width);
            flex: 1;
            padding: 0;
            min-height: 100vh;
        }
        .admin-header {
            background: #ffffff;
            border-bottom: 1px solid var(--admin-border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .admin-header h1 {
            font-size: 20px;
            font-weight: 800;
        }
        .admin-header .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .admin-header .header-actions .btn-primary {
            background: var(--admin-primary);
            color: #fff;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: var(--admin-transition);
        }
        .admin-header .header-actions .btn-primary:hover {
            background: #3a4be0;
            transform: translateY(-2px);
        }
        .admin-header .header-actions .theme-toggle-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--admin-border);
            background: transparent;
            cursor: pointer;
            color: var(--admin-text-secondary);
        }

        .admin-content { padding: 28px 32px; }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }
        .report-card {
            background: var(--admin-card-bg);
            border-radius: var(--admin-radius);
            border: 1px solid var(--admin-border);
            padding: 24px;
            text-align: center;
            transition: var(--admin-transition);
        }
        .report-card:hover {
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .report-card .number {
            font-size: 36px;
            font-weight: 900;
            color: var(--admin-primary);
        }
        .report-card .label {
            font-size: 14px;
            color: var(--admin-text-muted);
        }

        .table-wrap {
            background: var(--admin-card-bg);
            border-radius: var(--admin-radius);
            border: 1px solid var(--admin-border);
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }
        table th {
            background: #f8fafc;
            text-align: left;
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--admin-text-muted);
            border-bottom: 2px solid var(--admin-border);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        table td {
            padding: 12px 16px;
            font-size: 13px;
            color: var(--admin-text-secondary);
            border-bottom: 1px solid #f0f2f5;
            vertical-align: middle;
            max-width: 200px;
            word-break: break-word;
        }
        table tr:hover td { background: #f8fafc; }
        table tr:last-child td { border-bottom: none; }

        .status-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.pending { background: #fff3e0; color: #e65100; }
        .status-badge.reviewed { background: #e8edfe; color: #4a5cf5; }
        .status-badge.shortlisted { background: #e8f5e9; color: #2e7d32; }
        .status-badge.rejected { background: #fee2e2; color: #dc3545; }

        .resume-link {
            color: var(--admin-primary);
            font-weight: 600;
            font-size: 12px;
            text-decoration: underline;
        }
        .resume-link:hover { color: #3a4be0; }

        .phone-display {
            font-family: 'Inter', monospace;
            font-weight: 600;
            color: var(--admin-text-primary);
        }

        .export-actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 24px;
        }
        .export-actions .btn-export {
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: var(--admin-transition);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .export-actions .btn-export.pdf { background: #dc3545; color: #fff; }
        .export-actions .btn-export.pdf:hover { background: #c82333; transform: translateY(-2px); }
        .export-actions .btn-export.csv { background: #28a745; color: #fff; }
        .export-actions .btn-export.csv:hover { background: #218838; transform: translateY(-2px); }
        .export-actions .btn-export.print { background: #17a2b8; color: #fff; }
        .export-actions .btn-export.print:hover { background: #138496; transform: translateY(-2px); }

        .hamburger-btn {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--admin-border);
            background: transparent;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--admin-text-secondary);
            font-size: 18px;
        }

        body.dark-mode {
            --admin-bg: #0b0d10;
            --admin-card-bg: #14191f;
            --admin-text-primary: #eaeef2;
            --admin-text-secondary: #b0b8c5;
            --admin-text-muted: #6b7a8a;
            --admin-border: #1e242c;
        }
        body.dark-mode .admin-sidebar {
            background: #0b0d10;
            border-color: #1e242c;
        }
        body.dark-mode .admin-sidebar .nav-item:hover {
            background: #14191f;
        }
        body.dark-mode .admin-sidebar .nav-item.active {
            background: #4a5cf5;
        }
        body.dark-mode .admin-sidebar .nav-item.home-link {
            background: #14191f;
            border-color: #1e242c;
        }
        body.dark-mode .admin-sidebar .nav-item.home-link:hover {
            background: #4a5cf5;
            color: #ffffff;
        }
        body.dark-mode .admin-sidebar .sidebar-footer .user-info {
            background: #14191f;
        }
        body.dark-mode .admin-header {
            background: #0b0d10;
            border-color: #1e242c;
        }
        body.dark-mode table th {
            background: #14191f;
            border-color: #1e242c;
        }
        body.dark-mode table td {
            border-color: #1e242c;
        }
        body.dark-mode table tr:hover td {
            background: #14191f;
        }
        body.dark-mode .hamburger-btn:hover {
            background: #14191f;
        }
        body.dark-mode .btn-export.pdf { background: #dc3545; }
        body.dark-mode .btn-export.csv { background: #28a745; }
        body.dark-mode .btn-export.print { background: #17a2b8; }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: 99;
        }
        .sidebar-overlay.show { display: block; }

        @media (max-width: 992px) {
            .report-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .admin-header { padding: 0 16px; flex-wrap: wrap; gap: 10px; }
            .admin-content { padding: 16px; }
            .hamburger-btn { display: flex !important; }
            .report-grid { grid-template-columns: 1fr; }
            table { font-size: 12px; min-width: 700px; }
            table th, table td { padding: 8px 10px; }
            .export-actions { flex-direction: column; }
            .export-actions .btn-export { justify-content: center; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <aside class="admin-sidebar" id="adminSidebar">
        <div class="logo">HIFI <span>Admin</span><small>Marketing &amp; Technologies</small></div>
        <nav>
            <a href="../index.php" class="nav-item home-link">
                <i class="fas fa-home"></i> Home
            </a>
            <hr class="sidebar-divider" />
            <a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="jobs.php" class="nav-item"><i class="fas fa-briefcase"></i> Jobs</a>
            <a href="applications.php" class="nav-item"><i class="fas fa-users"></i> Applications</a>
            <a href="messages.php" class="nav-item"><i class="fas fa-envelope"></i> Messages <span class="badge"><?php echo $unread_messages; ?></span></a>
            <a href="export-report.php" class="nav-item active"><i class="fas fa-file-alt"></i> Reports</a>
            <a href="#" class="nav-item" onclick="alert('Settings coming soon!')"><i class="fas fa-cog"></i> Settings</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div style="display:flex;align-items:center;gap:16px;">
                <button class="hamburger-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1>Reports &amp; Analytics</h1>
            </div>
            <div class="header-actions">
                <button class="theme-toggle-btn" onclick="toggleTheme()"><i class="fas fa-moon" id="themeIcon"></i></button>
            </div>
        </header>

        <div class="admin-content">
            <div class="report-grid">
                <div class="report-card"><div class="number"><?php echo $total_jobs; ?></div><div class="label">Total Jobs</div></div>
                <div class="report-card"><div class="number"><?php echo $total_applications; ?></div><div class="label">Total Applications</div></div>
                <div class="report-card"><div class="number"><?php echo $pending_applications; ?></div><div class="label">Pending Applications</div></div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Applicant</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Job</th>
                            <th>Resume</th>
                            <th>Status</th>
                            <th>Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_apps) > 0): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($recent_apps as $app): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="phone-display">
                                        <?php echo htmlspecialchars($app['phone_code'] . ' ' . $app['phone']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($app['email']); ?></td>
                                <td style="max-width:150px;">
                                    <?php echo htmlspecialchars($app['address'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['job_title'] ?? 'N/A'); ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($app['resume_file'])): ?>
                                        <a href="../uploads/resumes/<?php echo htmlspecialchars($app['resume_file']); ?>" target="_blank" class="resume-link">
                                            <i class="fas fa-file-pdf"></i> View
                                        </a>
                                    <?php else: ?>
                                        <span style="color:var(--admin-text-muted);font-size:12px;">No file</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $app['status']; ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </td>
                                <td style="font-size:12px;color:var(--admin-text-muted);">
                                    <?php echo date('M d, Y', strtotime($app['applied_at'] ?? $app['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center;padding:60px 20px;color:var(--admin-text-muted);">
                                    <i class="fas fa-inbox" style="font-size:40px;display:block;margin-bottom:12px;"></i>
                                    <h3 style="color:var(--admin-text-primary);">No applications found</h3>
                                    <p>Applications will appear here once users submit them.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="export-actions">
                <button class="btn-export pdf" onclick="window.print()"><i class="fas fa-file-pdf"></i> Export as PDF</button>
                <button class="btn-export csv" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export as CSV</button>
                <button class="btn-export print" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }

        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById('themeIcon');
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
                icon.className = 'fas fa-sun';
            } else {
                localStorage.setItem('theme', 'light');
                icon.className = 'fas fa-moon';
            }
        }

        function exportCSV() {
            let csv = 'ID,Applicant,Phone,Email,Address,Job,Status,Applied\n';
            
            <?php foreach ($recent_apps as $app): ?>
                csv += '<?php echo $app['id']; ?>,';
                csv += '"<?php echo addslashes($app['first_name'] . ' ' . $app['last_name']); ?>",';
                csv += '"<?php echo addslashes($app['phone_code'] . ' ' . $app['phone']); ?>",';
                csv += '"<?php echo addslashes($app['email']); ?>",';
                csv += '"<?php echo addslashes($app['address']); ?>",';
                csv += '"<?php echo addslashes($app['job_title'] ?? 'N/A'); ?>",';
                csv += '<?php echo $app['status']; ?>,';
                csv += '<?php echo $app['applied_at'] ?? $app['created_at']; ?>\n';
            <?php endforeach; ?>
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'applications-report.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const icon = document.getElementById('themeIcon');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                icon.className = 'fas fa-sun';
            } else {
                icon.className = 'fas fa-moon';
            }
        });
    </script>
</body>
</html>