<?php
// =============================================
// HEADER.PHP - COMPLETE WORKING VERSION
// =============================================

// ===== START SESSION IF NOT STARTED =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== SET DEFAULT PAGE TITLE =====
$page_title = $page_title ?? 'HIFI Marketing & Technologies';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <!-- ============================================= -->
    <!-- ===== PAGE TITLE ===== -->
    <!-- ============================================= -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- ============================================= -->
    <!-- ===== FAVICON - BROWSER TAB ICON ===== -->
    <!-- ============================================= -->
    <link rel="icon" href="/HifiWebsite/images/fav-icon.png" type="image/png" />
    <link rel="shortcut icon" href="/HifiWebsite/images/fav-icon.png" type="image/png" />
    <link rel="apple-touch-icon" href="/HifiWebsite/images/fav-icon.png" />
    
    <!-- ============================================= -->
    <!-- ===== FONTS & ICONS ===== -->
    <!-- ============================================= -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="/HifiWebsite/css/style.css" />
    
    <!-- ============================================= -->
    <!-- ===== EXTRA CSS (PAGE SPECIFIC) ===== -->
    <!-- ============================================= -->
    <?php if (isset($extra_css)): ?>
        <link rel="stylesheet" href="<?php echo $extra_css; ?>" />
    <?php endif; ?>
</head>
<body>

    <!-- ============================================= -->
    <!-- ===== HEADER / NAVIGATION ===== -->
    <!-- ============================================= -->
    <header>
        <div class="container header-inner">
            
            <!-- ===== LOGO ===== -->
            <div class="logo">
                <a href="/HifiWebsite/index.php">
                    HIFI <span>Marketing & Technologies</span>
                </a>
            </div>
            
           <!-- ===== NAVIGATION MENU - Add Pricing ===== -->
<nav>
    <ul id="navMenu">
        <li><a href="/HifiWebsite/index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
        <li><a href="/HifiWebsite/services.php" <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'class="active"' : ''; ?>>Services</a></li>
        <li><a href="/HifiWebsite/about.php" <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'class="active"' : ''; ?>>About</a></li>
        <li><a href="/HifiWebsite/team.php" <?php echo basename($_SERVER['PHP_SELF']) == 'team.php' ? 'class="active"' : ''; ?>>Team</a></li>
        <li><a href="/HifiWebsite/careers.php" <?php echo basename($_SERVER['PHP_SELF']) == 'careers.php' ? 'class="active"' : ''; ?>>Careers</a></li>
        <!-- ===== PRICING PAGE LINK ===== -->
        <li><a href="/HifiWebsite/pricing.php" <?php echo basename($_SERVER['PHP_SELF']) == 'pricing.php' ? 'class="active"' : ''; ?>>Pricing</a></li>
        <li><a href="/HifiWebsite/contact.php" <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'class="active"' : ''; ?>>Contact</a></li>
    </ul>
</nav>
                    <!-- Client Portal Link - Only for logged in users -->
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] !== 'admin'): ?>
                    <li>
                        <a href="/HifiWebsite/client-portal/index.php" <?php echo strpos($_SERVER['PHP_SELF'], 'client-portal') !== false ? 'class="active"' : ''; ?>>
                            <i class="fas fa-user"></i> Client Portal
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Admin Portal Link - Only for admin -->
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin'): ?>
                    <li>
                        <a href="/HifiWebsite/admin-portal/index.php" <?php echo strpos($_SERVER['PHP_SELF'], 'admin-portal') !== false ? 'class="active"' : ''; ?>>
                            <i class="fas fa-shield-alt"></i> Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- ============================================= -->
            <!-- ===== RIGHT SIDE: Theme Toggle + User Controls ===== -->
            <!-- ============================================= -->
            <div class="header-right">

                <!-- ===== THEME TOGGLE ===== -->
                <div class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
                    <div class="toggle-icons">
                        <span class="sun-icon"><i class="fas fa-sun"></i></span>
                        <span class="moon-icon"><i class="fas fa-moon"></i></span>
                    </div>
                    <div class="toggle-thumb">
                        <i class="fas fa-adjust"></i>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <!-- ===== USER IS LOGGED IN ===== -->
                    <span class="user-name">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </span>
                    
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="/HifiWebsite/admin-portal/index.php" class="admin-badge">
                            <i class="fas fa-shield-alt"></i> Admin
                        </a>
                    <?php endif; ?>
                    
                    <a href="/HifiWebsite/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                    
                <?php else: ?>
                    
                    <!-- ===== USER IS NOT LOGGED IN ===== -->
                    <a href="/HifiWebsite/login.php" class="login-link">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="/HifiWebsite/register.php" class="btn-primary">
                        <i class="fas fa-user-plus"></i> Get Started
                    </a>
                    
                <?php endif; ?>
                
            </div>

            <!-- ============================================= -->
            <!-- ===== HAMBURGER MENU (Mobile) ===== -->
            <!-- ============================================= -->
            <button class="hamburger" onclick="toggleMenu()" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
        </div>
    </header>

    <!-- ============================================= -->
    <!-- ===== TOAST NOTIFICATION CONTAINER ===== -->
    <!-- ============================================= -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- ============================================= -->
    <!-- ===== MAIN CONTENT STARTS HERE ===== -->
    <!-- ============================================= -->
    <main>