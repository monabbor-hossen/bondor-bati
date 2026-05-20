<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-truck"></i> <?= __('Suppliers'); ?></h1>
            <p><?= __('Manage your vendors and track supplier payment dues.'); ?></p>
        </div>
    </div>
</header>

<!-- Add Supplier Form -->
<div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
    <div class="section-header"><h2><?= __('Add New Supplier'); ?></h2></div>
    <form data-ajax data-reload="true">
        <input type="hidden" name="action" value="add_supplier">
        <div class="form-row">
            <div class="form-group">
                <label><?= __('Supplier Name'); ?></label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Bagabarir Ghee" required>
            </div>
            <div class="form-group">
                <label><?= __('Contact'); ?></label>
                <input type="text" name="contact" class="form-control" placeholder="01XXXXXXXXX">
            </div>
            <div class="form-group">
                <label><?= __('Initial Due'); ?> (৳)</label>
                <input type="number" name="total_due" class="form-control" step="0.01" value="0">
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> <?= __('Add'); ?></button>
            </div>
        </div>
    </form>
</div>

<!-- Suppliers Table -->
<div class="section-panel glass-panel">
    <div class="section-header"><h2><?= __('All Suppliers'); ?> (<?= count($suppliers); ?>)</h2></div>
    <?php if (empty($suppliers)): ?>
        <div class="empty-state"><i class="fa-solid fa-truck"></i><p><?= __('No suppliers yet.'); ?></p></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= __('Name'); ?></th>
                    <th><?= __('Contact'); ?></th>
                    <th><?= __('Total Due'); ?></th>
                    <th><?= __('Actions'); ?></th>
                </tr>
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
                    <td style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if ($s['total_due'] > 0): ?>
                        <button class="btn btn-success btn-sm" onclick="openPayDue(<?= $s['id']; ?>, '<?= htmlspecialchars(addslashes($s['name'])); ?>', <?= $s['total_due']; ?>)" title="Pay Due">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-glass btn-sm" onclick="openEditSupplier(<?= htmlspecialchars(json_encode($s)); ?>)" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" data-action="delete_supplier" data-id="<?= $s['id']; ?>" title="Delete">
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

<!-- Edit Supplier Modal -->
<div id="edit-supplier-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width:90%; max-width:480px; padding:1.5rem; border-radius:16px; position:relative;">
        <button onclick="closeEditSupplier()" style="position:absolute; top:1rem; right:1rem; background:none; border:none; color:var(--text-muted); font-size:1.2rem; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
        <h2 style="margin-bottom:1.25rem; font-size:1.1rem;"><i class="fa-solid fa-pen-to-square" style="color:var(--accent-primary);"></i> <?= __('Edit Supplier'); ?></h2>
        <form data-ajax data-reload="true" data-reset="false">
            <input type="hidden" name="action" value="update_supplier">
            <input type="hidden" name="id" id="edit-supp-id">
            <div class="form-row">
                <div class="form-group">
                    <label><?= __('Supplier Name'); ?></label>
                    <input type="text" name="name" id="edit-supp-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?= __('Contact'); ?></label>
                    <input type="text" name="contact" id="edit-supp-contact" class="form-control">
                </div>
                <div class="form-group">
                    <label><?= __('Total Due'); ?> (৳)</label>
                    <input type="number" name="total_due" id="edit-supp-due" class="form-control" step="0.01">
                </div>
            </div>
            <div style="display:flex; gap:0.75rem; margin-top:1rem;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?= __('Save Changes'); ?></button>
                <button type="button" class="btn btn-glass" onclick="closeEditSupplier()"><?= __('Cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Pay Supplier Due Modal -->
<div id="pay-due-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width:90%; max-width:400px; padding:1.5rem; border-radius:16px; position:relative;">
        <button onclick="closePayDue()" style="position:absolute; top:1rem; right:1rem; background:none; border:none; color:var(--text-muted); font-size:1.2rem; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
        <h2 style="margin-bottom:0.5rem; font-size:1.1rem;"><i class="fa-solid fa-hand-holding-dollar" style="color:var(--success);"></i> <?= __('Pay Supplier Due'); ?></h2>
        <p id="pay-due-info" style="color:var(--text-muted); font-size:0.85rem; margin-bottom:1.25rem;"></p>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="pay_supplier_due">
            <input type="hidden" name="id" id="pay-due-id">
            <div class="form-group">
                <label><?= __('Amount Paid'); ?> (৳)</label>
                <input type="number" name="amount_paid" id="pay-due-amount" class="form-control" step="0.01" required>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> <?= __('Record Payment'); ?></button>
                <button type="button" class="btn btn-glass" onclick="closePayDue()"><?= __('Cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSupplier(s) {
    document.getElementById('edit-supp-id').value = s.id;
    document.getElementById('edit-supp-name').value = s.name;
    document.getElementById('edit-supp-contact').value = s.contact || '';
    document.getElementById('edit-supp-due').value = s.total_due;
    document.getElementById('edit-supplier-modal').style.display = 'flex';
}
function closeEditSupplier() {
    document.getElementById('edit-supplier-modal').style.display = 'none';
}
function openPayDue(id, name, due) {
    document.getElementById('pay-due-id').value = id;
    document.getElementById('pay-due-amount').value = due;
    document.getElementById('pay-due-info').textContent = name + ' — Outstanding: ৳' + Number(due).toLocaleString();
    document.getElementById('pay-due-modal').style.display = 'flex';
}
function closePayDue() {
    document.getElementById('pay-due-modal').style.display = 'none';
}
['edit-supplier-modal', 'pay-due-modal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
