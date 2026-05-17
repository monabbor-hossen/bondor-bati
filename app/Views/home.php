<?php
/**
 * Home Dashboard View
 * Loaded inside layout/main.php via $contentView = 'home'
 * Available variables: $pageTitle, $todayDate, $pendingOrders, $totalItemsActive
 */
?>

<!-- Welcome Banner -->
<div class="card" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-color: var(--accent);">
    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.25rem;">Good <?= (date('G') < 12 ? 'Morning' : (date('G') < 17 ? 'Afternoon' : 'Evening')) ?> 👋</p>
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);"><?= date('l') ?></h2>
    <p style="font-size: 0.9rem; color: var(--text-muted);"><?= date('F j, Y') ?></p>
</div>

<!-- Quick Stats Grid -->
<p class="section-title">Today's Snapshot</p>
<div class="stat-grid">
    <div class="stat-chip warning">
        <div class="value"><?= (int)($pendingOrders ?? 0) ?></div>
        <div class="label">Pending Orders</div>
    </div>
    <div class="stat-chip accent">
        <div class="value"><?= (int)($totalItemsActive ?? 0) ?></div>
        <div class="label">Active Items</div>
    </div>
</div>

<!-- Pending Orders (if any) -->
<?php if (!empty($pendingOrders) && $pendingOrders > 0): ?>
    <div class="alert alert-warning">
        ⚠️ <strong><?= $pendingOrders ?> advance orders</strong> are due for delivery today!
    </div>
<?php endif; ?>

<!-- Core Task Buttons -->
<p class="section-title" style="margin-top: 0.5rem;">Quick Actions</p>

<a href="?url=inventory/dailyPrep" class="btn btn-secondary" style="justify-content: flex-start; gap: 1rem;">
    <span style="font-size: 1.5rem;">🌅</span>
    <div>
        <div style="font-size: 0.95rem; font-weight: 700;">Morning Prep</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">Log wastage & fresh stock</div>
    </div>
</a>

<a href="?url=bazaar/index" class="btn btn-secondary" style="justify-content: flex-start; gap: 1rem;">
    <span style="font-size: 1.5rem;">🛒</span>
    <div>
        <div style="font-size: 0.95rem; font-weight: 700;">Bazaar Entry</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">Log today's raw purchases</div>
    </div>
</a>

<a href="?url=inventory/closeDayView" class="btn btn-primary" style="justify-content: flex-start; gap: 1rem;">
    <span style="font-size: 1.5rem;">🌙</span>
    <div>
        <div style="font-size: 0.95rem; font-weight: 700;">Night Closing</div>
        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.7); font-weight: 400;">Enter closing quantities & finalize</div>
    </div>
</a>
