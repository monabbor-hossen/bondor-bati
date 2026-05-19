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
