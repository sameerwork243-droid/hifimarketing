<?php
// =============================================
// REEL ANALYTICS - HISTORY PAGE
// =============================================

require_once __DIR__ . '/includes/init.php';
requireAdmin();

use Includes\ReelAnalytics;

$analytics = new ReelAnalytics();

// ===== GET FILTERS =====
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$platform = isset($_GET['platform']) ? $_GET['platform'] : '';

// ===== GET HISTORY DATA =====
$history = $analytics->getHistory($page, $limit, $search, $platform);

// ===== GET STATS =====
$stats = $analytics->getStats();

$total = 0;
$success = 0;
$failed = 0;

foreach ($stats as $stat) {
    if ($stat['platform'] === null) {
        $total = $stat['total'] ?? 0;
        $success = $stat['success_count'] ?? 0;
        $failed = $stat['failed_count'] ?? 0;
    }
}

$pageTitle = 'Reel Analytics History';
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
        /* ===== HISTORY SPECIFIC STYLES ===== */
        .history-filters {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            box-shadow: var(--shadow);
        }
        
        .history-filters .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 200px;
        }
        
        .history-filters .filter-group input,
        .history-filters .filter-group select {
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            transition: var(--transition);
            flex: 1;
        }
        
        .history-filters .filter-group input:focus,
        .history-filters .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74,92,245,0.1);
        }
        
        .history-filters .btn-filter {
            padding: 8px 20px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .history-filters .btn-filter:hover {
            background: var(--primary-dark);
        }
        
        .history-filters .btn-reset {
            padding: 8px 16px;
            background: var(--bg);
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .history-filters .btn-reset:hover {
            background: var(--border);
        }
        
        .history-filters .btn-export-csv {
            padding: 8px 20px;
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .history-filters .btn-export-csv:hover {
            background: #059669;
        }
        
        .history-filters .btn-delete-all {
            padding: 8px 20px;
            background: #ef4444;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .history-filters .btn-delete-all:hover {
            background: #dc2626;
        }
        
        /* ===== TABLE ===== */
        .table-wrapper {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .table-scroll {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 900px;
        }
        
        table th {
            background: #f8fafc;
            text-align: left;
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        table td {
            padding: 12px 16px;
            font-size: 13px;
            color: var(--text-secondary);
            border-bottom: 1px solid #f0f2f5;
            vertical-align: middle;
        }
        
        table tr:hover td {
            background: #f8fafc;
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        .platform-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .platform-badge.instagram {
            background: #fef3f7;
            color: #E4405F;
        }
        .platform-badge.facebook {
            background: #eaf3ff;
            color: #1877F2;
        }
        .platform-badge.tiktok {
            background: #f5f5f5;
            color: #000000;
        }
        .platform-badge.youtube {
            background: #fef2f2;
            color: #FF0000;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.failed {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-view {
            padding: 4px 12px;
            background: var(--primary-light);
            color: var(--primary);
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-view:hover {
            background: var(--primary);
            color: #fff;
        }
        
        .btn-delete {
            padding: 4px 10px;
            background: #fee2e2;
            color: #dc3545;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-delete:hover {
            background: #dc3545;
            color: #fff;
        }
        
        /* ===== PAGINATION ===== */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
        }
        
        .pagination a,
        .pagination span {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: var(--transition);
            border: 1px solid var(--border);
            background: var(--card-bg);
            color: var(--text-secondary);
        }
        
        .pagination a:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .pagination .active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination .info {
            border: none;
            background: transparent;
            color: var(--text-muted);
        }
        
        /* ===== THUMBNAIL IN TABLE ===== */
        .thumb-small {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--border);
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .history-filters {
                flex-direction: column;
                align-items: stretch;
            }
            .history-filters .filter-group {
                min-width: auto;
            }
            .history-filters .btn-filter,
            .history-filters .btn-reset,
            .history-filters .btn-export-csv,
            .history-filters .btn-delete-all {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header class="dashboard-header">
        <div class="header-inner">
            <div class="logo">
                <i class="fas fa-history"></i>
                Reel <span>History</span>
            </div>
            <div class="header-actions">
                <span class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                </span>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
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
                        <span class="stat-number"><?php echo $history['total']; ?></span>
                        <span class="stat-label">Showing</span>
                    </div>
                </div>
            </div>

            <!-- ===== FILTERS ===== -->
            <div class="history-filters">
                <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;width:100%;">
                    <div class="filter-group">
                        <i class="fas fa-search" style="color:var(--text-muted);"></i>
                        <input type="text" name="search" placeholder="Search by username, URL, caption..." value="<?php echo htmlspecialchars($search); ?>" />
                    </div>
                    
                    <div class="filter-group">
                        <i class="fas fa-filter" style="color:var(--text-muted);"></i>
                        <select name="platform">
                            <option value="">All Platforms</option>
                            <option value="instagram" <?php echo $platform === 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                            <option value="facebook" <?php echo $platform === 'facebook' ? 'selected' : ''; ?>>Facebook</option>
                            <option value="tiktok" <?php echo $platform === 'tiktok' ? 'selected' : ''; ?>>TikTok</option>
                            <option value="youtube" <?php echo $platform === 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    
                    <a href="history.php" class="btn-reset">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                    
                    <button type="button" class="btn-export-csv" onclick="exportCSV()">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                    
                    <button type="button" class="btn-delete-all" onclick="deleteAll()">
                        <i class="fas fa-trash"></i> Delete All
                    </button>
                </form>
            </div>

            <!-- ===== TABLE ===== -->
            <div class="table-wrapper">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Platform</th>
                                <th>Profile</th>
                                <th>Thumbnail</th>
                                <th>Caption</th>
                                <th>Likes</th>
                                <th>Views</th>
                                <th>Status</th>
                                <th>Fetched</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($history['data'])): ?>
                                <?php $counter = (($page - 1) * $limit) + 1; ?>
                                <?php foreach ($history['data'] as $row): ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <span class="platform-badge <?php echo $row['platform']; ?>">
                                                <i class="fab fa-<?php echo $row['platform']; ?>"></i>
                                                <?php echo ucfirst($row['platform']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['profile_name'] ?? $row['username'] ?? 'Unknown'); ?></strong>
                                            <br>
                                            <span style="font-size:11px;color:var(--text-muted);">
                                                @<?php echo htmlspecialchars($row['username'] ?? 'unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['thumbnail_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($row['thumbnail_url']); ?>" class="thumb-small" alt="Thumbnail" onerror="this.style.display='none'" />
                                            <?php else: ?>
                                                <span style="color:var(--text-muted);font-size:11px;">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width:150px;word-break:break-word;">
                                            <?php echo htmlspecialchars(substr($row['caption'] ?? '', 0, 50)); ?>
                                            <?php if (strlen($row['caption'] ?? '') > 50): ?>...<?php endif; ?>
                                        </td>
                                        <td><?php echo number_format($row['likes'] ?? 0); ?></td>
                                        <td><?php echo number_format($row['views'] ?? 0); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $row['status']; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size:12px;color:var(--text-muted);">
                                            <?php echo date('M d, Y', strtotime($row['fetch_date'] ?? $row['created_at'])); ?>
                                            <br>
                                            <small><?php echo date('h:i A', strtotime($row['fetch_date'] ?? $row['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <button class="btn-view" onclick="viewDetails(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-delete" onclick="deleteRecord(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align:center;padding:60px 20px;color:var(--text-muted);">
                                        <i class="fas fa-inbox" style="font-size:40px;display:block;margin-bottom:12px;"></i>
                                        <p style="font-size:16px;font-weight:600;color:var(--text-primary);">No records found</p>
                                        <p style="font-size:14px;">Start fetching reels to see them here.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- ===== PAGINATION ===== -->
                <?php if ($history['totalPages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&platform=<?php echo urlencode($platform); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $history['totalPages']; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php elseif ($i == 1 || $i == $history['totalPages'] || abs($i - $page) <= 2): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&platform=<?php echo urlencode($platform); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == 2 || $i == $history['totalPages'] - 1): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $history['totalPages']): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&platform=<?php echo urlencode($platform); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                        
                        <span class="info">
                            Showing <?php echo count($history['data']); ?> of <?php echo $history['total']; ?> records
                        </span>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- ===== DETAIL MODAL ===== -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal">
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            <h2>Reel Details</h2>
            <div id="modalContent">
                <!-- Dynamically populated -->
            </div>
        </div>
    </div>

    <!-- ===== TOAST CONTAINER ===== -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        // =============================================
        // VIEW DETAILS
        // =============================================
        function viewDetails(id) {
            const modal = document.getElementById('detailModal');
            const content = document.getElementById('modalContent');
            
            content.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:32px;color:var(--primary);"></i><p style="margin-top:12px;">Loading...</p></div>';
            modal.classList.add('show');
            
            fetch(`ajax/get-details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        content.innerHTML = `<div style="color:var(--danger);text-align:center;padding:20px;">${data.error}</div>`;
                        return;
                    }
                    
                    let html = `
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Platform</span>
                                <span class="detail-value"><span class="platform-badge ${data.platform}"><i class="fab fa-${data.platform}"></i> ${data.platform}</span></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Profile</span>
                                <span class="detail-value"><strong>${data.profile_name || 'Unknown'}</strong> (@${data.username || 'unknown'})</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Followers</span>
                                <span class="detail-value">${formatNumber(data.followers)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Video ID</span>
                                <span class="detail-value" style="font-size:12px;">${data.video_id || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">URL</span>
                                <span class="detail-value" style="font-size:12px;word-break:break-all;">
                                    <a href="${data.reel_url}" target="_blank" style="color:var(--primary);">${data.reel_url}</a>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value"><span class="status-badge ${data.status}">${data.status}</span></span>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-label">Caption</span>
                                <span class="detail-value">${data.caption || 'No caption'}</span>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-label">Stats</span>
                                <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:4px;">
                                    <span><i class="fas fa-eye" style="color:var(--primary);"></i> ${formatNumber(data.views)} views</span>
                                    <span><i class="fas fa-heart" style="color:#ec4899;"></i> ${formatNumber(data.likes)} likes</span>
                                    <span><i class="fas fa-comment" style="color:#f59e0b;"></i> ${formatNumber(data.comments)} comments</span>
                                    <span><i class="fas fa-share-alt" style="color:#8b5cf6;"></i> ${formatNumber(data.shares)} shares</span>
                                    <span><i class="fas fa-clock" style="color:#14b8a6;"></i> ${data.duration || 'N/A'}</span>
                                </div>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-label">Fetched</span>
                                <span class="detail-value">${new Date(data.fetch_date || data.created_at).toLocaleString()}</span>
                            </div>
                        </div>
                    `;
                    
                    content.innerHTML = html;
                })
                .catch(error => {
                    content.innerHTML = `<div style="color:var(--danger);text-align:center;padding:20px;">Error loading details</div>`;
                });
        }
        
        // =============================================
        // DELETE RECORD
        // =============================================
        function deleteRecord(id) {
            if (!confirm('Are you sure you want to delete this record?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'delete');
            
            fetch('ajax/delete-record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Record deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Failed to delete record', 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting record', 'error');
            });
        }
        
        // =============================================
        // DELETE ALL
        // =============================================
        function deleteAll() {
            if (!confirm('Are you sure you want to delete ALL records? This cannot be undone!')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_all');
            
            fetch('ajax/delete-record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('All records deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Failed to delete records', 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting records', 'error');
            });
        }
        
        // =============================================
        // EXPORT CSV
        // =============================================
        function exportCSV() {
            const search = document.querySelector('input[name="search"]')?.value || '';
            const platform = document.querySelector('select[name="platform"]')?.value || '';
            
            window.location.href = `ajax/export-csv.php?search=${encodeURIComponent(search)}&platform=${encodeURIComponent(platform)}`;
        }
        
        // =============================================
        // CLOSE MODAL
        // =============================================
        function closeModal() {
            document.getElementById('detailModal').classList.remove('show');
        }
        
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // =============================================
        // TOAST NOTIFICATIONS
        // =============================================
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-triangle-exclamation'
            };
            
            toast.innerHTML = `<i class="fas ${icons[type] || 'fa-info-circle'}"></i> ${message}`;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
        
        // =============================================
        // HELPER FUNCTIONS
        // =============================================
        function formatNumber(num) {
            if (num === undefined || num === null) return '0';
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }
    </script>
</body>
</html>