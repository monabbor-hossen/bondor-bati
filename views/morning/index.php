<?php 
$today = date('Y-m-d'); 
$shift = $_GET['shift'] ?? 'Morning';
?>
<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-sun" style="color: var(--warning);"></i> <?= __('Morning & Prep'); ?></h1>
            <p><?= __('Log today\'s bazaar purchases and prepare stock for the day.'); ?></p>
        </div>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center; margin-top: 0.5rem;">
        <label style="font-weight: 500; font-size: 0.9rem; color: var(--text-muted);"><?= __('Shift'); ?>:</label>
        <select class="form-control" onchange="location.href='?page=morning&shift=' + this.value" style="width: auto;">
            <option value="Morning" <?= $shift === 'Morning' ? 'selected' : ''; ?>><?= __('Morning'); ?></option>
            <option value="Evening" <?= $shift === 'Evening' ? 'selected' : ''; ?>><?= __('Evening'); ?></option>
            <option value="Night" <?= $shift === 'Night' ? 'selected' : ''; ?>><?= __('Night'); ?></option>
        </select>
    </div>
</header>

<!-- Pending Advance Orders Alert -->
<?php if (!empty($pendingOrders)): ?>
<div class="stat-card glass-panel" style="margin-bottom: 1.5rem; border-left: 4px solid var(--warning);">
    <h3><i class="fa-solid fa-bell"></i> Pending Advance Orders for Today</h3>
    <div class="list-group" style="margin-top: 0.75rem;">
        <?php foreach ($pendingOrders as $o): ?>
        <div class="list-item">
            <div class="item-info">
                <h4><?= htmlspecialchars($o['customer_info']); ?></h4>
                <p>Bill: ৳<?= number_format($o['total_bill'], 0); ?> | Advance: ৳<?= number_format($o['advance_paid'], 0); ?></p>
            </div>
            <span class="badge warning">Pending</span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="tab-bar" data-group="morning">
    <button class="tab-btn active" data-tab="tab-bazaar" data-group="morning"><i class="fa-solid fa-basket-shopping"></i> <?= __('Bazaar Ledger'); ?></button>
    <button class="tab-btn" data-tab="tab-stock" data-group="morning"><i class="fa-solid fa-boxes-stacked"></i> <?= __('Stock Preparation'); ?></button>
</div>

<!-- ═══ Bazaar Tab ═══ -->
<div class="tab-pane active" id="tab-bazaar" data-group="morning">
    <div class="section-panel glass-panel">
        <div class="section-header"><h2><?= __('Bazaar Entry'); ?> — <?= date('M d, Y'); ?></h2></div>

        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="save_bazaar">
            <input type="hidden" name="log_date" value="<?= $today; ?>">

            <div class="form-row" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label><?= __('Advance Cash Given'); ?></label>
                    <input type="number" name="advance_cash" class="form-control" step="0.01" value="<?= $todayLedger ? $todayLedger['advance_cash'] : $carriedAdvance; ?>" <?= !isAdmin() ? 'readonly' : ''; ?>>
                </div>
            </div>

            <h3 style="font-size: 0.95rem; margin-bottom: 0.75rem;"><?= __('Bazaar Items'); ?></h3>
            <div class="dynamic-rows" id="bazaar-rows">
                <?php if (!empty($bazaarItems)): ?>
                    <?php foreach ($bazaarItems as $idx => $bi): 
                            $priceVal = $bi['total_price'];
                            if (!isAdmin() && (float)$priceVal == 0) $priceVal = '';
                    ?>
                    <div class="dynamic-row">
                        <input type="text" name="bi_name[]" class="form-control" value="<?= htmlspecialchars($bi['item_name']); ?>" placeholder="<?= __('Item name'); ?>" style="flex:2;" <?= !isAdmin() ? 'readonly' : ''; ?>>
                        <input type="number" name="bi_qty[]" class="form-control" value="<?= $bi['bought_qty']; ?>" placeholder="<?= __('Qty'); ?>" style="flex:1;">
                        <select name="bi_unit[]" class="form-control" style="flex:0.6; padding-left:0.2rem; padding-right:0.2rem;">
                            <option value="pcs" <?= (isset($bi['unit']) && $bi['unit'] == 'pcs') ? 'selected' : ''; ?>>pcs</option>
                            <option value="kg" <?= (isset($bi['unit']) && $bi['unit'] == 'kg') ? 'selected' : ''; ?>>kg</option>
                        </select>
                        <input type="number" name="bi_price[]" class="form-control" value="<?= $priceVal; ?>" placeholder="<?= __('Price'); ?>" step="0.01" style="flex:1;">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeDynamicRow(this)"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dynamic-row">
                        <input type="text" name="bi_name[]" class="form-control" placeholder="<?= __('Item name'); ?>" style="flex:2;">
                        <input type="number" name="bi_qty[]" class="form-control" placeholder="<?= __('Qty'); ?>" style="flex:1;">
                        <select name="bi_unit[]" class="form-control" style="flex:0.6; padding-left:0.2rem; padding-right:0.2rem;">
                            <option value="pcs">pcs</option>
                            <option value="kg">kg</option>
                        </select>
                        <input type="number" name="bi_price[]" class="form-control" placeholder="<?= __('Price'); ?>" step="0.01" style="flex:1;">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeDynamicRow(this)"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-row" style="margin-top: 1.5rem; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                <div class="form-group">
                    <label><?= __('Return to Admin Cash Drawer'); ?></label>
                    <input type="number" name="return_cash" class="form-control" step="0.01" value="<?= $todayLedger ? $todayLedger['return_cash'] : 0; ?>">
                </div>
                <div class="form-group">
                    <label><?= __('Carry Forward to Next Day'); ?></label>
                    <input type="number" name="carry_forward_cash" class="form-control" step="0.01" value="<?= $todayLedger ? $todayLedger['carry_forward_cash'] : 0; ?>">
                </div>
            </div>

            <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                <button type="button" class="btn btn-glass" onclick="addBazaarRow()"><i class="fa-solid fa-plus"></i> <?= __('Add Row'); ?></button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?= __('Save Bazaar'); ?></button>
            </div>
        </form>

        <?php if ($todayLedger): ?>
        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--glass-border);">
            <p style="color: var(--text-muted);">Today's Total Bazaar Spent: <strong style="color: var(--warning);">৳ <?= number_format($todayLedger['total_spent'], 0); ?></strong></p>
            <?php 
                $balance = $todayLedger['advance_cash'] - $todayLedger['total_spent'];
                if ($balance < 0):
            ?>
                <p style="color: var(--danger); margin-top: 0.5rem;"><i class="fa-solid fa-triangle-exclamation"></i> Over-spent (Due to Staff): <strong>৳ <?= number_format(abs($balance), 0); ?></strong></p>
            <?php elseif ($balance > 0): ?>
                <p style="color: var(--success); margin-top: 0.5rem;"><i class="fa-solid fa-check"></i> Unspent Balance: <strong>৳ <?= number_format($balance, 0); ?></strong></p>
            <?php else: ?>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">Balance is exactly zero.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Stock Prep Tab ═══ -->
