<?php
/**
 * Admin Analytics Dashboard
 * Variables: $range, $from, $to, $salesSummary, $expenseSummary,
 *            $topSelling, $highWastage, $shiftBreakdown, $customerDues, $supplierDues
 */
$sales   = $salesSummary ?? [];
$expenses = $expenseSummary ?? [];
$netProfit = ((float)($sales['total_revenue'] ?? 0)) - ((float)($expenses['total'] ?? 0));
?>

<div class="mb-4 animate-slideUp">
    <h2 class="text-lg font-black">
        <i class="fas fa-chart-line text-accent mr-1"></i> <?= __('analytics') ?>
    </h2>
</div>

<!-- Date Range Selector -->
<div class="flex flex-wrap gap-2 mb-4 animate-slideUp">
    <?php foreach (['today' => __('today'), 'yesterday' => __('yesterday'), 'month' => __('this_month'), 'custom' => __('custom_range')] as $key => $label): ?>
    <a href="?url=analytics&range=<?= $key ?><?= $key === 'custom' ? '&from=' . $from . '&to=' . $to : '' ?>"
       class="text-xs font-bold px-3 py-1.5 rounded-full border transition-all duration-200
              <?= $range === $key ? 'bg-accent/10 border-accent/40 text-accent' : 'bg-card border-border text-text-muted hover:text-text-primary' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Custom Date Picker (shown when custom is active) -->
<?php if ($range === 'custom'): ?>
<form class="flex gap-2 mb-4 animate-slideUp" method="GET">
    <input type="hidden" name="url" value="analytics">
    <input type="hidden" name="range" value="custom">
    <input type="date" name="from" value="<?= $from ?>"
           class="flex-1 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:border-accent">
    <input type="date" name="to" value="<?= $to ?>"
           class="flex-1 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:border-accent">
    <button type="submit" class="bg-accent text-white px-4 rounded-lg font-bold text-sm">
        <i class="fas fa-search"></i>
    </button>
</form>
<?php endif; ?>

<!-- Revenue & Profit Cards -->
<div class="grid grid-cols-2 gap-3 mb-4 stagger">
    <div class="bg-card border border-border rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-accent leading-none">৳<?= number_format((float)($sales['total_revenue'] ?? 0)) ?></p>
        <p class="text-[0.6rem] text-text-muted mt-1 font-bold uppercase tracking-wider"><?= __('total_revenue') ?></p>
    </div>
    <div class="bg-card border border-border rounded-xl p-4 text-center">
        <p class="text-2xl font-black leading-none <?= $netProfit >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">
            ৳<?= number_format(abs($netProfit)) ?>
        </p>
        <p class="text-[0.6rem] text-text-muted mt-1 font-bold uppercase tracking-wider"><?= __('net_profit') ?></p>
    </div>
    <div class="bg-card border border-border rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-amber-400 leading-none"><?= number_format((float)($sales['total_sold'] ?? 0)) ?></p>
        <p class="text-[0.6rem] text-text-muted mt-1 font-bold uppercase tracking-wider"><?= __('sold') ?></p>
    </div>
    <div class="bg-card border border-border rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-indigo-400 leading-none">৳<?= number_format((float)($expenses['total'] ?? 0)) ?></p>
        <p class="text-[0.6rem] text-text-muted mt-1 font-bold uppercase tracking-wider"><?= __('total_expenses') ?></p>
    </div>
</div>

