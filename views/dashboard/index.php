<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1>Overview Dashboard</h1>
            <p>Welcome back, Admin. Today is <?= date('l, M d, Y'); ?>.</p>
        </div>
    </div>
    
    <div class="user-profile">
        <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> <span class="hide-mobile">New Order</span></button>
        <div class="avatar">AD</div>
    </div>
</header>

<!-- Low Stock Alerts -->
<?php if (!empty($lowStockItems)): ?>
<div class="stat-card glass-panel" style="margin-bottom: 1.5rem; border-left: 4px solid var(--danger);">
    <h3 style="color: var(--danger);"><i class="fa-solid fa-triangle-exclamation"></i> Low Stock Alerts</h3>
    <div class="list-group" style="margin-top: 0.75rem;">
        <?php foreach ($lowStockItems as $item): ?>
        <div class="list-item" style="padding: 0.5rem 0.75rem; border-radius: 6px;">
            <div class="item-info">
                <h4><?= htmlspecialchars($item['item_name']); ?></h4>
                <p>Current Stock: <strong style="color: var(--danger);"><?= $item['current_qty'] ?: 0; ?></strong> | Min Threshold: <?= $item['min_threshold']; ?></p>
            </div>
            <span class="badge danger">Low Stock</span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Top Statistics Row -->
<div class="grid-cards">
    <!-- True Net Profit -->
    <div class="stat-card glass-panel">
        <i class="fa-solid fa-wallet icon"></i>
        <h3>Est. True Net Profit</h3>
        <div class="value">৳ <?= number_format($profitData['net_profit'], 0); ?></div>
        <div class="trend <?= $profitTrend >= 0 ? 'positive' : 'negative'; ?>">
            <i class="fa-solid fa-arrow-trend-<?= $profitTrend >= 0 ? 'up' : 'down'; ?>"></i>
            <?= $profitTrend >= 0 ? '+' : ''; ?><?= $profitTrend; ?>% from yesterday
        </div>
    </div>
    
    <!-- Cash in Drawer -->
    <div class="stat-card glass-panel">
        <i class="fa-solid fa-cash-register icon"></i>
        <h3>Cash in Drawer</h3>
        <div class="value">৳ <?= number_format($cashData['cash_in_drawer'], 0); ?></div>
        <div class="trend" style="color: var(--text-muted);">
            Sales ৳<?= number_format($cashData['total_sales'], 0); ?> &middot; Due ৳<?= number_format($cashData['due_sales'], 0); ?>
        </div>
    </div>
    
    <!-- Gas Depletion -->
    <div class="stat-card glass-panel">
        <i class="fa-solid fa-fire icon"></i>
        <h3>Next Gas Refill</h3>
        <?php if ($nextGasDate && $gasDaysLeft !== null): ?>
            <div class="value">In <?= $gasDaysLeft; ?> Day<?= $gasDaysLeft !== 1 ? 's' : ''; ?></div>
            <div class="trend <?= $gasDaysLeft <= 3 ? 'warning' : 'positive'; ?>">
                <i class="fa-solid fa-<?= $gasDaysLeft <= 3 ? 'triangle-exclamation' : 'check'; ?>"></i>
                Expected on <?= date('M d', strtotime($nextGasDate)); ?>
            </div>
        <?php else: ?>
            <div class="value">No Data</div>
            <div class="trend" style="color: var(--text-muted);">Log a gas expense to start tracking</div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Stats: Items & Staff -->
    <div class="stat-card glass-panel">
        <i class="fa-solid fa-utensils icon"></i>
        <h3>Menu Items / Staff</h3>
        <div class="value"><?= (int)$totalItems; ?> / <?= (int)$totalStaff; ?></div>
        <div class="trend" style="color: var(--text-muted);">
            Active menu items & on-duty staff
        </div>
    </div>
</div>

