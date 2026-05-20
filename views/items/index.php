<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-list"></i> <?= __('Menu Items'); ?></h1>
            <p><?= __('Manage your sellable menu items, pricing, and minimum stock thresholds.'); ?></p>
        </div>
    </div>
    </div>
</header>

<div class="tab-bar" data-group="items">
    <button class="tab-btn active" data-tab="tab-sellable" data-group="items"><i class="fa-solid fa-burger"></i> <?= __('Sellable Items'); ?></button>
    <button class="tab-btn" data-tab="tab-raw" data-group="items"><i class="fa-solid fa-boxes-stacked"></i> <?= __('Raw Inventory'); ?></button>
</div>

<!-- ═══ Sellable Items Tab ═══ -->
<div class="tab-pane active" id="tab-sellable" data-group="items">
    <!-- Add Item Form -->
<div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
    <div class="section-header">
        <h2><?= __('Add New Item'); ?></h2>
    </div>
    <form data-ajax data-reload="true">
        <input type="hidden" name="action" value="add_item">
        <div class="form-row">
            <div class="form-group">
                <label><?= __('Item Name'); ?></label>
                <input type="text" name="item_name" class="form-control" placeholder="e.g., BBQ Tilapia" required>
            </div>
            <div class="form-group">
                <label><?= __('Selling Price'); ?> (৳)</label>
                <input type="number" name="selling_price" class="form-control" step="0.01" placeholder="120" required>
            </div>
            <div class="form-group">
                <label><?= __('Cost Price'); ?> (৳)</label>
                <input type="number" name="cost_price" class="form-control" step="0.01" placeholder="80" required>
            </div>
            <div class="form-group">
                <label><?= __('Min Stock Threshold'); ?> <span title="Low stock alert triggers below this qty" style="color: var(--text-muted); cursor: help;"><i class="fa-solid fa-circle-question"></i></span></label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="number" name="min_threshold" class="form-control" step="0.01" placeholder="10" value="10" style="flex: 2;">
                    <select name="unit" class="form-control" style="flex: 1;">
                        <option value="pcs">pcs</option>
                        <option value="kg">kg</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> <?= __('Add Item'); ?></button>
            </div>
        </div>
    </form>
</div>

