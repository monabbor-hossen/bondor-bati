<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-list"></i> Menu Items</h1>
            <p>Manage your sellable menu items and their pricing.</p>
        </div>
    </div>
</header>

<!-- Add Item Form -->
<div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
    <div class="section-header">
        <h2>Add New Item</h2>
    </div>
    <form data-ajax data-reload="true">
        <input type="hidden" name="action" value="add_item">
        <div class="form-row">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="item_name" class="form-control" placeholder="e.g., BBQ Tilapia" required>
            </div>
            <div class="form-group">
                <label>Selling Price (৳)</label>
                <input type="number" name="selling_price" class="form-control" step="0.01" placeholder="120" required>
            </div>
            <div class="form-group">
                <label>Cost Price (৳)</label>
                <input type="number" name="cost_price" class="form-control" step="0.01" placeholder="80" required>
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Item</button>
            </div>
        </div>
    </form>
</div>

<!-- Items Table -->
<div class="section-panel glass-panel">
    <div class="section-header">
        <h2>All Items (<?= count($items); ?>)</h2>
    </div>
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-utensils"></i>
            <p>No menu items yet. Add your first item above.</p>
        </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th>Selling Price</th>
                    <th>Cost Price</th>
                    <th>Margin</th>
                    <th>Actions</th>
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