<div class="sections-grid">
    
    <!-- Operational Workflow + Pending Orders -->
    <div class="section-panel glass-panel">
        <div class="section-header">
            <h2>Daily Operations Flow</h2>
            <button class="btn btn-glass"><i class="fa-solid fa-clock-rotate-left"></i> History</button>
        </div>
        
        <div class="list-group">
            <!-- Morning Step -->
            <div class="list-item">
                <div class="item-info">
                    <h4><i class="fa-solid fa-sun" style="color: var(--warning);"></i> 1. Morning (Bazaar & Prep)</h4>
                    <p>Enter bazaar details and check pending advance orders.</p>
                </div>
                <button class="btn btn-glass">Log Bazaar</button>
            </div>
            
            <!-- Prep Step -->
            <div class="list-item">
                <div class="item-info">
                    <h4><i class="fa-solid fa-blender" style="color: var(--accent-secondary);"></i> 2. Preparation (Stock)</h4>
                    <p>Log spoiled items (wastage) and fresh processed qty.</p>
                </div>
                <button class="btn btn-glass">Process Stock</button>
            </div>
            
            <!-- Closing Step -->
            <div class="list-item">
                <div class="item-info">
                    <h4><i class="fa-solid fa-moon" style="color: var(--accent-primary);"></i> 3. Night Closing</h4>
                    <p>Count unsold items. System auto-calculates profit/loss.</p>
                </div>
                <button class="btn btn-primary">Run Closing</button>
            </div>
        </div>

        <!-- Pending Advance Orders for Today -->
        <?php if (!empty($pendingOrders)): ?>
        <div class="section-header" style="margin-top: 2rem;">
            <h2>Today's Pending Orders</h2>
            <span class="badge warning"><?= count($pendingOrders); ?> Pending</span>
        </div>
        <div class="list-group">
            <?php foreach ($pendingOrders as $order): ?>
            <div class="list-item">
                <div class="item-info">
                    <h4><?= htmlspecialchars($order['customer_info']); ?></h4>
                    <p>Bill: ৳<?= number_format($order['total_bill'], 0); ?> &middot; Advance: ৳<?= number_format($order['advance_paid'], 0); ?></p>
                </div>
                <span class="badge warning"><?= htmlspecialchars($order['status']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Smart Forecasting & Alerts -->
    <div class="section-panel glass-panel">
        <div class="section-header">
            <h2>Smart Insights</h2>
        </div>
        
        <div class="list-group">

            <!-- Tomorrow's Event & Bazaar Suggestion -->
            <div class="list-item" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; width: 100%;">
                    <div class="item-info">
                        <h4>Tomorrow's Bazaar Suggestion</h4>
                        <?php if ($tomorrowEvent): ?>
                            <p>Event: <?= htmlspecialchars($tomorrowEvent['event_name']); ?> &middot; <?= $tomorrowEvent['impact_multiplier']; ?>x</p>
                        <?php else: ?>
                            <p>No special event scheduled</p>
                        <?php endif; ?>
                    </div>
                    <span class="badge <?= $tomorrowEvent ? 'success' : 'warning'; ?>">
                        <?= $tomorrowEvent ? 'High Traffic' : 'Normal'; ?>
                    </span>
                </div>
                <?php if (!empty($bazaarSuggestions)): ?>
                <div style="width: 100%; margin-top: 0.5rem;">
                    <?php foreach ($bazaarSuggestions as $suggestion): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem; padding: 0.25rem 0; color: var(--text-main);">
                        <span><?= htmlspecialchars($suggestion['item_name']); ?></span>
                        <strong>Prep ~<?= round($suggestion['suggested_prep_qty']); ?> pcs</strong>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="width: 100%; font-size: 0.9rem; color: var(--text-muted); margin-top: 0.5rem;">
                    No sales history yet. Start logging daily stocks to get smart suggestions.
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Supplier Dues from DB -->
            <div class="list-item" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; width: 100%;">
                    <div class="item-info">
                        <h4>Pending Supplier Dues</h4>
                    </div>
                    <?php if (!empty($supplierDues)): ?>
                        <span class="badge danger">Action Req</span>
                    <?php else: ?>
                        <span class="badge success">All Clear</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($supplierDues)): ?>
                    <?php foreach ($supplierDues as $supplier): ?>
                    <div style="width: 100%; font-size: 0.9rem; display: flex; justify-content: space-between;">
                        <span><?= htmlspecialchars($supplier['name']); ?></span>
                        <strong style="color: var(--danger);">৳ <?= number_format($supplier['total_due'], 0); ?></strong>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="width: 100%; font-size: 0.9rem; color: var(--text-muted);">
                        No outstanding supplier dues. You're all settled!
                    </div>
                <?php endif; ?>
            </div>

            <!-- Customer Dues from DB -->
            <div class="list-item" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; width: 100%;">
                    <div class="item-info">
                        <h4>Recent Customer Dues</h4>
                    </div>
                    <?php if (!empty($customerDues)): ?>
                        <span class="badge warning"><?= count($customerDues); ?> Unpaid</span>
                    <?php else: ?>
                        <span class="badge success">All Paid</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($customerDues)): ?>
                    <?php foreach ($customerDues as $due): ?>
                    <div style="width: 100%; font-size: 0.9rem; display: flex; justify-content: space-between;">
                        <span><?= htmlspecialchars($due['customer_name']); ?> <?= $due['phone'] ? '(' . htmlspecialchars($due['phone']) . ')' : ''; ?></span>
                        <strong style="color: var(--warning);">৳ <?= number_format($due['due_amount'], 0); ?></strong>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="width: 100%; font-size: 0.9rem; color: var(--text-muted);">
                        No unpaid customer dues.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Range Reporting Panel -->
