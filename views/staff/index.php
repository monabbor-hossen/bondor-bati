<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-users" style="color: var(--accent-secondary);"></i> Staff & Dues</h1>
            <p>Manage staff, salary, attendance, and supplier dues.</p>
        </div>
    </div>
</header>

<div class="tab-bar" data-group="staff">
    <button class="tab-btn active" data-tab="tab-staff-list" data-group="staff"><i class="fa-solid fa-user-group"></i> Staff</button>
    <button class="tab-btn" data-tab="tab-permissions" data-group="staff"><i class="fa-solid fa-shield-halved"></i> Permissions</button>
    <button class="tab-btn" data-tab="tab-attendance" data-group="staff"><i class="fa-solid fa-calendar-check"></i> Attendance</button>
    <button class="tab-btn" data-tab="tab-supplier-dues" data-group="staff"><i class="fa-solid fa-truck"></i> Supplier Dues</button>
</div>

<!-- ═══ Staff List Tab ═══ -->
<div class="tab-pane active" id="tab-staff-list" data-group="staff">
    <div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
        <div class="section-header"><h2>Add Staff Member</h2></div>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="add_staff">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Karim Mia" required>
                </div>
                <div class="form-group">
                    <label>Username (for Admin login)</label>
                    <input type="text" name="username" class="form-control" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Default: staff123" value="staff123">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <option value="STAFF">Staff</option>
                        <option value="ADMIN">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Monthly Salary (৳)</label>
                    <input type="number" name="monthly_salary" class="form-control" step="0.01" placeholder="12000">
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d'); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;"><i class="fa-solid fa-user-plus"></i> Add Staff</button>
        </form>
    </div>

    <div class="section-panel glass-panel">
        <div class="section-header"><h2>All Staff Members (<?= count($staff); ?>)</h2></div>
        <?php if (empty($staff)): ?>
            <div class="empty-state"><i class="fa-solid fa-user-group"></i><p>No staff members yet.</p></div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Role</th><th>Salary</th><th>Daily Rate</th><th>Access Key</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($staff as $u): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['name']); ?></strong></td>
                        <td><span class="badge <?= $u['role'] === 'ADMIN' ? 'warning' : 'success'; ?>"><?= $u['role']; ?></span></td>
                        <td><?= $u['monthly_salary'] ? '৳ ' . number_format($u['monthly_salary'], 0) : '—'; ?></td>
                        <td><?= $u['daily_rate'] ? '৳ ' . number_format($u['daily_rate'], 0) : '—'; ?></td>
                        <td>
                            <?php if (!empty($u['access_token'])): ?>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <code style="font-size: 0.7rem; color: var(--text-muted); max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= substr($u['access_token'], 0, 12); ?>…
                                    </code>
                                    <button class="btn btn-glass btn-sm" onclick="copyAccessUrl('<?= htmlspecialchars($u['access_token']); ?>')" title="Copy Staff Access URL">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem;"><i class="fa-solid fa-link-slash"></i> Used</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $u['is_active'] ? 'success' : 'danger'; ?>"><?= $u['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                        <td style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-glass btn-sm" data-action="toggle_staff" data-id="<?= $u['id']; ?>" title="Toggle Active/Inactive">
                                <i class="fa-solid fa-<?= $u['is_active'] ? 'pause' : 'play'; ?>"></i>
                            </button>
                            <button class="btn btn-glass btn-sm" onclick="regenerateToken(<?= $u['id']; ?>)" title="Generate New Magic Link">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.25rem; padding: 1rem; background: rgba(124, 58, 237, 0.08); border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 12px;">
            <p style="font-size: 0.85rem; color: var(--text-muted);">
                <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--accent-primary);"></i>
                <strong>Staff Magic Link:</strong> Click the <i class="fa-solid fa-copy"></i> button to copy a staff member's direct access URL. Share it with them — they can open it in any browser to access the POS instantly, no password needed.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function copyAccessUrl(token) {
        const url = window.location.origin + window.location.pathname + '?token=' + token;
        navigator.clipboard.writeText(url).then(() => {
            showToast('Staff access URL copied to clipboard!', 'success');
        }).catch(() => {
            prompt('Copy this URL:', url);
        });
    }

    function regenerateToken(userId) {
        if (!confirm('Generate a new magic link for this staff member? The old link will stop working immediately.')) return;
        const fd = new FormData();
        fd.append('action', 'regenerate_token');
        fd.append('id', userId);
        fetch('api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const url = window.location.origin + window.location.pathname + '?token=' + data.token;
                    navigator.clipboard.writeText(url).then(() => {
                        showToast('New magic link generated & copied!', 'success');
                    }).catch(() => {
                        prompt('New Staff Access URL:', url);
                    });
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(data.message, 'error');
                }
            });
    }
    </script>
</div>

