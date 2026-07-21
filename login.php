<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// ===== IF ALREADY LOGGED IN =====
if (isLoggedIn()) {
    // Redirect to dashboard based on role
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// ===== HANDLE LOGIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $query = "SELECT * FROM users WHERE email = '$email' OR username = '$email'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            
            // ===== CHECK PASSWORD (SUPPORTS BOTH HASHED & PLAIN TEXT) =====
            $password_valid = false;
            
            // 1. Check if password is hashed (using password_verify)
            if (password_verify($password, $user['password'])) {
                $password_valid = true;
            }
            // 2. Check if password is plain text (direct comparison)
            elseif ($password === $user['password']) {
                $password_valid = true;
                
                // OPTIONAL: Upgrade plain text password to hash for security
                // $new_hash = password_hash($password, PASSWORD_DEFAULT);
                // mysqli_query($conn, "UPDATE users SET password = '$new_hash' WHERE id = {$user['id']}");
            }
            
            if ($password_valid) {
                // ===== SET SESSION =====
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['created_at'] = $user['created_at'] ?? date('Y-m-d H:i:s');
                $_SESSION['login_time'] = time();
                
                // ===== REMEMBER ME (Cookie) =====
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
                    // Store token in database (optional)
                }
                
                // ===== REDIRECT LOGIC =====
                $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
                
                // Check session redirect first
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                }
                
                if (!empty($redirect)) {
                    header('Location: ' . $redirect);
                } else {
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: user/dashboard.php');
                    }
                }
                exit();
            } else {
                $error = 'Invalid email/username or password.';
            }
        } else {
            $error = 'Invalid email/username or password.';
        }
    }
}