<div class="section-panel glass-panel" style="margin-top: 1.5rem; width: 100%;">
    <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h2><i class="fa-solid fa-chart-pie" style="color: var(--accent-primary);"></i> <?= __('Dynamic Custom Range Report'); ?></h2>
        
        <form method="GET" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="dashboard">
            <select name="range" class="form-control" onchange="toggleCustomDates(this.value)" style="width: auto;">
                <option value="daily" <?= $range === 'daily' ? 'selected' : ''; ?>><?= __('Daily'); ?></option>
                <option value="monthly" <?= $range === 'monthly' ? 'selected' : ''; ?>><?= __('Monthly'); ?></option>
                <option value="lifetime" <?= $range === 'lifetime' ? 'selected' : ''; ?>><?= __('Lifetime'); ?></option>
                <option value="custom" <?= $range === 'custom' ? 'selected' : ''; ?>><?= __('Custom Date Range'); ?></option>
            </select>
            
            <div id="custom-date-inputs" style="display: <?= $range === 'custom' ? 'flex' : 'none'; ?>; gap: 0.5rem; align-items: center;">
                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($reportFrom); ?>" style="width: auto;">
                <span style="color: var(--text-muted);"><?= __('to'); ?></span>
                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($reportTo); ?>" style="width: auto;">
            </div>
            
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> <?= __('Filter'); ?></button>
        </form>
    </div>
    
    <div class="grid-cards" style="margin-top: 1.5rem; margin-bottom: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="stat-card glass-panel" style="background: rgba(255, 255, 255, 0.02);">
            <h3><?= __('Total Sales'); ?></h3>
            <div class="value" style="font-size: 1.5rem;">৳ <?= number_format($rangeReport['total_sales'], 0); ?></div>
        </div>
        <div class="stat-card glass-panel" style="background: rgba(255, 255, 255, 0.02);">
            <h3><?= __('Total Dues'); ?></h3>
            <div class="value" style="font-size: 1.5rem; color: var(--warning);">৳ <?= number_format($rangeReport['total_dues'], 0); ?></div>
        </div>
        <div class="stat-card glass-panel" style="background: rgba(255, 255, 255, 0.02);">
            <h3><?= __('Net Profit'); ?></h3>
            <div class="value" style="font-size: 1.5rem; color: <?= $rangeReport['net_profit'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                ৳ <?= number_format($rangeReport['net_profit'], 0); ?>
            </div>
        </div>
        <div class="stat-card glass-panel" style="background: rgba(255, 255, 255, 0.02);">
            <h3><?= __('Wastage Cost'); ?></h3>
            <div class="value" style="font-size: 1.5rem; color: var(--danger);">৳ <?= number_format($rangeReport['wastage_loss'], 0); ?></div>
        </div>
    </div>
    
    <div class="sections-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        <div>
            <h3 style="font-size: 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-trophy" style="color: var(--warning);"></i> <?= __('Top Selling Items'); ?>
            </h3>
            <?php if (empty($rangeReport['top_selling'])): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;"><?= __('No sales in this range.'); ?></p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($rangeReport['top_selling'] as $item): ?>
                    <div class="list-item" style="padding: 0.5rem 0.75rem;">
                        <div class="item-info">
                            <h4><?= htmlspecialchars($item['item_name']); ?></h4>
                            <p><?= __('Revenue'); ?>: ৳<?= number_format($item['total_revenue'], 0); ?></p>
                        </div>
                        <span class="badge success"><?= round($item['total_sold']); ?> <?= __('sold'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <h3 style="font-size: 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-dumpster-fire" style="color: var(--danger);"></i> <?= __('High Wastage Items'); ?>
            </h3>
            <?php if (empty($rangeReport['high_wastage'])): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;"><?= __('No wastage logged in this range.'); ?></p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($rangeReport['high_wastage'] as $item): ?>
                    <div class="list-item" style="padding: 0.5rem 0.75rem;">
                        <div class="item-info">
                            <h4><?= htmlspecialchars($item['item_name']); ?></h4>
                            <p><?= __('Cost Loss'); ?>: ৳<?= number_format($item['total_wasted_cost'], 0); ?></p>
                        </div>
                        <span class="badge danger"><?= round($item['total_wasted']); ?> <?= __('wasted'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleCustomDates(val) {
    document.getElementById('custom-date-inputs').style.display = (val === 'custom') ? 'flex' : 'none';
}
</script>