<!-- ═══ Attendance Tab ═══ -->
<div class="tab-pane" id="tab-attendance" data-group="staff">
    <div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
        <div class="section-header"><h2>Log Absence</h2></div>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="log_absence">
            <div class="form-row">
                <div class="form-group">
                    <label>Staff Member</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">Select Staff</option>
                        <?php foreach ($staff as $u): ?>
                        <option value="<?= $u['id']; ?>"><?= htmlspecialchars($u['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Absent Date</label>
                    <input type="date" name="absent_date" class="form-control" value="<?= date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Deduct Salary?</label>
                    <select name="deduct_salary" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-calendar-xmark"></i> Log</button>
                </div>
            </div>
        </form>
    </div>

    <div class="section-panel glass-panel">
        <div class="section-header"><h2>This Month's Absences</h2></div>
        <?php if (empty($attendance)): ?>
            <div class="empty-state"><i class="fa-solid fa-calendar-check"></i><p>No absences logged this month.</p></div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Staff</th><th>Date</th><th>Deducted</th></tr></thead>
                <tbody>
                    <?php foreach ($attendance as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['name']); ?></strong></td>
                        <td><?= date('M d', strtotime($a['absent_date'])); ?></td>
                        <td><span class="badge <?= $a['deduct_salary'] ? 'danger' : 'success'; ?>"><?= $a['deduct_salary'] ? 'Yes' : 'No'; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Supplier Dues Tab ═══ -->
<div class="tab-pane" id="tab-supplier-dues" data-group="staff">
    <div class="section-panel glass-panel">
        <div class="section-header"><h2>Supplier Due Balances</h2></div>
        <?php
            $dueSuppliers = array_filter($suppliers, fn($s) => $s['total_due'] > 0);
        ?>
        <?php if (empty($dueSuppliers)): ?>
            <div class="empty-state"><i class="fa-solid fa-circle-check"></i><p>All supplier dues are settled!</p></div>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($dueSuppliers as $s): ?>
            <div class="list-item">
                <div class="item-info">
                    <h4><?= htmlspecialchars($s['name']); ?></h4>
                    <p><?= htmlspecialchars($s['contact'] ?: 'No contact'); ?></p>
                </div>
                <strong style="color: var(--danger);">৳ <?= number_format($s['total_due'], 0); ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Permissions Tab ═══ -->
<div class="tab-pane" id="tab-permissions" data-group="staff">
    <div class="section-panel glass-panel">
        <div class="section-header">
            <h2><i class="fa-solid fa-shield-halved"></i> Page Access Control</h2>
        </div>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">
            Toggle which pages each staff member can see and access. Admins always have full access.
            <strong style="color: var(--success);"><i class="fa-solid fa-bolt"></i> Changes apply in real-time</strong> — no re-login needed.
        </p>

        <?php
        $staffOnly = array_filter($staff, fn($u) => $u['role'] === 'STAFF');
        ?>

        <?php if (empty($staffOnly)): ?>
            <div class="empty-state"><i class="fa-solid fa-user-group"></i><p>No staff members to configure. Add staff first.</p></div>
        <?php else: ?>
            <?php foreach ($staffOnly as $u):
                $userPerms = $staffPermissions[$u['id']] ?? [];
            ?>
            <div style="margin-bottom: 1.5rem; padding: 1.25rem; background: rgba(0,0,0,0.2); border-radius: 14px; border: 1px solid var(--glass-border);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div class="avatar" style="width: 36px; height: 36px; font-size: 0.8rem;">
                            <?= strtoupper(substr($u['name'], 0, 2)); ?>
                        </div>
                        <div>
                            <strong><?= htmlspecialchars($u['name']); ?></strong>
                            <div><span class="badge <?= $u['is_active'] ? 'success' : 'danger'; ?>" style="font-size: 0.65rem;"><?= $u['is_active'] ? 'Active' : 'Inactive'; ?></span></div>
                        </div>
                    </div>
                    <span style="color: var(--text-muted); font-size: 0.8rem;"><?= count($userPerms); ?>/<?= count(ALL_PAGES); ?> pages</span>
                </div>

                <form data-ajax data-reload="true" data-reset="false">
                    <input type="hidden" name="action" value="save_permissions">
                    <input type="hidden" name="perm_user_id" value="<?= $u['id']; ?>">

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem;">
                        <?php foreach (ALL_PAGES as $slug => $meta): ?>
                        <label class="perm-toggle" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 1rem; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px solid var(--glass-border); cursor: pointer; transition: all 0.2s;">
                            <input type="checkbox" name="pages[]" value="<?= $slug; ?>"
                                <?= in_array($slug, $userPerms) ? 'checked' : ''; ?>
                                style="width: 18px; height: 18px; accent-color: var(--accent-primary); cursor: pointer;">
                            <div>
                                <div style="font-size: 0.85rem; font-weight: 500;"><i class="fa-solid <?= $meta['icon']; ?>" style="width: 18px; opacity: 0.6;"></i> <?= $meta['label']; ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 1rem; display: flex; gap: 0.75rem; align-items: center;">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Save Permissions</button>
                        <button type="button" class="btn btn-glass btn-sm" onclick="toggleAllPerms(this, true)"><i class="fa-solid fa-check-double"></i> All On</button>
                        <button type="button" class="btn btn-glass btn-sm" onclick="toggleAllPerms(this, false)"><i class="fa-solid fa-xmark"></i> All Off</button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAllPerms(btn, state) {
    const form = btn.closest('form');
    form.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = state);
}
</script>
