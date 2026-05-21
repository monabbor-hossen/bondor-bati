<?php
/**
 * Spread Costs & Fixed Costs Configuration
 * Variables: $fixedCosts, $spreadCosts
 */
?>

<div class="mb-6 animate-slideUp">
    <h2 class="text-xl font-black tracking-tight flex items-center gap-2">
        <i class="fas fa-money-bill-wave text-cyan-400"></i> <?= __('link_spread_costs') ?>
    </h2>
</div>

<!-- 1. Daily Fixed Costs -->
<div class="bg-card border border-border/50 rounded-xl p-5 mb-6 animate-slideUp stagger">
    <h3 class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-4"><?= __('daily_fixed_costs') ?></h3>
    <form id="fixedCostForm" data-id="0" class="space-y-6">
        <div class="relative">
            <input type="text" id="fcName" required class="peer w-full bg-transparent border-b border-border focus:border-cyan-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Name">
            <label for="fcName" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-cyan-400">
                <?= __('cost_name') ?>
            </label>
        </div>
        <div class="relative">
            <input type="number" step="0.01" id="fcAmount" required class="peer w-full bg-transparent border-b border-border focus:border-cyan-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Amount">
            <label for="fcAmount" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-cyan-400">
                <?= __('cost_amount') ?> (৳)
            </label>
        </div>
        <button type="submit" class="w-full bg-cyan-500/20 text-cyan-400 hover:bg-cyan-500/30 border border-cyan-500/30 font-bold py-2.5 rounded-xl transition-colors">
            <i class="fas fa-plus mr-1"></i> <?= __('add_cost') ?>
        </button>
    </form>
</div>

<!-- 2. Capital Spread Costs -->
<div class="bg-card border border-border/50 rounded-xl p-5 mb-6 animate-slideUp stagger" style="animation-delay: 0.1s">
    <h3 class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-4"><?= __('spread_costs_setup') ?></h3>
    <form id="spreadCostForm" data-id="0" class="space-y-6">
        <div class="relative">
            <input type="text" id="scName" required class="peer w-full bg-transparent border-b border-border focus:border-purple-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Asset Name">
            <label for="scName" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-purple-400">
                <?= __('asset_name') ?>
            </label>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="relative">
                <input type="number" step="0.01" id="scTotal" required class="peer w-full bg-transparent border-b border-border focus:border-purple-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Total">
                <label for="scTotal" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-purple-400">
                    <?= __('total_cost') ?> (৳)
                </label>
            </div>
            <div class="relative">
                <input type="number" id="scDays" required class="peer w-full bg-transparent border-b border-border focus:border-purple-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Days">
                <label for="scDays" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-purple-400">
                    <?= __('spread_days') ?>
                </label>
            </div>
        </div>
        <div class="text-xs text-text-muted text-right">
            <?= __('daily_deduction') ?>: <span id="scDailyCalc" class="font-bold text-purple-400">৳0.00</span>
        </div>
        <button type="submit" class="w-full bg-purple-500/20 text-purple-400 hover:bg-purple-500/30 border border-purple-500/30 font-bold py-2.5 rounded-xl transition-colors">
            <i class="fas fa-plus mr-1"></i> <?= __('add_cost') ?>
        </button>
    </form>
</div>

