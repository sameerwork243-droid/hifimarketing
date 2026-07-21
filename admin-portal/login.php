<?php
// login.php - Login Page

// ===== Start session if not already started =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== Include config =====
require_once __DIR__ . '/../includes/config.php';

// ===== LOGIN HANDLING =====
$login_error = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Query from users table
    $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Check password (plain text or hashed)
        if ($password === $row['password'] || password_verify($password, $row['password'])) {
            // Set session
            $_SESSION['user_id'] = $row['id'];
            
            // Get client name if exists
            $client_name = $row['name'] ?? $row['username'];
            $client_sql = "SELECT name FROM clients WHERE user_id = ?";
            $client_stmt = mysqli_prepare($conn, $client_sql);
            mysqli_stmt_bind_param($client_stmt, "i", $row['id']);
            mysqli_stmt_execute($client_stmt);
            $client_result = mysqli_stmt_get_result($client_stmt);
            if ($client_row = mysqli_fetch_assoc($client_result)) {
                $client_name = $client_row['name'];
            }
            
            $_SESSION['user'] = [
                'id' => $row['id'],
                'name' => $client_name,
                'email' => $row['email'],
                'role' => $row['role'],
                'avatar' => $row['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'
            ];
            $_SESSION['portal_role'] = $row['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['last_activity'] = time();
            
            // Log login
            $log_sql = "INSERT INTO activity_logs (user_id, action, ip_address, created_at) VALUES (?, 'login', ?, NOW())";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "is", $row['id'], $_SERVER['REMOTE_ADDR']);
            mysqli_stmt_execute($log_stmt);
            
            // Redirect based on role
            if ($row['role'] === 'admin' || $row['role'] === 'pm') {
                header('Location: pm-portal.php');
            } else {
                header('Location: client-portal.php');
            }
            exit();
        } else {
            $login_error = "Invalid password!";
        }
    } else {
        $login_error = "Email not found! Please register.";
    }
}

// ===== HANDLE LOGOUT =====
if (isset($_GET['logout'])) {
    // Destroy session properly
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    header('Location: login.php');
    exit();
}

// ===== CHECK IF ALREADY LOGGED IN =====
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $role = $_SESSION['portal_role'] ?? 'client';
    if ($role === 'admin' || $role === 'pm') {
        header('Location: pm-portal.php');
    } else {
        header('Location: client-portal.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>HIFI Marketing - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="../images/fav-icon.png" type="image/png" />
    
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
        body { 
            background: #0f172a; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
        }
        
        .login-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        .login-card:hover {
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 12px 60px rgba(0,0,0,0.4);
        }
        
        .input-field {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }
        .input-field::placeholder { color: rgba(255,255,255,0.4); }
        .input-field:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.2);
            background: rgba(255,255,255,0.12);
        }

        /* ===== PASSWORD WRAPPER WITH EYE ICON ===== */
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            width: 100%;
            padding: 12px 50px 12px 16px !important;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }

        .password-wrapper input::placeholder {
            color: rgba(255,255,255,0.4);
        }

        .password-wrapper input:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.2);
            background: rgba(255,255,255,0.12);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.4);
            font-size: 18px;
            cursor: pointer;
            padding: 4px 6px;
            transition: 0.3s;
            z-index: 2;
        }

        .toggle-password:hover {
            color: #818cf8;
            transform: translateY(-50%) scale(1.1);
        }

        .toggle-password:active {
            transform: translateY(-50%) scale(0.9);
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(99,102,241,0.4);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        
        .demo-credentials {
            font-size: 11px;
            color: rgba(255,255,255,0.3);
            text-align: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .demo-credentials span {
            display: inline-block;
            margin: 0 6px;
        }
        
        @media (max-width: 480px) {
            .login-card { padding: 20px; border-radius: 16px; }
            .input-field { padding: 10px 14px; font-size: 13px; }
            .password-wrapper input[type="password"],
            .password-wrapper input[type="text"] { padding: 10px 50px 10px 14px !important; font-size: 13px; }
            .btn-primary { padding: 12px; font-size: 14px; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- Logo -->
    <div class="text-center mb-6">
        <div class="h-16 w-16 bg-gradient-to-tr from-indigo-500 to-indigo-700 rounded-2xl flex items-center justify-center text-white shadow-2xl font-bold text-3xl mx-auto">H</div>
        <h1 class="text-2xl font-black text-white mt-3 tracking-tight">HIFI Marketing</h1>
        <p class="text-slate-400 text-sm">Client Portal</p>
    </div>
    
    <!-- Login Card -->
    <div class="login-card">
        <h2 class="text-xl font-bold text-white mb-1">Welcome Back</h2>
        <p class="text-slate-400 text-sm mb-5">Sign in to access your dashboard</p>
        
        <?php if ($login_error): ?>
            <div class="alert-error"><?php echo $login_error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-slate-300 text-sm font-semibold mb-1.5">Email Address</label>
                <input type="email" name="email" required placeholder="Enter your email" class="input-field" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div>
                <label class="block text-slate-300 text-sm font-semibold mb-1.5">Password</label>
                <!-- ===== PASSWORD FIELD WITH EYE ICON ===== -->
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required placeholder="••••••••" />
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            <button type="submit" name="login" class="btn-primary">
                <i data-lucide="log-in" class="w-4 h-4 inline mr-2"></i> Sign In
            </button>
        </form>
        
        <div class="demo-credentials">
            <span><strong>Demo:</strong></span>
            <span>client@hifi.com / client123</span>
            <span>•</span>
            <span>pm@hifi.com / pm123</span>
        </div>
    </div>
</div>

<!-- ===== LUCIDE + EYE ICON JAVASCRIPT ===== -->
<script>
    // ===== LUCIDE ICONS =====
    lucide.createIcons();

    // ===== TOGGLE PASSWORD VISIBILITY =====
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');

    toggleBtn.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            // Change icon to eye-off
            eyeIcon.setAttribute('data-lucide', 'eye-off');
            lucide.createIcons();
            eyeIcon.style.color = '#818cf8';
        } else {
            passwordInput.type = 'password';
            // Change icon back to eye
            eyeIcon.setAttribute('data-lucide', 'eye');
            lucide.createIcons();
            eyeIcon.style.color = 'rgba(255,255,255,0.4)';
        }
    });
</script>

</body>
</html>