<!-- Items Table -->
<div class="section-panel glass-panel">
    <div class="section-header">
        <h2><?= __('All Items'); ?> (<?= count($items); ?>)</h2>
    </div>
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-utensils"></i>
            <p><?= __('No menu items yet. Add your first item above.'); ?></p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= __('Item Name'); ?></th>
                    <th><?= __('Selling Price'); ?></th>
                    <th><?= __('Cost Price'); ?></th>
                    <th><?= __('Margin'); ?></th>
                    <th><?= __('Min Threshold'); ?></th>
                    <th><?= __('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1; ?></td>
                    <td><strong><?= htmlspecialchars($item['item_name']); ?></strong></td>
                    <td>৳ <?= number_format($item['selling_price'], 0); ?></td>
                    <td>৳ <?= number_format($item['cost_price'], 0); ?></td>
                    <td>
                        <?php $margin = $item['selling_price'] - $item['cost_price']; ?>
                        <span class="badge <?= $margin > 0 ? 'success' : 'danger'; ?>">
                            ৳ <?= number_format($margin, 0); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge warning"><?= $item['min_threshold']; ?> <?= htmlspecialchars($item['unit'] ?? 'pcs'); ?></span>
                    </td>
                    <td style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-glass btn-sm" onclick="openEditItem(<?= htmlspecialchars(json_encode($item)); ?>)" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" data-action="delete_item" data-id="<?= $item['id']; ?>">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</div> <!-- End Sellable Tab -->

<!-- ═══ Raw Inventory Tab ═══ -->
<div class="tab-pane" id="tab-raw" data-group="items">
    <div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
        <div class="section-header">
            <h2><?= __('Add / Update Raw Material'); ?></h2>
        </div>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="update_raw_inventory">
            <div class="form-row">
                <div class="form-group">
                    <label><?= __('Raw Material Name'); ?></label>
                    <input type="text" name="item_name" class="form-control" placeholder="e.g., Raw Beef (kg)" required>
                </div>
                <div class="form-group">
                    <label><?= __('Current Stock Qty'); ?></label>
                    <input type="number" name="current_qty" class="form-control" step="0.01" required>
                </div>
                <div class="form-group">
                    <label><?= __('Average Unit Price'); ?> (৳)</label>
                    <input type="number" name="avg_unit_price" class="form-control" step="0.01" required>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?= __('Save Material'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <div class="section-panel glass-panel">
        <div class="section-header">
            <h2><?= __('Raw Materials In Stock'); ?></h2>
        </div>
        <?php if (empty($rawInventory)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-snowflake"></i>
                <p><?= __('No raw inventory logged. Add your raw materials above.'); ?></p>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= __('Material Name'); ?></th>
                        <th><?= __('Current Qty'); ?></th>
                        <th><?= __('Avg Unit Price'); ?></th>
                        <th><?= __('Total Value'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rawInventory as $i => $raw): ?>
                    <tr>
                        <td><?= $i + 1; ?></td>
                        <td><strong><?= htmlspecialchars($raw['item_name']); ?></strong></td>
                        <td><span class="badge warning"><?= $raw['current_qty']; ?></span></td>
                        <td>৳ <?= number_format($raw['avg_unit_price'], 0); ?></td>
                        <td><strong>৳ <?= number_format($raw['current_qty'] * $raw['avg_unit_price'], 0); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div> <!-- End Raw Tab -->

<!-- Edit Item Modal -->
<div id="edit-item-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width:90%; max-width:560px; padding:1.5rem; border-radius:16px; position:relative;">
        <button onclick="closeEditItem()" style="position:absolute; top:1rem; right:1rem; background:none; border:none; color:var(--text-muted); font-size:1.2rem; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
        <h2 style="margin-bottom:1.25rem; font-size:1.1rem;"><i class="fa-solid fa-pen-to-square" style="color:var(--accent-primary);"></i> <?= __('Edit Item'); ?></h2>
        <form data-ajax data-reload="true" data-reset="false">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="id" id="edit-item-id">
            <div class="form-row">
                <div class="form-group">
                    <label><?= __('Item Name'); ?></label>
                    <input type="text" name="item_name" id="edit-item-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?= __('Selling Price'); ?> (৳)</label>
                    <input type="number" name="selling_price" id="edit-selling-price" class="form-control" step="0.01" required>
                </div>
                <div class="form-group">
                    <label><?= __('Cost Price'); ?> (৳)</label>
                    <input type="number" name="cost_price" id="edit-cost-price" class="form-control" step="0.01" required>
                </div>
                <div class="form-group">
                    <label><?= __('Min Stock Threshold'); ?></label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="number" name="min_threshold" id="edit-min-threshold" class="form-control" step="0.01" style="flex: 2;">
                        <select name="unit" id="edit-unit" class="form-control" style="flex: 1;">
                            <option value="pcs">pcs</option>
                            <option value="kg">kg</option>
                        </select>
                    </div>
                </div>
            </div>
            <div style="display:flex; gap:0.75rem; margin-top:1rem;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?= __('Save Changes'); ?></button>
                <button type="button" class="btn btn-glass" onclick="closeEditItem()"><?= __('Cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditItem(item) {
    document.getElementById('edit-item-id').value = item.id;
    document.getElementById('edit-item-name').value = item.item_name;
    document.getElementById('edit-selling-price').value = item.selling_price;
    document.getElementById('edit-cost-price').value = item.cost_price;
    document.getElementById('edit-min-threshold').value = item.min_threshold ?? 10;
    document.getElementById('edit-unit').value = item.unit ?? 'pcs';
    const modal = document.getElementById('edit-item-modal');
    modal.style.display = 'flex';
}
function closeEditItem() {
    document.getElementById('edit-item-modal').style.display = 'none';
}
// Close modal on backdrop click
document.getElementById('edit-item-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditItem();
});
</script>
