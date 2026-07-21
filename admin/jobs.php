<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

// ===== GET UNREAD MESSAGES COUNT =====
$msg_query = "SELECT COUNT(*) as total FROM messages WHERE status = 'unread'";
$msg_result = mysqli_query($conn, $msg_query);
$unread_messages = mysqli_fetch_assoc($msg_result)['total'];

// ===== HANDLE DELETE =====
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $del_query = "DELETE FROM jobs WHERE id = $id";
    if (mysqli_query($conn, $del_query)) {
        header('Location: jobs.php?msg=deleted');
    } else {
        header('Location: jobs.php?msg=error');
    }
    exit();
}

// ===== HANDLE ADD/EDIT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_job'])) {
    $title = sanitize($_POST['title']);
    $department = sanitize($_POST['department']);
    $location = sanitize($_POST['location']);
    $type = sanitize($_POST['type']);
    $workplace = sanitize($_POST['workplace']);
    $description = sanitize($_POST['description']);
    $responsibilities = sanitize($_POST['responsibilities']);
    $requirements = sanitize($_POST['requirements']);
    $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;

    if ($job_id > 0) {
        $query = "UPDATE jobs SET 
                  title = '$title', 
                  department = '$department', 
                  location = '$location',
                  type = '$type', 
                  workplace = '$workplace', 
                  description = '$description',
                  responsibilities = '$responsibilities', 
                  requirements = '$requirements'
                  WHERE id = $job_id";
        
        if (mysqli_query($conn, $query)) {
            header('Location: jobs.php?msg=updated');
        } else {
            header('Location: jobs.php?msg=error');
        }
    } else {
        $query = "INSERT INTO jobs (title, department, location, type, workplace, description, responsibilities, requirements) 
                  VALUES ('$title', '$department', '$location', '$type', '$workplace', '$description', '$responsibilities', '$requirements')";
        
        if (mysqli_query($conn, $query)) {
            header('Location: jobs.php?msg=added');
        } else {
            header('Location: jobs.php?msg=error');
        }
    }
    exit();
}

// ===== GET ALL JOBS =====
$jobs_query = "SELECT * FROM jobs WHERE is_active = 1 ORDER BY posted_date DESC";
$jobs_result = mysqli_query($conn, $jobs_query);
$jobs = mysqli_fetch_all($jobs_result, MYSQLI_ASSOC);

