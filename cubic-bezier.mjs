0;
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
                syncTotal = data.