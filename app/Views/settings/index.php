<?php
/**
 * Consolidated Settings Dashboard
 */
$role = $_SESSION['role'] ?? 'staff';
?>

<div class="mb-6 animate-slideUp">
    <h2 class="text-2xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-accent to-accent-light drop-shadow-sm">
        <?= __('settings_title') ?>
    </h2>
</div>

<div class="space-y-8 pb-4">
    
    <?php if ($role === 'admin'): ?>
    <!-- SECTION 1: Management -->
    <div class="stagger">
        <h3 class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-2 px-2"><?= __('section_management') ?></h3>
        <div class="flex flex-col">
            <a href="?url=admin/users" class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group">
                <div class="flex items-center gap-4">
                    <i class="fas fa-users-cog text-lg text-info drop-shadow-[0_0_8px_rgba(99,102,241,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-semibold text-text-primary"><?= __('link_staff') ?></span>
                </div>
                <i class="fas fa-chevron-right text-xs text-text-muted group-hover:text-info transition-colors"></i>
            </a>
            <a href="?url=admin/settings" class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group">
                <div class="flex items-center gap-4">
                    <i class="fas fa-sliders-h text-lg text-emerald-400 drop-shadow-[0_0_8px_rgba(52,211,153,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-semibold text-text-primary">Configuration &amp; Permissions</span>
                </div>
                <i class="fas fa-chevron-right text-xs text-text-muted group-hover:text-emerald-400 transition-colors"></i>
            </a>
            <a href="?url=settings/priceCalculator" class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group">
                <div class="flex items-center gap-4">
                    <i class="fas fa-calculator text-lg text-rose-400 drop-shadow-[0_0_8px_rgba(251,113,133,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-semibold text-text-primary"><?= __('price_calculator') ?></span>
                </div>
                <i class="fas fa-chevron-right text-xs text-text-muted group-hover:text-rose-400 transition-colors"></i>
            </a>
        </div>
    </div>

    <!-- SECTION 2: Finance & Reports -->
    <div class="stagger">
        <h3 class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-2 px-2"><?= __('section_finance') ?></h3>
        <div class="flex flex-col">
            <a href="?url=analytics" class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group">
                <div class="flex items-center gap-4">
                    <i class="fas fa-chart-pie text-lg text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-semibold text-text-primary"><?= __('link_analytics') ?></span>
                </div>
                <i class="fas fa-chevron-right text-xs text-text-muted group-hover:text-amber-400 transition-colors"></i>
            </a>
            <a href="?url=finance/spreadCosts" class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group">
                <div class="flex items-center gap-4">
                    <i class="fas fa-money-bill-wave text-lg text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-semibold text-text-primary"><?= __('link_spread_costs') ?></span>
                </div>
                <i class="fas fa-chevron-right text-xs text-text-muted group-hover:text-cyan-400 transition-colors"></i>
            </a>
            <a href="?url=bazaar" class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group">
                <div class="flex items-center gap-4">
                    <i class="fas fa-receipt text-lg text-purple-400 drop-shadow-[0_0_8px_rgba(192,132,252,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-semibold text-text-primary">Expense Tracking</span>
                </div>
                <i class="fas fa-chevron-right text-xs text-text-muted group-hover:text-purple-400 transition-colors"></i>
            </a>
            <a href="?url=bazaar/history" class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group">
                <div class="flex items-center gap-4">
                    <i class="fas fa-history text-lg text-fuchsia-400 drop-shadow-[0_0_8px_rgba(232,121,249,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-semibold text-text-primary">Bazaar &amp; Sales History</span>
                </div>
                <i class="fas fa-chevron-right text-xs text-text-muted group-hover:text-fuchsia-400 transition-colors"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECTION 3: System -->
    <div class="stagger">
        <h3 class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-2 px-2"><?= __('section_system') ?></h3>
        <div class="flex flex-col">
            <?php 
                $oppLang = currentLang() === 'en' ? 'bn' : 'en'; 
                $urlParams = $_GET;
                $urlParams['lang'] = $oppLang;
                $langUrl = '?' . http_build_query($urlParams);
            ?>
            <a href="<?= $langUrl ?>" class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group">
                <div class="flex items-center gap-4">
                    <i class="fas fa-language text-lg text-blue-400 drop-shadow-[0_0_8px_rgba(96,165,250,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-semibold text-text-primary"><?= __('link_language') ?> (<?= strtoupper($oppLang) ?>)</span>
                </div>
                <i class="fas fa-sync-alt text-xs text-text-muted group-hover:text-blue-400 transition-colors"></i>
            </a>
            <div class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group cursor-pointer" onclick="if(typeof processQueue === 'function') { processQueue(); showToast('Sync triggered manually', 'info'); }">
                <div class="flex items-center gap-4">
                    <i class="fas fa-cloud-upload-alt text-lg text-sky-400 drop-shadow-[0_0_8px_rgba(56,189,248,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-semibold text-text-primary">Sync Status</span>
                </div>
                <span id="sync-status-badge" class="text-[0.65rem] font-bold px-2 py-0.5 rounded-full border border-success/30 text-success bg-success/10">Online</span>
            </div>
        </div>
    </div>

    <!-- SECTION 4: Account -->
    <div class="stagger">
        <div class="flex flex-col">
            <a href="?url=auth/logout" class="flex items-center justify-between py-4 px-2 border-b border-border/50 hover:bg-surface/50 transition-colors group">
                <div class="flex items-center gap-4">
                    <i class="fas fa-sign-out-alt text-lg text-red-400 drop-shadow-[0_0_8px_rgba(248,113,113,0.5)] group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-red-400"><?= __('link_logout') ?></span>
                </div>
            </a>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Basic sync badge update
    const badge = document.getElementById('sync-status-badge');
    if (!badge) return;
    
    const updateBadge = () => {
        if (navigator.onLine) {
            badge.textContent = 'Online';
            badge.className = 'text-[0.65rem] font-bold px-2 py-0.5 rounded-full border border-success/30 text-success bg-success/10';
        } else {
            badge.textContent = 'Offline';
            badge.className = 'text-[0.65rem] font-bold px-2 py-0.5 rounded-full border border-warning/30 text-warning bg-warning/10 pulse-accent';
        }
    };
    
    window.addEventListener('online', updateBadge);
    window.addEventListener('offline', updateBadge);
    updateBadge();
});
</script>
