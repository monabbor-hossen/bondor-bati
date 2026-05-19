<?php $today = date('Y-m-d'); ?>
<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-moon" style="color: var(--accent-primary);"></i> Night Closing</h1>
            <p>Count remaining stock. The system auto-calculates sold qty, profit, and cash balance.</p>
        </div>
    </div>
</header>

<?php if (empty($todayStocks)): ?>
<div class="section-panel glass-panel">
    <div class="empty-state">
        <i class="fa-solid fa-boxes-stacked"></i>
        <p>No stock entries for today. Please complete <a href="?page=morning" style="color: var(--accent-primary);">Morning & Prep</a> first.</p>
    </div>
</div>
<?php else: ?>

<!-- Closing Form -->
<div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
    <div class="section-header"><h2>Enter Closing Quantities — <?= date('M d, Y'); ?></h2></div>
    <form data-ajax data-reload="true">
        <input type="hidden" name="action" value="save_closing">
        <input type="hidden" name="log_date" value="<?= $today; ?>">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Opening</th>
                        <th>Closing Qty</th>
                        <th>Complimentary</th>
                        <th>Sold</th>
                        <th>Sales (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todayStocks as $ts): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ts['item_name']); ?></strong></td>
                        <td><span class="badge success"><?= $ts['opening_qty']; ?></span></td>
                        <td>
                            <input type="hidden" name="close_item_id[]" value="<?= $ts['item_id']; ?>">
                            <input type="number" name="closing_qty[]" class="form-control" step="0.01" value="<?= $ts['closing_qty']; ?>" style="width: 90px;">
                        </td>
                        <td>
                            <input type="number" name="complimentary_qty[]" class="form-control" step="0.01" value="<?= $ts['complimentary_qty']; ?>" style="width: 90px;">
                        </td>
                        <td>
                            <?php if ($ts['sold_qty'] > 0): ?>
                                <span class="badge success"><?= $ts['sold_qty']; ?></span>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ts['total_sales_amount'] > 0): ?>
                                <strong>৳ <?= number_format($ts['total_sales_amount'], 0); ?></strong>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-calculator"></i> Run Closing Calculation</button>
        </div>
    </form>
</div>

<!-- Auto-Calculated Results -->
<div class="grid-cards">
    <div class="stat-card glass-panel">
        <i class="fa-solid fa-cash-register icon"></i>
        <h3>Cash in Drawer</h3>
        <div class="value"><?= tk($cashData['cash_in_drawer']); ?></div>
        <div class="trend" style="color: var(--text-muted);">
            Sales <?= tk($cashData['total_sales']); ?> &minus; Due <?= tk($cashData['due_sales']); ?> &minus; Bazaar <?= tk($cashData['cash_bazaar']); ?>
        </div>
    </div>

    <div class="stat-card glass-panel">
        <i class="fa-solid fa-wallet icon"></i>
        <h3>True Net Profit</h3>
        <div class="value" style="color: <?= $profitData['net_profit'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
            <?= tk($profitData['net_profit']); ?>
        </div>
        <div class="trend" style="color: var(--text-muted);">
            Bazaar <?= tk($profitData['bazaar_cost']); ?> + Exp <?= tk($profitData['daily_exp']); ?> + Salary <?= tk($profitData['prorated_salary']); ?>
        </div>
    </div>

    <div class="stat-card glass-panel">
        <i class="fa-solid fa-heart-crack icon"></i>
        <h3>Wastage & Comp Loss</h3>
        <div class="value" style="color: var(--danger);"><?= tk($profitData['wastage_comp_loss']); ?></div>
        <div class="trend" style="color: var(--text-muted);">Calculated at cost price</div>
    </div>
</div>
<?php endif; ?>
