<?php
// login.php - Login Page with PM, Admin & Super Admin Support

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
        $password_valid = false;
        
        // Check if password is hashed (starts with $2y$)
        if (strlen($row['password']) >= 60 && substr($row['password'], 0, 4) === '$2y$') {
            $password_valid = password_verify($password, $row['password']);
        } else {
            // Plain text password comparison
            $password_valid = ($password === $row['password']);
        }
        
        if ($password_valid) {
            
            // ===== DETERMINE ROLE =====
            // Check both 'role' and 'user_role' columns
            $role = $row['role'] ?? 'client';
            $user_role = $row['user_role'] ?? 'client';
            
            // Determine final role - check for super_admin first
            $final_role = 'client'; // Default
            
            // IMPORTANT: Check super_admin first
            if ($role === 'super_admin' || $user_role === 'super_admin') {
                $final_role = 'super_admin';
            } elseif ($role === 'admin' || $user_role === 'admin') {
                $final_role = 'admin';
            } elseif ($role === 'pm' || $user_role === 'pm') {
                $final_role = 'pm';
            } elseif ($role === 'user') {
                // If role is 'user', treat as client
                $final_role = 'client';
            } else {
                $final_role = 'client';
            }
            
            // Set session
            $_SESSION['user_id'] = $row['id'];
            
            // Get client name if exists
            $client_name = $row['username'] ?? $row['name'] ?? 'User';
            
            // For Super Admin, use a proper name
            if ($final_role === 'super_admin' && $client_name === 'Super Admin') {
                $client_name = 'Super Admin';
            }
            
            $_SESSION['user'] = [
                'id' => $row['id'],
                'name' => $client_name,
                'email' => $row['email'],
                'role' => $final_role,
                'avatar' => $row['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'
            ];
            $_SESSION['portal_role'] = $final_role;
            $_SESSION['user_role'] = $final_role;
            $_SESSION['logged_in'] = true;
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['last_activity'] = time();
            
            // ===== LOG LOGIN (With Error Handling) =====
            try {
                // Check if activity_logs table exists
                $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'activity_logs'");
                if (mysqli_num_rows($check_table) > 0) {
                    $log_sql = "INSERT INTO activity_logs (user_id, action, ip_address, created_at) VALUES (?, 'login', ?, NOW())";
                    $log_stmt = mysqli_prepare($conn, $log_sql);
                    if ($log_stmt) {
                        mysqli_stmt_bind_param($log_stmt, "is", $row['id'], $_SERVER['REMOTE_ADDR']);
                        mysqli_stmt_execute($log_stmt);
                        mysqli_stmt_close($log_stmt);
                    }
                }
            } catch (Exception $e) {
                // Silently fail - don't stop login
                error_log("Activity log error: " . $e->getMessage());
            }
            
            // ===== ROLE-BASED REDIRECTION =====
            // Super Admin - Highest Priority
            if ($final_role === 'super_admin') {
                header('Location: ../admin-portal/index.php');
                exit();
            }
            // Admin
            elseif ($final_role === 'admin') {
                header('Location: ../admin-portal/index.php');
                exit();
            }
            // PM (Project Manager)
            elseif ($final_role === 'pm') {
                header('Location: ../pm-portal/index.php');
                exit();
            }
            // Client (default)
            else {
                header('Location: client-portal.php');
                exit();
            }
        } else {
            $login_error = "Invalid password!";
        }
    } else {
        $login_error = "Email not found! Please register or contact support.";
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
    header('Location: login.php?logout_success=1');
    exit();
}

// ===== CHECK IF ALREADY LOGGED IN =====
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $role = $_SESSION['portal_role'] ?? 'client';
    
    // Redirect based on role
    if ($role === 'super_admin' || $role === 'admin') {
        header('Location: ../admin-portal/index.php');
        exit();
    } elseif ($role === 'pm') {
        header('Location: ../pm-portal/index.php');
        exit();
    } else {
        header('Location: client-portal.php');
        exit();
    }
}

// ===== SHOW LOGOUT SUCCESS MESSAGE =====
if (isset($_GET['logout_success'])) {
    $login_error = "You have been logged out successfully.";
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
        
        .alert-success {
            background: rgba(52, 211, 153, 0.15);
            border: 1px solid rgba(52, 211, 153, 0.2);
            color: #34d399;
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
        
        .role-badge-demo {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        .role-badge-demo .badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge.super-admin {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        .badge.admin {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        .badge.pm {
            background: rgba(96, 165, 250, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(96, 165, 250, 0.3);
        }
        .badge.client {
            background: rgba(52, 211, 153, 0.2);
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.3);
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
        <p class="text-slate-400 text-sm">
    </div>
    
    <!-- Login Card -->
    <div class="login-card">
        <h2 class="text-xl font-bold text-white mb-1">Welcome Back</h2>
        <p class="text-slate-400 text-sm mb-5">Sign in to access your dashboard</p>
        
        <?php if ($login_error): ?>
            <div class="alert-<?php echo strpos($login_error, 'logged out') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-slate-300 text-sm font-semibold mb-1.5">Email Address</label>
                <input type="email" name="email" required placeholder="Enter your email" class="input-field" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div>
                <label class="block text-slate-300 text-sm font-semibold mb-1.5">Password</label>
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
        
        
        
      
<script>
    lucide.createIcons();

    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');

    toggleBtn.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.setAttribute('data-lucide', 'eye-off');
            lucide.createIcons();
            eyeIcon.style.color = '#818cf8';
        } else {
            passwordInput.type = 'password';
            eyeIcon.setAttribute('data-lucide', 'eye');
            lucide.createIcons();
            eyeIcon.style.color = 'rgba(255,255,255,0.4)';
        }
    });
</script>

</body>
</html>