<?php
// =============================================
// REEL ANALYTICS DASHBOARD
// =============================================

// Start output buffering to prevent header errors
ob_start();

// =============================================
// LOAD MAIN CONFIG
// =============================================
$mainConfigPath = '/home/sites/32a/2/2fb0787974/public_html/includes/config.php';

if (!file_exists($mainConfigPath)) {
    die("Main config not found at: " . $mainConfigPath);
}

require_once $mainConfigPath;

// =============================================
// LOAD REEL ANALYTICS
// =============================================
require_once __DIR__ . '/includes/ReelAnalytics.php';

use Includes\ReelAnalytics;

$analytics = new ReelAnalytics();

// Get statistics
$stats = $analytics->getStats();
$recent = $analytics->getRecent(10);

// Calculate totals
$total = 0;
$success = 0;
$failed = 0;
$platformStats = [];

foreach ($stats as $stat) {
    if (!isset($stat['platform'])) {
        $total = $stat['total'] ?? 0;
        $success = $stat['success_count'] ?? 0;
        $failed = $stat['failed_count'] ?? 0;
    } else {
        $platformStats[$stat['platform']] = $stat['platform_count'] ?? 0;
    }
}

// =============================================
// TIME AGO FUNCTION (PHP)
// =============================================
function timeAgo($datetime) {
    if (empty($datetime)) {
        return 'Just now';
    }
    
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return 'Just now';
    }
    
    $diff = time() - $timestamp;
    
    if ($diff < 0) {
        return 'Just now';
    }
    
    $intervals = array(
        'year' => 31536000,
        'month' => 2592000,
        'week' => 604800,
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1
    );
    
    foreach ($intervals as $unit => $value) {
        $count = floor($diff / $value);
        if ($count >= 1) {
            return $count . ' ' . $unit . ($count > 1 ? 's' : '') . ' ago';
        }
    }
    
    return 'Just now';
}

