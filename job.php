<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$isLoggedIn = isLoggedIn();
$username = $isLoggedIn ? $_SESSION['username'] : '';
$isAdmin = $isLoggedIn && $_SESSION['user_role'] === 'admin';

// ===== GET FILTERS FROM DATABASE =====
$locations = [];
$departments = [];
$types = [];
$workplace_types = ['On-site', 'Remote', 'Hybrid'];

$loc_query = "SELECT DISTINCT location FROM jobs WHERE is_active = 1 ORDER BY location";
$loc_result = mysqli_query($conn, $loc_query);
if ($loc_result) {
    $locations = mysqli_fetch_all($loc_result, MYSQLI_ASSOC);
}

$dept_query = "SELECT DISTINCT department FROM jobs WHERE is_active = 1 ORDER BY department";
$dept_result = mysqli_query($conn, $dept_query);
if ($dept_result) {
    $departments = mysqli_fetch_all($dept_result, MYSQLI_ASSOC);
}

$type_query = "SELECT DISTINCT type FROM jobs WHERE is_active = 1 ORDER BY type";
$type_result = mysqli_query($conn, $type_query);
if ($type_result) {
    $types = mysqli_fetch_all($type_result, MYSQLI_ASSOC);
}

// ===== GET JOBS (ALL - For Initial Load) =====
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter_location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
$filter_department = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$filter_type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$filter_workplace = isset($_GET['workplace']) ? sanitize($_GET['workplace']) : '';

$query = "SELECT * FROM jobs WHERE is_active = 1";

if (!empty($search)) {
    $query .= " AND (title LIKE '%$search%' OR department LIKE '%$search%' OR location LIKE '%$search%')";
}
if (!empty($filter_location)) {
    $query .= " AND location = '$filter_location'";
}
if (!empty($filter_department)) {
    $query .= " AND department = '$filter_department'";
}
if (!empty($filter_type)) {
    $query .= " AND type = '$filter_type'";
}
if (!empty($filter_workplace)) {
    $query .= " AND workplace = '$filter_workplace'";
}

