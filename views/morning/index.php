<?php $today = date('Y-m-d'); ?>
<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-sun" style="color: var(--warning);"></i> Morning & Prep</h1>
            <p>Log today's bazaar purchases and prepare stock for the day.</p>
        </div>
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
    <button class="tab-btn active" data-tab="tab-bazaar" data-group="morning"><i class="fa-solid fa-basket-shopping"></i> Bazaar Ledger</button>
    <button class="tab-btn" data-tab="tab-stock" data-group="morning"><i class="fa-solid fa-boxes-stacked"></i> Stock Preparation</button>
</div>

<!-- ═══ Bazaar Tab ═══ -->
<div class="tab-pane active" id="tab-bazaar" data-group="morning">
    <div class="section-panel glass-panel">
        <div class="section-header"><h2>Bazaar Entry — <?= date('M d, Y'); ?></h2></div>

        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="save_bazaar">
            <input type="hidden" name="log_date" value="<?= $today; ?>">

            <div class="form-row" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label>Advance Cash Given</label>
                    <input type="number" name="advance_cash" class="form-control" step="0.01" value="<?= $todayLedger ? $todayLedger['advance_cash'] : 0; ?>">
                </div>
            </div>

            <h3 style="font-size: 0.95rem; margin-bottom: 0.75rem;">Bazaar Items</h3>
            <div class="dynamic-rows" id="bazaar-rows">
                <?php if (!empty($bazaarItems)): ?>
                    <?php foreach ($bazaarItems as $idx => $bi): ?>
                    <div class="dynamic-row">
                        <input type="text" name="bi_name[]" class="form-control" value="<?= htmlspecialchars($bi['item_name']); ?>" placeholder="Item name" style="flex:2;">
                        <input type="number" name="bi_qty[]" class="form-control" value="<?= $bi['bought_qty']; ?>" placeholder="Qty" style="flex:1;">
                        <input type="number" name="bi_price[]" class="form-control" value="<?= $bi['total_price']; ?>" placeholder="Price" step="0.01" style="flex:1;">
                        <select name="bi_supplier[]" class="form-control" style="flex:1.5;">
                            <option value="">Cash / Market</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id']; ?>" <?= $bi['supplier_id'] == $s['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeDynamicRow(this)"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dynamic-row">
                        <input type="text" name="bi_name[]" class="form-control" placeholder="Item name" style="flex:2;">
                        <input type="number" name="bi_qty[]" class="form-control" placeholder="Qty" style="flex:1;">
                        <input type="number" name="bi_price[]" class="form-control" placeholder="Price" step="0.01" style="flex:1;">
                        <select name="bi_supplier[]" class="form-control" style="flex:1.5;">
                            <option value="">Cash / Market</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id']; ?>"><?= htmlspecialchars($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeDynamicRow(this)"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                <button type="button" class="btn btn-glass" onclick="addBazaarRow()"><i class="fa-solid fa-plus"></i> Add Row</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Bazaar</button>
            </div>
        </form>

        <?php if ($todayLedger): ?>
        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--glass-border);">
            <p style="color: var(--text-muted);">Today's Total Bazaar Spent: <strong style="color: var(--warning);">৳ <?= number_format($todayLedger['total_spent'], 0); ?></strong></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Stock Prep Tab ═══ -->
<div class="tab-pane" id="tab-stock" data-group="morning">
    <div class="section-panel glass-panel">
        <div class="section-header"><h2>Stock Preparation — <?= date('M d, Y'); ?></h2></div>

        <?php if (empty($items)): ?>
            <div class="empty-state"><i class="fa-solid fa-utensils"></i><p>No menu items found. <a href="?page=items" style="color: var(--accent-primary);">Add items first</a>.</p></div>
        <?php else: ?>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="save_morning_stock">
            <input type="hidden" name="log_date" value="<?= $today; ?>">

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Carry Forward</th>
                            <th>Wastage</th>
                            <th>Fresh Processed</th>
                            <th>Opening Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item):
                            $existing = null;
                            foreach ($todayStocks as $ts) { if ($ts['item_id'] == $item['id']) { $existing = $ts; break; } }
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['item_name']); ?></strong></td>
                            <td>
                                <input type="hidden" name="stock_item_id[]" value="<?= $item['id']; ?>">
                                <input type="number" name="carry_forward[]" class="form-control" step="0.01" value="<?= $existing ? $existing['carry_forward_qty'] : 0; ?>" style="width: 90px;">
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
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Morning Stock</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function addBazaarRow() {
    const suppOptions = <?= json_encode(array_map(fn($s) => ['id' => $s['id'], 'name' => $s['name']], $suppliers)); ?>;
    let opts = '<option value="">Cash / Market</option>';
    suppOptions.forEach(s => opts += `<option value="${s.id}">${s.name}</option>`);

    addDynamicRow('bazaar-rows', () => `
        <input type="text" name="bi_name[]" class="form-control" placeholder="Item name" style="flex:2;">
        <input type="number" name="bi_qty[]" class="form-control" placeholder="Qty" style="flex:1;">
        <input type="number" name="bi_price[]" class="form-control" placeholder="Price" step="0.01" style="flex:1;">
        <select name="bi_supplier[]" class="form-control" style="flex:1.5;">${opts}</select>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeDynamicRow(this)"><i class="fa-solid fa-xmark"></i></button>
    `);
}
</script>
