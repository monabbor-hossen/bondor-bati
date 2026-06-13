<?php
/**
 * Admin Analytics Dashboard — Revenue, Costs & Net Profit
 * Variables: $range, $start, $end, $metrics, $topItems,
 *            $expenseSummary, $highWastage, $shiftBreakdown,
 *            $customerDues, $supplierDues
 */
$metrics  = $metrics  ?? [];
$expenses = $expenseSummary ?? [];

// PHP helper: format as ৳ amount
function fmtTK(float $n): string {
    return '৳' . number_format($n, 0, '.', ',');
}
?>

<!-- Page Header -->
<div class="mb-5 animate-slideUp">
    <h1 class="text-lg font-black flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center">
            <i class="fas fa-chart-line text-accent text-sm"></i>
        </span>
        <?= __('analytics') ?>
    </h1>
</div>

<!-- ─── Quick Range Pills ─── -->
<div class="flex flex-wrap gap-2 mb-4 animate-slideUp">
    <?php
    $pills = [
        'today'     => __('today'),
        'yesterday' => __('yesterday'),
        'last7'     => __('last_7_days'),
        'month'     => __('this_month'),
        'custom'    => __('custom_range'),
    ];
    foreach ($pills as $key => $label):
        $active = $range === $key;
    ?>
    <a href="?url=analytics&range=<?= $key ?><?= $key === 'custom' ? '&start=' . $start . '&end=' . $end : '' ?>"
       class="text-xs font-bold px-3 py-1.5 rounded-full border transition-all duration-200
              <?= $active ? 'bg-accent/10 border-accent/40 text-accent' : 'bg-card border-border text-text-muted hover:text-text-primary' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ─── Custom Date Picker ─── -->
<form method="GET" class="<?= $range === 'custom' ? 'flex' : 'hidden' ?> gap-2 mb-5 animate-slideUp">
    <input type="hidden" name="url" value="analytics">
    <input type="hidden" name="range" value="custom">
    <input type="date" name="start" value="<?= htmlspecialchars($start) ?>"
           class="flex-1 bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary outline-none focus:border-accent">
    <input type="date" name="end" value="<?= htmlspecialchars($end) ?>"
           class="flex-1 bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary outline-none focus:border-accent">
    <button type="submit"
            class="bg-accent/20 text-accent border border-accent/50 rounded-lg px-4 py-2 text-sm font-bold hover:bg-accent hover:text-white transition-colors">
        <i class="fas fa-filter"></i>
    </button>
</form>

<!-- ─── 2×2 Metrics Grid ─── -->
<div class="grid grid-cols-2 gap-3 mb-5 stagger">

    <!-- Card 1 · Gross Revenue -->
    <div class="relative bg-card border border-border rounded-2xl p-4 text-center overflow-hidden group hover:border-emerald-500/40 transition-all duration-300">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>
        <div class="relative">
            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center mx-auto mb-2">
                <i class="fas fa-coins text-emerald-400 text-sm"></i>
            </div>
            <p class="text-xl font-black text-emerald-400 leading-none tabular-nums">
                <?= fmtTK((float)($metrics['total_revenue'] ?? 0)) ?>
            </p>
            <p class="text-[0.6rem] text-text-muted mt-1.5 font-bold uppercase tracking-wider"><?= __('total_revenue') ?></p>
        </div>
    </div>

    <!-- Card 2 · Raw Material Cost -->
    <div class="relative bg-card border border-border rounded-2xl p-4 text-center overflow-hidden group hover:border-amber-500/40 transition-all duration-300">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>
        <div class="relative">
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center mx-auto mb-2">
                <i class="fas fa-box-open text-amber-400 text-sm"></i>
            </div>
            <p class="text-xl font-black text-amber-400 leading-none tabular-nums">
                <?= fmtTK((float)($metrics['total_raw_cost'] ?? 0)) ?>
            </p>
            <p class="text-[0.6rem] text-text-muted mt-1.5 font-bold uppercase tracking-wider"><?= __('total_raw_cost') ?></p>
        </div>
    </div>

    <!-- Card 3 · Additional Costs -->
    <div class="relative bg-card border border-border rounded-2xl p-4 text-center overflow-hidden group hover:border-sky-500/40 transition-all duration-300">
        <div class="absolute inset-0 bg-gradient-to-br from-sky-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>
        <div class="relative">
            <div class="w-9 h-9 rounded-xl bg-sky-500/10 flex items-center justify-center mx-auto mb-2">
                <i class="fas fa-receipt text-sky-400 text-sm"></i>
            </div>
            <p class="text-xl font-black text-sky-400 leading-none tabular-nums">
                <?= fmtTK((float)($metrics['total_additional_cost'] ?? 0)) ?>
            </p>
            <p class="text-[0.6rem] text-text-muted mt-1.5 font-bold uppercase tracking-wider"><?= __('total_additional_cost') ?></p>
        </div>
    </div>

    <!-- Card 4 · Net Profit (glowing accent) -->
    <?php $profit = (float)($metrics['net_profit'] ?? 0); ?>
    <div class="relative bg-card border rounded-2xl p-4 text-center overflow-hidden transition-all duration-300
                <?= $profit >= 0 ? 'border-accent/40 shadow-[0_0_20px_rgba(244,63,94,0.15)]' : 'border-red-500/40 shadow-[0_0_20px_rgba(239,68,68,0.15)]' ?>">
        <div class="absolute inset-0 bg-gradient-to-br <?= $profit >= 0 ? 'from-accent/8' : 'from-red-500/8' ?> to-transparent rounded-2xl"></div>
        <div class="relative">
            <div class="w-9 h-9 rounded-xl <?= $profit >= 0 ? 'bg-accent/10' : 'bg-red-500/10' ?> flex items-center justify-center mx-auto mb-2">
                <i class="fas fa-chart-line <?= $profit >= 0 ? 'text-accent' : 'text-red-400' ?> text-sm"></i>
            </div>
            <p class="text-xl font-black <?= $profit >= 0 ? 'text-accent' : 'text-red-400' ?> leading-none tabular-nums">
                <?= ($profit < 0 ? '-' : '') . fmtTK(abs($profit)) ?>
            </p>
            <p class="text-[0.6rem] text-text-muted mt-1.5 font-bold uppercase tracking-wider"><?= __('item_net_profit') ?></p>
        </div>
    </div>

