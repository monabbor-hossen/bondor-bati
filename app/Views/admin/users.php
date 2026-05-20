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
        
        <div class="relative">
            <input type="text" id="staffName" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Name">
            <label for="staffName" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">
                <?= __('staff_name') ?>
            </label>
        </div>

        <div class="relative">
            <input type="text" id="staffPhone" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Phone">
            <label for="staffPhone" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">
                <?= __('whatsapp_number') ?>
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

        <button type="submit" class="w-full bg-accent hover:bg-accent-light text-white font-bold py-3 rounded-xl transition-colors mt-2 shadow-[0_0_15px_rgba(244,63,94,0.3)]">
            <i class="fas fa-plus mr-1"></i> <?= __('submit') ?>
        </button>
    </form>
</div>

<!-- Current Staff List -->
<div class="space-y-3 stagger" id="staffList">
    <?php foreach ($users as $user): ?>
    <div class="bg-card border border-border rounded-xl p-4 flex items-center justify-between">
        <div>
            <h4 class="font-bold text-sm mb-0.5"><?= htmlspecialchars($user['name' . (currentLang() === 'bn' ? '_bn' : '')] ?? $user['name']) ?></h4>
            <div class="text-xs text-text-muted flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[0.6rem] font-bold <?= $user['role'] === 'admin' ? 'bg-info/20 text-info border border-info/30' : 'bg-surface border border-border' ?> uppercase">
                    <?= __('role_' . $user['role']) ?>
                </span>
                <span><i class="fab fa-whatsapp text-success"></i> <?= htmlspecialchars($user['username']) ?></span>
            </div>
        </div>
        <button class="btn-generate-link w-10 h-10 rounded-full bg-surface border border-border text-text-muted hover:text-accent hover:border-accent transition-colors flex items-center justify-center flex-shrink-0" data-id="<?= $user['id'] ?>" title="<?= __('generate_link') ?>">
            <i class="fas fa-link"></i>
        </button>
    </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Add Staff Submit
    document.getElementById('addStaffForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const name = document.getElementById('staffName').value;
        const phone = document.getElementById('staffPhone').value;
        const role = document.getElementById('staffRole').value;
        
        const btn = e.target.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const res = await apiPost('?url=auth/addStaff', { name, phone, role });
        
        if (res.success) {
            if (res.offline) {
                showToast(res.message, 'warning');
            } else {
                showToast('Staff added successfully!', 'success');
                // Copy link to clipboard
                if (res.magic_link) {
                    navigator.clipboard.writeText(res.magic_link).then(() => {
                        showToast('<?= __("link_copied") ?>', 'info');
                    }).catch(() => {
                        // fallback if clipboard api fails
                    });
                }
                setTimeout(() => window.location.reload(), 1500);
            }
            e.target.reset();
        } else {
            showToast(res.error || 'Failed to add staff', 'error');
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
});
</script>
