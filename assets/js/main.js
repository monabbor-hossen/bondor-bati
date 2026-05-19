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

    // ── AJAX Form Submission ───────────────────────────────
    document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const origText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    body: new FormData(form)
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
});