</div>

<!-- ─── Top Selling Items ─── -->
<?php if (!empty($topItems)): ?>
<div class="bg-card border border-border rounded-2xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3 flex items-center gap-1.5">
        <i class="fas fa-trophy text-amber-400"></i>
        <?= __('top_selling_items') ?>
    </p>
    <div class="space-y-0">
        <?php foreach ($topItems as $idx => $item):
            $name = (currentLang() === 'bn' && !empty($item['item_name_bn']))
                ? $item['item_name_bn']
                : $item['item_name'];
            $pct  = ($metrics['total_revenue'] > 0)
                ? round(($item['item_revenue'] / $metrics['total_revenue']) * 100)
                : 0;
            $rankColors = ['text-amber-400', 'text-slate-300', 'text-orange-500', 'text-text-muted', 'text-text-muted'];
        ?>
        <div class="py-2.5 <?= $idx < count($topItems) - 1 ? 'border-b border-border/40' : '' ?>">
            <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-surface flex items-center justify-center text-[0.6rem] font-black <?= $rankColors[$idx] ?? 'text-text-muted' ?>">
                        <?= $idx + 1 ?>
                    </span>
                    <span class="text-sm font-semibold"><?= htmlspecialchars($name) ?></span>
                </div>
                <div class="text-right leading-none">
                    <span class="text-xs font-black text-accent"><?= fmtTK((float)$item['item_revenue']) ?></span>
                    <span class="text-[0.6rem] text-text-muted ml-1"><?= $item['total_sold'] ?> <?= __('qty_sold') ?></span>
                </div>
            </div>
            <!-- mini progress bar -->
            <div class="h-1 bg-surface rounded-full overflow-hidden">
                <div class="h-full bg-accent/60 rounded-full transition-all duration-700" style="width: <?= $pct ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ─── Expense Breakdown ─── -->
