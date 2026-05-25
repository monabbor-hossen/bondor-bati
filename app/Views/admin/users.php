<?php
/**
 * Admin - Staff Management
 * Variables: $users
 */
?>

<div class="mb-4 animate-slideUp">
    <h2 class="text-xl font-black tracking-tight">
        <i class="fas fa-users-cog text-text-muted mr-1"></i> <?= __('staff_management') ?>
    </h2>
</div>

<!-- Add New Staff Form -->
<div class="bg-card border border-border rounded-xl p-5 mb-6 animate-slideUp">
    <h3 class="text-sm font-bold text-text-muted mb-4 uppercase tracking-wider"><?= __('add_new_staff') ?></h3>
    <form id="addStaffForm" class="space-y-6">
        <input type="hidden" id="editUserId" value="0">
        
        <div class="relative">
            <input type="text" id="staffName" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Name">
            <label for="staffName" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">
                <?= __('staff_name') ?>
            </label>
        </div>

        <div class="relative">
            <input type="tel" id="staffPhone" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Phone">
            <label for="staffPhone" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">
                <?= __('whatsapp_number') ?>
            </label>
        </div>

        <div class="relative">
            <input type="text" id="staffNameBn" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Name (BN)">
            <label for="staffNameBn" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">
                Name (BN)
            </label>
        </div>

        <div class="relative">
            <select id="staffRole" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none appearance-none">
                <option value="staff" class="bg-card"><?= __('role_staff') ?></option>
                <option value="admin" class="bg-card"><?= __('role_admin') ?></option>
            </select>
            <label class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-focus:text-accent">
                <?= __('role') ?>
            </label>
            <i class="fas fa-chevron-down absolute right-2 top-3 text-xs text-text-muted pointer-events-none"></i>
        </div>

        <div class="relative">
            <input type="number" step="0.01" id="staffSalary" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Monthly Salary">
            <label for="staffSalary" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">
                <?= __('monthly_salary') ?>
            </label>
        </div>

        <div class="flex gap-2 mt-4">
            <button type="submit" id="submitStaffBtn" class="w-full bg-accent hover:bg-accent-light text-white font-bold py-3 rounded-xl transition-colors shadow-[0_0_15px_rgba(244,63,94,0.3)]">
                <i class="fas fa-save mr-1"></i> <?= __('save_staff') ?>
            </button>
            <button type="button" id="cancelEditBtn" class="hidden w-1/3 bg-surface border border-border text-text-muted hover:text-text-primary font-bold py-3 rounded-xl transition-colors">
                Cancel
            </button>
        </div>
    </form>
</div>

<!-- Current Staff List -->
<div class="space-y-3 stagger" id="staffList">
    <?php foreach ($users as $user): ?>
    <div class="bg-card border border-border/50 rounded-xl p-4 flex items-center justify-between" id="user-row-<?= $user['id'] ?>">
        <div>
            <?php $base = (float)($user['monthly_salary'] ?? 0); $deduct = (float)($user['month_deductions'] ?? 0); $net = $base - $deduct; ?>
            <h4 class="font-bold text-sm mb-0.5 <?= !$user['is_active'] ? 'line-through text-text-muted' : '' ?>" id="user-name-<?= $user['id'] ?>">
                <?= htmlspecialchars($user['name' . (currentLang() === 'bn' ? '_bn' : '')] ?? $user['name']) ?>
            </h4>
            <div class="text-xs text-text-muted flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded text-[0.6rem] font-bold <?= $user['role'] === 'admin' ? 'bg-info/10 text-info border border-info/30' : 'bg-surface border border-border' ?> uppercase">
                    <?= __('role_' . $user['role']) ?>
                </span>
                <span class="<?= !$user['is_active'] ? 'opacity-50' : '' ?>" id="user-phone-<?= $user['id'] ?>">
                    <a href="tel:<?= htmlspecialchars($user['username']) ?>" class="hover:text-accent transition-colors flex items-center gap-1">
                        <i class="fas fa-phone-alt text-success"></i> <?= htmlspecialchars($user['username']) ?>
                    </a>
                </span>
            </div>
            <?php if ($base > 0): ?>
            <div class="text-[0.65rem] text-text-muted flex items-center gap-2 mt-1">
                <span><i class="fas fa-money-bill-wave text-accent/70 mr-1"></i> ৳<?= number_format($base) ?>/mo</span>
                <span>(৳<?= number_format((float)($user['daily_rate'] ?? 0)) ?>/day)</span>
            </div>
            <div class="text-xs text-text-muted mt-1"><?= __('net_payable') ?>: <strong class="text-success"><?= $net ?></strong> (<?= __('base') ?>: <?= $base ?> - <?= __('cut') ?>: <?= $deduct ?>)</div>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-3">
            <!-- Line-art Toggle Switch -->
            <label class="relative inline-flex items-center cursor-pointer" title="<?= __('status_active') ?>">
                <input type="checkbox" class="sr-only peer toggle-user" data-id="<?= $user['id'] ?>" <?= $user['is_active'] ? 'checked' : '' ?>>
                <div class="w-8 h-4 bg-transparent border border-border peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-accent after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-text-muted peer-checked:after:bg-accent after:border-transparent after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:border-accent/50"></div>
            </label>

            <!-- Generate Link -->
            <button class="btn-generate-link text-text-muted hover:text-info hover:drop-shadow-[0_0_5px_rgba(96,165,250,0.5)] transition-all flex-shrink-0" data-id="<?= $user['id'] ?>" title="<?= __('generate_link') ?>">
                <i class="fas fa-link"></i>
            </button>

            <!-- Edit Staff -->
            <button class="btn-edit-user text-text-muted hover:text-amber-400 hover:drop-shadow-[0_0_5px_rgba(251,191,36,0.5)] transition-all flex-shrink-0" 
                    data-id="<?= $user['id'] ?>" 
                    data-name="<?= htmlspecialchars($user['name'] ?? '') ?>" 
                    data-namebn="<?= htmlspecialchars($user['name_bn'] ?? '') ?>" 
                    data-phone="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                    data-role="<?= $user['role'] ?>" 
                    data-salary="<?= $base ?>" 
                    title="Edit">
                <i class="fas fa-pencil-alt"></i>
            </button>

            <!-- Log Absence -->
            <button class="btn-log-absence text-text-muted hover:text-red-400 hover:drop-shadow-[0_0_5px_rgba(248,113,113,0.5)] transition-all flex-shrink-0" data-id="<?= $user['id'] ?>" title="<?= __('log_absence') ?>">
                <i class="fas fa-calendar-minus"></i>
            </button>

            <!-- Delete Button -->
            <button class="btn-delete-user text-text-muted hover:text-red-400 hover:drop-shadow-[0_0_5px_rgba(248,113,113,0.5)] transition-all flex-shrink-0" data-id="<?= $user['id'] ?>" title="<?= __('action_delete') ?>">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Log Absence Modal (Line-art style) -->
