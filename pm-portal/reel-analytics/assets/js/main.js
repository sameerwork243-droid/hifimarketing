// =============================================
// REEL ANALYTICS - COMPLETE WORKING
// =============================================

console.log('🔥 main.js loaded!');

// Auto-refresh timer
var refreshInterval = null;
var refreshCount = 0;

// Fetch Reel Function
function fetchReel() {
    console.log('🔥 fetchReel() called!');
    
    var urlInput = document.getElementById('reelUrlInput');
    if (!urlInput) {
        showToast('Error: Input field not found!', 'error');
        return;
    }
    
    var url = urlInput.value.trim();
    if (!url) {
        showToast('Please enter a reel URL', 'error');
        return;
    }
    
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
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
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
            startAutoRefresh(url);
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

// Start Auto-Refresh
function startAutoRefresh(url) {
    stopAutoRefresh();
    console.log('🔄 Auto-refresh started');
    
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
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'url=' + encodeURIComponent(url) + '&save=true'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                updateDisplay(data.data);
                if (refreshCount % 5 === 0) {
                    location.reload();
                }
            }
        })
        .catch(function(err) { console.error('Auto-refresh error:', err); });
    }, 1500);
}

// Stop Auto-Refresh
function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
        console.log('⏹️ Auto-refresh stopped');
    }
    var indicator = document.getElementById('refreshIndicator');
    if (indicator) indicator.style.display = 'none';
}

// Update Refresh Counter
function updateRefreshCounter() {
    var counter = document.getElementById('refreshCounter');
    if (counter) counter.textContent = '#' + refreshCount;
}

// Update Display
function updateDisplay(data) {
    console.log('📊 Updating display:', data);
    
    var views = document.getElementById('views');
    if (views) views.textContent = formatNumber(data.views || 0);
    
    var likes = document.getElementById('likes');
    if (likes) likes.textContent = formatNumber(data.likes || 0);
    
    var comments = document.getElementById('comments');
    if (comments) comments.textContent = formatNumber(data.comments || 0);
    
    var shares = document.getElementById('shares');
    if (shares) shares.textContent = formatNumber(data.shares || 0);
    
    window.currentData = data;
}

// Display Results
function displayResults(data) {
    console.log('📊 Displaying results:', data);
    
    var results = document.getElementById('resultsSection');
    if (results) results.style.display = 'block';
    
    window.currentData = data;
    window._lastData = data;
    
    // Platform
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
    
    // Profile
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
    
    // Stats
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
    
    // Thumbnail
    var thumbnailImg = document.getElementById('thumbnailImage');
    if (thumbnailImg && data.thumbnail_url) {
        thumbnailImg.src = data.thumbnail_url;
        thumbnailImg.style.display = 'block';
    }
    
    if (results) results.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Format Numbers
function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}

// Show Toast
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

// Save Current Data
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
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
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

// Export Data
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

// Clear Results
function clearResults() {
    stopAutoRefresh();
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('reelUrlInput').value = '';
    window.currentData = null;
    window._lastData = null;
    
    var thumb = document.getElementById('thumbnailImage');
    if (thumb) {
        thumb.src = '';
        thumb.style.display = 'none';
    }
    
    showToast('Cleared', 'info');
}

// Time Ago
function timeAgo(dateString) {
    if (!dateString) return 'Just now';
    var date = new Date(dateString);
    var seconds = Math.floor((new Date() - date) / 1000);
    if (seconds < 0) return 'Just now';
    
    var intervals = { year: 31536000, month: 2592000, week: 604800, day: 86400, hour: 3600, minute: 60, second: 1 };
    for (var unit in intervals) {
        var value = intervals[unit];
        var count = Math.floor(seconds / value);
        if (count >= 1) return count + ' ' + unit + (count > 1 ? 's' : '') + ' ago';
    }
    return 'Just now';
}

// DOM Ready
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
    
    // FORCE ENABLE FETCH BUTTON
    var fetchBtn = document.getElementById('fetchBtn');
    if (fetchBtn) {
        fetchBtn.disabled = false;
        fetchBtn.style.pointerEvents = 'auto';
        fetchBtn.style.opacity = '1';
        fetchBtn.style.cursor = 'pointer';
        fetchBtn.style.zIndex = '999';
        console.log('✅ Fetch button enabled');
        
        fetchBtn.onclick = function(e) {
            e.preventDefault();
            console.log('🖱️ Fetch button clicked!');
            fetchReel();
        };
    } else {
        console.error('❌ Fetch button NOT found!');
    }
    
    // Stop auto-refresh when leaving page
    window.addEventListener('beforeunload', function() {
        stopAutoRefresh();
    });
    
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

console.log('✅ main.js loaded completely!');