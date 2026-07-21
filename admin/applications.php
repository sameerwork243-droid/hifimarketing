<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

// ===== UPDATE APPLICATION STATUS =====
if (isset($_POST['update_status'])) {
    $app_id = (int)$_POST['app_id'];
    $status = sanitize($_POST['status']);
    $query = "UPDATE applications SET status = '$status' WHERE id = $app_id";
    if (mysqli_query($conn, $query)) {
        header('Location: applications.php?msg=updated');
    } else {
        header('Location: applications.php?msg=error');
    }
    exit();
}

// ===== GET ALL APPLICATIONS =====
$apps_query = "SELECT a.*, j.title as job_title, u.username 
               FROM applications a 
               LEFT JOIN jobs j ON a.job_id = j.id 
               LEFT JOIN users u ON a.user_id = u.id
               ORDER BY a.applied_at DESC";
$apps_result = mysqli_query($conn, $apps_query);
$applications = mysqli_fetch_all($apps_result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Applications | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        /* ===== ADMIN SIDEBAR ===== */
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

        .table-wrap {
            background: var(--admin-card-bg);
            border-radius: var(--admin-radius);
            border: 1px solid var(--admin-border);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background: #f8fafc;
            text-align: left;
            padding: 14px 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--admin-text-muted);
            border-bottom: 1px solid var(--admin-border);
        }
        table td {
            padding: 14px 20px;
            font-size: 14px;
            color: var(--admin-text-secondary);
            border-bottom: 1px solid #f0f2f5;
        }
        table tr:hover td { background: #f8fafc; }

        .status-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.pending { background: #fff3e0; color: #e65100; }
        .status-badge.reviewed { background: #e8edfe; color: var(--admin-primary); }
        .status-badge.shortlisted { background: #e8f5e9; color: #2e7d32; }
        .status-badge.rejected { background: #fee2e2; color: #dc3545; }

        .status-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .status-form select {
            padding: 4px 8px;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-size: 13px;
            background: #f8fafc;
        }
        .status-form .btn-update {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            background: var(--admin-primary);
            color: #fff;
            cursor: pointer;
        }
        .status-form .btn-update:hover {
            background: #3a4be0;
        }

        .btn-resume {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: #e8edfe;
            color: var(--admin-primary);
            border: none;
            cursor: pointer;
            transition: var(--admin-transition);
            text-decoration: none;
        }
        .btn-resume:hover {
            background: var(--admin-primary);
            color: #fff;
        }
        .btn-resume.download {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .btn-resume.download:hover {
            background: #2e7d32;
            color: #fff;
        }

        .msg {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .msg.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .msg.error { background: #fee2e2; color: #dc3545; border: 1px solid #fecaca; }

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

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: 99;
        }
        .sidebar-overlay.show { display: block; }

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
        body.dark-mode .status-form select {
            background: #0b0d10;
            border-color: #1e242c;
            color: #eaeef2;
        }
        body.dark-mode .hamburger-btn:hover {
            background: #14191f;
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
            table { font-size: 13px; }
            table th, table td { padding: 10px 12px; }
            .status-form { flex-wrap: wrap; }
            .table-wrap { overflow-x: auto; }
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
            <a href="applications.php" class="nav-item active"><i class="fas fa-users"></i> Applications</a>
            <a href="messages.php" class="nav-item"><i class="fas fa-envelope"></i> Messages</a>
            <a href="export-report.php" class="nav-item"><i class="fas fa-file-alt"></i> Reports</a>
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
                <h1>Applications</h1>
            </div>
            <div class="header-actions">
                <button class="theme-toggle-btn" onclick="toggleTheme()"><i class="fas fa-moon" id="themeIcon"></i></button>
            </div>
        </header>

        <div class="admin-content">
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                <div class="msg success">✅ Application status updated successfully!</div>
            <?php endif; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Applicant</th>
                            <th>Job</th>
                            <th>Email</th>
                            <th>Resume</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($applications) > 0): ?>
                            <?php $i = 1; foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><strong><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($app['job_title'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($app['email']); ?></td>
                                <td>
                                    <?php if (!empty($app['resume']) && file_exists('../' . $app['resume'])): ?>
                                        <a href="#" onclick="previewResume('../<?php echo $app['resume']; ?>')" class="btn-resume" title="Preview Resume">
                                            <i class="fas fa-eye"></i> Preview
                                        </a>
                                        <a href="../<?php echo $app['resume']; ?>" download class="btn-resume download" title="Download Resume">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span style="color:var(--admin-text-muted);font-size:12px;">No resume</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge <?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span></td>
                                <td><?php echo time_ago($app['applied_at']); ?></td>
                                <td>
                                    <form method="POST" action="" class="status-form">
                                        <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>" />
                                        <select name="status">
                                            <option value="pending" <?php echo $app['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="reviewed" <?php echo $app['status'] == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                            <option value="shortlisted" <?php echo $app['status'] == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                            <option value="rejected" <?php echo $app['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-update">Update</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--admin-text-muted);">No applications received yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ===== RESUME PREVIEW MODAL ===== -->
    <div id="resumeModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#ffffff;border-radius:16px;max-width:800px;width:100%;max-height:90vh;overflow:hidden;position:relative;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e9edf2;">
                <h3 style="font-size:18px;font-weight:700;color:#1a1c26;"><i class="fas fa-file-pdf" style="color:#dc3545;"></i> Resume Preview</h3>
                <button onclick="closeResumePreview()" style="background:transparent;border:none;font-size:24px;cursor:pointer;color:#8a94a0;">&times;</button>
            </div>
            <div style="padding:20px;overflow-y:auto;max-height:calc(90vh - 70px);">
                <iframe id="resumeFrame" src="" style="width:100%;height:600px;border:none;border-radius:8px;"></iframe>
                <p style="text-align:center;margin-top:12px;font-size:13px;color:#8a94a0;">
                    <i class="fas fa-info-circle"></i> If preview doesn't load, 
                    <a href="#" id="resumeDownloadLink" style="color:#4a5cf5;font-weight:600;">download the file</a> to view it.
                </p>
            </div>
        </div>
    </div>

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

        // ===== RESUME PREVIEW =====
        function previewResume(filePath) {
            const modal = document.getElementById('resumeModal');
            const frame = document.getElementById('resumeFrame');
            const downloadLink = document.getElementById('resumeDownloadLink');
            
            modal.style.display = 'flex';
            frame.src = filePath;
            downloadLink.href = filePath;
            
            // Close modal on outside click
            modal.onclick = function(e) {
                if (e.target === modal) {
                    closeResumePreview();
                }
            };
        }

        function closeResumePreview() {
            const modal = document.getElementById('resumeModal');
            const frame = document.getElementById('resumeFrame');
            modal.style.display = 'none';
            frame.src = '';
        }

        // ===== ESC KEY TO CLOSE =====
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeResumePreview();
            }
        });

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