// ===== CHECK FOR REDIRECT PARAMETER =====
$redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | HIFI Marketing & Technologies</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/style.css" />
    <style>
        .login-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            background: #f8fafc;
        }
        .login-box {
            max-width: 420px;
            width: 100%;
            background: #ffffff;
            padding: 40px 36px;
            border-radius: 20px;
            border: 1px solid #e9edf2;
            box-shadow: 0 4px 30px rgba(0,0,0,0.05);
        }
        .login-box .logo-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-box .logo-icon i {
            font-size: 48px;
            color: #4a5cf5;
            background: #e8edfe;
            padding: 16px;
            border-radius: 50%;
        }
        .login-box h1 {
            font-size: 28px;
            font-weight: 800;
            color: #1a1c26;
            margin-bottom: 4px;
            text-align: center;
        }
        .login-box .sub-text {
            text-align: center;
            color: #3d4452;
            font-size: 15px;
            margin-bottom: 28px;
        }
        .login-box label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #1a1c26;
            margin-bottom: 6px;
        }
        .login-box label .required {
            color: #dc3545;
        }
        
        /* ===== PASSWORD FIELD WITH EYE ICON ===== */
        .password-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 16px;
        }
        
        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            width: 100%;
            padding: 12px 50px 12px 16px !important;
            border: 2px solid #e9edf2;
            border-radius: 10px;
            font-size: 14px;
            background: #f8fafc;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
            margin-bottom: 0 !important;
        }
        
        .password-wrapper input:focus {
            border-color: #4a5cf5;
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(74,92,245,0.08);
        }
        
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #8a94a0;
            font-size: 18px;
            cursor: pointer;
            padding: 4px 6px;
            transition: 0.3s;
            z-index: 2;
        }
        
        .toggle-password:hover {
            color: #4a5cf5;
            transform: translateY(-50%) scale(1.1);
        }
        
        .toggle-password:active {
            transform: translateY(-50%) scale(0.9);
        }
        
        .login-box input[type="text"],
        .login-box input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9edf2;
            border-radius: 10px;
            font-size: 14px;
            background: #f8fafc;
            transition: 0.3s;
            margin-bottom: 16px;
            font-family: 'Inter', sans-serif;
        }
        
        .login-box input:focus {
            border-color: #4a5cf5;
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(74,92,245,0.08);
        }
        
        .login-box .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #4a5260;
        }
        .login-box .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #4a5cf5;
            cursor: pointer;
        }
        .login-box .btn-primary {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            background: #4a5cf5;
            color: #fff;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 20px rgba(74,92,245,0.2);
        }
        .login-box .btn-primary:hover {
            background: #3a4be0;
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(74,92,245,0.3);
        }
        .login-box .error-msg {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
            border-left: 4px solid #dc2626;
        }
        .login-box .success-msg {
            background: #dcfce7;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
            border-left: 4px solid #16a34a;
        }
        .login-box .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #3d4452;
            padding-top: 16px;
            border-top: 1px solid #e9edf2;
        }
        .login-box .register-link a {
            color: #4a5cf5;
            font-weight: 600;
            transition: 0.2s;
        }
        .login-box .register-link a:hover {
            text-decoration: underline;
        }
        .login-box .forgot-link {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 16px;
        }
        .login-box .forgot-link a {
            font-size: 13px;
            color: #8a94a0;
            transition: 0.2s;
        }
        .login-box .forgot-link a:hover {
            color: #4a5cf5;
        }
        .login-box .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        .login-box .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9edf2;
        }
        .login-box .divider span {
            background: #ffffff;
            padding: 0 16px;
            position: relative;
            color: #8a94a0;
            font-size: 13px;
        }
        .login-box .social-login {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .login-box .social-login .social-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 1px solid #e9edf2;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
            font-size: 18px;
            color: #4a5260;
        }
        .login-box .social-login .social-btn:hover {
            border-color: #4a5cf5;
            color: #4a5cf5;
            box-shadow: 0 4px 12px rgba(74,92,245,0.1);
        }

        body.dark-mode .login-section {
            background: #0b0d10;
        }
        body.dark-mode .login-box {
            background: #14191f;
            border-color: #1e242c;
        }
        body.dark-mode .login-box .logo-icon i {
            background: #1e242c;
        }
        body.dark-mode .login-box h1 {
            color: #eaeef2;
        }
        body.dark-mode .login-box .sub-text {
            color: #b0b8c5;
        }
        body.dark-mode .login-box label {
            color: #eaeef2;
        }
        body.dark-mode .password-wrapper input[type="password"],
        body.dark-mode .password-wrapper input[type="text"] {
            background: #0b0d10;
            border-color: #1e242c;
            color: #eaeef2;
        }
        body.dark-mode .password-wrapper input:focus {
            border-color: #4a5cf5;
            background: #14191f;
        }
        body.dark-mode .login-box input[type="text"],
        body.dark-mode .login-box input[type="email"] {
            background: #0b0d10;
            border-color: #1e242c;
            color: #eaeef2;
        }
        body.dark-mode .login-box input:focus {
            border-color: #4a5cf5;
            background: #14191f;
        }
        body.dark-mode .login-box .register-link {
            border-color: #1e242c;
            color: #b0b8c5;
        }
        body.dark-mode .login-box .divider::before {
            background: #1e242c;
        }
        body.dark-mode .login-box .divider span {
            background: #14191f;
            color: #6b7a8a;
        }
        body.dark-mode .login-box .social-login .social-btn {
            background: #0b0d10;
            border-color: #1e242c;
            color: #b0b8c5;
        }
        body.dark-mode .login-box .social-login .social-btn:hover {
            border-color: #4a5cf5;
            color: #6c7aff;
        }
        body.dark-mode .login-box .remember-me {
            color: #b0b8c5;
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 28px 20px;
            }
            .login-box h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="login-section">
        <div class="login-box">
            <div class="logo-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            
            <h1>Welcome Back</h1>
            <p class="sub-text">Login to access your dashboard and apply for jobs</p>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($redirect_url)): ?>
                <div style="background:#e8edfe;padding:10px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;color:#4a5cf5;">
                    <i class="fas fa-info-circle"></i> Please login to continue
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="email">Email or Username <span class="required">*</span></label>
                <input type="text" id="email" name="email" placeholder="Enter your email or username" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />

                <label for="password">Password <span class="required">*</span></label>
                
                <!-- ===== PASSWORD FIELD WITH EYE ICON ===== -->
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required />
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>

                <div class="forgot-link">
                    <a href="forgot-password.php">Forgot password?</a>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember" />
                    <label for="remember" style="display:inline;font-weight:400;cursor:pointer;">Remember me</label>
                </div>

                <button type="submit" class="btn-primary"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>

            <div class="divider">
                <span>or continue with</span>
            </div>

            <div class="social-login">
                <button class="social-btn" onclick="alert('Google login coming soon!')">
                    <i class="fab fa-google"></i>
                </button>
                <button class="social-btn" onclick="alert('Facebook login coming soon!')">
                    <i class="fab fa-facebook-f"></i>
                </button>
                <button class="social-btn" onclick="alert('LinkedIn login coming soon!')">
                    <i class="fab fa-linkedin-in"></i>
                </button>
            </div>

            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- ===== EYE ICON JAVASCRIPT ===== -->
    <script>
        // ===== TOGGLE PASSWORD VISIBILITY =====
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
                eyeIcon.style.color = '#4a5cf5';
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
                eyeIcon.style.color = '#8a94a0';
            }
        });

        // ===== THEME TOGGLE =====
        function toggleTheme() {
            const body = document.body;
            body.classList.toggle('dark-mode');
            localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
            }
        });
    </script>

</body>
</html>