$query .= " ORDER BY posted_date DESC";
$result = mysqli_query($conn, $query);
$jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Careers at HIFI | HIFI Marketing & Technologies</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
        <!-- ===== BROWSER TAB ICON (FAVICON) ===== -->
    <link rel="icon" href="/images/fav-icon.png" type="image/png" />
    <link rel="shortcut icon" href="/images/fav-icon.png" type="image/png" />
    
    <!-- Rest of head -->

    <style>
        /* ===== RESET & BASE ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; color: #1e1f2a; line-height: 1.6; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

        /* ===== HEADER ===== */
        header {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e9edf2;
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: -0.5px; color: #1e1f2a; flex-shrink: 0; }
        .logo span { color: #4a5cf5; }
        .header-right { display: flex; align-items: center; gap: 16px; }
        .header-right .btn-primary {
            background: #4a5cf5; color: #fff; padding: 10px 28px; border-radius: 40px;
            font-weight: 700; font-size: 14px; transition: 0.2s; border: none;
            box-shadow: 0 4px 12px rgba(74,92,245,0.2); cursor: pointer;
        }
        .header-right .btn-primary:hover { background: #3a4be0; transform: translateY(-2px); }
        .header-right .btn-logout {
            background: transparent; color: #dc3545; padding: 8px 20px; border-radius: 40px;
            font-weight: 600; font-size: 14px; border: 1px solid #dc3545; cursor: pointer;
            transition: 0.2s;
        }
        .header-right .btn-logout:hover { background: #dc3545; color: #fff; }
        .header-right .user-name { font-weight: 600; font-size: 14px; color: #1a1c26; }
        .header-right .user-name i { color: #4a5cf5; margin-right: 6px; }

        /* ===== THEME TOGGLE ===== */
        .theme-toggle {
            position: relative; width: 52px; height: 28px; background: #f0f3f8;
            border-radius: 50px; border: 2px solid #e9edf2; cursor: pointer;
            display: flex; align-items: center; padding: 2px; transition: 0.4s ease; flex-shrink: 0;
        }
        .theme-toggle:hover { border-color: #4a5cf5; box-shadow: 0 0 20px rgba(74,92,245,0.15); }
        .theme-toggle .toggle-thumb {
            width: 20px; height: 20px; border-radius: 50%; background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12); display: flex; align-items: center;
            justify-content: center; transition: 0.4s cubic-bezier(0.68,-0.55,0.265,1.55);
            z-index: 2; color: #4a5cf5; font-size: 11px;
        }
        body.dark-mode .theme-toggle .toggle-thumb { transform: translateX(24px); background: #4a5cf5; color: #fff; }
        .theme-toggle .toggle-icons {
            position: absolute; width: 100%; display: flex; justify-content: space-between;
            padding: 0 6px; font-size: 11px; pointer-events: none; color: #6b7280;
        }

        /* ===== JOB PAGE HEADER ===== */
        .job-page-header {
            text-align: center; padding: 50px 0 40px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e9edf2;
        }
        .job-page-header .sub-title {
            font-size: 14px; font-weight: 700; color: #4a5cf5;
            letter-spacing: 4px; text-transform: uppercase; margin-bottom: 6px;
        }
        .job-page-header h1 { font-size: 52px; font-weight: 900; color: #1a1c26; margin-bottom: 12px; letter-spacing: -2px; }
        .job-page-header p { font-size: 18px; color: #3d4452; max-width: 620px; margin: 0 auto; line-height: 1.8; }

        /* ===== FILTERS ===== */
        .job-filters {
            background: #ffffff; padding: 28px 0; border-bottom: 1px solid #e9edf2;
            position: sticky; top: 72px; z-index: 50; box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }
        .job-filters .filter-wrap {
            display: flex; flex-wrap: wrap; align-items: center; gap: 14px;
        }
        .job-filters .search-box { flex: 2; min-width: 250px; position: relative; }
        .job-filters .search-box input {
            width: 100%; padding: 14px 20px 14px 50px; border: 2px solid #e9edf2;
            border-radius: 12px; font-size: 15px; background: #f8fafc; transition: 0.3s;
            font-family: 'Inter', sans-serif;
        }
        .job-filters .search-box input:focus {
            border-color: #4a5cf5; outline: none; background: #ffffff;
            box-shadow: 0 0 0 4px rgba(74,92,245,0.08);
        }
        .job-filters .search-box i {
            position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
            color: #8a94a0; font-size: 18px;
        }
        .job-filters .filter-group { display: flex; flex-wrap: wrap; gap: 10px; flex: 3; }
        .job-filters .filter-select {
            padding: 12px 16px; border: 2px solid #e9edf2; border-radius: 12px;
            font-size: 14px; background: #f8fafc; cursor: pointer; min-width: 140px;
            color: #1a1c26; font-family: 'Inter', sans-serif; transition: 0.3s; flex: 1;
        }
        .job-filters .filter-select:focus {
            border-color: #4a5cf5; outline: none; box-shadow: 0 0 0 4px rgba(74,92,245,0.08);
        }
        .job-filters .results-count {
            font-size: 14px; color: #4a5260; font-weight: 600; padding: 8px 16px;
            background: #f0f3f8; border-radius: 30px; white-space: nowrap;
        }

        /* ===== JOB LISTINGS ===== */
        .job-listings { margin-top: 30px; }
        .job-item {
            background: #ffffff; border-radius: 16px; border: 1px solid #e9edf2;
            padding: 28px 32px; transition: 0.3s; margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .job-item:hover {
            border-color: #4a5cf5; box-shadow: 0 8px 40px rgba(74,92,245,0.08);
            transform: translateY(-3px);
        }
        .job-item .job-title { font-size: 20px; font-weight: 700; color: #1a1c26; margin-bottom: 6px; }
        .job-item .job-title a { color: #1a1c26; transition: 0.2s; }
        .job-item .job-title a:hover { color: #4a5cf5; }
        .job-item .job-meta {
            display: flex; flex-wrap: wrap; gap: 8px 24px; font-size: 14px; color: #4a5260;
        }
        .job-item .job-meta span { display: flex; align-items: center; gap: 6px; }
        .job-item .job-meta i { color: #4a5cf5; font-size: 14px; width: 16px; }
        .job-item .job-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .job-item .job-tags .tag {
            font-size: 12px; font-weight: 600; padding: 4px 16px; border-radius: 20px;
            background: #f0f3ff; color: #4a5cf5;
        }
        .job-item .job-tags .tag.location { background: #f0f3f8; color: #4a5260; }
        .job-item .job-tags .tag.workplace { background: #e8f5e9; color: #2e7d32; }
        .job-item .job-footer {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px; margin-top: 14px; padding-top: 14px;
            border-top: 1px solid #f0f3f8;
        }
        .job-item .job-footer .posted { font-size: 13px; color: #8a94a0; }
        .job-item .job-footer .apply-link {
            color: #4a5cf5; font-weight: 600; font-size: 15px; transition: 0.2s;
            padding: 8px 24px; border-radius: 30px; background: #f0f3ff;
            border: 1px solid #e9edf2;
        }
        .job-item .job-footer .apply-link:hover {
            background: #4a5cf5; color: #fff; border-color: #4a5cf5;
            box-shadow: 0 4px 16px rgba(74,92,245,0.25);
        }

        /* ===== LOADING ===== */
        .loading-spinner {
            text-align: center;
            padding: 40px 0;
            display: none;
        }
        .loading-spinner i {
            font-size: 36px;
            color: #4a5cf5;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* ===== SHOW MORE ===== */
        .show-more-wrap { text-align: center; margin-top: 30px; padding: 20px 0; }
        .show-more-btn {
            background: transparent; color: #4a5cf5; padding: 14px 48px;
            border-radius: 40px; font-weight: 700; font-size: 16px; border: 2px solid #4a5cf5;
            cursor: pointer; transition: 0.3s; display: inline-block;
        }
        .show-more-btn:hover {
            background: #4a5cf5; color: #fff; box-shadow: 0 8px 30px rgba(74,92,245,0.25);
            transform: translateY(-2px);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 48px;
            color: #d0d7e0;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 20px;
            color: #1a1c26;
            margin-bottom: 4px;
        }
        .empty-state p {
            color: #4a5260;
        }

        /* ===== FOOTER ===== */
        footer {
            background: #ffffff; border-top: 1px solid #e9edf2; padding: 28px 0 20px;
            text-align: center;
        }
        footer p { color: #4a5260; font-size: 13px; }
        footer .footer-links {
            display: flex; justify-content: center; gap: 24px; margin-bottom: 10px;
            font-size: 13px; color: #4a5260; flex-wrap: wrap;
        }
        footer .footer-links a:hover { color: #4a5cf5; }
        footer .powered { font-size: 12px; color: #8a94a0; margin-top: 4px; }
        footer .powered span { color: #4a5cf5; font-weight: 600; }

        /* ===== DARK MODE ===== */
        body.dark-mode {
            background: #0b0d10; color: #eaeef2;
        }
        body.dark-mode header {
            background: rgba(11,13,16,0.98); border-bottom-color: #1e242c;
        }
        body.dark-mode .logo { color: #eaeef2; }
        body.dark-mode .job-page-header {
            background: linear-gradient(135deg, #0b0d10 0%, #14191f 100%);
            border-bottom-color: #1e242c;
        }
        body.dark-mode .job-page-header h1 { color: #eaeef2; }
        body.dark-mode .job-page-header p { color: #b0b8c5; }
        body.dark-mode .job-filters {
            background: #0b0d10; border-bottom-color: #1e242c;
        }
        body.dark-mode .job-filters .search-box input {
            background: #14191f; border-color: #1e242c; color: #eaeef2;
        }
        body.dark-mode .job-filters .search-box input:focus {
            background: #14191f; border-color: #4a5cf5;
        }
        body.dark-mode .job-filters .filter-select {
            background: #14191f; border-color: #1e242c; color: #eaeef2;
        }
        body.dark-mode .job-filters .results-count {
            background: #1e242c; color: #b0b8c5;
        }
        body.dark-mode .job-item {
            background: #14191f; border-color: #1e242c;
        }
        body.dark-mode .job-item .job-title { color: #eaeef2; }
        body.dark-mode .job-item .job-meta { color: #b0b8c5; }
        body.dark-mode .job-item .job-tags .tag { background: #1e242c; color: #6c7aff; }
        body.dark-mode .job-item .job-tags .tag.location { background: #1e242c; color: #b0b8c5; }
        body.dark-mode .job-item .job-tags .tag.workplace { background: #1e242c; color: #4caf50; }
        body.dark-mode .job-item .job-footer { border-top-color: #1e242c; }
        body.dark-mode .job-item .job-footer .posted { color: #6b7a8a; }
        body.dark-mode .job-item .job-footer .apply-link {
            background: #1e242c; border-color: #2a3340; color: #6c7aff;
        }
        body.dark-mode .job-item .job-footer .apply-link:hover {
            background: #4a5cf5; color: #fff;
        }
        body.dark-mode footer {
            background: #0b0d10; border-color: #1e242c;
        }
        body.dark-mode footer p { color: #6b7a8a; }
        body.dark-mode footer .footer-links { color: #6b7a8a; }
        body.dark-mode .empty-state h3 { color: #eaeef2; }
        body.dark-mode .empty-state p { color: #b0b8c5; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .job-page-header h1 { font-size: 40px; }
            .job-filters .filter-wrap { flex-direction: column; align-items: stretch; }
            .job-filters .filter-group { flex-wrap: wrap; }
            .job-filters .filter-select { min-width: 120px; flex: 1 1 calc(50% - 10px); }
            .job-filters .results-count { text-align: center; align-self: center; }
        }
        @media (max-width: 768px) {
            .job-page-header h1 { font-size: 30px; }
            .job-page-header p { font-size: 16px; }
            .job-item { padding: 20px; }
            .job-item .job-title { font-size: 17px; }
            .job-item .job-meta { gap: 6px 16px; font-size: 13px; }
            .job-item .job-footer { flex-direction: column; align-items: flex-start; gap: 10px; }
            .header-right .btn-primary { display: none; }
            .job-filters .filter-select { flex: 1 1 100%; }
        }
        @media (max-width: 480px) {
            .job-page-header h1 { font-size: 24px; }
            .job-page-header { padding: 30px 0 20px; }
            .job-filters { padding: 16px 0; top: 64px; }
            .job-filters .search-box input { padding: 12px 16px 12px 44px; font-size: 14px; }
            .job-item { padding: 16px; }
            .job-item .job-tags .tag { font-size: 10px; padding: 2px 12px; }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header>
        <div class="container header-inner">
            <div class="logo">HIFI <span>Marketing & Technologies</span></div>
            <div class="header-right">
                <?php if ($isLoggedIn): ?>
                    <span class="user-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($username); ?></span>
                    <a href="user/dashboard.php" class="btn-primary"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <?php if ($isAdmin): ?>
                        <a href="admin/dashboard.php" style="background:#6c7aff;color:#fff;padding:6px 16px;border-radius:40px;font-weight:600;font-size:12px;">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-logout">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="color:#4a5cf5;font-weight:600;font-size:14px;">Login</a>
                    <a href="register.php" class="btn-primary">Get Started</a>
                <?php endif; ?>
                <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
                    <div class="toggle-icons">
                        <span class="sun-icon" style="color:#f59e0b;"><i class="fas fa-sun"></i></span>
                        <span class="moon-icon" style="color:#6b7280;"><i class="fas fa-moon"></i></span>
                    </div>
                    <div class="toggle-thumb">
                        <i class="fas fa-adjust"></i>
                    </div>
                </button>
            </div>
        </div>
    </header>

    <!-- ===== JOB PAGE HEADER ===== -->
    <section class="job-page-header">
        <div class="container">
            <div class="sub-title">Careers at</div>
            <h1>HIFI</h1>
            <p>We're excited to meet you. Outlined below are the current roles that HIFI is looking to find new individuals to join our team.</p>
        </div>
    </section>

    <!-- ===== FILTERS ===== -->
    <section class="job-filters" id="filterSection">
        <div class="container">
            <div class="filter-wrap">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search jobs by title, department, or location..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" />
                </div>
                <div class="filter-group">
                    <select class="filter-select" id="filterLocation" onchange="applyFilters()">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc['location']); ?>" <?php echo $filter_location === $loc['location'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($loc['location']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="filterDepartment" onchange="applyFilters()">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $filter_department === $dept['department'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="filterType" onchange="applyFilters()">
                        <option value="">All Types</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?php echo $t['type']; ?>" <?php echo $filter_type === $t['type'] ? 'selected' : ''; ?>><?php echo $t['type']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="filterWorkplace" onchange="applyFilters()">
                        <option value="">All Workplace</option>
                        <?php foreach ($workplace_types as $wp): ?>
                            <option value="<?php echo $wp; ?>" <?php echo $filter_workplace === $wp ? 'selected' : ''; ?>><?php echo $wp; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="results-count" id="resultsCount"><?php echo count($jobs); ?> jobs found</span>
            </div>
        </div>
    </section>

    <!-- ===== JOB LISTINGS ===== -->
    <section class="job-page">
        <div class="container">
            <!-- Loading Spinner -->
            <div class="loading-spinner" id="loadingSpinner">
                <i class="fas fa-spinner"></i>
            </div>

            <div class="job-listings" id="jobListings">
                <?php if (count($jobs) > 0): ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="job-item" data-id="<?php echo $job['id']; ?>">
                            <div class="job-title">
                                <a href="job-detail.php?id=<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a>
                            </div>
                            <div class="job-meta">
                                <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($job['department']); ?></span>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo $job['type']; ?></span>
                            </div>
                            <div class="job-tags">
                                <span class="tag"><?php echo $job['type']; ?></span>
                                <span class="tag location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                <span class="tag workplace"><i class="fas fa-laptop"></i> <?php echo $job['workplace'] ?? 'On-site'; ?></span>
                            </div>
                            <div class="job-footer">
                                <span class="posted"><i class="fas fa-clock"></i> Posted <?php echo time_ago($job['posted_date']); ?></span>
                                <a href="job-detail.php?id=<?php echo $job['id']; ?>" class="apply-link">View & Apply →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No jobs found</h3>
                        <p>Try adjusting your search or filter to find what you're looking for.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== SHOW MORE ===== -->
            <?php if (count($jobs) > 5): ?>
                <div class="show-more-wrap">
                    <button class="show-more-btn" onclick="showMoreJobs()">
                        Show More <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="container">
            <div class="footer-links">
                <a href="careers.php">Careers</a>
                <a href="contact.php">Contact</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Cookie Settings</a>
                <a href="#">Help</a>
                <a href="#">Accessibility</a>
            </div>
            <p>&copy; 2026 HIFI Marketing &amp; Technologies. All rights reserved.</p>
            <p class="powered">Powered by <span>HIFI</span></p>
        </div>
    </footer>

    <script>
        // ============================================================
        // LIVE SEARCH - REAL-TIME FILTERING (NO PAGE RELOAD)
        // ============================================================
        const searchInput = document.getElementById('searchInput');
        const filterLocation = document.getElementById('filterLocation');
        const filterDepartment = document.getElementById('filterDepartment');
        const filterType = document.getElementById('filterType');
        const filterWorkplace = document.getElementById('filterWorkplace');
        const jobListings = document.getElementById('jobListings');
        const resultsCount = document.getElementById('resultsCount');
        const loadingSpinner = document.getElementById('loadingSpinner');

        let searchTimeout;

        function applyFilters() {
            // Show loading spinner
            loadingSpinner.style.display = 'block';
            jobListings.style.opacity = '0.5';

            const search = searchInput.value.trim();
            const location = filterLocation.value;
            const department = filterDepartment.value;
            const type = filterType.value;
            const workplace = filterWorkplace.value;

            // Build URL with parameters
            let url = 'job.php?ajax=1&';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (location) url += 'location=' + encodeURIComponent(location) + '&';
            if (department) url += 'department=' + encodeURIComponent(department) + '&';
            if (type) url += 'type=' + encodeURIComponent(type) + '&';
            if (workplace) url += 'workplace=' + encodeURIComponent(workplace);

            // Remove trailing &
            url = url.replace(/[&?]$/, '');

            // Fetch filtered results
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    // Extract job listings and count from response
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newListings = doc.querySelector('#jobListings');
                    const newCount = doc.querySelector('#resultsCount');
                    
                    if (newListings) {
                        jobListings.innerHTML = newListings.innerHTML;
                    }
                    if (newCount) {
                        resultsCount.textContent = newCount.textContent;
                    }
                    
                    loadingSpinner.style.display = 'none';
                    jobListings.style.opacity = '1';
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingSpinner.style.display = 'none';
                    jobListings.style.opacity = '1';
                });
        }

        // ===== LIVE SEARCH ON KEYUP (With Debounce) =====
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 250);
        });

        // ===== FILTER ON CHANGE =====
        filterLocation.addEventListener('change', applyFilters);
        filterDepartment.addEventListener('change', applyFilters);
        filterType.addEventListener('change', applyFilters);
        filterWorkplace.addEventListener('change', applyFilters);

        // ============================================================
        // SHOW MORE JOBS (Load More)
        // ============================================================
        let currentLimit = <?php echo count($jobs); ?>;

        function showMoreJobs() {
            const search = searchInput.value.trim();
            const location = filterLocation.value;
            const department = filterDepartment.value;
            const type = filterType.value;
            const workplace = filterWorkplace.value;

            currentLimit += 5;

            let url = 'job.php?ajax=1&limit=' + currentLimit;
            if (search) url += '&search=' + encodeURIComponent(search);
            if (location) url += '&location=' + encodeURIComponent(location);
            if (department) url += '&department=' + encodeURIComponent(department);
            if (type) url += '&type=' + encodeURIComponent(type);
            if (workplace) url += '&workplace=' + encodeURIComponent(workplace);

            loadingSpinner.style.display = 'block';
            jobListings.style.opacity = '0.5';

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newListings = doc.querySelector('#jobListings');
                    const newCount = doc.querySelector('#resultsCount');
                    
                    if (newListings) {
                        jobListings.innerHTML = newListings.innerHTML;
                    }
                    if (newCount) {
                        resultsCount.textContent = newCount.textContent;
                    }
                    
                    loadingSpinner.style.display = 'none';
                    jobListings.style.opacity = '1';
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingSpinner.style.display = 'none';
                    jobListings.style.opacity = '1';
                });
        }

        // ============================================================
        // THEME TOGGLE
        // ============================================================
        function toggleTheme() {
            const body = document.body;
            if (body) {
                body.classList.toggle('dark-mode');
                localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
            }
        });
    </script>

</body>
</html>