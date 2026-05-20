<?php
/**
 * Smart Dashboard View
 * Variables: $userName, $role, $gasInfo, $bazaarSuggestions, $lowStockItems,
 *            $todayStats, $pendingOrders, $upcomingEvent
 */
$stats = $todayStats ?? [];
$gas   = $gasInfo ?? [];
?>

<!-- Welcome Strip -->
<div class="flex items-center justify-between mb-5 animate-slideUp">
    <div>
        <p class="text-[0.7rem] text-text-muted font-medium"><?= __('welcome_back') ?></p>
        <h2 class="text-xl font-black"><?= htmlspecialchars($userName ?? 'Staff') ?> 👋</h2>
    </div>
    <a href="?url=auth/logout" id="btn-logout"
       class="text-xs font-bold text-accent bg-accent/10 border border-accent/30 px-3 py-1.5 rounded-full
              hover:bg-accent/20 transition-all duration-200">
        <?= __('sign_out') ?>
    </a>
</div>

<!-- Pending Orders Banner -->
<?php if (($pendingOrders ?? 0) > 0): ?>
<div class="bg-amber-500/10 border border-amber-500/30 text-amber-400 px-4 py-3 rounded-xl text-sm font-semibold mb-4 flex items-center gap-2 animate-slideUp">
    <i class="fas fa-box-open"></i>
    <span><?= $pendingOrders ?> <?= __('pending_orders') ?> <?= __('today') ?>!</span>
</div>
<?php endif; ?>

<!-- Upcoming Event Banner -->
<?php if ($upcomingEvent): ?>
<div class="bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 px-4 py-3 rounded-xl text-sm font-medium mb-4 flex items-center gap-2 animate-slideUp">
    <i class="fas fa-calendar-star"></i>
    <span>
        <strong><?= htmlspecialchars(currentLang() === 'bn' ? ($upcomingEvent['event_name_bn'] ?: $upcomingEvent['event_name']) : $upcomingEvent['event_name']) ?></strong>
        — <?= date('d M', strtotime($upcomingEvent['event_date'])) ?>
        (<?= $upcomingEvent['impact_multiplier'] ?>x <?= __('multiplier') ?>)
    </span>
</div>
<?php endif; ?>