$edit_job = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM jobs WHERE id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_job = mysqli_fetch_assoc($edit_result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Jobs | Admin</title>
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
        table .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        table .actions .btn {
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: var(--admin-transition);
            text-decoration: none;
            display: inline-block;
        }
        table .actions .btn-view {
            background: #e8edfe;
            color: var(--admin-primary);
        }
        table .actions .btn-view:hover {
            background: var(--admin-primary);
            color: #fff;
        }
        table .actions .btn-edit {
            background: #fff3e0;
            color: #e65100;
        }
        table .actions .btn-edit:hover {
            background: #e65100;
            color: #fff;
        }
        table .actions .btn-delete {
            background: #fee2e2;
            color: #dc3545;
        }
        table .actions .btn-delete:hover {
            background: #dc3545;
            color: #fff;
        }
        .status-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.active { background: #e8f5e9; color: #2e7d32; }
        .status-badge.inactive { background: #fee2e2; color: #dc3545; }

        .msg {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .msg.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .msg.error { background: #fee2e2; color: #dc3545; border: 1px solid #fecaca; }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 200;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.show { display: flex; }
        .modal {
            background: #ffffff;
            border-radius: var(--admin-radius);
            max-width: 750px;
            width: 92%;
            max-height: 92vh;
            overflow-y: auto;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            animation: modalIn 0.3s ease;
        }
        @keyframes modalIn {
            from { transform: scale(0.95) translateY(20px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        .modal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--admin-border);
        }
        .modal .modal-header h2 {
            font-size: 22px;
            font-weight: 800;
            color: var(--admin-text-primary);
        }
        .modal .modal-header h2 i {
            color: var(--admin-primary);
            margin-right: 10px;
        }
        .modal .modal-header .close-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #f0f2f5;
            cursor: pointer;
            font-size: 18px;
            transition: var(--admin-transition);
            color: var(--admin-text-secondary);
        }
        .modal .modal-header .close-btn:hover {
            background: #fee2e2;
            color: #dc3545;
        }
        .view-detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f0f2f5;
        }
        .view-detail-row:last-child { border-bottom: none; }
        .view-detail-row .label {
            font-weight: 700;
            color: var(--admin-text-primary);
            min-width: 150px;
            font-size: 14px;
        }
        .view-detail-row .value {
            color: var(--admin-text-secondary);
            font-size: 14px;
            flex: 1;
        }
        .modal .form-group { margin-bottom: 16px; }
        .modal .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
            color: var(--admin-text-primary);
        }
        .modal .form-group label .required { color: #dc3545; }
        .modal .form-group input,
        .modal .form-group select,
        .modal .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--admin-border);
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            transition: var(--admin-transition);
            color: var(--admin-text-primary);
        }
        .modal .form-group input:focus,
        .modal .form-group select:focus,
        .modal .form-group textarea:focus {
            border-color: var(--admin-primary);
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(74,92,245,0.08);
        }
        .modal .form-group textarea { min-height: 80px; resize: vertical; }
        .modal .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .modal .btn-submit {
            background: var(--admin-primary);
            color: #fff;
            padding: 12px 32px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: var(--admin-transition);
            width: 100%;
        }
        .modal .btn-submit:hover {
            background: #3a4be0;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(74,92,245,0.3);
        }

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
        body.dark-mode .modal {
            background: #14191f;
            border: 1px solid #1e242c;
        }
        body.dark-mode .modal .modal-header {
            border-color: #1e242c;
        }
        body.dark-mode .modal .modal-header h2 {
            color: #eaeef2;
        }
        body.dark-mode .modal .modal-header .close-btn {
            background: #1e242c;
            color: #b0b8c5;
        }
        body.dark-mode .modal .modal-header .close-btn:hover {
            background: #2a3340;
            color: #dc3545;
        }
        body.dark-mode .modal .form-group input,
        body.dark-mode .modal .form-group select,
        body.dark-mode .modal .form-group textarea {
            background: #0b0d10;
            border-color: #1e242c;
            color: #eaeef2;
        }
        body.dark-mode .modal .form-group input:focus,
        body.dark-mode .modal .form-group select:focus,
        body.dark-mode .modal .form-group textarea:focus {
            background: #14191f;
            border-color: #4a5cf5;
        }
        body.dark-mode .view-detail-row {
            border-color: #1e242c;
        }
        body.dark-mode .view-detail-row .label {
            color: #eaeef2;
        }
        body.dark-mode .view-detail-row .value {
            color: #b0b8c5;
        }
        body.dark-mode .hamburger-btn:hover {
            background: #14191f;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: 99;
        }
        .sidebar-overlay.show { display: block; }

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
            .modal .form-row { grid-template-columns: 1fr; }
            table { font-size: 13px; }
            table th, table td { padding: 10px 12px; }
            .table-wrap { overflow-x: auto; }
            .modal { padding: 20px; }
            .view-detail-row { flex-direction: column; gap: 4px; }
            .view-detail-row .label { min-width: auto; }
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
            <a href="jobs.php" class="nav-item active"><i class="fas fa-briefcase"></i> Jobs <span class="badge"><?php echo count($jobs); ?></span></a>
            <a href="applications.php" class="nav-item"><i class="fas fa-users"></i> Applications</a>
            <a href="messages.php" class="nav-item"><i class="fas fa-envelope"></i> Messages <span class="badge"><?php echo $unread_messages; ?></span></a>
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
                <h1>Manage Jobs</h1>
            </div>
            <div class="header-actions">
                <button class="btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add New Job</button>
                <button class="theme-toggle-btn" onclick="toggleTheme()"><i class="fas fa-moon" id="themeIcon"></i></button>
            </div>
        </header>

        <div class="admin-content">
            <?php if (isset($_GET['msg'])): ?>
                <div class="msg <?php echo ($_GET['msg'] == 'error') ? 'error' : 'success'; ?>">
                    <?php
                        if ($_GET['msg'] == 'added') echo '✅ Job added successfully!';
                        elseif ($_GET['msg'] == 'updated') echo '✅ Job updated successfully!';
                        elseif ($_GET['msg'] == 'deleted') echo '✅ Job deleted successfully!';
                        elseif ($_GET['msg'] == 'error') echo '❌ Something went wrong. Please try again.';
                    ?>
                </div>
            <?php endif; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Workplace</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($jobs) > 0): ?>
                            <?php $i = 1; foreach ($jobs as $job): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><strong><?php echo htmlspecialchars($job['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($job['department']); ?></td>
                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                <td><?php echo $job['type']; ?></td>
                                <td><?php echo $job['workplace'] ?? 'On-site'; ?></td>
                                <td><span class="status-badge active">Active</span></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-view" onclick="viewJob(<?php echo $job['id']; ?>)"><i class="fas fa-eye"></i> View</button>
                                        <button class="btn btn-edit" onclick="editJob(<?php echo $job['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="btn btn-delete" onclick="deleteJob(<?php echo $job['id']; ?>)"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--admin-text-muted);">No jobs found. Create your first job!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ===== MODAL: VIEW JOB ===== -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-briefcase"></i> Job Details</h2>
                <button class="close-btn" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
            </div>
            <div id="viewContent">
                <div class="view-detail-row"><div class="label">Job Title</div><div class="value" id="viewTitle">-</div></div>
                <div class="view-detail-row"><div class="label">Department</div><div class="value" id="viewDepartment">-</div></div>
                <div class="view-detail-row"><div class="label">Location</div><div class="value" id="viewLocation">-</div></div>
                <div class="view-detail-row"><div class="label">Employment Type</div><div class="value" id="viewType">-</div></div>
                <div class="view-detail-row"><div class="label">Workplace</div><div class="value" id="viewWorkplace">-</div></div>
                <div class="view-detail-row"><div class="label">Description</div><div class="value" id="viewDescription" style="white-space:pre-wrap;">-</div></div>
                <div class="view-detail-row"><div class="label">Responsibilities</div><div class="value" id="viewResponsibilities" style="white-space:pre-wrap;">-</div></div>
                <div class="view-detail-row"><div class="label">Requirements</div><div class="value" id="viewRequirements" style="white-space:pre-wrap;">-</div></div>
                <div class="view-detail-row"><div class="label">Posted Date</div><div class="value" id="viewPosted">-</div></div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL: ADD/EDIT JOB ===== -->
    <div class="modal-overlay" id="jobModal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Add New Job</h2>
                <button class="close-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="" id="jobForm">
                <input type="hidden" name="job_id" id="jobId" value="" />
                <input type="hidden" name="submit_job" value="1" />
                <div class="form-group">
                    <label>Job Title <span class="required">*</span></label>
                    <input type="text" name="title" id="jobTitle" required placeholder="e.g. Senior Software Engineer" />
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Department</label><input type="text" name="department" id="jobDept" placeholder="e.g. Engineering" /></div>
                    <div class="form-group"><label>Location</label><input type="text" name="location" id="jobLocation" placeholder="e.g. Lahore, Pakistan" /></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Employment Type</label>
                        <select name="type" id="jobType">
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Workplace Type</label>
                        <select name="workplace" id="jobWorkplace">
                            <option value="On-site">On-site</option>
                            <option value="Remote">Remote</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Job Description</label>
                    <textarea name="description" id="jobDesc" placeholder="Write a detailed job description..." rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Key Responsibilities <span style="font-size:12px;color:var(--admin-text-muted);">(one per line)</span></label>
                    <textarea name="responsibilities" id="jobResp" placeholder="List responsibilities (one per line)..." rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Requirements <span style="font-size:12px;color:var(--admin-text-muted);">(one per line)</span></label>
                    <textarea name="requirements" id="jobReq" placeholder="List requirements (one per line)..." rows="3"></textarea>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Job</button>
            </form>
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

        function viewJob(id) {
            fetch('get-job.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('viewTitle').textContent = data.job.title || '-';
                        document.getElementById('viewDepartment').textContent = data.job.department || '-';
                        document.getElementById('viewLocation').textContent = data.job.location || '-';
                        document.getElementById('viewType').textContent = data.job.type || '-';
                        document.getElementById('viewWorkplace').textContent = data.job.workplace || 'On-site';
                        document.getElementById('viewDescription').textContent = data.job.description || 'No description provided.';
                        document.getElementById('viewResponsibilities').textContent = data.job.responsibilities || 'No responsibilities listed.';
                        document.getElementById('viewRequirements').textContent = data.job.requirements || 'No requirements listed.';
                        document.getElementById('viewPosted').textContent = data.job.posted_date ? new Date(data.job.posted_date).toLocaleDateString() : '-';
                        document.getElementById('viewModal').classList.add('show');
                    } else {
                        alert('Error loading job details.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading job details.');
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('show');
        }

        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Job';
            document.getElementById('jobId').value = '';
            document.getElementById('jobTitle').value = '';
            document.getElementById('jobDept').value = '';
            document.getElementById('jobLocation').value = '';
            document.getElementById('jobType').value = 'Full-time';
            document.getElementById('jobWorkplace').value = 'On-site';
            document.getElementById('jobDesc').value = '';
            document.getElementById('jobResp').value = '';
            document.getElementById('jobReq').value = '';
            document.getElementById('jobModal').classList.add('show');
        }

        function editJob(id) {
            fetch('get-job.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Job';
                        document.getElementById('jobId').value = data.job.id;
                        document.getElementById('jobTitle').value = data.job.title || '';
                        document.getElementById('jobDept').value = data.job.department || '';
                        document.getElementById('jobLocation').value = data.job.location || '';
                        document.getElementById('jobType').value = data.job.type || 'Full-time';
                        document.getElementById('jobWorkplace').value = data.job.workplace || 'On-site';
                        document.getElementById('jobDesc').value = data.job.description || '';
                        document.getElementById('jobResp').value = data.job.responsibilities || '';
                        document.getElementById('jobReq').value = data.job.requirements || '';
                        document.getElementById('jobModal').classList.add('show');
                    } else {
                        alert('Error loading job details.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading job details.');
                });
        }

        function deleteJob(id) {
            if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
                window.location.href = '?delete=' + id;
            }
        }

        function closeModal() {
            document.getElementById('jobModal').classList.remove('show');
        }

        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewModal();
        });
        document.getElementById('jobModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
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