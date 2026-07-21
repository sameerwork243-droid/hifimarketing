<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// ===== IF ALREADY LOGGED IN =====
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// ===== HANDLE REGISTRATION =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // ===== VALIDATE INPUT =====
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // ===== CHECK IF USER EXISTS =====
        $check_query = "SELECT * FROM users WHERE email = '$email' OR username = '$username'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Username or email already exists.';
        } else {
            // ===== HASH PASSWORD =====
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // ===== INSERT USER =====
            $insert_query = "INSERT INTO users (username, email, password, role) 
                            VALUES ('$username', '$email', '$hashed_password', 'user')";
            
            if (mysqli_query($conn, $insert_query)) {
                // Auto login after registration
                $_SESSION['user_id'] = mysqli_insert_id($conn);
                $_SESSION['username'] = $username;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register | HIFI Marketing & Technologies</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/style.css" />
    <style>
        .register-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            background: #f8fafc;
        }
        .register-box {
            max-width: 440px;
            width: 100%;
            background: #ffffff;
            padding: 40px 36px;
            border-radius: 20px;
            border: 1px solid #e9edf2;
            box-shadow: 0 4px 30px rgba(0,0,0,0.05);
        }
        .register-box h1 {
            font-size: 28px;
            font-weight: 800;
            color: #1a1c26;
            margin-bottom: 4px;
            text-align: center;
        }
        .register-box .sub-text {
            text-align: center;
            color: #3d4452;
            font-size: 15px;
            margin-bottom: 28px;
        }
        .register-box label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #1a1c26;
            margin-bottom: 6px;
        }
        .register-box input[type="text"],
        .register-box input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e9edf2;
            border-radius: 10px;
            font-size: 14px;
            background: #f8fafc;
            transition: 0.3s;
            margin-bottom: 16px;
        }
        .register-box input:focus {
            border-color: #4a5cf5;
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(74, 92, 245, 0.08);
        }

        /* ===== PASSWORD WRAPPER WITH EYE ICON ===== */
        .password-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 16px;
        }

        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            width: 100%;
            padding: 12px 50px 12px 16px !important;
            border: 1px solid #e9edf2;
            border-radius: 10px;
            font-size: 14px;
            background: #f8fafc;
            transition: 0.3s;
            margin-bottom: 0 !important;
        }

        .password-wrapper input:focus {
            border-color: #4a5cf5;
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(74, 92, 245, 0.08);
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

        .register-box .btn-primary {
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
        }
        .register-box .btn-primary:hover {
            background: #3a4be0;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(74, 92, 245, 0.2);
        }
        .register-box .error-msg {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
            border-left: 4px solid #dc2626;
        }
        .register-box .success-msg {
            background: #dcfce7;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
            border-left: 4px solid #16a34a;
        }
        .register-box .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
            color: #3d4452;
        }
        .register-box .login-link a {
            color: #4a5cf5;
            font-weight: 600;
            transition: 0.2s;
        }
        .register-box .login-link a:hover {
            text-decoration: underline;
        }

        /* ===== DARK MODE ===== */
        body.dark-mode .register-section {
            background: #0b0d10;
        }
        body.dark-mode .register-box {
            background: #14191f;
            border-color: #1e242c;
        }
        body.dark-mode .register-box h1 {
            color: #eaeef2;
        }
        body.dark-mode .register-box .sub-text {
            color: #b0b8c5;
        }
        body.dark-mode .register-box label {
            color: #eaeef2;
        }
        body.dark-mode .register-box input[type="text"],
        body.dark-mode .register-box input[type="email"] {
            background: #1e242c;
            border-color: #2a3340;
            color: #eaeef2;
        }
        body.dark-mode .register-box input:focus {
            border-color: #4a5cf5;
            background: #14191f;
        }
        body.dark-mode .password-wrapper input[type="password"],
        body.dark-mode .password-wrapper input[type="text"] {
            background: #1e242c;
            border-color: #2a3340;
            color: #eaeef2;
        }
        body.dark-mode .password-wrapper input:focus {
            border-color: #4a5cf5;
            background: #14191f;
        }
        body.dark-mode .register-box .login-link {
            color: #b0b8c5;
        }
        body.dark-mode .toggle-password {
            color: #6b7a8a;
        }
        body.dark-mode .toggle-password:hover {
            color: #4a5cf5;
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="register-section">
        <div class="register-box">
            <h1>Create Account</h1>
            <p class="sub-text">Join HIFI and unlock exclusive features</p>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required />

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required />

                <!-- ===== PASSWORD FIELD WITH EYE ===== -->
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Create a password (min 6 chars)" required />
                    <button type="button" class="toggle-password" id="togglePassword1">
                        <i class="fas fa-eye" id="eyeIcon1"></i>
                    </button>
                </div>

                <!-- ===== CONFIRM PASSWORD FIELD WITH EYE ===== -->
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required />
                    <button type="button" class="toggle-password" id="togglePassword2">
                        <i class="fas fa-eye" id="eyeIcon2"></i>
                    </button>
                </div>

                <button type="submit" class="btn-primary"><i class="fas fa-user-plus"></i> Create Account</button>
            </form>

            <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- ===== EYE ICON JAVASCRIPT ===== -->
    <script>
        // ===== TOGGLE PASSWORD VISIBILITY - PASSWORD FIELD =====
        const passwordInput = document.getElementById('password');
        const toggleBtn1 = document.getElementById('togglePassword1');
        const eyeIcon1 = document.getElementById('eyeIcon1');

        toggleBtn1.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon1.classList.remove('fa-eye');
                eyeIcon1.classList.add('fa-eye-slash');
                eyeIcon1.style.color = '#4a5cf5';
            } else {
                passwordInput.type = 'password';
                eyeIcon1.classList.remove('fa-eye-slash');
                eyeIcon1.classList.add('fa-eye');
                eyeIcon1.style.color = '#8a94a0';
            }
        });

        // ===== TOGGLE PASSWORD VISIBILITY - CONFIRM PASSWORD FIELD =====
        const confirmPasswordInput = document.getElementById('confirm_password');
        const toggleBtn2 = document.getElementById('togglePassword2');
        const eyeIcon2 = document.getElementById('eyeIcon2');

        toggleBtn2.addEventListener('click', function() {
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                eyeIcon2.classList.remove('fa-eye');
                eyeIcon2.classList.add('fa-eye-slash');
                eyeIcon2.style.color = '#4a5cf5';
            } else {
                confirmPasswordInput.type = 'password';
                eyeIcon2.classList.remove('fa-eye-slash');
                eyeIcon2.classList.add('fa-eye');
                eyeIcon2.style.color = '#8a94a0';
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