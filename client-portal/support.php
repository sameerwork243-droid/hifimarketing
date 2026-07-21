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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HIFI | Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        /* Same styles as projects.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #1a1c26; line-height: 1.6; }
        a { text-decoration: none; color: inherit; }
        .portal-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #ffffff; border-right: 1px solid #e9edf2; padding: 24px 16px; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 99; transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .sidebar .sidebar-logo { font-size: 22px; font-weight: 900; color: #1a1c26; padding: 0 8px 24px; border-bottom: 1px solid #e9edf2; margin-bottom: 20px; }
        .sidebar .sidebar-logo span { color: #4a5cf5; }
        .sidebar .sidebar-logo small { display: block; font-size: 12px; font-weight: 400; color: #4a5260; }
        .sidebar .nav-section { margin-bottom: 24px; }
        .sidebar .nav-section .section-title { font-size: 11px; font-weight: 700; color: #4a5260; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 12px; margin-bottom: 8px; }
        .sidebar .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px; color: #4a5260; font-weight: 500; font-size: 14px; transition: all 0.3s ease; cursor: pointer; }
        .sidebar .nav-item:hover { background: #f0f3f8; color: #1a1c26; }
        .sidebar .nav-item.active { background: #4a5cf5; color: #ffffff; box-shadow: 0 4px 16px rgba(74, 92, 245, 0.2); }
        .sidebar .nav-item i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar .nav-item .badge { margin-left: auto; background: #e9edf2; color: #4a5260; font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 20px; }
        .sidebar .nav-item.active .badge { background: rgba(255,255,255,0.2); color: #ffffff; }
        .sidebar .sidebar-footer { border-top: 1px solid #e9edf2; padding-top: 16px; margin-top: 16px; }
        .sidebar .sidebar-footer .user-info { display: flex; align-items: center; gap: 12px; padding: 8px 12px; }
        .sidebar .sidebar-footer .user-info .avatar { width: 40px; height: 40px; border-radius: 50%; background: #4a5cf5; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; }
        .sidebar .sidebar-footer .user-info .user-details h4 { font-size: 14px; font-weight: 600; color: #1a1c26; }
        .sidebar .sidebar-footer .user-info .user-details p { font-size: 12px; color: #4a5260; }
        .sidebar-toggle { display: none; position: fixed; top: 16px; left: 16px; z-index: 100; background: #ffffff; border: 1px solid #e9edf2; border-radius: 10px; padding: 10px 12px; cursor: pointer; font-size: 20px; color: #1a1c26; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
        
        .main-content { margin-left: 260px; flex: 1; padding: 24px 32px 60px; background: #f5f7fa; min-height: 100vh; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; background: #ffffff; padding: 16px 24px; border-radius: 16px; border: 1px solid #e9edf2; }
        .page-header h1 { font-size: 22px; font-weight: 800; color: #1a1c26; }
        .page-header h1 span { background: linear-gradient(135deg, #4a5cf5, #6c7aff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .page-header p { color: #4a5260; font-size: 14px; }
        .page-header .header-right { display: flex; align-items: center; gap: 16px; }
        .page-header .header-right .welcome-badge { background: linear-gradient(135deg, #4a5cf5, #6c7aff); color: #fff; padding: 6px 16px; border-radius: 40px; font-weight: 600; font-size: 13px; box-shadow: 0 4px 16px rgba(74, 92, 245, 0.2); }
        .page-header .header-right .theme-toggle { position: relative; width: 50px; height: 28px; background: #f0f3f8; border-radius: 50px; border: 2px solid #e9edf2; cursor: pointer; display: flex; align-items: center; padding: 3px; transition: 0.4s ease; flex-shrink: 0; box-shadow: inset 0 2px 6px rgba(0,0,0,0.05); }
        .page-header .header-right .theme-toggle:hover { border-color: #4a5cf5; box-shadow: 0 0 20px rgba(74, 92, 245, 0.15); }
        .page-header .header-right .theme-toggle .toggle-track { position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 50px; background: #e9edf2; transition: 0.4s ease; }
        body.dark-mode .page-header .header-right .theme-toggle .toggle-track { background: #1e242c; }
        .page-header .header-right .theme-toggle .toggle-thumb { position: relative; width: 20px; height: 20px; border-radius: 50%; background: #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; transition: 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); z-index: 2; }
        body.dark-mode .page-header .header-right .theme-toggle .toggle-thumb { transform: translateX(22px); background: #4a5cf5; }
        .page-header .header-right .theme-toggle .toggle-thumb i { font-size: 12px; color: #4a5cf5; transition: 0.4s ease; }
        body.dark-mode .page-header .header-right .theme-toggle .toggle-thumb i { color: #ffffff; }
        .page-header .header-right .theme-toggle .toggle-icons { position: absolute; width: 100%; height: 100%; display: flex; justify-content: space-between; align-items: center; padding: 0 8px; z-index: 1; pointer-events: none; }
        .page-header .header-right .theme-toggle .toggle-icons .icon-sun { font-size: 11px; color: #f59e0b; opacity: 1; transition: 0.4s ease; }
        .page-header .header-right .theme-toggle .toggle-icons .icon-moon { font-size: 11px; color: #6b7280; opacity: 0.5; transition: 0.4s ease; }
        body.dark-mode .page-header .header-right .theme-toggle .toggle-icons .icon-sun { opacity: 0.5; }
        body.dark-mode .page-header .header-right .theme-toggle .toggle-icons .icon-moon { opacity: 1; color: #fbbf24; }
        .page-header .header-right .btn-logout { background: transparent; color: #dc3545; padding: 6px 16px; border-radius: 40px; font-weight: 600; font-size: 13px; transition: 0.2s; border: 1px solid #dc3545; cursor: pointer; }
        .page-header .header-right .btn-logout:hover { background: #dc3545; color: #fff; }

        .card { background: #ffffff; border-radius: 16px; padding: 24px; border: 1px solid #e9edf2; box-shadow: 0 2px 12px rgba(0,0,0,0.02); transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94); margin-bottom: 24px; }
        .card:hover { border-color: #4a5cf5; box-shadow: 0 12px 32px rgba(74, 92, 245, 0.06); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .card-header h3 { font-size: 16px; font-weight: 700; color: #1a1c26; }

        .support-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .support-card { background: #f8fafc; border-radius: 16px; padding: 30px 20px; text-align: center; border: 1px solid #e9edf2; transition: all 0.3s ease; cursor: pointer; }
        .support-card:hover { border-color: #4a5cf5; transform: translateY(-4px); box-shadow: 0 12px 32px rgba(74, 92, 245, 0.06); }
        .support-card i { font-size: 40px; color: #4a5cf5; margin-bottom: 12px; }
        .support-card h4 { font-size: 18px; font-weight: 700; color: #1a1c26; }
        .support-card p { font-size: 13px; color: #4a5260; }

        .contact-form .form-group { margin-bottom: 16px; }
        .contact-form .form-group label { display: block; font-weight: 600; font-size: 13px; color: #1a1c26; margin-bottom: 4px; }
        .contact-form .form-group input, .contact-form .form-group textarea, .contact-form .form-group select {
            width: 100%; padding: 10px 14px; border: 1px solid #e9edf2; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; transition: 0.3s ease; background: #ffffff;
        }
        .contact-form .form-group input:focus, .contact-form .form-group textarea:focus, .contact-form .form-group select:focus {
            outline: none; border-color: #4a5cf5; box-shadow: 0 0 0 3px rgba(74, 92, 245, 0.1);
        }
        .contact-form .btn-submit { background: #4a5cf5; color: #fff; padding: 12px 32px; border-radius: 40px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; transition: 0.3s ease; }
        .contact-form .btn-submit:hover { background: #3a4be0; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(74, 92, 245, 0.2); }

        .empty-state { text-align: center; padding: 40px 20px; color: #4a5260; }
        .empty-state i { font-size: 48px; color: #e9edf2; margin-bottom: 16px; }

        body.dark-mode { background: #0b0d10; color: #eaeef2; }
        body.dark-mode .sidebar { background: #0b0d10; border-right-color: #1e242c; }
        body.dark-mode .sidebar .sidebar-logo { color: #eaeef2; border-bottom-color: #1e242c; }
        body.dark-mode .sidebar .nav-item { color: #b0b8c5; }
        body.dark-mode .sidebar .nav-item:hover { background: #14191f; color: #eaeef2; }
        body.dark-mode .sidebar .nav-item.active { background: #4a5cf5; color: #ffffff; }
        body.dark-mode .sidebar .sidebar-footer { border-top-color: #1e242c; }
        body.dark-mode .sidebar .sidebar-footer .user-info .user-details h4 { color: #eaeef2; }
        body.dark-mode .sidebar .sidebar-footer .user-info .user-details p { color: #b0b8c5; }
        body.dark-mode .main-content { background: #0b0d10; }
        body.dark-mode .page-header { background: #14191f; border-color: #1e242c; }
        body.dark-mode .page-header h1 { color: #eaeef2; }
        body.dark-mode .page-header p { color: #b0b8c5; }
        body.dark-mode .card { background: #14191f; border-color: #1e242c; }
        body.dark-mode .card-header h3 { color: #eaeef2; }
        body.dark-mode .support-card { background: #0b0d10; border-color: #1e242c; }
        body.dark-mode .support-card h4 { color: #eaeef2; }
        body.dark-mode .support-card p { color: #b0b8c5; }
        body.dark-mode .contact-form .form-group input, 
        body.dark-mode .contact-form .form-group textarea,
        body.dark-mode .contact-form .form-group select { background: #0b0d10; border-color: #1e242c; color: #eaeef2; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: block; }
            .main-content { margin-left: 0; padding: 80px 16px 40px; }
            .page-header { flex-direction: column; align-items: flex-start; padding: 14px 16px; }
            .page-header h1 { font-size: 18px; }
            .page-header .header-right { width: 100%; justify-content: flex-end; }
            .support-options { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

    <div class="portal-wrapper">

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">HIFI <span>Portal</span><small>Client Dashboard</small></div>
            <div class="nav-section">
                <div class="section-title">Main</div>
                <a href="index.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
                <a href="projects.php" class="nav-item"><i class="fas fa-project-diagram"></i> Projects</a>
                <a href="tasks.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
                <a href="invoices.php" class="nav-item"><i class="fas fa-file-invoice"></i> Invoices</a>
            </div>
            <div class="nav-section">
                <div class="section-title">Resources</div>
                <a href="documents.php" class="nav-item"><i class="fas fa-folder-open"></i> Documents</a>
                <a href="support.php" class="nav-item active"><i class="fas fa-headset"></i> Support</a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
            </div>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($username); ?></h4>
                        <p>Client</p>
                    </div>
                </div>
                <a href="../logout.php" class="nav-item" style="margin-top:8px;color:#dc3545;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <main class="main-content">

            <div class="page-header">
                <div>
                    <h1>Support <span>Center</span></h1>
                    <p>How can we help you today?</p>
                </div>
                <div class="header-right">
                    <div class="welcome-badge"><i class="fas fa-user"></i> <?php echo htmlspecialchars($username); ?></div>
                    <div class="theme-toggle" onclick="toggleTheme()">
                        <div class="toggle-track"></div>
                        <div class="toggle-icons">
                            <span class="icon-sun"><i class="fas fa-sun"></i></span>
                            <span class="icon-moon"><i class="fas fa-moon"></i></span>
                        </div>
                        <div class="toggle-thumb"><i class="fas fa-adjust"></i></div>
                    </div>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>

            <!-- Support Options -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-life-ring" style="color:#4a5cf5;margin-right:8px;"></i> How can we help?</h3>
                </div>
                <div class="support-options">
                    <div class="support-card" onclick="alert('📚 Knowledge base loading...')">
                        <i class="fas fa-book"></i>
                        <h4>Knowledge Base</h4>
                        <p>Browse articles and guides</p>
                    </div>
                    <div class="support-card" onclick="alert('📧 Opening email support...')">
                        <i class="fas fa-envelope"></i>
                        <h4>Email Support</h4>
                        <p>support@hifiwebsite.com</p>
                    </div>
                    <div class="support-card" onclick="alert('💬 Live chat loading...')">
                        <i class="fas fa-comment-dots"></i>
                        <h4>Live Chat</h4>
                        <p>Chat with our team</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-paper-plane" style="color:#4a5cf5;margin-right:8px;"></i> Send a Message</h3>
                </div>
                <form class="contact-form" onsubmit="event.preventDefault(); alert('✅ Your message has been sent! We will get back to you soon.');">
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" placeholder="Enter subject" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select>
                            <option>General Inquiry</option>
                            <option>Technical Support</option>
                            <option>Billing Question</option>
                            <option>Project Related</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea rows="4" placeholder="Describe your issue..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send Message</button>
                </form>
            </div>

        </main>
    </div>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');
        });
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const toggle = document.querySelector('.sidebar-toggle');
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) sidebar.classList.remove('open');
            }
        });
    </script>

</body>
</html>