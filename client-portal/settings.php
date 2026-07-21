<?php
// settings.php - Complete Theme Customizer with Live Preview
session_start();
require_once __DIR__ . '/../includes/config.php';

// ===== SESSION VALIDATION =====
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['portal_role']) || ($_SESSION['portal_role'] !== 'pm' && $_SESSION['portal_role'] !== 'admin')) {
    header('Location: client-portal.php');
    exit();
}

$userData = $_SESSION['user'] ?? [];
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';

// ===== GET CURRENT THEME SETTINGS =====
$theme_sql = "SELECT * FROM theme_settings WHERE id = 1";
$theme_result = mysqli_query($conn, $theme_sql);
$theme = mysqli_fetch_assoc($theme_result);

if (!$theme) {
    // Insert default theme settings
    $default_theme = [
        'dashboard_bg' => '#f8fafc',
        'dashboard_card_bg' => '#ffffff',
        'dashboard_card_border' => '#e2e8f0',
        'dashboard_heading' => '#1e293b',
        'dashboard_text' => '#475569',
        'dashboard_metric_bg' => '#ffffff',
        'dashboard_metric_text' => '#1e293b',
        'dashboard_metric_icon' => '#4f46e5',
        'dashboard_metric_value' => '#1e293b',
        'dashboard_progress_bg' => '#e2e8f0',
        'dashboard_progress_fill' => '#4f46e5',
        'plan_bg' => '#ffffff',
        'plan_card_bg' => '#ffffff',
        'plan_card_border' => '#e2e8f0',
        'plan_heading' => '#1e293b',
        'plan_text' => '#475569',
        'plan_price' => '#1e293b',
        'plan_active_border' => '#4f46e5',
        'plan_active_bg' => '#eef2ff',
        'plan_button_bg' => '#4f46e5',
        'plan_button_text' => '#ffffff',
        'addons_bg' => '#ffffff',
        'addons_card_bg' => '#ffffff',
        'addons_card_border' => '#e2e8f0',
        'addons_heading' => '#1e293b',
        'addons_text' => '#475569',
        'addons_price' => '#4f46e5',
        'addons_button_bg' => '#4f46e5',
        'addons_button_text' => '#ffffff',
        'deliverables_bg' => '#ffffff',
        'deliverables_card_bg' => '#ffffff',
        'deliverables_card_border' => '#e2e8f0',
        'deliverables_heading' => '#1e293b',
        'deliverables_text' => '#475569',
        'deliverables_status_todo' => '#94a3b8',
        'deliverables_status_progress' => '#f59e0b',
        'deliverables_status_done' => '#22c55e',
        'tickets_bg' => '#ffffff',
        'tickets_card_bg' => '#ffffff',
        'tickets_card_border' => '#e2e8f0',
        'tickets_heading' => '#1e293b',
        'tickets_text' => '#475569',
        'tickets_status_open' => '#ef4444',
        'tickets_status_resolved' => '#22c55e',
        'tickets_button_bg' => '#4f46e5',
        'tickets_button_text' => '#ffffff',
        'billing_bg' => '#ffffff',
        'billing_table_header' => '#f1f5f9',
        'billing_table_border' => '#e2e8f0',
        'billing_heading' => '#1e293b',
        'billing_text' => '#475569',
        'billing_paid' => '#22c55e',
        'billing_pending' => '#f59e0b',
        'billing_button_bg' => '#4f46e5',
        'billing_button_text' => '#ffffff',
        'reports_bg' => '#ffffff',
        'reports_card_bg' => '#ffffff',
        'reports_card_border' => '#e2e8f0',
        'reports_heading' => '#1e293b',
        'reports_text' => '#475569',
        'reports_button_bg' => '#4f46e5',
        'reports_button_text' => '#ffffff',
        'pm_operations_bg' => '#ffffff',
        'pm_operations_card_bg' => '#ffffff',
        'pm_operations_card_border' => '#e2e8f0',
        'pm_operations_heading' => '#1e293b',
        'pm_operations_text' => '#475569',
        'pm_operations_status' => '#4f46e5',
        'pm_operations_button_bg' => '#4f46e5',
        'pm_operations_button_text' => '#ffffff',
        'pm_deliverables_bg' => '#ffffff',
        'pm_deliverables_card_bg' => '#ffffff',
        'pm_deliverables_card_border' => '#e2e8f0',
        'pm_deliverables_heading' => '#1e293b',
        'pm_deliverables_text' => '#475569',
        'pm_deliverables_status_todo' => '#94a3b8',
        'pm_deliverables_status_progress' => '#f59e0b',
        'pm_deliverables_status_done' => '#22c55e',
        'pm_deliverables_button_bg' => '#4f46e5',
        'pm_deliverables_button_text' => '#ffffff',
        'pm_tickets_bg' => '#ffffff',
        'pm_tickets_card_bg' => '#ffffff',
        'pm_tickets_card_border' => '#e2e8f0',
        'pm_tickets_heading' => '#1e293b',
        'pm_tickets_text' => '#475569',
        'pm_tickets_status_open' => '#ef4444',
        'pm_tickets_status_resolved' => '#22c55e',
        'pm_tickets_button_bg' => '#4f46e5',
        'pm_tickets_button_text' => '#ffffff',
        'pm_verbal_bg' => '#ffffff',
        'pm_verbal_card_bg' => '#ffffff',
        'pm_verbal_card_border' => '#e2e8f0',
        'pm_verbal_heading' => '#1e293b',
        'pm_verbal_text' => '#475569',
        'pm_verbal_button_bg' => '#4f46e5',
        'pm_verbal_button_text' => '#ffffff',
        'pm_sync_bg' => '#ffffff',
        'pm_sync_card_bg' => '#ffffff',
        'pm_sync_card_border' => '#e2e8f0',
        'pm_sync_heading' => '#1e293b',
        'pm_sync_text' => '#475569',
        'pm_sync_slider' => '#4f46e5',
        'pm_sync_button_bg' => '#4f46e5',
        'pm_sync_button_text' => '#ffffff'
    ];
    
    $columns = array_keys($default_theme);
    $placeholders = array_fill(0, count($columns), '?');
    $insert_sql = "INSERT INTO theme_settings (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = mysqli_prepare($conn, $insert_sql);
    $types = str_repeat('s', count($columns));
    $values = array_values($default_theme);
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    $theme = $default_theme;
}

