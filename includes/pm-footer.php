<?php
// pm-footer.php - Shared Footer for PM Pages
?>

    <!-- ===== MODALS ===== -->
    <!-- Verbal Project Modal -->
    <div class="modal-overlay" id="modal-verbal">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-verbal')"><i class="fas fa-times"></i></button>
            <h3>Add Verbal Project</h3>
            <p class="modal-sub">Client verbal request will be sent to Finance for pricing.</p>
            <form onsubmit="addVerbalTask(event)">
                <label>Select Client</label>
                <select id="modal-verbal-client">
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Category</label>
                <select id="modal-verbal-category">
                    <option value="Software & Web dev">Software & Web dev</option>
                    <option value="Design & Branding">Design & Branding</option>
                    <option value="Marketing & Ads">Marketing & Ads</option>
                    <option value="Content & Copy">Content & Copy</option>
                    <option value="Other">Other</option>
                </select>
                <label>Task Title</label>
                <input type="text" id="modal-verbal-title" required placeholder="e.g. Design 10 extra custom posts">
                <label>Verbal Context</label>
                <textarea id="modal-verbal-desc" rows="3" required placeholder="Client verbal instructions..."></textarea>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Post to Finance</button>
            </form>
        </div>
    </div>

    <!-- Note Modal -->
    <div class="modal-overlay" id="modal-note">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-note')"><i class="fas fa-times"></i></button>
            <h3>Add Reply Note</h3>
            <p class="modal-sub">Provide response commentary to the client.</p>
            <form onsubmit="saveNoteFromModal(event)">
                <input type="hidden" id="note-req-id">
                <label>Your Reply</label>
                <textarea id="note-reply-text" rows="4" required placeholder="Enter your response..."></textarea>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Reply Note</button>
            </form>
        </div>
    </div>

    <!-- Invoice Modal -->
    <div class="modal-overlay" id="modal-invoice">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-invoice')"><i class="fas fa-times"></i></button>
            <h3>Generate Invoice</h3>
            <p class="modal-sub">Generate invoice for verbal task.</p>
            <form onsubmit="generateInvoiceFromModal(event)">
                <input type="hidden" id="invoice-client-id">
                <input type="hidden" id="invoice-task-id">
                <label>Amount (PKR)</label>
                <input type="number" id="invoice-amount" required placeholder="e.g. 25000">
                <label>Description</label>
                <textarea id="invoice-desc" rows="2" required placeholder="Invoice description..."></textarea>
                <button type="submit" class="btn-submit"><i class="fas fa-file-invoice"></i> Generate Invoice</button>
            </form>
        </div>
    </div>

    <!-- ===== BRAND UPLOAD MODAL ===== -->
    <div class="modal-overlay" id="modal-upload-brand">
        <div class="modal">
            <button class="modal-close" onclick="closeModal('modal-upload-brand')"><i class="fas fa-times"></i></button>
            <h3>Upload Brand2Social File</h3>
            <p class="modal-sub">Upload analytics files for client to download.</p>
            <form id="upload-brand-form" onsubmit="uploadBrandFile(event)" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Select Client *</label>
                    <select id="brand-client-id" name="brand-client-id" required style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['username']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Select File (PDF, CSV, XLSX) *</label>
                    <input type="file" id="brand-upload-file" name="brand-upload-file" required accept=".pdf,.csv,.xlsx,.xls" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                </div>
                <div class="form-group" style="margin-bottom:12px;">
                    <label>File Description (Optional)</label>
                    <input type="text" id="brand-file-desc" name="brand-file-desc" placeholder="e.g. Monthly Report - June 2024" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#f8fafc;font-size:13px;">
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-upload"></i> Upload File</button>
            </form>
        </div>
    </div>

    <!-- ===== TOAST CONTAINER ===== -->
    <div class="toast-container" id="toast-container"></div>

    <!-- ===== SECURITY BADGE ===== -->
    <div class="security-badge">🔒 Secure Session • <?php echo $_SERVER['REMOTE_ADDR']; ?></div>

    <script>
        // ===== SIDEBAR TOGGLE =====
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000`;
        }

        // ===== MOBILE MENU =====
        function toggleMobileMenu() {
            const nav = document.getElementById('mobileNav');
            const overlay = document.getElementById('mobileNavOverlay');
            const toggle = document.getElementById('mobileMenuToggle');
            nav.classList.toggle('active');
            overlay.classList.toggle('active');
            toggle.classList.toggle('active');
            document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
        }

        function closeMobileMenu() {
            const nav = document.getElementById('mobileNav');
            const overlay = document.getElementById('mobileNavOverlay');
            const toggle = document.getElementById('mobileMenuToggle');
            nav.classList.remove('active');
            overlay.classList.remove('active');
            toggle.classList.remove('active');
            document.body.style.overflow = '';
        }

        // ===== MODAL FUNCTIONS =====
        function openModal(id) {
            document.getElementById(id).classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
            document.body.style.overflow = '';
        }
        document.querySelectorAll('.modal-overlay').forEach(el => {
            el.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        });

        // ===== TOAST =====
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-triangle-exclamation';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; setTimeout(() => toast.remove(), 300); }, 3500);
        }

        // ===== 1. UPDATE TASK PROGRESS =====
        function updateTaskProgress(taskId, value) {
            document.getElementById('progress-label-' + taskId).textContent = value + '%';
            
            const formData = new FormData();
            formData.append('ajax_action', 'update_task_progress');
            formData.append('task_id', taskId);
            formData.append('progress', value);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showToast('Error updating progress: ' + data.message, 'error');
                }
            })
            .catch(error => {});
        }

        // ===== 2. UPDATE DELIVERABLE STATUS =====
        function updateDeliverableStatus(deliverableId, status) {
            const formData = new FormData();
            formData.append('ajax_action', 'update_deliverable_status');
            formData.append('deliverable_id', deliverableId);
            formData.append('status', status);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Status updated to "' + status + '"');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error updating status', 'error');
            });
        }

        // ===== 3. DELETE DELIVERABLE =====
        function deleteDeliverable(deliverableId) {
            if (confirm('Are you sure you want to delete this deliverable?')) {
                const formData = new FormData();
                formData.append('ajax_action', 'delete_deliverable');
                formData.append('deliverable_id', deliverableId);
                
                fetch(window.location.href, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Deliverable deleted successfully');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error deleting deliverable', 'error');
                });
            }
        }

        // ===== 4. ADD DELIVERABLE =====
        function addDeliverable(e) {
            e.preventDefault();
            const client_id = document.getElementById('dl-client').value;
            const name = document.getElementById('dl-name').value;
            const type = document.getElementById('dl-type').value;
            const assignee = document.getElementById('dl-assignee').value;
            const date = document.getElementById('dl-date').value;
            
            if (!name || !assignee || !date) {
                showToast('Please fill all fields', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'add_deliverable');
            formData.append('client_id', client_id);
            formData.append('name', name);
            formData.append('type', type);
            formData.append('assigned_to', assignee);
            formData.append('due_date', date);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Deliverable "' + name + '" published!');
                    document.getElementById('dl-name').value = '';
                    document.getElementById('dl-assignee').value = '';
                    document.getElementById('dl-date').value = '';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error adding deliverable', 'error');
            });
        }

        // ===== 5. RESOLVE TICKET =====
        function resolveTicket(ticketId) {
            if (confirm('Mark this ticket as resolved?')) {
                const formData = new FormData();
                formData.append('ajax_action', 'resolve_ticket');
                formData.append('ticket_id', ticketId);
                
                fetch(window.location.href, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Ticket resolved successfully!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Error resolving ticket', 'error');
                });
            }
        }

        // ===== 6. OPEN NOTE MODAL =====
        function openNoteModal(ticketId, currentNote) {
            document.getElementById('note-req-id').value = ticketId;
            document.getElementById('note-reply-text').value = currentNote || '';
            openModal('modal-note');
        }

        // ===== 7. SAVE NOTE FROM MODAL =====
        function saveNoteFromModal(e) {
            e.preventDefault();
            const ticketId = document.getElementById('note-req-id').value;
            const note = document.getElementById('note-reply-text').value;
            
            if (!note.trim()) {
                showToast('Please enter a reply note', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'add_reply_note');
            formData.append('ticket_id', ticketId);
            formData.append('note', note);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Reply note saved!');
                    closeModal('modal-note');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error saving note', 'error');
            });
        }

        // ===== 8. OPEN INVOICE MODAL =====
        function openInvoiceModal(clientId, taskId, title) {
            document.getElementById('invoice-client-id').value = clientId;
            document.getElementById('invoice-task-id').value = taskId;
            document.getElementById('invoice-desc').value = 'Invoice for: ' + title;
            document.getElementById('invoice-amount').value = '';
            openModal('modal-invoice');
        }

        // ===== 9. GENERATE INVOICE FROM MODAL =====
        function generateInvoiceFromModal(e) {
            e.preventDefault();
            const clientId = document.getElementById('invoice-client-id').value;
            const taskId = document.getElementById('invoice-task-id').value;
            const amount = document.getElementById('invoice-amount').value;
            const description = document.getElementById('invoice-desc').value;
            
            if (!amount || amount <= 0) {
                showToast('Please enter a valid amount', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'generate_invoice');
            formData.append('client_id', clientId);
            formData.append('task_id', taskId);
            formData.append('amount', amount);
            formData.append('description', description);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Invoice ' + data.invoice_number + ' generated!');
                    closeModal('modal-invoice');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error generating invoice', 'error');
            });
        }

        // ===== 10. ADD VERBAL TASK =====
        function addVerbalTask(e) {
            e.preventDefault();
            
            let clientId, category, title, description;
            const modalClient = document.getElementById('modal-verbal-client');
            
            if (modalClient && modalClient.value) {
                clientId = modalClient.value;
                category = document.getElementById('modal-verbal-category').value;
                title = document.getElementById('modal-verbal-title').value;
                description = document.getElementById('modal-verbal-desc').value;
                closeModal('modal-verbal');
            } else {
                clientId = document.getElementById('verbal-client').value;
                category = document.getElementById('verbal-category').value;
                title = document.getElementById('verbal-title').value;
                description = document.getElementById('verbal-desc').value;
            }
            
            if (!title.trim()) {
                showToast('Please enter a task title', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'add_verbal_task');
            formData.append('client_id', clientId);
            formData.append('title', title);
            formData.append('category', category);
            formData.append('description', description);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Verbal project "' + title + '" posted!');
                    if (document.getElementById('verbal-title')) {
                        document.getElementById('verbal-title').value = '';
                        document.getElementById('verbal-desc').value = '';
                    }
                    if (document.getElementById('modal-verbal-title')) {
                        document.getElementById('modal-verbal-title').value = '';
                        document.getElementById('modal-verbal-desc').value = '';
                    }
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error adding verbal task', 'error');
            });
        }

        // ===== 11. UPDATE PROGRESS DISPLAY =====
        function updateProgressDisplay(clientId, type, value, limit) {
            if (type === 'posts') {
                document.getElementById('posts-display-' + clientId).textContent = value + ' / ' + limit;
            } else if (type === 'stories') {
                document.getElementById('stories-display-' + clientId).textContent = value + ' / ' + limit;
            }
        }

        // ===== 12. SAVE CLIENT PROGRESS =====
        function saveClientProgress(clientId) {
            const posts = document.getElementById('sync-posts-' + clientId).value;
            const stories = document.getElementById('sync-stories-' + clientId).value;
            const likes = document.getElementById('sync-likes-' + clientId).value;
            const followers = document.getElementById('sync-followers-' + clientId).value;
            
            const formData = new FormData();
            formData.append('ajax_action', 'update_social_progress');
            formData.append('client_id', clientId);
            formData.append('posts', posts);
            formData.append('stories', stories);
            formData.append('likes', likes);
            formData.append('followers', followers);
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Progress synced for client!');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error syncing progress', 'error');
            });
        }

        // ===== 13. SYNC ALL PROGRESS =====
        function syncAllProgress() {
            const buttons = document.querySelectorAll('[onclick^="saveClientProgress"]');
            if (buttons.length === 0) {
                showToast('No clients to sync', 'warning');
                return;
            }
            buttons.forEach(btn => {
                const clientId = btn.getAttribute('onclick').match(/\d+/)[0];
                saveClientProgress(clientId);
            });
            showToast('Syncing all client progress...');
        }

        // ===== 14. UPLOAD BRAND FILE =====
        function uploadBrandFile(e) {
            e.preventDefault();
            
            const clientId = document.getElementById('brand-client-id').value;
            const fileInput = document.getElementById('brand-upload-file');
            const description = document.getElementById('brand-file-desc').value || '';
            
            if (!clientId) {
                showToast('Please select a client first.', 'error');
                return;
            }
            
            if (!fileInput.files || fileInput.files.length === 0) {
                showToast('Please select a file.', 'error');
                return;
            }
            
            if (fileInput.files[0].size > 10485760) {
                showToast('File size too large. Maximum 10MB allowed.', 'error');
                return;
            }
            
            const fileName = fileInput.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            const allowedExts = ['pdf', 'csv', 'xlsx', 'xls'];
            if (!allowedExts.includes(fileExt)) {
                showToast('File type not allowed. Please upload PDF, CSV, or Excel files.', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax_action', 'upload_brand_file');
            formData.append('file', fileInput.files[0]);
            formData.append('client_id', clientId);
            formData.append('description', description);
            
            showToast('Uploading file...', 'warning');
            
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('File uploaded successfully!');
                    closeModal('modal-upload-brand');
                    document.getElementById('upload-brand-form').reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                showToast('Error uploading file. Please try again.', 'error');
            });
        }

        // ===== 15. DELETE ATTACHMENT =====
        function deleteAttachment(docId) {
            if (!confirm('Are you sure you want to delete this attachment?')) return;
            
            const formData = new FormData();
            formData.append('ajax_action', 'delete_attachment');
            formData.append('doc_id', docId);
            
            showToast('Deleting...', 'warning');
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Attachment deleted successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error deleting attachment.', 'error');
            });
        }

        // ===== 16. DOWNLOAD FILE =====
        function downloadFile(docId) {
            window.location.href = 'download.php?doc_id=' + docId;
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
    </script>
</body>
</html>