<!-- Summaries -->
<div class="space-y-4 animate-slideUp stagger" style="animation-delay: 0.2s">
    <?php if (!empty($fixedCosts)): ?>
    <div>
        <h4 class="text-xs font-bold text-cyan-400 mb-2 uppercase tracking-wide"><?= __('daily_fixed_costs') ?></h4>
        <div class="space-y-2">
            <?php foreach ($fixedCosts as $fc): ?>
            <div class="flex justify-between items-center py-2 px-3 bg-surface border border-border/50 rounded-lg">
                <div class="flex-1">
                    <span class="text-sm font-semibold block"><?= htmlspecialchars($fc['name']) ?></span>
                    <span class="text-sm font-black text-text-primary">৳<?= number_format($fc['daily_amount'], 2) ?></span>
                </div>
                <button class="btn-edit-fc p-2 text-text-muted hover:text-cyan-400 transition-colors" data-item='<?= htmlspecialchars(json_encode($fc), ENT_QUOTES, 'UTF-8') ?>'>
                    <i class="fas fa-pencil-alt"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($spreadCosts)): ?>
    <div>
        <h4 class="text-xs font-bold text-purple-400 mb-2 uppercase tracking-wide"><?= __('spread_costs_setup') ?></h4>
        <div class="space-y-2">
            <?php foreach ($spreadCosts as $sc): ?>
            <div class="flex justify-between items-center py-2 px-3 bg-surface border border-border/50 rounded-lg">
                <div class="flex-1">
                    <span class="text-sm font-semibold block"><?= htmlspecialchars($sc['asset_name']) ?></span>
                    <span class="text-[0.6rem] text-text-muted">Total: ৳<?= number_format($sc['total_cost'], 0) ?> / <?= $sc['spread_days'] ?> days</span>
                    <div class="text-sm font-black text-purple-400">৳<?= number_format($sc['daily_deduction'], 2) ?> / day</div>
                </div>
                <button class="btn-edit-sc p-2 text-text-muted hover:text-purple-400 transition-colors" data-item='<?= htmlspecialchars(json_encode($sc), ENT_QUOTES, 'UTF-8') ?>'>
                    <i class="fas fa-pencil-alt"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Live calculation for Spread Cost
    const totalInput = document.getElementById('scTotal');
    const daysInput = document.getElementById('scDays');
    const calcOut = document.getElementById('scDailyCalc');

    const updateCalc = () => {
        const total = parseFloat(totalInput.value) || 0;
        const days = parseInt(daysInput.value) || 0;
        if (days > 0) {
            calcOut.textContent = '৳' + (total / days).toFixed(2);
        } else {
            calcOut.textContent = '৳0.00';
        }
    };
    totalInput.addEventListener('input', updateCalc);
    daysInput.addEventListener('input', updateCalc);

    // Edit Fixed Costs
    document.querySelectorAll('.btn-edit-fc').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = JSON.parse(btn.dataset.item);
            const form = document.getElementById('fixedCostForm');
            form.dataset.id = item.id;
            document.getElementById('fcName').value = item.name;
            document.getElementById('fcAmount').value = item.daily_amount;
            form.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save mr-1"></i> <?= __("btn_update") ?? "Update" ?>';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // Edit Spread Costs
    document.querySelectorAll('.btn-edit-sc').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = JSON.parse(btn.dataset.item);
            const form = document.getElementById('spreadCostForm');
            form.dataset.id = item.id;
            document.getElementById('scName').value = item.asset_name;
            document.getElementById('scTotal').value = item.total_cost;
            document.getElementById('scDays').value = item.spread_days;
            updateCalc();
            form.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save mr-1"></i> <?= __("btn_update") ?? "Update" ?>';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // Fixed Cost Form
    document.getElementById('fixedCostForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const res = await apiPost('?url=finance/addFixedCost', {
            id: form.dataset.id || 0,
            name: document.getElementById('fcName').value,
            amount: document.getElementById('fcAmount').value
        });
        
        if (res.success) {
            showToast('Fixed Cost Saved', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(res.error || 'Failed to add cost', 'error');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });

    // Spread Cost Form
    document.getElementById('spreadCostForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const res = await apiPost('?url=finance/addSpreadCost', {
            id: form.dataset.id || 0,
            asset_name: document.getElementById('scName').value,
            total_cost: totalInput.value,
            spread_days: daysInput.value
        });
        
        if (res.success) {
            showToast('Spread Cost Saved', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(res.error || 'Failed to add cost', 'error');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
});
</script>
