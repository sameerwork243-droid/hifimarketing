<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : 0;
$username = $isLoggedIn ? $_SESSION['username'] : '';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

if (!$isLoggedIn || $userRole === 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/functions.php';

$clientData = null;
$tasks = [];
$supportTickets = [];

$query = "SELECT * FROM clients WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$clientData = mysqli_fetch_assoc($result);

if ($clientData) {
    $clientId = $clientData['id'];
    
    $query = "SELECT t.*, p.project_name FROM tasks t 
              LEFT JOIN projects p ON t.project_id = p.id 
              WHERE p.client_id = ? ORDER BY t.created_at DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $clientId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) { $tasks[] = $row; }
    mysqli_stmt_close($stmt);
    
    $query = "SELECT * FROM support_tickets WHERE client_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $clientId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) { $supportTickets[] = $row; }
    mysqli_stmt_close($stmt);
}

$currentPage = 'tasks';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HIFI | Tasks & Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="css/portal.css" />
    <style>
        /* ===== SAME SIDEBAR STYLES ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #1a1c26; line-height: 1.6; }
        a { text-decoration: none; color: inherit; }

        .portal-wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px; background: #ffffff; border-right: 1px solid #e9edf2;
            padding: 24px 16px; position: fixed; top: 0; left: 0; bottom: 0;
            overflow-y: auto; z-index: 99; transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .sidebar .sidebar-logo {
            font-size: 22px; font-weight: 900; color: #1a1c26;
            padding: 0 8px 24px; border-bottom: 1px solid #e9edf2; margin-bottom: 20px;
        }
        .sidebar .sidebar-logo span { color: #4a5cf5; }
        .sidebar .sidebar-logo small { display: block; font-size: 12px; font-weight: 400; color: #4a5260; }

        .sidebar .nav-section { margin-bottom: 24px; }
        .sidebar .nav-section .section-title {
            font-size: 11px; font-weight: 700; color: #4a5260;
            text-transform: uppercase; letter-spacing: 0.5px; padding: 0 12px; margin-bottom: 8px;
        }
        .sidebar .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px; border-radius: 10px; color: #4a5260;
            font-weight: 500; font-size: 14px; transition: all 0.3s ease; cursor: pointer;
        }
        .sidebar .nav-item:hover { background: #f0f3f8; color: #1a1c26; }
        .sidebar .nav-item.active {
            background: #4a5cf5; color: #ffffff; box-shadow: 0 4px 16px rgba(74, 92, 245, 0.2);
        }
        .sidebar .nav-item i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar .nav-item .badge {
            margin-left: auto; background: #e9edf2; color: #4a5260;
            font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 20px;
        }
        .sidebar .nav-item.active .badge { background: rgba(255,255,255,0.2); color: #ffffff; }

        .sidebar .sidebar-footer {
            border-top: 1px solid #e9edf2; padding-top: 16px; margin-top: 16px;
        }
        .sidebar .sidebar-footer .user-info {
            display: flex; align-items: center; gap: 12px; padding: 8px 12px;
        }
        .sidebar .sidebar-footer .user-info .avatar {
            width: 40px; height: 40px; border-radius: 50%; background: #4a5cf5;
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px;
        }
        .sidebar .sidebar-footer .user-info .user-details h4 { font-size: 14px; font-weight: 600; color: #1a1c26; }
        .sidebar .sidebar-footer .user-info .user-details p { font-size: 12px; color: #4a5260; }
        .sidebar-toggle {
            display: none; position: fixed; top: 16px; left: 16px; z-index: 100;
            background: #ffffff; border: 1px solid #e9edf2; border-radius: 10px;
            padding: 10px 12px; cursor: pointer; font-size: 20px; color: #1a1c26;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 24px 32px 60px;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; flex-wrap: wrap; gap: 16px;
        }
        .page-header h2 { font-size: 24px; font-weight: 800; color: #1a1c26; }
        .page-header p { color: #4a5260; font-size: 14px; }

        .btn-primary {
            background: #4a5cf5; color: #fff; padding: 10px 24px;
            border-radius: 10px; font-weight: 700; font-size: 14px; border: none;
            cursor: pointer; transition: 0.3s ease; font-family: 'Inter', sans-serif;
        }
        .btn-primary:hover { background: #3a4be0; transform: translateY(-2px); box-shadow: 0 4px 16px rgba(74, 92, 245, 0.2); }

        /* ===== TASK ITEMS ===== */
        .task-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0; border-bottom: 1px solid #f0f3f8;
        }
        .task-item:last-child { border-bottom: none; }
        .task-checkbox {
            width: 18px; height: 18px; border: 2px solid #d0d7e0;
            border-radius: 4px; cursor: pointer; flex-shrink: 0; transition: 0.3s ease;
        }
        .task-checkbox.done {
            background: #4a5cf5; border-color: #4a5cf5; position: relative;
        }
        .task-checkbox.done::after {
            content: "✓"; color: #fff; font-size: 12px;
            display: flex; align-items: center; justify-content: center; height: 100%;
        }
        .task-info { flex: 1; }
        .task-info h4 { font-size: 14px; font-weight: 500; color: #1a1c26; }
        .task-info p { font-size: 12px; color: #4a5260; }
        .task-info h4.done-text { text-decoration: line-through; color: #8a94a0; }
        .task-priority {
            font-size: 10px; font-weight: 700; padding: 2px 10px; border-radius: 20px;
        }
        .task-priority.high { background: #fecaca; color: #ef4444; }
        .task-priority.medium { background: #fef3c7; color: #f59e0b; }
        .task-priority.low { background: #dcfce7; color: #22c55e; }

        /* ===== TICKET ITEMS ===== */
        .ticket-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 0; border-bottom: 1px solid #f0f3f8;
        }
        .ticket-item:last-child { border-bottom: none; }
        .ticket-item h4 { font-size: 14px; font-weight: 600; color: #1a1c26; }
        .ticket-item p { font-size: 12px; color: #4a5260; max-width: 60%; }
        .ticket-status {
            padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .ticket-status.open { background: #fef3c7; color: #f59e0b; }
        .ticket-status.in-progress { background: #dbeafe; color: #4a5cf5; }
        .ticket-status.resolved { background: #dcfce7; color: #22c55e; }

        .empty-state { text-align: center; padding: 20px; color: #4a5260; font-size: 14px; }

        /* ===== MODAL ===== */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 999; backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: #ffffff; border-radius: 20px; padding: 32px;
            max-width: 500px; width: 90%; box-shadow: 0 24px 64px rgba(0,0,0,0.2);
            animation: modalIn 0.3s ease; max-height: 90vh; overflow-y: auto;
        }
        @keyframes modalIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal .modal-close { float: right; background: none; border: none; font-size: 24px; cursor: pointer; color: #4a5260; }
        .modal h2 { font-size: 22px; font-weight: 800; color: #1a1c26; margin-bottom: 8px; }
        .modal p { color: #4a5260; margin-bottom: 20px; }
        .modal .form-group { margin-bottom: 16px; }
        .modal .form-group label {
            display: block; font-weight: 600; font-size: 13px; color: #1a1c26; margin-bottom: 4px;
        }
        .modal .form-group input, .modal .form-group select, .modal .form-group textarea {
            width: 100%; padding: 10px 14px; border: 1px solid #e9edf2; border-radius: 10px;
            font-family: 'Inter', sans-serif; font-size: 14px; transition: 0.3s ease; background: #f8fafc;
        }
        .modal .form-group input:focus, .modal .form-group select:focus, .modal .form-group textarea:focus {
            outline: none; border-color: #4a5cf5; box-shadow: 0 0 0 3px rgba(74, 92, 245, 0.1); background: #ffffff;
        }
        .modal .modal-actions {
            display: flex; gap: 12px; margin-top: 20px;
        }
        .modal .modal-actions button {
            padding: 10px 24px; border-radius: 10px; font-weight: 600; font-size: 14px;
            cursor: pointer; transition: 0.3s ease; border: none; font-family: 'Inter', sans-serif;
        }
        .btn-secondary { background: #f1f5f9; color: #1a1c26; }
        .btn-secondary:hover { background: #e9edf2; }

        .toast-container {
            position: fixed; bottom: 20px; right: 20px; z-index: 9999;
        }
        .toast {
            background: #1a1c26; color: #fff; padding: 14px 24px; border-radius: 12px;
            margin-top: 10px; box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            animation: slideIn 0.4s ease; display: flex; align-items: center; gap: 12px;
            min-width: 280px;
        }
        .toast.success { border-left: 4px solid #22c55e; }
        .toast.info { border-left: 4px solid #4a5cf5; }
        .toast i { font-size: 18px; }
        .toast.success i { color: #22c55e; }
        .toast.info i { color: #4a5cf5; }
        .toast .toast-close { margin-left: auto; cursor: pointer; opacity: 0.6; transition: 0.3s ease; }
        .toast .toast-close:hover { opacity: 1; }
        @keyframes slideIn {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .card {
            background: #ffffff; border-radius: 16px; padding: 24px;
            border: 1px solid #e9edf2; box-shadow: 0 2px 12px rgba(0,0,0,0.02);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            margin-bottom: 24px;
        }
        .card:hover { border-color: #4a5cf5; box-shadow: 0 12px 32px rgba(74, 92, 245, 0.06); }
        .card-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;
        }
        .card-header h3 { font-size: 16px; font-weight: 700; color: #1a1c26; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: block; }
            .main-content { margin-left: 0; padding: 80px 16px 40px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .ticket-item { flex-direction: column; align-items: flex-start; gap: 8px; }
            .ticket-item p { max-width: 100%; }
        }
    </style>
</head>
<body>

    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="portal-wrapper">

        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                HIFI <span>Portal</span>
                <small>Client Dashboard</small>
            </div>
            <div class="nav-section">
                <div class="section-title">Main</div>
                <a href="../index.php" class="nav-item" style="margin-bottom:4px;border-bottom:1px solid #f0f3f8;">
                    <i class="fas fa-home" style="color:#4a5cf5;"></i> Home
                    <span style="margin-left:auto;font-size:11px;color:#4a5260;">← Website</span>
                </a>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard Overview
                </a>
                <a href="packages.php" class="nav-item">
                    <i class="fas fa-credit-card"></i> Service Packages
                </a>
                <a href="addons.php" class="nav-item">
                    <i class="fas fa-layers"></i> Addons & Custom Projects
                </a>
                <a href="deliverables.php" class="nav-item">
                    <i class="fas fa-check-square"></i> Deliverables Board
                </a>
                <a href="tasks.php" class="nav-item active">
                    <i class="fas fa-tasks"></i> Tasks & Support
                </a>
                <a href="billing.php" class="nav-item">
                    <i class="fas fa-file-invoice"></i> Billing Ledger
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i> Marketing Reports
                </a>
            </div>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($username); ?></h4>
                        <p>Client</p>
                    </div>
                </div>
                <a href="../logout.php" class="nav-item" style="margin-top:8px;color:#dc3545;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="main-content">

            <div class="page-header">
                <div>
                    <h2>Tasks & Support</h2>
                    <p>Manage your tasks and submit support tickets</p>
                </div>
                <button class="btn-primary" onclick="openModal('supportModal')">+ New Ticket</button>
            </div>

            <!-- ===== TASKS ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list-check" style="color:#4a5cf5;"></i> Your Tasks</h3>
                </div>
                <?php if (count($tasks) > 0): ?>
                    <?php foreach($tasks as $task): ?>
                    <div class="task-item">
                        <div class="task-checkbox <?php echo $task['status'] === 'done' ? 'done' : ''; ?>" onclick="toggleTask(this)"></div>
                        <div class="task-info">
                            <h4 class="<?php echo $task['status'] === 'done' ? 'done-text' : ''; ?>"><?php echo htmlspecialchars($task['task_title']); ?></h4>
                            <p><?php echo htmlspecialchars($task['project_name'] ?? ''); ?></p>
                        </div>
                        <span class="task-priority <?php echo $task['priority']; ?>"><?php echo ucfirst($task['priority']); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">No tasks assigned.</p>
                <?php endif; ?>
            </div>

            <!-- ===== SUPPORT TICKETS ===== -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-headset" style="color:#4a5cf5;"></i> Support Tickets</h3>
                </div>
                <?php if (count($supportTickets) > 0): ?>
                    <?php foreach($supportTickets as $ticket): ?>
                    <div class="ticket-item">
                        <div>
                            <h4><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                            <p><?php echo htmlspecialchars($ticket['message'] ?? ''); ?></p>
                        </div>
                        <span class="ticket-status <?php echo $ticket['status'] ?? 'open'; ?>"><?php echo ucfirst($ticket['status'] ?? 'Open'); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">No support tickets.</p>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- ===== SUPPORT MODAL ===== -->
    <div class="modal-overlay" id="supportModal">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('supportModal')">&times;</button>
            <h2>Submit Support Ticket</h2>
            <p>Describe your issue and we'll get back to you</p>
            <form onsubmit="handleSupportSubmit(event)">
                <div class="form-group">
                    <label>Subject *</label>
                    <input type="text" id="supportSubject" placeholder="Brief subject" required />
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select id="supportCategory">
                        <option>General Inquiry</option>
                        <option>Technical Support</option>
                        <option>Billing Question</option>
                        <option>Project Related</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Message *</label>
                    <textarea id="supportMessage" placeholder="Describe your issue..." rows="4" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('supportModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== TOAST ===== -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        function showToast(type, message) {
            const container = document.getElementById('toastContainer');
            const icons = { success: 'fa-check-circle', info: 'fa-info-circle' };
            const toast = document.createElement('div');
            toast.className = 'toast ' + type;
            toast.innerHTML = `
                <i class="fas ${icons[type] || icons.info}"></i>
                <span>${message}</span>
                <span class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></span>
            `;
            container.appendChild(toast);
            setTimeout(() => { if (toast.parentElement) toast.remove(); }, 4000);
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function toggleTask(element) {
            element.classList.toggle('done');
            const taskText = element.parentElement.querySelector('.task-info h4');
            if (taskText) { taskText.classList.toggle('done-text'); }
            showToast('success', 'Task ' + (element.classList.contains('done') ? 'completed' : 'reopened') + '!');
        }

        function handleSupportSubmit(e) {
            e.preventDefault();
            const subject = document.getElementById('supportSubject').value;
            const message = document.getElementById('supportMessage').value;
            if (!subject || !message) { showToast('info', 'Please fill in all required fields.'); return; }
            showToast('success', 'Support ticket "' + subject + '" submitted!');
            closeModal('supportModal');
            document.getElementById('supportSubject').value = '';
            document.getElementById('supportMessage').value = '';
            setTimeout(() => location.reload(), 1000);
        }

        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const toggle = document.querySelector('.sidebar-toggle');
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) sidebar.classList.remove('open');
            }
        });

        console.log('✅ Tasks & Support page loaded!');
    </script>

</body>
</html>