<div class="tab-pane" id="tab-stock" data-group="morning">
    <div class="section-panel glass-panel">
        <div class="section-header"><h2><?= __('Stock Preparation'); ?> — <?= date('M d, Y'); ?></h2></div>

        <?php if (empty($items)): ?>
            <div class="empty-state"><i class="fa-solid fa-utensils"></i><p>No menu items found. <a href="?page=items" style="color: var(--accent-primary);">Add items first</a>.</p></div>
        <?php else: ?>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="save_morning_stock">
            <input type="hidden" name="log_date" value="<?= $today; ?>">
            <input type="hidden" name="shift" value="<?= $shift; ?>">

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?= __('Item'); ?></th>
                            <th><?= __('Carry Forward'); ?></th>
                            <th><?= __('Wastage'); ?></th>
                            <th><?= __('Fresh Processed'); ?></th>
                            <th><?= __('Opening Qty'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item):
                            $existing = $todayStocksMap[$item['id']] ?? null;
                            $cfVal = $existing ? $existing['carry_forward_qty'] : ($prevClosings[$item['id']] ?? 0);
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['item_name']); ?></strong>
                                <?php if ($cfVal < $item['min_threshold']): ?>
                                    <span class="badge danger" style="font-size: 0.65rem; padding: 0.1rem 0.25rem; margin-left: 0.5rem;"><i class="fa-solid fa-triangle-exclamation"></i> Low</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="hidden" name="stock_item_id[]" value="<?= $item['id']; ?>">
                                <input type="number" name="carry_forward[]" class="form-control" step="0.01" value="<?= $cfVal; ?>" style="width: 90px;">
                            </td>
                            <td><input type="number" name="wastage[]" class="form-control" step="0.01" value="<?= $existing ? $existing['wastage_qty'] : 0; ?>" style="width: 90px;"></td>
                            <td><input type="number" name="fresh_processed[]" class="form-control" step="0.01" value="<?= $existing ? $existing['fresh_processed_qty'] : 0; ?>" style="width: 90px;"></td>
                            <td><span class="badge success"><?= $existing ? $existing['opening_qty'] : '—'; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?= __('Save Morning Stock'); ?></button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function addBazaarRow() {
    addDynamicRow('bazaar-rows', () => `
        <input type="text" name="bi_name[]" class="form-control" placeholder="Item name" style="flex:2;">
        <input type="number" name="bi_qty[]" class="form-control" placeholder="Qty" style="flex:1;">
        <select name="bi_unit[]" class="form-control" style="flex:0.6; padding-left:0.2rem; padding-right:0.2rem;"><option value="pcs">pcs</option><option value="kg">kg</option></select>
        <input type="number" name="bi_price[]" class="form-control" placeholder="Price" step="0.01" style="flex:1;">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeDynamicRow(this)"><i class="fa-solid fa-xmark"></i></button>
    `);
}
</script>
