/**
 * Bondor Bati POS — Main JS
 * Handles: AJAX submissions, toast notifications, tabs, dynamic rows, mobile menu.
 */
document.addEventListener('DOMContentLoaded', () => {

    // ── Mobile Menu ────────────────────────────────────────
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    function toggleMobileMenu() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
    if (mobileBtn) mobileBtn.addEventListener('click', toggleMobileMenu);
    if (overlay) overlay.addEventListener('click', toggleMobileMenu);

    // ── Toast Notifications ────────────────────────────────
    window.showToast = function(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'circle-xmark'}"></i> ${message}`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3200);
    };

    // ── Offline Queue & Auto-Sync ──────────────────────────
    function updateOfflineIndicator() {
        const queue = JSON.parse(localStorage.getItem('offline_queue') || '[]');
        let banner = document.getElementById('offline-sync-banner');
        if (queue.length > 0) {
            if (!banner) {
                banner = document.createElement('div');
                banner.id = 'offline-sync-banner';
                banner.style.cssText = 'background: var(--warning); color: #000; padding: 0.75rem; text-align: center; font-weight: 500; font-size: 0.9rem; z-index: 9999; display: flex; justify-content: center; align-items: center; gap: 1rem;';
                document.body.prepend(banner);
            }
            banner.innerHTML = `
                <span><i class="fa-solid fa-cloud-arrow-up"></i> You have ${queue.length} pending offline transaction(s).</span>
                <button class="btn btn-sm" onclick="syncOfflineQueue()" style="background: #000; color: #fff; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer;">Sync Now</button>
            `;
        } else if (banner) {
            banner.remove();
        }
    }

    window.syncOfflineQueue = async function() {
        if (!navigator.onLine) {
            showToast('Cannot sync: device is offline.', 'error');
            return;
        }
        const queue = JSON.parse(localStorage.getItem('offline_queue') || '[]');
        if (queue.length === 0) return;
        
        showToast(`Syncing ${queue.length} offline transactions...`, 'success');
        
        const remaining = [];
        for (const item of queue) {
            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: item.body
                });
                const data = await res.json();
                if (!data.success) {
                    remaining.push(item);
                }
            } catch (err) {
                remaining.push(item);
            }
        }
        
        localStorage.setItem('offline_queue', JSON.stringify(remaining));
        updateOfflineIndicator();
        if (remaining.length === 0) {
            showToast('All offline data synchronized successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(`Failed to sync ${remaining.length} items. Will retry.`, 'error');
        }
    };

    window.addEventListener('online', syncOfflineQueue);
    if (navigator.onLine) {
        syncOfflineQueue();
    } else {
        updateOfflineIndicator();
    }

    // ── AJAX Form Submission ───────────────────────────────
    document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const origText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            const formData = new FormData(form);

            if (!navigator.onLine) {
                const queue = JSON.parse(localStorage.getItem('offline_queue') || '[]');
                const bodyParams = new URLSearchParams();
                formData.forEach((value, key) => bodyParams.append(key, value));
                
                queue.push({
                    action: bodyParams.get('action') || 'unknown',
                    body: bodyParams.toString(),
                    timestamp: Date.now()
                });
                localStorage.setItem('offline_queue', JSON.stringify(queue));
                
                showToast('Saved offline. Will sync when online.', 'warning');
                btn.innerHTML = origText;
                btn.disabled = false;
                
                if (form.dataset.reset !== 'false') {
                    form.reset();
                }
                updateOfflineIndicator();
                return;
            }

            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');

                if (data.success && form.dataset.reset !== 'false') {
                    form.reset();
                }
                if (data.success && form.dataset.reload === 'true') {
                    setTimeout(() => location.reload(), 600);
                }
            } catch (err) {
                showToast('Network error. Try again.', 'error');
            }

            btn.innerHTML = origText;
            btn.disabled = false;
        });
    });

    // ── Quick Action Buttons (delete, toggle, etc.) ────────
    document.querySelectorAll('[data-action]').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const action = this.dataset.action;
            const id = this.dataset.id;
            const extra = this.dataset.extra ? JSON.parse(this.dataset.extra) : {};

            if (action.startsWith('delete') && !confirm('Are you sure you want to delete this?')) return;

            const formData = new FormData();
            formData.append('action', action);
            formData.append('id', id);
            Object.entries(extra).forEach(([k, v]) => formData.append(k, v));

            if (!navigator.onLine) {
                const queue = JSON.parse(localStorage.getItem('offline_queue') || '[]');
                const bodyParams = new URLSearchParams();
                formData.forEach((value, key) => bodyParams.append(key, value));
                
                queue.push({
                    action: action,
                    body: bodyParams.toString(),
                    timestamp: Date.now()
                });
                localStorage.setItem('offline_queue', JSON.stringify(queue));
                
                showToast('Action saved offline. Will sync when online.', 'warning');
                updateOfflineIndicator();
                return;
            }

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => location.reload(), 600);
            } catch (err) {
                showToast('Network error.', 'error');
            }
        });
    });

    // ── Tabs ───────────────────────────────────────────────
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const group = this.closest('.tab-bar').dataset.group;
            document.querySelectorAll(`.tab-btn[data-group="${group}"]`).forEach(t => t.classList.remove('active'));
            document.querySelectorAll(`.tab-pane[data-group="${group}"]`).forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });

    // ── Dynamic Row Add ────────────────────────────────────
    window.addDynamicRow = function(containerId, templateFn) {
        const container = document.getElementById(containerId);
        const idx = container.children.length;
        const row = document.createElement('div');
        row.className = 'dynamic-row';
        row.innerHTML = templateFn(idx);
        container.appendChild(row);
    };

    window.removeDynamicRow = function(btn) {
        btn.closest('.dynamic-row').remove();
    };

    // ── Service Worker Registration ────────────────────────
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('./sw.js')
                .then(reg => console.log('Service Worker registered', reg))
                .catch(err => console.error('Service Worker registration failed', err));
        });
    }
});