// ===== UPDATE THEME SETTINGS =====
if (isset($_POST['save_theme'])) {
    $fields = array_keys($theme);
    $updates = [];
    $values = [];
    $types = '';
    
    foreach ($fields as $field) {
        if ($field !== 'id' && isset($_POST[$field])) {
            $updates[] = "$field = ?";
            $values[] = trim($_POST[$field]);
            $types .= 's';
        }
    }
    
    if (!empty($updates)) {
        $update_sql = "UPDATE theme_settings SET " . implode(', ', $updates) . " WHERE id = 1";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        if (mysqli_stmt_execute($stmt)) {
            generateThemeCSS($conn);
            $success_msg = "Theme settings saved successfully!";
            // Reload theme data
            $theme_result = mysqli_query($conn, "SELECT * FROM theme_settings WHERE id = 1");
            $theme = mysqli_fetch_assoc($theme_result);
        } else {
            $error_msg = "Error saving settings: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// ===== GENERATE THEME CSS =====
function generateThemeCSS($conn) {
    $sql = "SELECT * FROM theme_settings WHERE id = 1";
    $result = mysqli_query($conn, $sql);
    $t = mysqli_fetch_assoc($result);
    if (!$t) return;
    
    $css = "/* ===== AUTO-GENERATED THEME CSS ===== */\n\n";
    
    // Global theme variables
    $css .= ":root {\n";
    foreach ($t as $key => $value) {
        if ($key !== 'id') {
            $css .= "    --" . str_replace('_', '-', $key) . ": {$value};\n";
        }
    }
    $css .= "}\n\n";
    
    // Page-specific CSS classes
    $css .= "/* Dashboard */\n";
    $css .= ".dashboard-bg { background: var(--dashboard-bg); }\n";
    $css .= ".dashboard-card { background: var(--dashboard-card-bg); border-color: var(--dashboard-card-border); }\n";
    $css .= ".dashboard-heading { color: var(--dashboard-heading); }\n";
    $css .= ".dashboard-text { color: var(--dashboard-text); }\n";
    $css .= ".dashboard-metric { background: var(--dashboard-metric-bg); }\n";
    $css .= ".dashboard-metric-text { color: var(--dashboard-metric-text); }\n";
    $css .= ".dashboard-metric-icon { color: var(--dashboard-metric-icon); }\n";
    $css .= ".dashboard-metric-value { color: var(--dashboard-metric-value); }\n";
    $css .= ".dashboard-progress-bg { background: var(--dashboard-progress-bg); }\n";
    $css .= ".dashboard-progress-fill { background: var(--dashboard-progress-fill); }\n\n";
    
    $css .= "/* Packages */\n";
    $css .= ".plan-bg { background: var(--plan-bg); }\n";
    $css .= ".plan-card { background: var(--plan-card-bg); border-color: var(--plan-card-border); }\n";
    $css .= ".plan-heading { color: var(--plan-heading); }\n";
    $css .= ".plan-text { color: var(--plan-text); }\n";
    $css .= ".plan-price { color: var(--plan-price); }\n";
    $css .= ".plan-active { border-color: var(--plan-active-border); background: var(--plan-active-bg); }\n";
    $css .= ".plan-button { background: var(--plan-button-bg); color: var(--plan-button-text); }\n\n";
    
    $css .= "/* Addons */\n";
    $css .= ".addons-bg { background: var(--addons-bg); }\n";
    $css .= ".addons-card { background: var(--addons-card-bg); border-color: var(--addons-card-border); }\n";
    $css .= ".addons-heading { color: var(--addons-heading); }\n";
    $css .= ".addons-text { color: var(--addons-text); }\n";
    $css .= ".addons-price { color: var(--addons-price); }\n";
    $css .= ".addons-button { background: var(--addons-button-bg); color: var(--addons-button-text); }\n\n";
    
    $css .= "/* Deliverables */\n";
    $css .= ".deliverables-bg { background: var(--deliverables-bg); }\n";
    $css .= ".deliverables-card { background: var(--deliverables-card-bg); border-color: var(--deliverables-card-border); }\n";
    $css .= ".deliverables-heading { color: var(--deliverables-heading); }\n";
    $css .= ".deliverables-text { color: var(--deliverables-text); }\n";
    $css .= ".deliverables-status-todo { background: var(--deliverables-status-todo); }\n";
    $css .= ".deliverables-status-progress { background: var(--deliverables-status-progress); }\n";
    $css .= ".deliverables-status-done { background: var(--deliverables-status-done); }\n\n";
    
    $css .= "/* Tickets */\n";
    $css .= ".tickets-bg { background: var(--tickets-bg); }\n";
    $css .= ".tickets-card { background: var(--tickets-card-bg); border-color: var(--tickets-card-border); }\n";
    $css .= ".tickets-heading { color: var(--tickets-heading); }\n";
    $css .= ".tickets-text { color: var(--tickets-text); }\n";
    $css .= ".tickets-status-open { color: var(--tickets-status-open); }\n";
    $css .= ".tickets-status-resolved { color: var(--tickets-status-resolved); }\n";
    $css .= ".tickets-button { background: var(--tickets-button-bg); color: var(--tickets-button-text); }\n\n";
    
    $css .= "/* Billing */\n";
    $css .= ".billing-bg { background: var(--billing-bg); }\n";
    $css .= ".billing-table-header { background: var(--billing-table-header); }\n";
    $css .= ".billing-table-border { border-color: var(--billing-table-border); }\n";
    $css .= ".billing-heading { color: var(--billing-heading); }\n";
    $css .= ".billing-text { color: var(--billing-text); }\n";
    $css .= ".billing-paid { color: var(--billing-paid); }\n";
    $css .= ".billing-pending { color: var(--billing-pending); }\n";
    $css .= ".billing-button { background: var(--billing-button-bg); color: var(--billing-button-text); }\n\n";
    
    $css .= "/* Reports */\n";
    $css .= ".reports-bg { background: var(--reports-bg); }\n";
    $css .= ".reports-card { background: var(--reports-card-bg); border-color: var(--reports-card-border); }\n";
    $css .= ".reports-heading { color: var(--reports-heading); }\n";
    $css .= ".reports-text { color: var(--reports-text); }\n";
    $css .= ".reports-button { background: var(--reports-button-bg); color: var(--reports-button-text); }\n\n";
    
    $file_path = __DIR__ . '/../assets/css/theme.css';
    $dir = dirname($file_path);
    if (!is_dir($dir)) { mkdir($dir, 0777, true); }
    file_put_contents($file_path, $css);
}

// ===== GET ALL PAGES WITH TABS =====
$pages = [
    'client' => [
        'label' => 'Client Portal',
        'icon' => 'user',
        'tabs' => [
            'dashboard' => ['label' => 'Dashboard Overview', 'icon' => 'layout-dashboard'],
            'plan' => ['label' => 'Service Packages', 'icon' => 'credit-card'],
            'addons' => ['label' => 'Addons & Custom', 'icon' => 'layers'],
            'deliverables' => ['label' => 'Deliverables Board', 'icon' => 'check-square'],
            'tickets' => ['label' => 'Tasks & Support', 'icon' => 'message-square'],
            'billing' => ['label' => 'Billing Ledger', 'icon' => 'file-text'],
            'reports' => ['label' => 'Marketing Reports', 'icon' => 'bar-chart-2']
        ]
    ],
    'pm' => [
        'label' => 'PM Portal',
        'icon' => 'briefcase',
        'tabs' => [
            'pm_operations' => ['label' => 'Operations Desk', 'icon' => 'layout-dashboard'],
            'pm_deliverables' => ['label' => 'Manage Deliverables', 'icon' => 'check-square'],
            'pm_tickets' => ['label' => 'Client Tickets & Tasks', 'icon' => 'message-square'],
            'pm_verbal' => ['label' => 'Client Verbal Requests', 'icon' => 'phone'],
            'pm_sync' => ['label' => 'Progress Counter Sync', 'icon' => 'sliders']
        ]
    ],
    'global' => [
        'label' => 'Global Styles',
        'icon' => 'globe',
        'tabs' => [
            'global_sidebar' => ['label' => 'Sidebar', 'icon' => 'sidebar'],
            'global_header' => ['label' => 'Header', 'icon' => 'header'],
            'global_buttons' => ['label' => 'Buttons', 'icon' => 'square'],
            'global_typography' => ['label' => 'Typography', 'icon' => 'type']
        ]
    ]
];

// ===== GET COLOR GROUPS FOR UI =====
function getColorGroups($theme) {
    $groups = [];
    foreach ($theme as $key => $value) {
        if ($key !== 'id') {
            $group = explode('_', $key);
            $page = $group[0];
            $element = isset($group[1]) ? $group[1] : '';
            if (!isset($groups[$page])) {
                $groups[$page] = [];
            }
            $groups[$page][] = ['key' => $key, 'value' => $value, 'label' => ucwords(str_replace('_', ' ', $key))];
        }
    }
    return $groups;
}

$colorGroups = getColorGroups($theme);
$activePage = isset($_GET['page']) ? $_GET['page'] : 'client';
$activeTabSettings = isset($_GET['tab_settings']) ? $_GET['tab_settings'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMMA Scale - Theme Customizer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .sidebar-link { transition: all 0.2s ease; white-space: nowrap; }
        .sidebar-link:hover { background: rgba(255,255,255,0.05); }
        .sidebar-link.active { background: rgba(251,191,36,0.15); color: #fbbf24; border-right: 3px solid #fbbf24; }
        .sidebar-link { color: #94a3b8; }
        .sidebar-link:hover { color: #e2e8f0; }
        .sidebar-collapsed .sidebar-text { display: none; }
        .sidebar-collapsed .sidebar-link { justify-content: center; padding: 12px 8px; }
        .sidebar-collapsed .sidebar-brand-text { display: none; }
        .sidebar-collapsed .sidebar-user-text { display: none; }
        .sidebar-collapsed { width: 72px !important; }
        .sidebar-collapsed .sidebar-toggle-icon { transform: rotate(180deg); }
        .sidebar-collapsed .sidebar-nav { padding: 12px 4px; }
        .sidebar-collapsed .sidebar-footer { padding: 12px 8px; }
        .sidebar-collapsed .sidebar-footer .flex.items-center.gap-3 { justify-content: center; }
        .sidebar-collapsed .sidebar-footer .flex.items-center.gap-3 img { width: 32px; height: 32px; }
        .sidebar-collapsed .sidebar-footer .mt-3.pt-3 { display: none; }
        .sidebar-collapsed .sidebar-badge { display: none; }
        .sidebar-transition { transition: width 0.3s cubic-bezier(0.4,0,0.2,1); }
        .sidebar-link-transition { transition: all 0.2s ease; }
        .security-badge { position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.7); color: #4ade80; padding: 4px 12px; border-radius: 20px; font-size: 9px; font-weight: bold; z-index: 9999; backdrop-filter: blur(10px); border: 1px solid rgba(74,222,128,0.2); pointer-events: none; }
        
        .color-picker-wrapper { display: flex; align-items: center; gap: 12px; }
        .color-picker-wrapper input[type="color"] { width: 45px; height: 45px; border: 3px solid #e2e8f0; border-radius: 8px; cursor: pointer; padding: 2px; flex-shrink: 0; }
        .color-picker-wrapper input[type="color"]:hover { border-color: #94a3b8; }
        
        .setting-item { transition: all 0.2s ease; }
        .setting-item:hover { background: #f8fafc; border-color: #cbd5e1; }
        .setting-item .preview-box { width: 60px; height: 35px; border-radius: 6px; flex-shrink: 0; border: 1px solid #e2e8f0; transition: all 0.3s ease; }
        
        .page-menu-item { transition: all 0.2s ease; cursor: pointer; }
        .page-menu-item:hover { background: #f1f5f9; }
        .page-menu-item.active { background: #eef2ff; border-color: #818cf8; }
        .page-menu-item .sub-tab { transition: all 0.2s ease; cursor: pointer; }
        .page-menu-item .sub-tab:hover { background: #f8fafc; }
        .page-menu-item .sub-tab.active { background: #eef2ff; color: #4f46e5; }
        
        .preview-frame { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.3s ease; }
        .preview-frame iframe { width: 100%; height: 500px; border: none; }
        
        .color-preview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
        .color-preview-item { padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; transition: all 0.3s ease; }
        .color-preview-item .color-swatch { width: 100%; height: 30px; border-radius: 4px; margin-bottom: 6px; border: 1px solid #e2e8f0; }
        
        .live-preview-toggle { transition: all 0.3s ease; }
        .live-preview-toggle:hover { transform: scale(1.02); }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 antialiased min-h-screen flex">

    <!-- ===== SIDEBAR ===== -->
    <aside id="main-sidebar" class="w-72 bg-[#0f172a] text-slate-300 flex flex-col shrink-0 border-r border-slate-800 h-screen sticky top-0 sidebar-transition <?php echo $isCollapsed ? 'sidebar-collapsed' : ''; ?>">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3 sidebar-brand">
                <div class="h-9 w-9 bg-gradient-to-tr from-amber-500 to-amber-700 rounded-xl flex items-center justify-center text-white shadow-lg font-bold text-lg flex-shrink-0">P</div>
                <div class="sidebar-brand-text">
                    <h1 class="text-sm font-extrabold text-white tracking-wide leading-none">Theme Customizer</h1>
                    <span class="text-[9px] text-amber-400 font-bold tracking-wider uppercase block">Settings Panel</span>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="p-1.5 hover:bg-slate-800 rounded-lg transition">
                <i data-lucide="chevron-left" class="w-4 h-4 text-slate-400 sidebar-toggle-icon"></i>
            </button>
        </div>

        <div class="px-4 py-2 bg-[#1e293b]/60 border-b border-slate-800 flex items-center justify-between sidebar-badge">
            <span class="text-[10px] font-bold text-slate-400">Access:</span>
            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase bg-amber-500/20 text-amber-400">PM Admin</span>
        </div>

        <!-- ===== PAGE MENU ===== -->
        <nav class="px-3 py-4 space-y-1 flex-1 overflow-y-auto sidebar-nav">
            <?php foreach ($pages as $pageKey => $page): ?>
            <div class="page-menu-item">
                <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-bold <?php echo $activePage === $pageKey ? 'active bg-slate-800/50 text-amber-400' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/30'; ?>" 
                     onclick="togglePage('<?php echo $pageKey; ?>')">
                    <i data-lucide="<?php echo $page['icon']; ?>" class="w-4 h-4 flex-shrink-0"></i>
                    <span class="sidebar-text flex-1"><?php echo $page['label']; ?></span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 sidebar-text transition-transform duration-200 <?php echo $activePage === $pageKey ? 'rotate-180' : ''; ?>"></i>
                </div>
                <div class="pl-6 mt-1 space-y-0.5 <?php echo $activePage === $pageKey ? '' : 'hidden'; ?>" id="submenu-<?php echo $pageKey; ?>">
                    <?php foreach ($page['tabs'] as $tabKey => $tab): ?>
                    <a href="?page=<?php echo $pageKey; ?>&tab_settings=<?php echo $tabKey; ?>" 
                       class="sub-tab flex items-center gap-2 px-3 py-1.5 rounded-lg text-[10px] font-semibold <?php echo $activeTabSettings === $tabKey ? 'active text-indigo-600' : 'text-slate-500 hover:text-slate-300'; ?>">
                        <i data-lucide="<?php echo $tab['icon']; ?>" class="w-3.5 h-3.5 flex-shrink-0"></i>
                        <span class="sidebar-text"><?php echo $tab['label']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="border-t border-slate-800 pt-3 mt-3">
                <a href="pm-portal.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-bold text-slate-400 hover:text-slate-100 hover:bg-slate-800/30 transition sidebar-link">
                    <i data-lucide="arrow-left" class="w-4 h-4 flex-shrink-0"></i>
                    <span class="sidebar-text">Back to Dashboard</span>
                </a>
            </div>
        </nav>

        <div class="p-3 border-t border-slate-800 bg-[#0b0f19] sidebar-footer">
            <div class="flex items-center gap-3">
                <img class="h-9 w-9 rounded-lg object-cover ring-2 ring-amber-500/20" 
                     src="<?php echo $userData['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&auto=format&fit=crop&q=80'; ?>" 
                     alt="Avatar">
                <div class="sidebar-user-text">
                    <p class="text-xs font-bold text-white truncate"><?php echo $userData['name'] ?? 'PM'; ?></p>
                    <p class="text-[9px] text-slate-500 truncate">Senior Account Director</p>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-800 sidebar-user-text">
                <a href="login.php?logout=true" class="flex items-center gap-2 text-[10px] font-bold text-rose-400 hover:text-rose-300 transition">
                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 h-14 px-6 flex items-center justify-between sticky top-0 z-30 shrink-0 shadow-sm">
            <div class="relative w-80 max-w-xs">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" id="global-search" oninput="filterContent(this.value)" 
                       placeholder="Search color settings..." 
                       class="block w-full pl-9 pr-3 py-1.5 border border-slate-200 rounded-lg bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-xs">
            </div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 bg-amber-50 text-amber-800 text-[10px] font-bold rounded-lg border border-amber-200 flex items-center gap-1.5">
                    <i data-lucide="palette" class="w-3 h-3"></i>
                    THEME CUSTOMIZER
                </span>
                <button class="relative p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                    <span class="absolute top-1 right-1 h-1.5 w-1.5 bg-rose-500 rounded-full ring-1 ring-white"></span>
                </button>
                <span class="text-[8px] text-slate-400 hidden md:block">🔒 Secure</span>
            </div>
        </header>

        <div class="p-4 md:p-6 max-w-[1600px] w-full mx-auto space-y-5 flex-1">
            
            <!-- ===== HEADER ===== -->
            <div class="bg-gradient-to-r from-amber-50 to-amber-100/30 border border-amber-100 p-4 md:p-6 rounded-2xl">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="palette" class="w-3 h-3"></i>
                            VISUAL THEME EDITOR
                        </p>
                        <h2 class="text-2xl font-black text-slate-900 mt-0.5">
                            <?php 
                                $currentPage = $pages[$activePage]['label'] ?? 'Settings';
                                $currentTab = $pages[$activePage]['tabs'][$activeTabSettings]['label'] ?? 'Colors';
                                echo $currentPage . ' - ' . $currentTab;
                            ?>
                        </h2>
                        <p class="text-sm text-slate-600">Customize every element of your portal's appearance with live preview</p>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <button onclick="toggleLivePreview()" class="px-4 py-2 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded-lg hover:bg-indigo-200 transition flex items-center gap-1.5 live-preview-toggle">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            <span id="preview-toggle-text">Show Preview</span>
                        </button>
                        <button onclick="resetAllSettings()" class="px-4 py-2 bg-rose-100 text-rose-700 text-[10px] font-bold rounded-lg hover:bg-rose-200 transition flex items-center gap-1.5">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            Reset All
                        </button>
                        <span class="px-3 py-1 bg-emerald-500/10 text-emerald-700 border border-emerald-200 rounded-lg text-[10px] font-bold flex items-center gap-1.5">
                            <i data-lucide="radio" class="w-3 h-3 text-emerald-500"></i>
                            Live Preview Active
                        </span>
                    </div>
                </div>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_msg)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-500"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- ===== SETTINGS FORM ===== -->
            <form method="POST" action="" id="settings-form">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- ===== COLOR SETTINGS PANEL ===== -->
                    <div class="lg:col-span-2 space-y-5">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6">
                            <h3 class="text-sm font-extrabold text-slate-950 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <i data-lucide="palette" class="w-4 h-4 text-amber-600"></i>
                                Color Settings for <?php echo $currentPage . ' - ' . $currentTab; ?>
                            </h3>
                            <p class="text-xs text-slate-500 mb-4">Click on any color picker to change the color. Changes are saved instantly.</p>
                            
                            <div class="space-y-3">
                                <?php 
                                $prefix = '';
                                if ($activePage === 'client') {
                                    $prefix = $activeTabSettings;
                                } elseif ($activePage === 'pm') {
                                    $prefix = $activeTabSettings;
                                } else {
                                    $prefix = 'global';
                                }
                                
                                $filteredColors = [];
                                foreach ($theme as $key => $value) {
                                    if ($key !== 'id' && strpos($key, $prefix) === 0) {
                                        $filteredColors[$key] = $value;
                                    }
                                }
                                
                                // If no specific colors found, show all
                                if (empty($filteredColors)) {
                                    $filteredColors = $theme;
                                    unset($filteredColors['id']);
                                }
                                ?>
                                
                                <?php foreach ($filteredColors as $key => $value): ?>
                                <div class="setting-item p-3 border border-slate-200 rounded-xl flex items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <label class="text-xs font-bold text-slate-700 block"><?php echo ucwords(str_replace('_', ' ', str_replace($prefix . '_', '', $key))); ?></label>
                                        <span class="text-[9px] text-slate-400 font-mono"><?php echo $key; ?></span>
                                    </div>
                                    <div class="color-picker-wrapper">
                                        <input type="color" name="<?php echo $key; ?>" value="<?php echo $value; ?>" 
                                               onchange="updateColorPreview(this, 'preview-<?php echo $key; ?>')">
                                        <div id="preview-<?php echo $key; ?>" class="preview-box" style="background: <?php echo $value; ?>;"></div>
                                        <span class="text-[10px] font-mono text-slate-600 w-16"><?php echo $value; ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- ===== SAVE BUTTON ===== -->
                        <div class="flex items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                            <div>
                                <p class="text-xs font-bold text-slate-700">Changes will be applied immediately</p>
                                <p class="text-[10px] text-slate-400">All portal pages will reflect these changes</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="submit" name="save_theme" class="px-6 py-2.5 bg-amber-600 text-white font-extrabold rounded-xl hover:bg-amber-700 transition text-sm flex items-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    Apply Changes
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===== LIVE PREVIEW PANEL ===== -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6 sticky top-20">
                            <div class="flex items-center gap-2 mb-4">
                                <i data-lucide="eye" class="w-4 h-4 text-indigo-600"></i>
                                <h3 class="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Live Preview</h3>
                            </div>
                            <p class="text-xs text-slate-500 mb-4">See how your colors will look on the actual page.</p>
                            
                            <div id="live-preview-container" class="preview-frame">
                                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                                    </div>
                                    <span class="text-[10px] text-slate-500">Preview</span>
                                </div>
                                <div class="p-4" id="preview-content">
                                    <!-- Dynamic preview content -->
                                    <div class="space-y-4">
                                        <!-- Card Preview -->
                                        <div class="p-4 rounded-xl border" style="background: <?php echo $theme[$activeTabSettings . '_bg'] ?? '#ffffff'; ?>; border-color: <?php echo $theme[$activeTabSettings . '_card_border'] ?? '#e2e8f0'; ?>;">
                                            <h4 class="font-bold text-sm" style="color: <?php echo $theme[$activeTabSettings . '_heading'] ?? '#1e293b'; ?>;">Sample Card Title</h4>
                                            <p class="text-xs mt-1" style="color: <?php echo $theme[$activeTabSettings . '_text'] ?? '#475569'; ?>;">This is a preview of how your colors will appear on the actual page. All elements will reflect your chosen colors.</p>
                                            <button class="mt-3 px-4 py-1.5 text-xs font-bold rounded-lg" style="background: <?php echo $theme[$activeTabSettings . '_button_bg'] ?? '#4f46e5'; ?>; color: <?php echo $theme[$activeTabSettings . '_button_text'] ?? '#ffffff'; ?>;">Sample Button</button>
                                        </div>

                                        <!-- Metric Preview -->
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="p-3 rounded-xl border" style="background: <?php echo $theme[$activeTabSettings . '_metric_bg'] ?? '#ffffff'; ?>; border-color: <?php echo $theme[$activeTabSettings . '_card_border'] ?? '#e2e8f0'; ?>;">
                                                <p class="text-[10px] font-bold uppercase" style="color: <?php echo $theme[$activeTabSettings . '_text'] ?? '#475569'; ?>;">Metric 1</p>
                                                <p class="text-lg font-black" style="color: <?php echo $theme[$activeTabSettings . '_metric_value'] ?? '#1e293b'; ?>;">1,420</p>
                                            </div>
                                            <div class="p-3 rounded-xl border" style="background: <?php echo $theme[$activeTabSettings . '_metric_bg'] ?? '#ffffff'; ?>; border-color: <?php echo $theme[$activeTabSettings . '_card_border'] ?? '#e2e8f0'; ?>;">
                                                <p class="text-[10px] font-bold uppercase" style="color: <?php echo $theme[$activeTabSettings . '_text'] ?? '#475569'; ?>;">Metric 2</p>
                                                <p class="text-lg font-black" style="color: <?php echo $theme[$activeTabSettings . '_metric_value'] ?? '#1e293b'; ?>;">65%</p>
                                            </div>
                                        </div>

                                        <!-- Status Preview -->
                                        <div class="flex gap-2 flex-wrap">
                                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full" style="background: <?php echo $theme[$activeTabSettings . '_status_todo'] ?? '#94a3b8'; ?>; color: white;">To Do</span>
                                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full" style="background: <?php echo $theme[$activeTabSettings . '_status_progress'] ?? '#f59e0b'; ?>; color: white;">In Progress</span>
                                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full" style="background: <?php echo $theme[$activeTabSettings . '_status_done'] ?? '#22c55e'; ?>; color: white;">Done</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Color Palette Quick View -->
                            <div class="mt-4 pt-4 border-t border-slate-200">
                                <p class="text-[10px] font-bold text-slate-500 uppercase mb-2">Current Color Palette</p>
                                <div class="flex gap-1 flex-wrap">
                                    <?php 
                                    $uniqueColors = array_unique(array_values($filteredColors));
                                    foreach ($uniqueColors as $color): 
                                    ?>
                                    <div class="w-6 h-6 rounded-full border border-slate-200" style="background: <?php echo $color; ?>;" title="<?php echo $color; ?>"></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </form>

        </div>
    </main>

    <!-- Security Badge -->
    <div class="security-badge">🔒 Secure Session • <?php echo $_SERVER['REMOTE_ADDR']; ?></div>
    <div id="toast-container" class="fixed top-4 right-4 z-50 pointer-events-none flex flex-col gap-2"></div>

    <script>
        lucide.createIcons();

        // ===== SIDEBAR TOGGLE =====
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
            const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000`;
            setTimeout(() => lucide.createIcons(), 100);
        }

        // ===== TOGGLE PAGE MENU =====
        function togglePage(pageKey) {
            const submenu = document.getElementById('submenu-' + pageKey);
            if (submenu) {
                submenu.classList.toggle('hidden');
                const parent = submenu.closest('.page-menu-item');
                const icon = parent.querySelector('.page-menu-item .flex .chevron-down');
                if (icon) {
                    icon.classList.toggle('rotate-180');
                }
            }
        }

        // ===== UPDATE COLOR PREVIEW =====
        function updateColorPreview(input, previewId) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.style.background = input.value;
            }
            // Update the label next to color picker
            const label = input.closest('.color-picker-wrapper').querySelector('.font-mono');
            if (label) {
                label.textContent = input.value;
            }
            // Update live preview
            updateLivePreview();
        }

        // ===== UPDATE LIVE PREVIEW =====
        function updateLivePreview() {
            const formData = new FormData(document.getElementById('settings-form'));
            const previewContent = document.getElementById('preview-content');
            
            // Get all color values from form
            const colors = {};
            for (let [key, value] of formData.entries()) {
                if (key !== 'save_theme') {
                    colors[key] = value;
                }
            }
            
            // Update preview elements with new colors
            const prefix = '<?php echo $activeTabSettings; ?>';
            const cards = previewContent.querySelectorAll('.p-4.rounded-xl, .p-3.rounded-xl');
            cards.forEach(card => {
                if (colors[prefix + '_bg']) card.style.background = colors[prefix + '_bg'];
                if (colors[prefix + '_card_border']) card.style.borderColor = colors[prefix + '_card_border'];
            });
            
            const headings = previewContent.querySelectorAll('h4');
            headings.forEach(h => {
                if (colors[prefix + '_heading']) h.style.color = colors[prefix + '_heading'];
            });
            
            const texts = previewContent.querySelectorAll('p:not(.font-black)');
            texts.forEach(t => {
                if (colors[prefix + '_text']) t.style.color = colors[prefix + '_text'];
            });
            
            const buttons = previewContent.querySelectorAll('button');
            buttons.forEach(btn => {
                if (colors[prefix + '_button_bg']) btn.style.background = colors[prefix + '_button_bg'];
                if (colors[prefix + '_button_text']) btn.style.color = colors[prefix + '_button_text'];
            });
            
            const metrics = previewContent.querySelectorAll('.p-3.rounded-xl .font-black');
            metrics.forEach(m => {
                if (colors[prefix + '_metric_value']) m.style.color = colors[prefix + '_metric_value'];
            });
            
            const statuses = previewContent.querySelectorAll('.rounded-full');
            statuses.forEach(s => {
                const text = s.textContent.trim();
                if (text === 'To Do' && colors[prefix + '_status_todo']) s.style.background = colors[prefix + '_status_todo'];
                if (text === 'In Progress' && colors[prefix + '_status_progress']) s.style.background = colors[prefix + '_status_progress'];
                if (text === 'Done' && colors[prefix + '_status_done']) s.style.background = colors[prefix + '_status_done'];
            });
        }

        // ===== TOGGLE LIVE PREVIEW =====
        function toggleLivePreview() {
            const container = document.getElementById('live-preview-container');
            const text = document.getElementById('preview-toggle-text');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                text.textContent = 'Hide Preview';
            } else {
                container.style.display = 'none';
                text.textContent = 'Show Preview';
            }
        }

        // ===== RESET ALL SETTINGS =====
        function resetAllSettings() {
            if (confirm('Are you sure you want to reset all theme settings to defaults?')) {
                window.location.href = '?reset=1';
            }
        }

        // ===== FILTER CONTENT =====
        function filterContent(val) {
            const items = document.querySelectorAll('.setting-item');
            const query = val.toLowerCase();
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
        }

        // ===== TOAST =====
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const colors = { success: 'emerald', error: 'rose', warning: 'amber', info: 'blue' };
            toast.className = `flex items-center gap-3 bg-slate-900 text-white px-5 py-4 rounded-xl shadow-2xl border border-slate-800 max-w-sm transition-all duration-300 transform translate-y-2 opacity-0`;
            toast.innerHTML = `
                <i data-lucide="${type === 'success' ? 'check-circle-2' : 'alert-circle'}" class="text-${colors[type] || 'emerald'}-400 shrink-0 w-5 h-5"></i>
                <span class="text-xs font-medium">${message}</span>
            `;
            container.appendChild(toast);
            lucide.createIcons();
            setTimeout(() => toast.classList.remove('translate-y-2', 'opacity-0'), 100);
            setTimeout(() => { toast.classList.add('translate-y-2', 'opacity-0'); setTimeout(() => toast.remove(), 300); }, 4000);
        }

        // ===== SESSION TIMEOUT WARNING =====
        let sessionTimeout;
        function resetSessionTimer() {
            clearTimeout(sessionTimeout);
            sessionTimeout = setTimeout(function() {
                showToast('Session expiring soon. Please save your work.', 'warning');
            }, 1500000);
        }
        document.addEventListener('click', resetSessionTimer);
        document.addEventListener('keydown', resetSessionTimer);
        resetSessionTimer();

        // ===== AUTO UPDATE PREVIEW ON COLOR CHANGE =====
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('input', function() {
                updateLivePreview();
            });
        });

        // ===== INITIAL LIVE PREVIEW =====
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(updateLivePreview, 500);
        });
    </script>

</body>
</html>