<!-- Expense Breakdown -->
<div class="bg-card border border-border rounded-xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3"><?= __('total_expenses') ?> Breakdown</p>
    <div class="space-y-2">
        <?php
        $expItems = [
            ['label' => __('bazaar'),       'value' => $expenses['bazaar'] ?? 0, 'color' => 'text-emerald-400'],
            ['label' => 'Fixed Daily',      'value' => $expenses['fixed'] ?? 0,  'color' => 'text-amber-400'],
            ['label' => __('spread_cost'),   'value' => $expenses['spread'] ?? 0, 'color' => 'text-indigo-400'],
            ['label' => __('salary'),        'value' => $expenses['salary'] ?? 0, 'color' => 'text-blue-400'],
            ['label' => __('wastage'),       'value' => $expenses['wastage'] ?? 0,'color' => 'text-red-400'],
        ];
        foreach ($expItems as $exp):
        ?>
        <div class="flex justify-between text-sm">
            <span class="text-text-muted"><?= $exp['label'] ?></span>
            <span class="font-bold <?= $exp['color'] ?>">৳<?= number_format((float)$exp['value']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Shift Performance -->
<?php if (!empty($shiftBreakdown)): ?>
<div class="bg-card border border-border rounded-xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3">
        <i class="fas fa-clock text-accent mr-1"></i> <?= __('shift') ?> Performance
    </p>
    <div class="space-y-2">
        <?php foreach ($shiftBreakdown as $shift): ?>
        <div class="flex items-center justify-between text-sm">
            <span class="font-semibold capitalize"><?= __($shift['shift']) ?></span>
            <div class="text-right">
                <span class="font-bold text-accent">৳<?= number_format((float)$shift['revenue']) ?></span>
                <span class="text-text-muted text-xs ml-1">(<?= $shift['total_sold'] ?> sold)</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Top Selling Items -->
<?php if (!empty($topSelling)): ?>
<div class="bg-card border border-border rounded-xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3">
        <i class="fas fa-trophy text-amber-400 mr-1"></i> <?= __('top_selling') ?>
    </p>
    <?php foreach ($topSelling as $i => $item): ?>
    <div class="flex items-center justify-between text-sm py-2 <?= $i < count($topSelling) - 1 ? 'border-b border-border/50' : '' ?>">
        <div class="flex items-center gap-2">
            <span class="w-5 h-5 bg-accent/10 text-accent text-xs font-bold rounded-full flex items-center justify-center"><?= $i + 1 ?></span>
            <span class="font-semibold"><?= htmlspecialchars(currentLang() === 'bn' ? ($item['item_name_bn'] ?: $item['item_name']) : $item['item_name']) ?></span>
        </div>
        <div class="text-right">
            <span class="font-bold"><?= $item['total_sold'] ?></span>
            <span class="text-text-muted text-xs ml-1">৳<?= number_format((float)$item['total_revenue']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- High Wastage Items -->
<?php if (!empty($highWastage)): ?>
<div class="bg-card border border-red-500/20 rounded-xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-red-400 uppercase tracking-widest mb-3">
        <i class="fas fa-triangle-exclamation mr-1"></i> <?= __('high_wastage') ?>
    </p>
    <?php foreach ($highWastage as $item): ?>
    <div class="flex items-center justify-between text-sm py-2 border-b border-border/50 last:border-0">
        <span class="font-semibold"><?= htmlspecialchars(currentLang() === 'bn' ? ($item['item_name_bn'] ?: $item['item_name']) : $item['item_name']) ?></span>
        <div class="text-right">
            <span class="font-bold text-red-400"><?= $item['total_wastage'] ?></span>
            <span class="text-text-muted text-xs ml-1">৳<?= number_format((float)$item['wastage_cost']) ?> loss</span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Customer Dues -->
<?php if (!empty($customerDues)): ?>
<div class="bg-card border border-amber-500/20 rounded-xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-amber-400 uppercase tracking-widest mb-3">
        <i class="fas fa-hand-holding-dollar mr-1"></i> <?= __('customer_dues') ?>
    </p>
    <?php foreach ($customerDues as $due): ?>
    <div class="flex items-center justify-between text-sm py-2 border-b border-border/50 last:border-0">
        <div>
            <span class="font-semibold"><?= htmlspecialchars($due['customer_name']) ?></span>
            <span class="text-text-muted text-xs block"><?= $due['log_date'] ?></span>
        </div>
        <span class="font-bold text-amber-400">৳<?= number_format((float)$due['due_amount']) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Supplier Dues -->
<?php if (!empty($supplierDues)): ?>
<div class="bg-card border border-border rounded-xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3">
        <i class="fas fa-truck mr-1"></i> <?= __('supplier_dues') ?>
    </p>
    <?php foreach ($supplierDues as $sup): ?>
    <div class="flex items-center justify-between text-sm py-2 border-b border-border/50 last:border-0">
        <span class="font-semibold"><?= htmlspecialchars(currentLang() === 'bn' ? ($sup['name_bn'] ?: $sup['name']) : $sup['name']) ?></span>
        <span class="font-bold text-red-400">৳<?= number_format((float)$sup['total_due']) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