<!-- Low Stock Alerts -->
<?php if (!empty($lowStockItems)): ?>
<div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl text-sm font-medium mb-4 animate-slideUp">
    <div class="flex items-center gap-2 mb-2">
        <i class="fas fa-triangle-exclamation"></i>
        <strong><?= __('low_stock_alert') ?></strong>
        <span class="ml-auto text-xs bg-red-500/20 px-2 py-0.5 rounded-full"><?= count($lowStockItems) ?> <?= __('items_below_threshold') ?></span>
    </div>
    <div class="space-y-1">
        <?php foreach (array_slice($lowStockItems, 0, 3) as $item): ?>
        <div class="flex justify-between text-xs">
            <span><?= htmlspecialchars(currentLang() === 'bn' ? ($item['item_name_bn'] ?: $item['item_name']) : $item['item_name']) ?></span>
            <span class="font-bold"><?= $item['current_qty'] ?> / <?= $item['min_stock_threshold'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Today's Stats Grid -->
<div class="grid grid-cols-2 gap-3 mb-5 stagger">
    <div class="bg-card border border-border rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-accent leading-none"><?= number_format((float)($stats['total_sold'] ?? 0)) ?></p>
        <p class="text-[0.65rem] text-text-muted mt-1 font-semibold uppercase tracking-wider"><?= __('sold') ?></p>
    </div>
    <div class="bg-card border border-border rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-emerald-400 leading-none">৳<?= number_format((float)($stats['total_revenue'] ?? 0)) ?></p>
        <p class="text-[0.65rem] text-text-muted mt-1 font-semibold uppercase tracking-wider"><?= __('total_revenue') ?></p>
    </div>
    <div class="bg-card border border-border rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-amber-400 leading-none"><?= number_format((float)($stats['total_comp'] ?? 0)) ?></p>
        <p class="text-[0.65rem] text-text-muted mt-1 font-semibold uppercase tracking-wider"><?= __('complimentary') ?></p>
    </div>
    <div class="bg-card border border-border rounded-xl p-4 text-center">
        <p class="text-2xl font-black text-indigo-400 leading-none">৳<?= number_format((float)($stats['total_due'] ?? 0)) ?></p>
        <p class="text-[0.65rem] text-text-muted mt-1 font-semibold uppercase tracking-wider"><?= __('due_baki') ?></p>
    </div>
</div>

<!-- Shift Status -->
<?php
$allShifts = ['morning', 'evening', 'night'];
$closedShifts = $stats['closed_shifts'] ?? [];
?>
<div class="mb-5 animate-slideUp">
    <p class="text-[0.7rem] font-bold text-text-muted uppercase tracking-widest mb-2"><?= __('shift') ?> <?= __('status') ?></p>
    <div class="flex gap-2">
        <?php foreach ($allShifts as $s): ?>
        <div class="flex-1 text-center py-2 rounded-lg text-xs font-bold border
                    <?= in_array($s, $closedShifts)
                        ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400'
                        : 'bg-card border-border text-text-muted' ?>">
            <?= __($s) ?>
            <?= in_array($s, $closedShifts) ? '<i class="fas fa-check ml-1"></i>' : '' ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     GAS STATUS WIDGET
════════════════════════════════════════════════════════ -->
<div class="mb-5 animate-slideUp">
    <p class="text-[0.7rem] font-bold text-text-muted uppercase tracking-widest mb-2">
        <i class="fas fa-fire text-accent mr-1"></i> <?= __('gas_status') ?>
    </p>
    <div class="bg-card border border-border rounded-xl p-4">
        <?php
        $gasStatus = $gas['status'] ?? 'no_data';
        $statusColor = match($gasStatus) {
            'critical' => 'text-red-400',
            'warning'  => 'text-amber-400',
            'ok'       => 'text-emerald-400',
            default    => 'text-text-muted',
        };
        ?>

        <?php if ($gasStatus === 'no_data'): ?>
            <p class="text-text-muted text-sm"><?= __('no_gas_data') ?></p>
        <?php else: ?>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-3xl font-black <?= $statusColor ?> leading-none"><?= $gas['days'] ?></p>
                    <p class="text-[0.65rem] text-text-muted mt-1 font-semibold uppercase"><?= __('days_remaining') ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold <?= $statusColor ?>"><?= __($gasStatus === 'critical' ? 'critical' : ($gasStatus === 'warning' ? 'low_plan' : 'sufficient')) ?></p>
                    <p class="text-[0.65rem] text-text-muted mt-1"><?= __('refill_date') ?>: <?= $gas['date'] ?></p>
                    <p class="text-[0.65rem] text-text-muted">৳<?= number_format($gas['remaining'] ?? 0) ?> <?= __('remaining') ?></p>
                </div>
            </div>
            <?php if ($gasStatus === 'critical'): ?>
            <div class="mt-3 h-1.5 bg-surface rounded-full overflow-hidden">
                <div class="h-full bg-red-500 rounded-full pulse-accent" style="width: <?= min(100, max(5, ($gas['days'] / 30) * 100)) ?>%"></div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     BAZAAR SUGGESTIONS WIDGET
════════════════════════════════════════════════════════ -->
<?php if (!empty($bazaarSuggestions)): ?>
<div class="mb-5 animate-slideUp">
    <p class="text-[0.7rem] font-bold text-text-muted uppercase tracking-widest mb-2">
        <i class="fas fa-cart-shopping text-accent mr-1"></i> <?= __('bazaar_suggestions') ?>
    </p>
    <div class="bg-card border border-border rounded-xl overflow-hidden">
        <div class="grid grid-cols-3 text-[0.6rem] font-bold text-text-muted uppercase tracking-wider px-4 py-2 border-b border-border">
            <span><?= __('item') ?></span>
            <span class="text-center"><?= __('avg_7d') ?></span>
            <span class="text-right"><?= __('suggested') ?></span>
        </div>
        <?php foreach ($bazaarSuggestions as $sug): ?>
        <div class="grid grid-cols-3 px-4 py-2.5 border-b border-border/50 last:border-0 text-sm">
            <span class="font-semibold truncate"><?= htmlspecialchars(currentLang() === 'bn' ? ($sug['item_name_bn'] ?: $sug['item_name']) : $sug['item_name']) ?></span>
            <span class="text-center text-text-muted"><?= $sug['avg_sold'] ?></span>
            <span class="text-right font-bold text-accent"><?= $sug['suggested'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="space-y-2.5 mb-4 stagger">
    <a href="?url=inventory/dailyPrep" id="btn-morning-prep"
       class="flex items-center justify-center gap-2 w-full bg-accent/10 border border-accent/30 text-accent
              font-bold py-3.5 rounded-xl hover:bg-accent/20 transition-all active:scale-[0.97] text-sm">
        <i class="fas fa-sun"></i> <?= __('morning_prep') ?>
    </a>
    <a href="?url=inventory/closeDayView" id="btn-shift-close"
       class="flex items-center justify-center gap-2 w-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400
              font-bold py-3.5 rounded-xl hover:bg-indigo-500/20 transition-all active:scale-[0.97] text-sm">
        <i class="fas fa-moon"></i> <?= __('close_shift') ?>
    </a>
    <a href="?url=bazaar" id="btn-bazaar"
       class="flex items-center justify-center gap-2 w-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400
              font-bold py-3.5 rounded-xl hover:bg-emerald-500/20 transition-all active:scale-[0.97] text-sm">
        <i class="fas fa-cart-shopping"></i> <?= __('bazaar') ?>
    </a>
</div>