$pageTitle = 'Reel Analytics Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $pageTitle; ?> | Admin Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        .recent-item .recent-stats {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .recent-item .recent-stats i {
            margin-right: 3px;
        }
        
        #refreshIndicator {
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: #1a1c26;
            color: #fff;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 999;
            display: none;
            align-items: center;
            gap: 10px;
        }
        #refreshIndicator .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse-dot 1s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        #refreshIndicator .counter {
            background: rgba(255,255,255,0.1);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
        }
        #stopRefreshBtn {
            padding: 4px 12px;
            font-size: 11px;
            background: #fee2e2;
            color: #dc3545;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 8px;
        }
        #stopRefreshBtn:hover {
            background: #dc3545;
            color: #fff;
        }
        #lastUpdated {
            text-align: center;
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 8px;
            padding: 4px;
        }

        /* ===== SYNC BUTTON STYLES ===== */
        .sync-section {
            margin: 20px 0;
            padding: 15px 20px;
            background: #1a1c26;
            border-radius: 12px;
            border: 1px solid #2d2f3a;
        }
        .sync-section .sync-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .sync-section .sync-header h3 {
            color: #e2e8f0;
            margin: 0;
            font-size: 16px;
        }
        .sync-section .sync-header p {
            color: #94a3b8;
            margin: 5px 0 0 0;
            font-size: 13px;
        }
        .sync-section .sync-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        #syncStatus {
            color: #94a3b8;
            font-size: 13px;
        }
        .btn-sync {
            padding: 10px 25px;
            background: #8b5cf6;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-sync:hover {
            background: #7c3aed;
            transform: scale(1.02);
        }
        .btn-sync.running {
            background: #ef4444;
        }
        .btn-sync.running:hover {
            background: #dc2626;
        }
        .btn-sync:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        #syncProgress {
            display: none;
            margin-top: 12px;
        }
        #syncProgress .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        #syncProgress .progress-bar {
            width: 100%;
            height: 6px;
            background: #2d2f3a;
            border-radius: 4px;
            overflow: hidden;
        }
        #syncProgress .progress-bar .bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #8b5cf6, #4a5cf5);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* ===== AUTO BUTTON ===== */
        .btn-auto {
            padding: 10px 20px;
            background: #8b5cf6;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-auto:hover {
            background: #7c3aed;
            transform: scale(1.02);
        }
        .btn-auto.running {
            background: #ef4444;
        }
        .btn-auto.running:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header class="dashboard-header">
        <div class="header-inner">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
                Reel <span>Analytics</span>
            </div>
            <div class="header-actions">
                <span class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                </span>
                <a href="../index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Portal
                </a>
                <a href="history.php" class="btn-history">
                    <i class="fas fa-history"></i> History
                </a>
                <button id="stopRefreshBtn" style="display:none;" onclick="stopAutoRefresh()">
                    <i class="fas fa-stop"></i> Stop Auto-Refresh
                </button>
                <a href="../../logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="dashboard-main">
        <div class="container">

            <!-- ===== STATS CARDS ===== -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4a5cf5;">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $total; ?></span>
                        <span class="stat-label">Total Reels</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #10b981;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $success; ?></span>
                        <span class="stat-label">Successful</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ef4444;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo $failed; ?></span>
                        <span class="stat-label">Failed</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f59e0b;">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-number"><?php echo count($platformStats); ?></span>
                        <span class="stat-label">Platforms</span>
                    </div>
                </div>
            </div>

            <!-- ===== PERMANENT SYNC SECTION ===== -->
            <div class="sync-section">
                <div class="sync-header">
                    <div>
                        <h3><i class="fas fa-sync" style="color: #8b5cf6;"></i> Auto Sync All Videos</h3>
                        <p>Automatically fetches latest data for ALL saved videos</p>
                    </div>
                    <div class="sync-actions">
                        <span id="syncStatus">⏸️ Stopped</span>
                        <button id="syncBtn" class="btn-sync" onclick="toggleSync()">
                            <i class="fas fa-play"></i> Start Sync
                        </button>
                    </div>
                </div>
                <div id="syncProgress">
                    <div class="progress-info">
                        <span id="syncProgressText">Loading...</span>
                        <span id="syncCountText">0 / 0</span>
                    </div>
                    <div class="progress-bar">
                        <div id="syncProgressBar" class="bar"></div>
                    </div>
                </div>
            </div>

            <!-- ===== URL INPUT SECTION ===== -->
            <div class="input-section">
                <div class="input-container">
                    <div class="input-wrapper">
                        <i class="fas fa-link input-icon"></i>
                        <input 
                            type="url" 
                            id="reelUrlInput" 
                            placeholder="Paste Reel URL (Instagram, Facebook, TikTok, YouTube)..." 
                            autocomplete="off"
                            spellcheck="false"
                        />
                        <button id="fetchBtn" class="btn-fetch" onclick="fetchReel()">
                            <i class="fas fa-search"></i> Fetch
                        </button>
                    </div>
                    <div class="input-hint">
                        <i class="fas fa-info-circle"></i>
                        Press Enter or click Fetch to analyze the reel
                    </div>
                </div>
                
                <!-- ===== PLATFORM TAGS ===== -->
                <div class="platform-tags">
                    <span class="platform-tag instagram"><i class="fab fa-instagram"></i> Instagram</span>
                    <span class="platform-tag facebook"><i class="fab fa-facebook"></i> Facebook</span>
                    <span class="platform-tag tiktok"><i class="fab fa-tiktok"></i> TikTok</span>
                    <span class="platform-tag youtube"><i class="fab fa-youtube"></i> YouTube</span>
                </div>
            </div>

            <!-- ===== LOADING SPINNER ===== -->
            <div id="loadingSpinner" class="loading-spinner" style="display:none;">
                <div class="spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
                <p>Fetching reel analytics...</p>
            </div>

            <!-- ===== RESULTS SECTION ===== -->
            <div id="resultsSection" class="results-section" style="display:none;">
                <div id="errorMessage" class="alert alert-danger" style="display:none;"></div>
                <div id="successMessage" class="alert alert-success" style="display:none;"></div>

                <div class="analytics-grid" id="analyticsGrid">
                    <!-- Platform Card -->
                    <div class="analytics-card platform-card">
                        <div class="card-icon" id="platformIcon">
                            <i class="fab fa-tiktok"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Platform</span>
                            <span class="card-value" id="platformName">TikTok</span>
                        </div>
                    </div>

                    <!-- Profile Card -->
                    <div class="analytics-card profile-card">
                        <div class="profile-info">
                            <img id="profilePicture" src="" alt="Profile" class="profile-avatar" />
                            <div class="profile-details">
                                <span class="profile-name" id="profileName">Username</span>
                                <span class="profile-username" id="profileUsername">@username</span>
                                <span class="profile-followers" id="profileFollowers">
                                    <i class="fas fa-users"></i> 0 followers
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Views -->
                    <div class="analytics-card">
                        <div class="card-icon" style="background:#4a5cf5;">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Views</span>
                            <span class="card-value" id="views">0</span>
                        </div>
                    </div>

                    <!-- Likes -->
                    <div class="analytics-card">
                        <div class="card-icon" style="background:#10b981;">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Likes</span>
                            <span class="card-value" id="likes">0</span>
                        </div>
                    </div>

                    <!-- Comments -->
                    <div class="analytics-card">
                        <div class="card-icon" style="background:#f59e0b;">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Comments</span>
                            <span class="card-value" id="comments">0</span>
                        </div>
                    </div>

                    <!-- Shares -->
                    <div class="analytics-card">
                        <div class="card-icon" style="background:#8b5cf6;">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Shares</span>
                            <span class="card-value" id="shares">0</span>
                        </div>
                    </div>

                    <!-- Duration -->
                    <div class="analytics-card">
                        <div class="card-icon" style="background:#ec4899;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Duration</span>
                            <span class="card-value" id="duration">N/A</span>
                        </div>
                    </div>

                    <!-- Upload Date -->
                    <div class="analytics-card">
                        <div class="card-icon" style="background:#14b8a6;">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Upload Date</span>
                            <span class="card-value" id="uploadDate">N/A</span>
                        </div>
                    </div>

                    <!-- Video ID -->
                    <div class="analytics-card">
                        <div class="card-icon" style="background:#6366f1;">
                            <i class="fas fa-hashtag"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Video ID</span>
                            <span class="card-value" id="videoId">N/A</span>
                        </div>
                    </div>

                    <!-- Caption -->
                    <div class="analytics-card full-width">
                        <div class="card-icon" style="background:#64748b;">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Caption</span>
                            <span class="card-value" id="caption">No caption available</span>
                        </div>
                    </div>

                    <!-- Thumbnail -->
                    <div class="analytics-card full-width">
                        <div class="card-icon" style="background:#475569;">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-label">Thumbnail</span>
                            <div id="thumbnailContainer" style="margin-top:10px;">
                                <img id="thumbnailImage" src="" alt="Thumbnail" style="max-width:200px;border-radius:8px;display:none;" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn-save" onclick="saveCurrentData()">
                        <i class="fas fa-save"></i> Save to Database
                    </button>
                    <button class="btn-export" onclick="exportData()">
                        <i class="fas fa-file-export"></i> Export JSON
                    </button>
                    <button class="btn-clear" onclick="clearResults()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                    <button id="autoBtn" class="btn-auto" onclick="toggleAutomation()">
                        <i class="fas fa-play"></i> Start Automation
                    </button>
                </div>
            </div>

            <!-- ===== RECENT ACTIVITY ===== -->
            <div class="recent-section">
                <div class="section-header">
                    <h3><i class="fas fa-clock"></i> Recent Activity</h3>
                    <a href="history.php" class="view-all">View All →</a>
                </div>
                <div class="recent-list">
                    <?php if (!empty($recent)): ?>
                        <?php foreach ($recent as $item): ?>
                            <div class="recent-item">
                                <div class="recent-icon platform-<?php echo $item['platform']; ?>">
                                    <i class="fab fa-<?php echo $item['platform']; ?>"></i>
                                </div>
                                <div class="recent-info">
                                    <span class="recent-title">
                                        <?php echo htmlspecialchars($item['profile_name'] ?? $item['username'] ?? 'Unknown'); ?>
                                    </span>
                                    <span class="recent-url">
                                        <?php echo htmlspecialchars(substr($item['reel_url'] ?? '', 0, 50)) . '...'; ?>
                                    </span>
                                </div>
                                <div class="recent-stats">
                                    <span><i class="fas fa-heart"></i> <?php echo number_format($item['likes'] ?? 0); ?></span>
                                    <span><i class="fas fa-eye"></i> <?php echo number_format($item['views'] ?? 0); ?></span>
                                </div>
                                <span class="recent-time"><?php echo timeAgo($item['fetch_date'] ?? $item['created_at']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No recent activity. Start by fetching a reel!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- ===== AUTO-REFRESH INDICATOR ===== -->
    <div id="refreshIndicator">
        <span class="dot"></span>
        <span>Live Auto-Refresh</span>
        <span class="counter" id="refreshCounter">#0</span>
    </div>

    <!-- ===== TOAST CONTAINER ===== -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
    console.log('🔥 Complete Script Loaded!');

    // =============================================
    // VARIABLES
    // =============================================
    var refreshInterval = null;
    var refreshCount = 0;
    var currentUrl = '';
    var allUrls = [];
    var currentIndex = 0;
    var isAutoMode = false;

    // Sync Variables
    var isSyncing = false;
    var syncUrls = [];
    var syncIndex = 0;
    var syncTotal = 0;
    var syncUpdated = 0;
    var syncFailed = 0;

    // =============================================
    // TOGGLE SYNC
    // =============================================
    function toggleSync() {
        var btn = document.getElementById('syncBtn');
        var status = document.getElementById('syncStatus');
        var progress = document.getElementById('syncProgress');
        
        if (isSyncing) {
            isSyncing = false;
            if (syncInterval) {
                clearInterval(syncInterval);
                syncInterval = null;
            }
            btn.innerHTML = '<i class="fas fa-play"></i> Start Sync';
            btn.className = 'btn-sync';
            status.textContent = '⏸️ Stopped';
            status.style.color = '#94a3b8';
            showToast('⏹️ Sync stopped', 'info');
            progress.style.display = 'none';
        } else {
            startSync();
        }
    }

    // =============================================
    // START SYNC
    // =============================================
    function startSync() {
        var btn = document.getElementById('syncBtn');
        var status = document.getElementById('syncStatus');
        var progress = document.getElementById('syncProgress');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        status.textContent = '📥 Loading history...';
        progress.style.display = 'block';
        
        fetch('ajax/get-history-urls.php')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.urls.length > 0) {
                syncUrls = data.urls;
                syncTotal = data.urls.length;
                syncIndex = 0;
                syncUpdated = 0;
                syncFailed = 0;
                isSyncing = true;
                
                btn.innerHTML = '<i class="fas fa-stop"></i> Stop Sync';
                btn.className = 'btn-sync running';
                btn.disabled = false;
                status.textContent = '🔄 Syncing...';
                status.style.color = '#8b5cf6';
                
                showToast('🔄 Sync started for ' + syncTotal + ' videos', 'success');
                updateSyncProgress();
                processNextSync();
            } else {
                btn.innerHTML = '<i class="fas fa-play"></i> Start Sync';
                btn.className = 'btn-sync';
                btn.disabled = false;
                status.textContent = '❌ No videos found';
                status.style.color = '#ef4444';
                progress.style.display = 'none';
                showToast('❌ No videos in history', 'error');
            }
        })
        .catch(function(error) {
            btn.innerHTML = '<i class="fas fa-play"></i> Start Sync';
            btn.className = 'btn-sync';
            btn.disabled = false;
            status.textContent = '❌ Error loading';
            status.style.color = '#ef4444';
            progress.style.display = 'none';
            showToast('❌ Error: ' + error.message, 'error');
        });
    }

    // =============================================
    // PROCESS NEXT SYNC
    // =============================================
    function processNextSync() {
        if (!isSyncing || syncIndex >= syncUrls.length) {
            completeSync();
            return;
        }
        
        var url = syncUrls[syncIndex];
        syncIndex++;
        
        updateSyncProgress();
        
        var urlInput = document.getElementById('reelUrlInput');
        if (urlInput) {
            urlInput.value = url;
        }
        
        fetch('ajax/fetch-reel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'url=' + encodeURIComponent(url) + '&save=true'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                syncUpdated++;
                console.log('✅ Synced: ' + url);
                if (data.data) {
                    updateDisplay(data.data);
                    window.currentData = data.data;
                    var results = document.getElementById('resultsSection');
                    if (results) results.style.display = 'block';
                }
            } else {
                syncFailed++;
                console.warn('⚠️ Failed: ' + url);
            }
            updateSyncProgress();
            setTimeout(processNextSync, 1000);
        })
        .catch(function(error) {
            syncFailed++;
            console.error('❌ Error:', error);
            updateSyncProgress();
            setTimeout(processNextSync, 1500);
        });
    }

    // =============================================
    // UPDATE SYNC PROGRESS
    // =============================================
    function updateSyncProgress() {
        var progressBar = document.getElementById('syncProgressBar');
        var progressText = document.getElementById('syncProgressText');
        var countText = document.getElementById('syncCountText');
        var status = document.getElementById('syncStatus');
        
        var percent = syncTotal > 0 ? (syncIndex / syncTotal) * 100 : 0;
        
        if (progressBar) progressBar.style.width = Math.min(percent, 100) + '%';
        if (progressText) {
            progressText.textContent = '🔄 Processing ' + syncIndex + ' of ' + syncTotal;
        }
        if (countText) {
            countText.textContent = '✅ ' + syncUpdated + ' | ❌ ' + syncFailed;
        }
        if (status) {
            status.textContent = '🔄 ' + Math.round(percent) + '% done';
        }
    }

    // =============================================
    // COMPLETE SYNC
    // =============================================
    function completeSync() {
        isSyncing = false;
        if (syncInterval) {
            clearInterval(syncInterval);
            syncInterval = null;
        }
        
        var btn = document.getElementById('syncBtn');
        var status = document.getElementById('syncStatus');
        var progress = document.getElementById('syncProgress');
        
        btn.innerHTML = '<i class="fas fa-play"></i> Start Sync';
        btn.className = 'btn-sync';
        btn.disabled = false;
        
        var msg = '✅ Sync complete! ' + syncUpdated + ' updated, ' + syncFailed + ' failed';
        status.textContent = msg;
        status.style.color = syncUpdated > 0 ? '#10b981' : '#ef4444';
        
        showToast(msg, syncUpdated > 0 ? 'success' : 'error');
        
        setTimeout(function() {
            progress.style.display = 'none';
        }, 3000);
        
        setTimeout(function() {
            location.reload();
        }, 2000);
    }

    // =============================================
    // TOGGLE AUTOMATION
    // =============================================
    function toggleAutomation() {
        var btn = document.getElementById('autoBtn');
        
        if (isAutoMode) {
            stopAutomation();
            btn.innerHTML = '<i class="fas fa-play"></i> Start Automation';
            btn.className = 'btn-auto';
            showToast('⏹️ Automation stopped', 'info');
        } else {
            startAutomation();
            btn.innerHTML = '<i class="fas fa-stop"></i> Stop Automation';
            btn.className = 'btn-auto running';
            showToast('🔄 Automation starting...', 'info');
        }
    }

    // =============================================
    // START AUTOMATION
    // =============================================
    function startAutomation() {
        if (isAutoMode) {
            showToast('⏹️ Automation already running', 'info');
            return;
        }
        
        showToast('🔄 Loading all history links...', 'info');
        
        fetch('ajax/get-history-urls.php')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.urls.length > 0) {
                allUrls = data.urls;
                currentIndex = 0;
                isAutoMode = true;
                showToast('🔄 Automation started for ' + allUrls.length + ' videos', 'success');
                console.log('📋 URLs loaded:', allUrls.length);
                processNextUrl();
            } else {
                showToast('❌ No URLs found in history', 'error');
            }
        })
        .catch(function(error) {
            showToast('❌ Error loading URLs: ' + error.message, 'error');
        });
    }

    // =============================================
    // PROCESS NEXT URL (Automation)
    // =============================================
    function processNextUrl() {
        if (!isAutoMode || currentIndex >= allUrls.length) {
            if (isAutoMode && allUrls.length > 0) {
                currentIndex = 0;
                showToast('🔄 Cycle complete! Starting again...', 'info');
                setTimeout(processNextUrl, 3000);
            }
            return;
        }

        var url = allUrls[currentIndex];
        currentUrl = url;
        currentIndex++;
        
        console.log('🔄 Processing URL ' + currentIndex + '/' + allUrls.length + ': ' + url);
        showToast('📊 Updating: ' + url.substring(0, 50) + '...', 'info');
        
        var urlInput = document.getElementById('reelUrlInput');
        if (urlInput) {
            urlInput.value = url;
        }
        
        fetch('ajax/fetch-reel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'url=' + encodeURIComponent(url) + '&save=true'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                console.log('✅ Updated: ' + url);
                if (data.data) {
                    updateDisplay(data.data);
                    window.currentData = data.data;
                    var results = document.getElementById('resultsSection');
                    if (results) results.style.display = 'block';
                }
                checkAndNotify(data.data);
            } else {
                console.warn('⚠️ Failed to update: ' + url);
            }
            setTimeout(processNextUrl, 2000);
        })
        .catch(function(error) {
            console.error('❌ Error processing URL:', error);
            setTimeout(processNextUrl, 3000);
        });
    }

    // =============================================
    // STOP AUTOMATION
    // =============================================
    function stopAutomation() {
        isAutoMode = false;
        console.log('⏹️ Automation stopped');
    }

    // =============================================
    // CHECK AND NOTIFY
    // =============================================
    function checkAndNotify(data) {
        if (!data) return;
        
        var oldViews = window._lastViews || 0;
        var oldLikes = window._lastLikes || 0;
        var newViews = data.views || 0;
        var newLikes = data.likes || 0;
        
        if (newViews > oldViews) {
            showToast('📈 Views increased: ' + formatNumber(oldViews) + ' → ' + formatNumber(newViews), 'info');
        }
        if (newLikes > oldLikes) {
            showToast('❤️ Likes increased: ' + formatNumber(oldLikes) + ' → ' + formatNumber(newLikes), 'info');
        }
        
        window._lastViews = newViews;
        window._lastLikes = newLikes;
    }

    // =============================================
    // FETCH REEL
    // =============================================
    function fetchReel() {
        console.log('🔥 fetchReel() called!');
        
        var urlInput = document.getElementById('reelUrlInput');
        if (!urlInput) {
            alert('Error: Input field not found!');
            return;
        }
        
        var url = urlInput.value.trim();
        if (!url) {
            alert('Please enter a reel URL');
            return;
        }
        
        currentUrl = url;
        console.log('🔗 URL:', url);
        
        var loading = document.getElementById('loadingSpinner');
        var results = document.getElementById('resultsSection');
        if (loading) loading.style.display = 'block';
        if (results) results.style.display = 'none';
        
        var errorMsg = document.getElementById('errorMessage');
        if (errorMsg) {
            errorMsg.style.display = 'none';
            errorMsg.textContent = '';
        }
        
        fetch('ajax/fetch-reel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'url=' + encodeURIComponent(url)
        })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(function(data) {
            console.log('📦 Data:', data);
            if (loading) loading.style.display = 'none';
            
            if (data.success) {
                displayResults(data.data);
                showToast('✅ Data fetched successfully!', 'success');
                saveToHistory(url);
            } else {
                showToast('❌ ' + (data.message || 'Failed'), 'error');
                if (errorMsg) {
                    errorMsg.textContent = data.message || 'Unknown error';
                    errorMsg.style.display = 'block';
                }
            }
        })
        .catch(function(error) {
            console.error('❌ Error:', error);
            if (loading) loading.style.display = 'none';
            showToast('❌ Network error: ' + error.message, 'error');
            if (errorMsg) {
                errorMsg.textContent = 'Network error: ' + error.message;
                errorMsg.style.display = 'block';
            }
        });
    }

    // =============================================
    // SAVE TO HISTORY
    // =============================================
    function saveToHistory(url) {
        fetch('ajax/fetch-reel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'url=' + encodeURIComponent(url) + '&save=true'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                console.log('✅ Saved to history:', url);
                setTimeout(function() { location.reload(); }, 1000);
            }
        })
        .catch(function(err) { console.error('Save error:', err); });
    }

    // =============================================
    // START AUTO-REFRESH
    // =============================================
    function startAutoRefresh(url) {
        stopAutoRefresh();
        currentUrl = url;
        console.log('🔄 Auto-refresh started for:', url);
        
        var indicator = document.getElementById('refreshIndicator');
        if (indicator) indicator.style.display = 'flex';
        
        refreshCount = 0;
        updateRefreshCounter();
        
        refreshInterval = setInterval(function() {
            refreshCount++;
            updateRefreshCounter();
            console.log('🔄 Auto-refresh #' + refreshCount);
            
            fetch('ajax/fetch-reel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'url=' + encodeURIComponent(url) + '&save=true'
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    updateDisplay(data.data);
                    checkAndNotify(data.data);
                    window.currentData = data.data;
                    if (refreshCount % 5 === 0) {
                        location.reload();
                    }
                }
            })
            .catch(function(err) { console.error('Auto-refresh error:', err); });
        }, 1500);
    }

    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
            console.log('⏹️ Auto-refresh stopped');
        }
        var indicator = document.getElementById('refreshIndicator');
        if (indicator) indicator.style.display = 'none';
    }

    function updateRefreshCounter() {
        var counter = document.getElementById('refreshCounter');
        if (counter) counter.textContent = '#' + refreshCount;
    }

    // =============================================
    // UPDATE DISPLAY
    // =============================================
    function updateDisplay(data) {
        console.log('📊 Updating display:', data);
        
        var views = document.getElementById('views');
        if (views) {
            var newViews = data.views || 0;
            views.textContent = formatNumber(newViews);
            if (newViews > window._lastViews) {
                views.style.color = '#10b981';
                setTimeout(function() { views.style.color = ''; }, 1000);
            }
        }
        
        var likes = document.getElementById('likes');
        if (likes) {
            var newLikes = data.likes || 0;
            likes.textContent = formatNumber(newLikes);
            if (newLikes > window._lastLikes) {
                likes.style.color = '#10b981';
                setTimeout(function() { likes.style.color = ''; }, 1000);
            }
        }
        
        var comments = document.getElementById('comments');
        if (comments) comments.textContent = formatNumber(data.comments || 0);
        
        var shares = document.getElementById('shares');
        if (shares) shares.textContent = formatNumber(data.shares || 0);
        
        window._lastViews = data.views || 0;
        window._lastLikes = data.likes || 0;
        window.currentData = data;
    }

    // =============================================
    // DISPLAY RESULTS
    // =============================================
    function displayResults(data) {
        console.log('📊 Displaying results:', data);
        
        var results = document.getElementById('resultsSection');
        if (results) results.style.display = 'block';
        
        window.currentData = data;
        window._lastViews = data.views || 0;
        window._lastLikes = data.likes || 0;
        
        var platformIcons = {
            'tiktok': 'fab fa-tiktok',
            'instagram': 'fab fa-instagram',
            'facebook': 'fab fa-facebook',
            'youtube': 'fab fa-youtube'
        };
        
        var platformIcon = document.getElementById('platformIcon');
        if (platformIcon) {
            platformIcon.innerHTML = '<i class="' + (platformIcons[data.platform] || 'fas fa-link') + '"></i>';
        }
        
        var platformName = document.getElementById('platformName');
        if (platformName) {
            platformName.textContent = data.platform ? data.platform.charAt(0).toUpperCase() + data.platform.slice(1) : 'Unknown';
        }
        
        var profilePic = document.getElementById('profilePicture');
        if (profilePic) {
            profilePic.src = data.profile_picture || 'https://i.pravatar.cc/150?img=' + Math.floor(Math.random() * 70);
        }
        
        var profileName = document.getElementById('profileName');
        if (profileName) profileName.textContent = data.profile_name || data.username || 'Unknown';
        
        var profileUsername = document.getElementById('profileUsername');
        if (profileUsername) profileUsername.textContent = '@' + (data.username || 'unknown');
        
        var profileFollowers = document.getElementById('profileFollowers');
        if (profileFollowers) {
            profileFollowers.innerHTML = '<i class="fas fa-users"></i> ' + formatNumber(data.followers || 0) + ' followers';
        }
        
        var views = document.getElementById('views');
        if (views) views.textContent = formatNumber(data.views || 0);
        
        var likes = document.getElementById('likes');
        if (likes) likes.textContent = formatNumber(data.likes || 0);
        
        var comments = document.getElementById('comments');
        if (comments) comments.textContent = formatNumber(data.comments || 0);
        
        var shares = document.getElementById('shares');
        if (shares) shares.textContent = formatNumber(data.shares || 0);
        
        var duration = document.getElementById('duration');
        if (duration) duration.textContent = data.duration || 'N/A';
        
        var uploadDate = document.getElementById('uploadDate');
        if (uploadDate) {
            if (data.upload_date) {
                var date = new Date(data.upload_date);
                uploadDate.textContent = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            } else {
                uploadDate.textContent = 'N/A';
            }
        }
        
        var videoId = document.getElementById('videoId');
        if (videoId) videoId.textContent = data.video_id || 'N/A';
        
        var caption = document.getElementById('caption');
        if (caption) caption.textContent = data.caption || 'No caption available';
        
        var thumbnailImg = document.getElementById('thumbnailImage');
        if (thumbnailImg && data.thumbnail_url) {
            thumbnailImg.src = data.thumbnail_url;
            thumbnailImg.style.display = 'block';
        }
        
        if (results) results.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // =============================================
    // UTILITY FUNCTIONS
    // =============================================
    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return num.toString();
    }

    function showToast(message, type) {
        type = type || 'info';
        
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = '<span>' + message + '</span>';
        container.appendChild(toast);
        
        setTimeout(function() {
            toast.classList.add('fade-out');
            setTimeout(function() { toast.remove(); }, 300);
        }, 5000);
    }

    function saveCurrentData() {
        if (!window.currentData) {
            showToast('No data to save. Fetch a reel first!', 'error');
            return;
        }
        var data = window.currentData;
        var urlInput = document.getElementById('reelUrlInput');
        data.reel_url = urlInput ? urlInput.value.trim() : '';
        
        if (!data.reel_url) {
            showToast('No URL found. Please fetch again!', 'error');
            return;
        }
        
        showToast('Saving to database...', 'info');
        
        fetch('ajax/fetch-reel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'url=' + encodeURIComponent(data.reel_url) + '&save=true'
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                showToast('✅ ' + result.message, 'success');
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                showToast('❌ ' + (result.message || 'Failed to save'), 'error');
            }
        })
        .catch(function(error) {
            showToast('❌ Network error: ' + error.message, 'error');
        });
    }

    function exportData() {
        if (!window.currentData) {
            showToast('No data to export. Fetch a reel first!', 'error');
            return;
        }
        var data = window.currentData;
        var blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'reel-data-' + (data.video_id || Date.now()) + '.json';
        a.click();
        URL.revokeObjectURL(url);
        showToast('✅ Data exported successfully!', 'success');
    }

    function clearResults() {
        stopAutoRefresh();
        document.getElementById('resultsSection').style.display = 'none';
        document.getElementById('reelUrlInput').value = '';
        window.currentData = null;
        window._lastViews = 0;
        window._lastLikes = 0;
        
        var thumb = document.getElementById('thumbnailImage');
        if (thumb) {
            thumb.src = '';
            thumb.style.display = 'none';
        }
        showToast('Cleared', 'info');
    }

    // =============================================
    // DOM READY
    // =============================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('📄 DOM loaded!');
        
        var input = document.getElementById('reelUrlInput');
        if (input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    fetchReel();
                }
            });
        }
        
        var fetchBtn = document.getElementById('fetchBtn');
        if (fetchBtn) {
            fetchBtn.disabled = false;
            fetchBtn.style.pointerEvents = 'auto';
            fetchBtn.style.opacity = '1';
            fetchBtn.style.cursor = 'pointer';
            fetchBtn.onclick = function(e) {
                e.preventDefault();
                fetchReel();
            };
        }
        
        // Toast styles
        if (!document.getElementById('toastStyles')) {
            var style = document.createElement('style');
            style.id = 'toastStyles';
            style.textContent = `
                .toast-container {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }
                .toast {
                    padding: 12px 20px;
                    border-radius: 10px;
                    color: #fff;
                    font-size: 14px;
                    font-weight: 500;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                    animation: slideIn 0.3s ease;
                    max-width: 420px;
                }
                .toast-success { background: #10b981; }
                .toast-error { background: #ef4444; }
                .toast-info { background: #4a5cf5; }
                .toast.fade-out {
                    opacity: 0;
                    transform: translateX(50px);
                    transition: all 0.4s ease;
                }
                @keyframes slideIn {
                    from { opacity: 0; transform: translateX(50px); }
                    to { opacity: 1; transform: translateX(0); }
                }
            `;
            document.head.appendChild(style);
        }
    });
    
    console.log('✅ Complete Script Loaded!');
    </script>
</body>
</html>