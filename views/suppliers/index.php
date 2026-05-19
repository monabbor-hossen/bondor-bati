<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-truck"></i> Suppliers</h1>
            <p>Manage your vendors and track their dues.</p>
        </div>
    </div>
</header>

<!-- Add Supplier Form -->
<div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
    <div class="section-header"><h2>Add New Supplier</h2></div>
    <form data-ajax data-reload="true">
        <input type="hidden" name="action" value="add_supplier">
        <div class="form-row">
            <div class="form-group">
                <label>Supplier Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Bagabarir Ghee" required>
            </div>
            <div class="form-group">
                <label>Contact</label>
                <input type="text" name="contact" class="form-control" placeholder="01XXXXXXXXX">
            </div>
            <div class="form-group">
                <label>Initial Due (৳)</label>
                <input type="number" name="total_due" class="form-control" step="0.01" value="0">
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add</button>
            </div>
        </div>
    </form>
</div>

<!-- Suppliers Table -->
<div class="section-panel glass-panel">
    <div class="section-header"><h2>All Suppliers (<?= count($suppliers); ?>)</h2></div>
    <?php if (empty($suppliers)): ?>
        <div class="empty-state"><i class="fa-solid fa-truck"></i><p>No suppliers yet.</p></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Name</th><th>Contact</th><th>Total Due</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $i => $s): ?>
                <tr>
                    <td><?= $i + 1; ?></td>
                    <td><strong><?= htmlspecialchars($s['name']); ?></strong></td>
                    <td><?= htmlspecialchars($s['contact'] ?: '—'); ?></td>
                    <td>
                        <span class="badge <?= $s['total_due'] > 0 ? 'danger' : 'success'; ?>">
                            ৳ <?= number_format($s['total_due'], 0); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm" data-action="delete_supplier" data-id="<?= $s['id']; ?>">
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