<?php if (!empty($expenses)): ?>
<div class="bg-card border border-border rounded-2xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3">
        <?= __('total_expenses') ?> Breakdown
    </p>
    <div class="space-y-2">
        <?php
        $expItems = [
            ['label' => __('bazaar'),      'value' => $expenses['bazaar']  ?? 0, 'color' => 'text-emerald-400'],
            ['label' => 'Fixed Daily',     'value' => $expenses['fixed']   ?? 0, 'color' => 'text-amber-400'],
            ['label' => __('spread_cost'), 'value' => $expenses['spread']  ?? 0, 'color' => 'text-indigo-400'],
            ['label' => __('salary'),      'value' => $expenses['salary']  ?? 0, 'color' => 'text-blue-400'],
            ['label' => __('wastage'),     'value' => $expenses['wastage'] ?? 0, 'color' => 'text-red-400'],
        ];
        foreach ($expItems as $exp):
        ?>
        <div class="flex justify-between text-sm">
            <span class="text-text-muted"><?= $exp['label'] ?></span>
            <span class="font-bold <?= $exp['color'] ?>"><?= fmtTK((float)$exp['value']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ─── Shift Performance ─── -->
<?php if (!empty($shiftBreakdown)): ?>
<div class="bg-card border border-border rounded-2xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3">
        <i class="fas fa-clock text-accent mr-1"></i> <?= __('shift') ?> Performance
    </p>
    <div class="space-y-2">
        <?php foreach ($shiftBreakdown as $shift): ?>
        <div class="flex items-center justify-between text-sm">
            <span class="font-semibold capitalize"><?= __($shift['shift']) ?></span>
            <div class="text-right">
                <span class="font-bold text-accent"><?= fmtTK((float)$shift['revenue']) ?></span>
                <span class="text-text-muted text-xs ml-1">(<?= $shift['total_sold'] ?> <?= __('sold') ?>)</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ─── High Wastage ─── -->
<?php if (!empty($highWastage)): ?>
<div class="bg-card border border-red-500/20 rounded-2xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-red-400 uppercase tracking-widest mb-3">
        <i class="fas fa-triangle-exclamation mr-1"></i> <?= __('high_wastage') ?>
    </p>
    <?php foreach ($highWastage as $item): ?>
    <div class="flex items-center justify-between text-sm py-2 border-b border-border/50 last:border-0">
        <span class="font-semibold"><?= htmlspecialchars(currentLang() === 'bn' ? ($item['item_name_bn'] ?: $item['item_name']) : $item['item_name']) ?></span>
        <div class="text-right">
            <span class="font-bold text-red-400"><?= $item['total_wastage'] ?></span>
            <span class="text-text-muted text-xs ml-1"><?= fmtTK((float)$item['wastage_cost']) ?> loss</span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ─── Customer Dues ─── -->
<?php if (!empty($customerDues)): ?>
<div class="bg-card border border-amber-500/20 rounded-2xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-amber-400 uppercase tracking-widest mb-3">
        <i class="fas fa-hand-holding-dollar mr-1"></i> <?= __('customer_dues') ?>
    </p>
    <div id="analytics-dues-list" class="space-y-0">
        <?php foreach ($customerDues as $due): ?>
        <div class="due-row flex items-center justify-between py-2.5 border-b border-border/40 last:border-0 transition-all duration-500"
             data-due-id="<?= (int)$due['id'] ?>">
            <div class="flex-1 min-w-0 mr-2">
                <span class="text-sm font-semibold block truncate"><?= htmlspecialchars($due['customer_name']) ?></span>
                <span class="text-[0.6rem] text-text-muted"><?= $due['log_date'] ?></span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="font-bold text-amber-400 text-sm"><?= fmtTK((float)$due['due_amount']) ?></span>
                <button type="button"
                        onclick="settleBaki(this, <?= (int)$due['id'] ?>)"
                        title="<?= __('settle') ?>"
                        class="settle-btn flex items-center gap-1 text-[0.6rem] font-black uppercase tracking-wide
                               bg-emerald-500/10 text-emerald-400 border border-emerald-500/30
                               hover:bg-emerald-500 hover:text-white hover:border-emerald-500
                               px-2.5 py-1 rounded-lg transition-all duration-200 active:scale-95">
                    <i class="fas fa-check"></i>
                    <?= __('paid') ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
async function settleBaki(btn, dueId) {
    if (!confirm('<?= __("confirm_action") ?>')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const res = await apiPost('?url=inventory/settleDue', { due_id: dueId });
        if (res.success) {
            const row = btn.closest('.due-row');
            row.style.transition = 'opacity 0.4s, transform 0.4s, max-height 0.4s';
            row.style.opacity    = '0';
            row.style.transform  = 'translateX(30px)';
            row.style.maxHeight  = row.offsetHeight + 'px';
            setTimeout(() => {
                row.style.maxHeight = '0';
                row.style.overflow  = 'hidden';
                row.style.padding   = '0';
                row.style.margin    = '0';
            }, 400);
            setTimeout(() => row.remove(), 800);
            if (typeof showToast === 'function') showToast('<?= __("success") ?>', 'success');
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> <?= __("paid") ?>';
            if (typeof showToast === 'function') showToast(res.error || '<?= __("error") ?>', 'error');
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> <?= __("paid") ?>';
        if (typeof showToast === 'function') showToast('<?= __("error") ?>', 'error');
    }
}
</script>


<!-- ─── Supplier Dues ─── -->
<?php if (!empty($supplierDues)): ?>
<div class="bg-card border border-border rounded-2xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3">
        <i class="fas fa-truck mr-1"></i> <?= __('supplier_dues') ?>
    </p>
    <?php foreach ($supplierDues as $sup): ?>
    <div class="flex items-center justify-between text-sm py-2 border-b border-border/50 last:border-0">
        <span class="font-semibold"><?= htmlspecialchars(currentLang() === 'bn' ? ($sup['name_bn'] ?: $sup['name']) : $sup['name']) ?></span>
        <span class="font-bold text-red-400"><?= fmtTK((float)$sup['total_due']) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