<div id="absence-modal" class="fixed inset-0 z-[100] flex items-center justify-center px-4" style="display:none;">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('absence-modal').style.display='none'"></div>
    <div class="relative bg-card border border-border rounded-2xl p-6 w-full max-w-sm z-10">
        <h3 class="text-sm font-bold mb-4 uppercase tracking-wider text-text-muted"><i class="fas fa-calendar-minus text-accent mr-1"></i> <?= __('log_absence') ?></h3>
        <form id="absenceForm" class="space-y-4">
            <input type="hidden" id="absentUserId" value="">
            
            <div class="relative">
                <input type="date" id="absentDate" required value="<?= date('Y-m-d') ?>" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none">
                <label for="absentDate" class="absolute left-1 -top-3.5 text-xs text-accent">
                    <?= __('absent_date') ?>
                </label>
            </div>

            <div class="relative pt-2">
                <input type="date" id="absentEndDate" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent">
                <label for="absentEndDate" class="absolute left-1 -top-1.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-1.5 peer-focus:text-xs peer-focus:text-accent">
                    <?= __('end_date_optional') ?>
                </label>
            </div>

            <div class="flex items-center justify-between pt-2">
                <span class="text-sm text-text-muted"><?= __('deduct_salary') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="deductSalary" class="sr-only peer toggle-user" checked>
                    <div class="w-8 h-4 bg-transparent border border-border peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-accent after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-text-muted peer-checked:after:bg-accent after:border-transparent after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:border-accent/50"></div>
                </label>
            </div>

            <div class="relative pt-2">
                <input type="text" id="absenceNote" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Note">
                <label for="absenceNote" class="absolute left-1 -top-1.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-1.5 peer-focus:text-xs peer-focus:text-accent">
                    <?= __('absence_note') ?>
                </label>
            </div>

            <button type="submit" id="btnSubmitAbsence" class="w-full bg-transparent border border-accent hover:bg-accent/10 text-accent font-bold py-2 rounded-xl transition-colors mt-2">
                <?= __('submit') ?>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Edit Button Logic
    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editUserId').value  = btn.dataset.id;
            document.getElementById('staffName').value   = btn.dataset.name   || '';
            document.getElementById('staffPhone').value  = btn.dataset.phone  || '';
            document.getElementById('staffRole').value   = btn.dataset.role   || 'staff';
            document.getElementById('staffSalary').value = btn.dataset.salary || '';

            document.getElementById('submitStaffBtn').innerHTML = '<i class="fas fa-edit mr-1"></i> Update';
            document.getElementById('cancelEditBtn').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // 2. Cancel Edit Logic
    document.getElementById('cancelEditBtn').addEventListener('click', () => {
        document.getElementById('editUserId').value = '0';
        document.getElementById('addStaffForm').reset();
        document.getElementById('submitStaffBtn').innerHTML = '<i class="fas fa-save mr-1"></i> <?= __("save_staff") ?>';
        document.getElementById('cancelEditBtn').classList.add('hidden');
    });

    // 3. Submit Logic (No DOM manipulation — strict reload)
    document.getElementById('addStaffForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        // Force integer parse to prevent string '0' issues
        const userId = parseInt(document.getElementById('editUserId').value) || 0;
        const name   = document.getElementById('staffName').value.trim();
        const nameBn = document.getElementById('staffNameBn').value.trim();
        const phone  = document.getElementById('staffPhone').value.trim();
        const role   = document.getElementById('staffRole').value;
        const salary = document.getElementById('staffSalary').value;

        if (!name || !phone) {
            showToast('Name and phone are required.', 'error');
            return;
        }

        const btn = document.getElementById('submitStaffBtn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        // CRITICAL: Must hit admin/saveStaff
        const res = await apiPost('?url=admin/saveStaff', {
            user_id: userId,
            name: name,
            name_bn: nameBn,
            phone: phone,
            role: role,
            monthly_salary: salary
        });

        if (res.success) {
            showToast('Staff saved successfully!', 'success');
            // STRICT RELOAD: Prevents ghost duplicates on screen
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(res.error || 'Failed to save staff', 'error');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });

    // Log Absence Button Click
    document.querySelectorAll('.btn-log-absence').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('absentUserId').value = btn.dataset.id;
            document.getElementById('absenceForm').reset();
            document.getElementById('absentDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('absence-modal').style.display = 'flex';
        });
    });

    // Submit Absence Form
    document.getElementById('absenceForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const userId = document.getElementById('absentUserId').value;
        const absentDate = document.getElementById('absentDate').value;
        const absentEndDate = document.getElementById('absentEndDate').value;
        const isDeducted = document.getElementById('deductSalary').checked ? 1 : 0;
        const note = document.getElementById('absenceNote').value;
        
        const btn = document.getElementById('btnSubmitAbsence');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const res = await apiPost('?url=admin/logAbsence', { 
            user_id: userId, 
            absent_date: absentDate, 
            end_date: absentEndDate,
            is_deducted: isDeducted, 
            note: note 
        });
        
        if (res.success) {
            showToast('<?= __("absence_saved") ?>', 'success');
            document.getElementById('absence-modal').style.display = 'none';
        } else {
            showToast(res.error || 'Failed to record absence', 'error');
        }
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });

    // Generate Link Click
    document.querySelectorAll('.btn-generate-link').forEach(btn => {
        btn.addEventListener('click', async () => {
            const userId = btn.dataset.id;
            const icon = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const res = await apiPost('?url=auth/generateLink', { user_id: userId });
            
            if (res.success && res.magic_link) {
                navigator.clipboard.writeText(res.magic_link).then(() => {
                    showToast('<?= __("link_copied") ?>', 'success');
                    btn.innerHTML = '<i class="fas fa-check text-success"></i>';
                    setTimeout(() => { btn.innerHTML = icon; btn.disabled = false; }, 2000);
                }).catch(() => {
                    alert('Magic Link: ' + res.magic_link);
                    btn.innerHTML = icon;
                    btn.disabled = false;
                });
            } else {
                showToast(res.error || 'Failed to generate link', 'error');
                btn.innerHTML = icon;
                btn.disabled = false;
            }
        });
    });

    // Toggle User Status
    document.querySelectorAll('.toggle-user').forEach(toggle => {
        toggle.addEventListener('change', async (e) => {
            const userId = e.target.dataset.id;
            const isActive = e.target.checked ? 1 : 0;
            
            const res = await apiPost('?url=admin/toggleUserStatus', { user_id: userId, is_active: isActive });
            
            if (res.success) {
                showToast(isActive ? 'User Activated' : '<?= __("revoke_success") ?>', isActive ? 'success' : 'warning');
                const nameEl = document.getElementById(`user-name-${userId}`);
                const phoneEl = document.getElementById(`user-phone-${userId}`);
                if (isActive) {
                    nameEl.classList.remove('line-through', 'text-text-muted');
                    phoneEl.classList.remove('opacity-50');
                } else {
                    nameEl.classList.add('line-through', 'text-text-muted');
                    phoneEl.classList.add('opacity-50');
                }
            } else {
                e.target.checked = !isActive; // Revert
                showToast(res.error || 'Failed to toggle status', 'error');
            }
        });
    });

    // Delete User
    document.querySelectorAll('.btn-delete-user').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('<?= __("confirm_action") ?>')) return;
            
            const userId = btn.dataset.id;
            const icon = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const res = await apiPost('?url=admin/deleteUser', { user_id: userId });
            
            if (res.success) {
                document.getElementById(`user-row-${userId}`).remove();
                showToast('User deleted', 'success');
            } else {
                showToast(res.error || 'Failed to delete user', 'error');
                btn.innerHTML = icon;
                btn.disabled = false;
            }
        });
    });
});
</script>
