<?php 
$today = date('Y-m-d'); 
$shift = $_GET['shift'] ?? 'Morning';
?>
<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-utensils" style="color: var(--accent-secondary);"></i> <?= __('Service & Sales'); ?></h1>
            <p><?= __('Log customer credit sales and complimentary meals during service.'); ?></p>
        </div>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center; margin-top: 0.5rem;">
        <label style="font-weight: 500; font-size: 0.9rem; color: var(--text-muted);"><?= __('Shift'); ?>:</label>
        <select class="form-control" onchange="location.href='?page=service&shift=' + this.value" style="width: auto;">
            <option value="Morning" <?= $shift === 'Morning' ? 'selected' : ''; ?>><?= __('Morning'); ?></option>
            <option value="Evening" <?= $shift === 'Evening' ? 'selected' : ''; ?>><?= __('Evening'); ?></option>
            <option value="Night" <?= $shift === 'Night' ? 'selected' : ''; ?>><?= __('Night'); ?></option>
        </select>
    </div>
</header>

<div class="tab-bar" data-group="service">
    <button class="tab-btn active" data-tab="tab-dues" data-group="service"><i class="fa-solid fa-hand-holding-dollar"></i> <?= __('Customer Dues'); ?></button>
    <button class="tab-btn" data-tab="tab-comp" data-group="service"><i class="fa-solid fa-gift"></i> <?= __('Complimentary Log'); ?></button>
</div>

<!-- ═══ Customer Dues Tab ═══ -->
<div class="tab-pane active" id="tab-dues" data-group="service">
    <!-- Add Due -->
    <div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
        <div class="section-header"><h2><?= __('Log a Customer Due'); ?></h2></div>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="add_due">
            <input type="hidden" name="log_date" value="<?= $today; ?>">
            <input type="hidden" name="shift" value="<?= $shift; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label><?= __('Customer Name'); ?></label>
                    <input type="text" name="customer_name" class="form-control" placeholder="e.g., Rahim Bhai" required>
                </div>
                <div class="form-group">
                    <label><?= __('Phone'); ?></label>
                    <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX">
                </div>
                <div class="form-group">
                    <label><?= __('Due Amount'); ?> (৳)</label>
                    <input type="number" name="due_amount" class="form-control" step="0.01" required>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> <?= __('Log Due'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Dues List -->
    <div class="section-panel glass-panel">
        <div class="section-header"><h2><?= __('All Customer Dues'); ?></h2></div>
        <?php if (empty($customerDues)): ?>
            <div class="empty-state"><i class="fa-solid fa-hand-holding-dollar"></i><p><?= __('No customer dues logged yet.'); ?></p></div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= __('Customer'); ?></th>
                        <th><?= __('Phone'); ?></th>
                        <th><?= __('Amount'); ?></th>
                        <th><?= __('Date'); ?></th>
                        <th><?= __('Status'); ?></th>
                        <th><?= __('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customerDues as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['customer_name']); ?></strong></td>
                        <td><?= htmlspecialchars($d['phone'] ?: '—'); ?></td>
                        <td>৳ <?= number_format($d['due_amount'], 0); ?></td>
                        <td><?= date('M d', strtotime($d['log_date'])); ?></td>
                        <td>
                            <span class="badge <?= $d['status'] === 'Paid' ? 'success' : 'danger'; ?>">
                                <?= __($d['status']); ?>
                            </span>
                        </td>
                        <td style="display: flex; gap: 0.5rem;">
                            <?php if ($d['status'] === 'Unpaid'): ?>
                            <button class="btn btn-success btn-sm" data-action="mark_due_paid" data-id="<?= $d['id']; ?>">
                                <i class="fa-solid fa-check"></i> <?= __('Paid'); ?>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-danger btn-sm" data-action="delete_due" data-id="<?= $d['id']; ?>">
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
</div>

<!-- ═══ Complimentary Meals Tab ═══ -->
<div class="tab-pane" id="tab-comp" data-group="service">
    <div class="section-panel glass-panel">
        <div class="section-header"><h2><?= __("Today's Stock — Complimentary Log"); ?></h2></div>
        <p style="color: var(--text-muted); margin-bottom: 1rem; font-size: 0.85rem;">
            <?= __('Complimentary meals will be deducted from sold qty during Night Closing. Set the counts here or during closing.'); ?>
        </p>

        <?php if (empty($todayStocks)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-boxes-stacked"></i>
                <p><?= __('No stock prepared for today.'); ?> <a href="?page=morning&shift=<?= $shift; ?>" style="color: var(--accent-primary);"><?= __('Go to Morning & Prep'); ?></a> <?= __('first.'); ?></p>
            </div>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($todayStocks as $ts): ?>
            <div class="list-item">
                <div class="item-info">
                    <h4><?= htmlspecialchars($ts['item_name']); ?></h4>
                    <p><?= __('Opening Qty'); ?>: <?= $ts['opening_qty']; ?> | <?= __('Complimentary logged'); ?>: <?= $ts['complimentary_qty']; ?></p>
                </div>
                <span class="badge success"><?= __('In Stock'); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
