<header>
    <div class="mobile-header">
        <button id="mobile-menu-btn" class="btn btn-glass mobile-only"><i class="fa-solid fa-bars"></i></button>
        <div class="header-title">
            <h1><i class="fa-solid fa-chart-line" style="color: var(--success);"></i> <?= __('Forecasting & Smart Features'); ?></h1>
            <p><?= __('Gas prediction, bazaar suggestions, calendar events, advance orders, and expenses.'); ?></p>
        </div>
    </div>
</header>

<!-- Gas Refill Prediction Card -->
<div class="grid-cards">
    <div class="stat-card glass-panel">
        <i class="fa-solid fa-fire icon"></i>
        <h3><?= __('Next Gas Refill'); ?></h3>
        <?php if ($nextGasDate && $gasDaysLeft !== null): ?>
            <div class="value"><?= __('In'); ?> <?= $gasDaysLeft; ?> <?= __('Day'); ?><?= $gasDaysLeft !== 1 ? 's' : ''; ?></div>
            <div class="trend <?= $gasDaysLeft <= 3 ? 'warning' : 'positive'; ?>">
                <i class="fa-solid fa-<?= $gasDaysLeft <= 3 ? 'triangle-exclamation' : 'check'; ?>"></i>
                <?= date('M d, Y', strtotime($nextGasDate)); ?>
            </div>
        <?php else: ?>
            <div class="value"><?= __('No Data'); ?></div>
            <div class="trend" style="color: var(--text-muted);"><?= __('Log gas expenses to start predictions.'); ?></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($bazaarSuggestions)): ?>
    <div class="stat-card glass-panel">
        <i class="fa-solid fa-wand-magic-sparkles icon"></i>
        <h3><?= __('Smart Bazaar Prep'); ?></h3>
        <div class="value"><?= count($bazaarSuggestions); ?> <?= __('Items'); ?></div>
        <div class="trend positive"><i class="fa-solid fa-chart-simple"></i> <?= __('Based on 7-day average'); ?></div>
    </div>
    <?php endif; ?>
</div>

<!-- Tabs -->
<div class="tab-bar" data-group="forecast">
    <button class="tab-btn active" data-tab="tab-expenses" data-group="forecast"><i class="fa-solid fa-coins"></i> <?= __('Expenses'); ?></button>
    <button class="tab-btn" data-tab="tab-events" data-group="forecast"><i class="fa-solid fa-calendar-day"></i> <?= __('Calendar Events'); ?></button>
    <button class="tab-btn" data-tab="tab-orders" data-group="forecast"><i class="fa-solid fa-clipboard-list"></i> <?= __('Advance Orders'); ?></button>
    <button class="tab-btn" data-tab="tab-suggestions" data-group="forecast"><i class="fa-solid fa-lightbulb"></i> <?= __('Bazaar Suggestions'); ?></button>
</div>

