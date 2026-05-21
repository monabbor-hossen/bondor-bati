<?php
/**
 * Day Ledger View — Unified Closing System
 * Variables: $menuItems, $todayItems, $todayDues, $businessDate, $currentShift
 */
?>

<div class="mb-4 animate-slideUp">
    <div class="flex items-center justify-between mb-1">
        <h2 class="text-lg font-black">
            <i class="fas fa-book text-indigo-400 mr-1"></i> <?= __('day_ledger') ?>
        </h2>
        <span class="text-xs font-semibold text-text-muted bg-card border border-border px-2.5 py-1 rounded-full">
            <?= date('d M, Y', strtotime($businessDate)) ?>
        </span>
    </div>
    <div class="flex items-center gap-2 mt-1">
        <span class="text-[0.65rem] font-bold text-accent uppercase tracking-widest">
            <i class="fas fa-clock mr-1"></i> <?= __($currentShift) ?> <?= __('shift') ?>
        </span>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  SECTION 1: Add Item to Today's Ledger                   -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="bg-card border border-border rounded-xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3">
        <i class="fas fa-plus-circle text-accent mr-1"></i> <?= __('add_to_day') ?>
    </p>
    <div class="flex gap-2">
        <select id="add-item-select"
                class="flex-1 bg-surface border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-accent appearance-none cursor-pointer">
            <option value=""><?= __('select_menu_item') ?></option>
            <?php foreach ($menuItems as $mi): ?>
                <option value="<?= $mi['id'] ?>" data-price="<?= $mi['selling_price'] ?>">
                    <?= htmlspecialchars(currentLang() === 'bn' ? ($mi['item_name_bn'] ?? $mi['item_name']) : $mi['item_name']) ?> — ৳<?= $mi['selling_price'] ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" id="add-item-opening" step="0.5" min="0" placeholder="<?= __('opening_qty') ?>"
               class="w-24 bg-surface border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary text-center focus:outline-none focus:border-accent">
        <button type="button" id="btn-add-day-item"
                class="text-xs font-bold text-accent bg-accent/10 border border-accent/30 px-3 py-2.5 rounded-lg
                       hover:bg-accent/20 transition-all">
            <i class="fas fa-plus"></i>
        </button>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  SECTION 2: Today's Active Items                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="space-y-3 mb-4 stagger" id="day-items-list">
    <?php if (!empty($todayItems)): ?>
        <?php foreach ($todayItems as $item): ?>
        <div class="bg-card border border-border rounded-xl p-4 day-item"
             data-item-id="<?= $item['item_id'] ?>"
             data-selling-price="<?= $item['selling_price'] ?>">

            <!-- Item Header -->
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-sm">
                    <?= htmlspecialchars(currentLang() === 'bn' ? $item['item_name_bn'] : $item['item_name']) ?>
                </h3>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-text-muted mr-1">৳<?= number_format($item['selling_price']) ?>/<?= __('unit') ?></span>
                    <button type="button" class="btn-remove-item text-text-muted hover:text-red-400 transition-colors p-1" title="<?= __('delete') ?>">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Input Grid -->
            <div class="grid grid-cols-3 gap-2.5 mb-3">
                <!-- Opening -->
                <div>
                    <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-wider mb-1"><?= __('opening_qty') ?></label>
                    <input type="number" step="0.5" min="0"
                           class="w-full bg-surface border border-border rounded-lg px-3 py-2.5 text-sm font-semibold text-center focus:border-accent focus:outline-none di-opening"
                           value="<?= (float)$item['opening_qty'] ?>" placeholder="0">
                </div>
                <!-- Closing -->
                <div>
                    <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-wider mb-1"><?= __('closing_qty') ?></label>
                    <input type="number" step="0.5" min="0"
                           class="w-full bg-surface border border-border rounded-lg px-3 py-2.5 text-sm font-semibold text-center focus:border-accent focus:outline-none di-closing"
                           value="<?= $item['closing_qty'] !== '' ? (float)$item['closing_qty'] : '' ?>" placeholder="0">
                </div>
                <!-- Complimentary -->
                <div>
                    <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-wider mb-1"><?= __('comp_qty') ?></label>
                    <input type="number" step="0.5" min="0"
                           class="w-full bg-surface border border-border rounded-lg px-3 py-2.5 text-sm font-semibold text-center focus:border-accent focus:outline-none di-comp"
                           value="<?= (float)$item['complimentary_qty'] ?: '' ?>" placeholder="0">
                </div>
            </div>

            <!-- Calculated Sold -->
            <div class="flex items-center justify-between bg-accent/5 border border-accent/20 rounded-lg px-3 py-2">
                <span class="text-xs font-bold text-text-muted uppercase"><?= __('sold') ?></span>
                <div class="text-right">
                    <span class="text-lg font-black text-accent di-sold">0</span>
                    <span class="text-xs text-text-muted ml-1">= ৳<span class="di-revenue text-accent font-bold">0</span></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div id="empty-state" class="bg-card border border-border/30 rounded-xl p-8 text-center animate-slideUp">
            <i class="fas fa-clipboard-list text-3xl text-text-muted/30 mb-2"></i>
            <p class="text-sm text-text-muted"><?= __('no_data') ?></p>
        </div>
    <?php endif; ?>

    <!-- Update Ledger Button -->
    <?php if (!empty($todayItems)): ?>
    <button type="button" id="btn-update-ledger"
            class="w-full mt-4 bg-indigo-600 text-white font-bold py-3.5 rounded-xl
                   hover:bg-indigo-500 transition-all active:scale-[0.97] text-sm">
        <i class="fas fa-check-circle mr-2"></i> Update Day Ledger
    </button>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  SECTION 3: Customer Dues (Baki)                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="bg-card border border-amber-500/20 rounded-xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-amber-400 uppercase tracking-widest mb-3">
        <i class="fas fa-hand-holding-dollar mr-1"></i> <?= __('customer_dues') ?>
    </p>

    <!-- Add Due Form -->
    <div class="space-y-2 mb-4">
        <input type="text" id="due-name" placeholder="<?= __('customer_name_req') ?>"
               class="w-full bg-surface border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber-400">
        <div class="flex gap-2">
            <input type="number" id="due-amount" step="1" min="1" placeholder="<?= __('due_amount_req') ?>"
                   class="flex-1 bg-surface border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber-400">
            <input type="tel" id="due-phone" placeholder="<?= __('phone') ?>"
                   class="w-28 bg-surface border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber-400">
        </div>
        <div class="flex gap-2">
            <select id="due-item-id" class="flex-1 bg-surface border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary focus:outline-none focus:border-amber-400 appearance-none cursor-pointer">
                <option value="">(Optional) Select Item...</option>
                <?php foreach ($menuItems as $mi): ?>
                    <option value="<?= $mi['id'] ?>">
                        <?= htmlspecialchars(currentLang() === 'bn' ? ($mi['item_name_bn'] ?? $mi['item_name']) : $mi['item_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" id="due-item-qty" step="0.5" min="0" placeholder="Qty"
                   class="w-20 bg-surface border border-border rounded-lg px-3 py-2.5 text-sm text-text-primary text-center focus:outline-none focus:border-amber-400">
        </div>
        <button type="button" id="btn-add-due"
                class="w-full text-xs font-bold text-amber-400 bg-amber-500/10 border border-amber-500/30 px-3 py-2.5 rounded-lg
                       hover:bg-amber-500/20 transition-all mt-1">
            <i class="fas fa-plus mr-1"></i> <?= __('add_due') ?>
        </button>
    </div>

    <!-- Today's Dues List -->
    <div id="dues-list" class="space-y-2">
        <?php if (!empty($todayDues)): ?>
            <?php foreach ($todayDues as $due): ?>
            <div class="flex justify-between items-center bg-surface/50 rounded-lg px-3 py-2 due-row" data-due-id="<?= $due['id'] ?>">
                <div>
                    <span class="text-sm font-semibold"><?= htmlspecialchars($due['customer_name']) ?></span>
                    <?php if (!empty($due['phone'])): ?>
                        <span class="text-[0.6rem] text-text-muted ml-1"><?= htmlspecialchars($due['phone']) ?></span>
                    <?php endif; ?>
                </div>
                <span class="text-sm font-bold text-amber-400">৳<?= number_format($due['due_amount']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  Summary                                                   -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="bg-card border border-border rounded-xl p-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3"><?= __('daily_summary') ?></p>
    <div class="space-y-2">
        <div class="flex justify-between text-sm">
            <span class="text-text-muted"><?= __('total_sales') ?></span>
            <span class="font-bold text-accent" id="summary-total-sales">৳0</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-text-muted"><?= __('sold') ?> (<?= __('qty') ?>)</span>
            <span class="font-bold" id="summary-total-sold">0</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-text-muted"><?= __('complimentary') ?></span>
            <span class="font-bold text-amber-400" id="summary-total-comp">0</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Live Calculation ──────────────────────────────────────
    function recalcAll() {
        let totalSold = 0, totalRevenue = 0, totalComp = 0;

        document.querySelectorAll('.day-item').forEach(row => {
            const opening = parseFloat(row.querySelector('.di-opening').value) || 0;
            const closing = parseFloat(row.querySelector('.di-closing').value) || 0;
            const comp    = parseFloat(row.querySelector('.di-comp').value) || 0;
            const price   = parseFloat(row.dataset.sellingPrice) || 0;

            const sold = Math.max(0, opening - closing - comp);
            const revenue = sold * price;

            row.querySelector('.di-sold').textContent = sold % 1 === 0 ? sold : sold.toFixed(1);
            row.querySelector('.di-revenue').textContent = revenue.toLocaleString('en-IN');

            totalSold    += sold;
            totalRevenue += revenue;
            totalComp    += comp;
        });

        document.getElementById('summary-total-sales').textContent = '৳' + totalRevenue.toLocaleString('en-IN');
        document.getElementById('summary-total-sold').textContent = totalSold;
        document.getElementById('summary-total-comp').textContent = totalComp;
    }

    // Bind existing rows
    function bindRowInputs(row) {
        ['di-opening', 'di-closing', 'di-comp'].forEach(cls => {
            const el = row.querySelector('.' + cls);
            if (el) el.addEventListener('input', recalcAll);
        });
    }

    document.querySelectorAll('.day-item').forEach(bindRowInputs);
    recalcAll();

    // ── Add Item to Day ───────────────────────────────────────
    document.getElementById('btn-add-day-item').addEventListener('click', async () => {
        const sel = document.getElementById('add-item-select');
        const openingInput = document.getElementById('add-item-opening');
        const itemId = sel.value;
        const openingQty = parseFloat(openingInput.value) || 0;

        if (!itemId) return showToast('<?= __("select_menu_item") ?>', 'error');
        if (openingQty <= 0) return showToast('<?= __("opening_qty") ?> required', 'error');

        // Check if already on the list
        if (document.querySelector(`.day-item[data-item-id="${itemId}"]`)) {
            return showToast('Already added!', 'error');
        }

        const res = await apiPost('?url=inventory/upsertDayItem', {
            item_id: itemId,
            opening_qty: openingQty,
            closing_qty: 0,
            complimentary_qty: 0,
        });

        if (res.success) {
            showToast('<?= __("success") ?>', 'success');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(res.error || '<?= __("error") ?>', 'error');
        }
    });

    // ── Update Day Ledger (All Items) ─────────────────────────
    const btnUpdateLedger = document.getElementById('btn-update-ledger');
    if (btnUpdateLedger) {
        btnUpdateLedger.addEventListener('click', async () => {
            btnUpdateLedger.disabled = true;
            btnUpdateLedger.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';

            const closeItems = [];
            document.querySelectorAll('.day-item').forEach(row => {
                closeItems.push({
                    item_id: row.dataset.itemId,
                    effective_opening: parseFloat(row.querySelector('.di-opening').value) || 0,
                    closing_qty: parseFloat(row.querySelector('.di-closing').value) || 0,
                    complimentary_qty: parseFloat(row.querySelector('.di-comp').value) || 0,
                    due_qty: 0,
                    selling_price: parseFloat(row.dataset.sellingPrice) || 0,
                });
            });

            // Use the legacy saveShiftClose endpoint which processes an array of items
            const res = await apiPost('?url=inventory/saveShiftClose', {
                items: closeItems,
                dues: [] // Dues are handled separately now
            });

            if (res.success) {
                showToast('<?= __("success") ?> Ledger Updated', 'success');
            } else {
                showToast(res.error || '<?= __("error") ?>', 'error');
            }

            btnUpdateLedger.disabled = false;
            btnUpdateLedger.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Update Day Ledger';
        });
    }

    // ── Remove Item ───────────────────────────────────────────
    document.querySelectorAll('.btn-remove-item').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('<?= __("confirm_delete") ?>')) return;

            const row = btn.closest('.day-item');
            const res = await apiPost('?url=inventory/removeDayItem', {
                item_id: row.dataset.itemId,
            });

            if (res.success) {
                row.remove();
                recalcAll();
                showToast('<?= __("success") ?>', 'success');
            } else {
                showToast(res.error || '<?= __("error") ?>', 'error');
            }
        });
    });

    // ── Add Customer Due ──────────────────────────────────────
    document.getElementById('btn-add-due').addEventListener('click', async () => {
        const nameInput   = document.getElementById('due-name');
        const amountInput = document.getElementById('due-amount');
        const phoneInput  = document.getElementById('due-phone');
        const itemSelect  = document.getElementById('due-item-id');
        const qtyInput    = document.getElementById('due-item-qty');

        const name   = nameInput.value.trim();
        const amount = parseFloat(amountInput.value) || 0;
        const phone  = phoneInput.value.trim();
        const itemId = parseInt(itemSelect.value) || 0;
        const qty    = parseFloat(qtyInput.value) || 0;

        if (!name) return showToast('<?= __("customer_name_req") ?>', 'error');
        if (amount <= 0) return showToast('<?= __("due_amount_req") ?>', 'error');

        const res = await apiPost('?url=inventory/addCustomerDue', {
            customer_name: name,
            due_amount: amount,
            phone: phone,
            item_id: itemId,
            qty: qty
        });

        if (res.success) {
            // Append to list
            const list = document.getElementById('dues-list');
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center bg-surface/50 rounded-lg px-3 py-2 due-row';
            
            let itemNameStr = '';
            if (itemId > 0) {
                const itemOpt = itemSelect.options[itemSelect.selectedIndex];
                itemNameStr = ` <span class="text-xs text-text-muted bg-card px-1.5 py-0.5 rounded ml-1 border border-border">${itemOpt.text.trim()} (x${qty})</span>`;
            }

            div.innerHTML = `
                <div>
                    <span class="text-sm font-semibold">${name}</span>${itemNameStr}
                    ${phone ? `<br><span class="text-[0.6rem] text-text-muted">${phone}</span>` : ''}
                </div>
                <span class="text-sm font-bold text-amber-400">৳${amount.toLocaleString('en-IN')}</span>
            `;
            list.prepend(div);

            // Clear inputs
            nameInput.value = '';
            amountInput.value = '';
            phoneInput.value = '';
            itemSelect.value = '';
            qtyInput.value = '';

            showToast('<?= __("success") ?>', 'success');
        } else {
            showToast(res.error || '<?= __("error") ?>', 'error');
        }
    });
});
</script>