<!-- ═══ Expenses Tab ═══ -->
<div class="tab-pane active" id="tab-expenses" data-group="forecast">
    <div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
        <div class="section-header"><h2><?= __('Add Expense'); ?></h2></div>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="add_expense">
            <div class="form-row">
                <div class="form-group">
                    <label><?= __('Category'); ?></label>
                    <select name="category" class="form-control" required>
                        <option value="Gas"><?= __('Gas'); ?></option>
                        <option value="Fixed"><?= __('Fixed'); ?></option>
                        <option value="Asset"><?= __('Asset'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= __('Name'); ?></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Gas Cylinder" required>
                </div>
                <div class="form-group">
                    <label><?= __('Total Amount'); ?> (৳)</label>
                    <input type="number" name="total_amount" class="form-control" step="0.01" required>
                </div>
                <div class="form-group">
                    <label><?= __('Spread Daily?'); ?></label>
                    <select name="is_spread" class="form-control" id="spread-select">
                        <option value="0"><?= __('No (One-time)'); ?></option>
                        <option value="1"><?= __('Yes (Daily deduction)'); ?></option>
                    </select>
                </div>
                <div class="form-group" id="daily-amount-group" style="display: none;">
                    <label><?= __('Daily Amount'); ?> (৳)</label>
                    <input type="number" name="daily_amount" class="form-control" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label><?= __('Date'); ?></label>
                    <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d'); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;"><i class="fa-solid fa-plus"></i> <?= __('Add Expense'); ?></button>
        </form>
    </div>

    <div class="section-panel glass-panel">
        <div class="section-header"><h2><?= __('All Expenses'); ?></h2></div>
        <?php if (empty($expenses)): ?>
            <div class="empty-state"><i class="fa-solid fa-coins"></i><p><?= __('No expenses recorded.'); ?></p></div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= __('Category'); ?></th>
                        <th><?= __('Name'); ?></th>
                        <th><?= __('Total'); ?></th>
                        <th><?= __('Spread'); ?></th>
                        <th><?= __('Daily'); ?></th>
                        <th><?= __('Date'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $e): ?>
                    <tr>
                        <td><span class="badge <?= $e['category'] === 'Gas' ? 'warning' : ($e['category'] === 'Fixed' ? 'success' : 'danger'); ?>"><?= $e['category']; ?></span></td>
                        <td><?= htmlspecialchars($e['name']); ?></td>
                        <td>৳ <?= number_format($e['total_amount'], 0); ?></td>
                        <td><?= $e['is_spread'] ? __('Yes') : __('No'); ?></td>
                        <td>৳ <?= number_format($e['daily_amount'], 0); ?></td>
                        <td><?= date('M d', strtotime($e['expense_date'])); ?></td>
                        <td><button class="btn btn-danger btn-sm" data-action="delete_expense" data-id="<?= $e['id']; ?>"><i class="fa-solid fa-trash"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Calendar Events Tab ═══ -->
<div class="tab-pane" id="tab-events" data-group="forecast">
    <div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
        <div class="section-header"><h2><?= __('Add Calendar Event'); ?></h2></div>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="add_event">
            <div class="form-row">
                <div class="form-group">
                    <label><?= __('Event Date'); ?></label>
                    <input type="date" name="event_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?= __('Event Name'); ?></label>
                    <input type="text" name="event_name" class="form-control" placeholder="e.g., Agargaon Mela" required>
                </div>
                <div class="form-group">
                    <label><?= __('Impact Multiplier'); ?></label>
                    <input type="number" name="impact_multiplier" class="form-control" step="0.01" value="1.50" min="1">
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> <?= __('Add'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <div class="section-panel glass-panel">
        <div class="section-header"><h2><?= __('Upcoming Events'); ?></h2></div>
        <?php if (empty($events)): ?>
            <div class="empty-state"><i class="fa-solid fa-calendar-day"></i><p><?= __('No events scheduled.'); ?></p></div>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($events as $ev): ?>
            <div class="list-item">
                <div class="item-info">
                    <h4><?= htmlspecialchars($ev['event_name']); ?></h4>
                    <p><?= date('M d, Y', strtotime($ev['event_date'])); ?></p>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="badge warning"><?= $ev['impact_multiplier']; ?>x</span>
                    <button class="btn btn-danger btn-sm" data-action="delete_event" data-id="<?= $ev['id']; ?>"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Advance Orders Tab ═══ -->
<div class="tab-pane" id="tab-orders" data-group="forecast">
    <div class="section-panel glass-panel" style="margin-bottom: 1.5rem;">
        <div class="section-header"><h2><?= __('Place Advance Order'); ?></h2></div>
        <form data-ajax data-reload="true">
            <input type="hidden" name="action" value="add_order">
            <div class="form-row">
                <div class="form-group">
                    <label><?= __('Delivery Date'); ?></label>
                    <input type="date" name="delivery_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?= __('Customer Info'); ?></label>
                    <input type="text" name="customer_info" class="form-control" placeholder="Name & details" required>
                </div>
                <div class="form-group">
                    <label><?= __('Total Bill'); ?> (৳)</label>
                    <input type="number" name="total_bill" class="form-control" step="0.01" required>
                </div>
                <div class="form-group">
                    <label><?= __('Advance Paid'); ?> (৳)</label>
                    <input type="number" name="advance_paid" class="form-control" step="0.01" value="0">
                </div>
            </div>
            <h3 style="font-size: 0.9rem; margin: 1rem 0 0.5rem;"><?= __('Order Items'); ?></h3>
            <div class="dynamic-rows" id="order-item-rows">
                <div class="dynamic-row">
                    <select name="oi_item[]" class="form-control" style="flex:2;">
                        <option value=""><?= __('Select Item'); ?></option>
                        <?php foreach ($items as $it): ?>
                        <option value="<?= $it['id']; ?>"><?= htmlspecialchars($it['item_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="oi_qty[]" class="form-control" placeholder="<?= __('Qty'); ?>" style="flex:1;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeDynamicRow(this)"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div style="margin-top: 0.75rem; display: flex; gap: 1rem;">
                <button type="button" class="btn btn-glass" onclick="addOrderItemRow()"><i class="fa-solid fa-plus"></i> <?= __('Add Item'); ?></button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?= __('Place Order'); ?></button>
            </div>
        </form>
    </div>

    <div class="section-panel glass-panel">
        <div class="section-header"><h2><?= __('All Orders'); ?></h2></div>
        <?php if (empty($orders)): ?>
            <div class="empty-state"><i class="fa-solid fa-clipboard-list"></i><p><?= __('No advance orders yet.'); ?></p></div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= __('Delivery'); ?></th>
                        <th><?= __('Customer'); ?></th>
                        <th><?= __('Bill'); ?></th>
                        <th><?= __('Advance'); ?></th>
                        <th><?= __('Status'); ?></th>
                        <th><?= __('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= date('M d', strtotime($o['delivery_date'])); ?></td>
                        <td><strong><?= htmlspecialchars($o['customer_info']); ?></strong></td>
                        <td>৳ <?= number_format($o['total_bill'], 0); ?></td>
                        <td>৳ <?= number_format($o['advance_paid'], 0); ?></td>
                        <td>
                            <span class="badge <?= $o['status'] === 'Delivered' ? 'success' : ($o['status'] === 'Cancelled' ? 'danger' : 'warning'); ?>"><?= __($o['status']); ?></span>
                        </td>
                        <td style="display: flex; gap: 0.5rem;">
                            <?php if ($o['status'] === 'Pending'): ?>
                            <button class="btn btn-success btn-sm" data-action="update_order_status" data-id="<?= $o['id']; ?>" data-extra='{"status":"Delivered"}' title="<?= __('Mark Delivered'); ?>"><i class="fa-solid fa-check"></i></button>
                            <button class="btn btn-danger btn-sm" data-action="update_order_status" data-id="<?= $o['id']; ?>" data-extra='{"status":"Cancelled"}' title="<?= __('Cancel'); ?>"><i class="fa-solid fa-xmark"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ Bazaar Suggestions Tab ═══ -->
<div class="tab-pane" id="tab-suggestions" data-group="forecast">
    <div class="section-panel glass-panel">
        <div class="section-header"><h2><?= __('Smart Bazaar Suggestions for Tomorrow'); ?></h2></div>
        <?php if (empty($bazaarSuggestions)): ?>
            <div class="empty-state"><i class="fa-solid fa-lightbulb"></i><p><?= __('No data yet. Start logging daily stocks to get AI-powered suggestions.'); ?></p></div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= __('Item'); ?></th>
                        <th><?= __('Suggested Prep Qty'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bazaarSuggestions as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['item_name']); ?></strong></td>
                        <td><span class="badge success">~<?= round($s['suggested_prep_qty']); ?> pcs</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('spread-select').addEventListener('change', function() {
    document.getElementById('daily-amount-group').style.display = this.value === '1' ? 'block' : 'none';
});

function addOrderItemRow() {
    const itemOpts = <?= json_encode(array_map(fn($i) => ['id' => $i['id'], 'name' => $i['item_name']], $items)); ?>;
    let opts = '<option value=""><?= __("Select Item"); ?></option>';
    itemOpts.forEach(i => opts += `<option value="${i.id}">${i.name}</option>`);

    addDynamicRow('order-item-rows', () => `
        <select name="oi_item[]" class="form-control" style="flex:2;">${opts}</select>
        <input type="number" name="oi_qty[]" class="form-control" placeholder="Qty" style="flex:1;">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeDynamicRow(this)"><i class="fa-solid fa-xmark"></i></button>
    `);
}
</script>
