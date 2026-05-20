<?php
/**
 * Shift Closing View — 3-Shift System
 * Variables: $closeData, $logDate, $currentShift, $closedShifts
 * Formula: Sold = Effective Opening - Closing - Complimentary - Due
 */
$shifts = ['morning', 'evening', 'night'];
?>

<div class="mb-4 animate-slideUp">
    <div class="flex items-center justify-between mb-1">
        <h2 class="text-lg font-black">
            <i class="fas fa-moon text-indigo-400 mr-1"></i> <?= __('shift_closing') ?>
        </h2>
        <span class="text-xs font-semibold text-text-muted bg-card border border-border px-2.5 py-1 rounded-full">
            <?= date('D, d M', strtotime($logDate)) ?>
        </span>
    </div>
</div>

<!-- Shift Selector -->
<div class="flex gap-2 mb-4 animate-slideUp">
    <?php foreach ($shifts as $s): ?>
    <button class="shift-tab flex-1 text-center py-2.5 rounded-xl text-xs font-bold border transition-all duration-200
                   <?= $s === $currentShift
                       ? 'bg-accent/10 border-accent/40 text-accent'
                       : (in_array($s, $closedShifts)
                           ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400'
                           : 'bg-card border-border text-text-muted hover:text-text-primary') ?>"
            data-shift="<?= $s ?>"
            onclick="window.location='?url=inventory/closeDayView&date=<?= $logDate ?>&shift=<?= $s ?>'">
        <?= __($s) ?>
        <?= in_array($s, $closedShifts) ? ' <i class="fas fa-check"></i>' : '' ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- Current Shift Label -->
<div class="flex items-center gap-2 mb-4 animate-slideUp">
    <span class="text-[0.65rem] font-bold text-accent uppercase tracking-widest">
        <i class="fas fa-clock mr-1"></i> <?= __($currentShift) ?> <?= __('shift') ?>
    </span>
    <?php if (in_array($currentShift, $closedShifts)): ?>
    <span class="text-[0.6rem] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">
        <i class="fas fa-lock mr-0.5"></i> Already Closed
    </span>
    <?php endif; ?>
</div>

<!-- Items List -->
<form id="close-form" class="space-y-3 stagger">
    <input type="hidden" id="close-log-date" value="<?= $logDate ?>">
    <input type="hidden" id="close-shift" value="<?= $currentShift ?>">

    <?php foreach ($closeData as $item): ?>
    <div class="bg-card border border-border rounded-xl p-4 close-item"
         data-item-id="<?= $item['item_id'] ?>"
         data-selling-price="<?= $item['selling_price'] ?>"
         data-cost-price="<?= $item['cost_price'] ?>">

        <!-- Item Header -->
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-sm">
                <?= htmlspecialchars(currentLang() === 'bn' ? $item['item_name_bn'] : $item['item_name']) ?>
            </h3>
            <span class="text-xs text-text-muted">৳<?= number_format($item['selling_price']) ?>/<?= __('unit') ?></span>
        </div>

        <!-- Effective Opening (read-only) -->
        <div class="flex items-center justify-between bg-surface/50 rounded-lg px-3 py-2 mb-3">
            <span class="text-[0.65rem] font-bold text-text-muted uppercase"><?= __('opening_stock') ?></span>
            <span class="text-lg font-black text-text-primary close-opening"><?= $item['effective_opening'] ?></span>
            <input type="hidden" class="close-opening-val" value="<?= $item['effective_opening'] ?>">
        </div>

        <!-- Input Grid -->
        <div class="grid grid-cols-2 gap-2.5 mb-3">
            <!-- Closing Stock -->
            <div>
                <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-wider mb-1"><?= __('closing_stock') ?></label>
                <input type="number" step="0.5" min="0"
                       class="w-full bg-surface border border-border rounded-lg px-3 py-2.5 text-sm font-semibold text-center focus:border-accent close-closing"
                       value="<?= $item['closing_qty'] ?>" placeholder="0">
            </div>
            <!-- Complimentary -->
            <div>
                <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-wider mb-1"><?= __('complimentary') ?></label>
                <input type="number" step="0.5" min="0"
                       class="w-full bg-surface border border-border rounded-lg px-3 py-2.5 text-sm font-semibold text-center focus:border-accent close-comp"
                       value="<?= $item['complimentary_qty'] ?>" placeholder="0">
            </div>
        </div>

        <!-- Due (Baki) -->
        <div class="mb-3">
            <label class="block text-[0.6rem] font-bold text-amber-400 uppercase tracking-wider mb-1">
                <i class="fas fa-hand-holding-dollar mr-0.5"></i> <?= __('due_baki') ?>
            </label>
            <input type="number" step="0.5" min="0"
                   class="w-full bg-surface border border-amber-500/30 rounded-lg px-3 py-2.5 text-sm font-semibold text-center focus:border-amber-400 close-due"
                   value="<?= $item['due_qty'] ?>" placeholder="0">
        </div>

        <!-- Calculated Sold -->
        <div class="flex items-center justify-between bg-accent/5 border border-accent/20 rounded-lg px-3 py-2">
            <span class="text-xs font-bold text-text-muted uppercase"><?= __('sold') ?></span>
            <div class="text-right">
                <span class="text-lg font-black text-accent close-sold">0</span>
                <span class="text-xs text-text-muted ml-1">= ৳<span class="close-revenue text-accent font-bold">0</span></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Due Customer Details (collapsible) -->
    <div class="bg-card border border-amber-500/20 rounded-xl p-4 animate-slideUp" id="due-section" style="display:none;">
        <p class="text-xs font-bold text-amber-400 uppercase tracking-widest mb-3">
            <i class="fas fa-users mr-1"></i> <?= __('customer_dues') ?>
        </p>
        <div id="due-entries" class="space-y-3"></div>
        <button type="button" id="btn-add-due"
                class="mt-2 text-xs font-bold text-amber-400 bg-amber-500/10 border border-amber-500/30 px-3 py-2 rounded-lg w-full
                       hover:bg-amber-500/20 transition-all">
            <i class="fas fa-plus mr-1"></i> <?= __('add') ?> <?= __('due_baki') ?>
        </button>
    </div>

    <!-- Totals Summary -->
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
            <div class="flex justify-between text-sm">
                <span class="text-text-muted"><?= __('due_baki') ?></span>
                <span class="font-bold text-indigo-400" id="summary-total-due">0</span>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <button type="button" id="btn-close-shift"
            class="w-full bg-indigo-600 text-white font-bold py-3.5 rounded-xl
                   hover:bg-indigo-500 transition-all active:scale-[0.97] text-sm">
        <i class="fas fa-lock mr-2"></i> <?= __('close_shift') ?> — <?= __(ucfirst($currentShift)) ?>
    </button>
</form>

<!-- Results Modal (shown after successful close) -->
<div id="results-modal" class="fixed inset-0 z-[100] flex items-center justify-center px-4" style="display:none;">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('results-modal').style.display='none'"></div>
    <div class="relative bg-card border border-border rounded-2xl p-6 w-full max-w-sm z-10">
        <h3 class="text-center text-lg font-black mb-4">
            <i class="fas fa-check-circle text-emerald-400 mr-1"></i> <?= __('results') ?>
        </h3>
        <div class="space-y-3 mb-5">
            <div class="flex justify-between text-sm">
                <span class="text-text-muted"><?= __('total_revenue') ?></span>
                <span class="font-bold text-accent" id="result-revenue">৳0</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-text-muted"><?= __('cash_in_drawer') ?></span>
                <span class="font-bold text-emerald-400" id="result-cash">৳0</span>
            </div>
            <div class="flex justify-between text-sm border-t border-border pt-2">
                <span class="text-text-muted"><?= __('net_profit') ?></span>
                <span class="font-bold text-lg" id="result-profit">৳0</span>
            </div>
        </div>
        <button onclick="document.getElementById('results-modal').style.display='none'"
                class="w-full bg-accent text-white font-bold py-3 rounded-xl hover:bg-accent-light transition-all text-sm">
            <?= __('close') ?>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.close-item');
    const dueSection = document.getElementById('due-section');

    // ── Live Calculation ──────────────────────────────────────
    function recalcAll() {
        let totalSold = 0, totalRevenue = 0, totalComp = 0, totalDue = 0;

        items.forEach(row => {
            const opening = parseFloat(row.querySelector('.close-opening-val').value) || 0;
            const closing = parseFloat(row.querySelector('.close-closing').value) || 0;
            const comp    = parseFloat(row.querySelector('.close-comp').value) || 0;
            const due     = parseFloat(row.querySelector('.close-due').value) || 0;
            const price   = parseFloat(row.dataset.sellingPrice) || 0;

            const sold = Math.max(0, opening - closing - comp - due);
            const revenue = sold * price;

            row.querySelector('.close-sold').textContent = sold % 1 === 0 ? sold : sold.toFixed(1);
            row.querySelector('.close-revenue').textContent = revenue.toLocaleString('en-IN');

            totalSold    += sold;
            totalRevenue += revenue;
            totalComp    += comp;
            totalDue     += due;
        });

        document.getElementById('summary-total-sales').textContent = '৳' + totalRevenue.toLocaleString('en-IN');
        document.getElementById('summary-total-sold').textContent = totalSold;
        document.getElementById('summary-total-comp').textContent = totalComp;
        document.getElementById('summary-total-due').textContent = totalDue;

        // Show/hide due section
        dueSection.style.display = totalDue > 0 ? 'block' : 'none';
    }

    items.forEach(row => {
        ['close-closing', 'close-comp', 'close-due'].forEach(cls => {
            row.querySelector('.' + cls).addEventListener('input', recalcAll);
        });
    });

    recalcAll();

    // ── Add Due Entry ─────────────────────────────────────────
    let dueIndex = 0;
    document.getElementById('btn-add-due').addEventListener('click', () => {
        const container = document.getElementById('due-entries');
        const div = document.createElement('div');
        div.className = 'bg-surface border border-border rounded-lg p-3 space-y-2';
        div.innerHTML = `
            <div class="flex gap-2">
                <input type="text" placeholder="<?= __('customer_name') ?>" class="due-name flex-1 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary">
                <input type="tel" placeholder="<?= __('phone') ?>" class="due-phone w-28 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary">
            </div>
            <div class="flex gap-2">
                <input type="number" placeholder="<?= __('amount') ?> (৳)" class="due-amount flex-1 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary">
                <button type="button" onclick="this.closest('.space-y-2').remove()" class="text-red-400 px-3"><i class="fas fa-trash-alt"></i></button>
            </div>
        `;
        container.appendChild(div);
    });

    // ── Submit Shift Close ────────────────────────────────────
    document.getElementById('btn-close-shift').addEventListener('click', async () => {
        const btn = document.getElementById('btn-close-shift');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

        const closeItems = [];
        items.forEach(row => {
            closeItems.push({
                item_id: row.dataset.itemId,
                effective_opening: parseFloat(row.querySelector('.close-opening-val').value) || 0,
                closing_qty: parseFloat(row.querySelector('.close-closing').value) || 0,
                complimentary_qty: parseFloat(row.querySelector('.close-comp').value) || 0,
                due_qty: parseFloat(row.querySelector('.close-due').value) || 0,
                selling_price: parseFloat(row.dataset.sellingPrice) || 0,
            });
        });

        // Collect due entries
        const dues = [];
        document.querySelectorAll('#due-entries > div').forEach(entry => {
            const name   = entry.querySelector('.due-name').value;
            const phone  = entry.querySelector('.due-phone').value;
            const amount = parseFloat(entry.querySelector('.due-amount').value) || 0;
            if (name && amount > 0) {
                dues.push({ customer_name: name, phone, amount });
            }
        });

        const res = await apiPost('?url=inventory/saveShiftClose', {
            log_date: document.getElementById('close-log-date').value,
            shift: document.getElementById('close-shift').value,
            items: closeItems,
            dues: dues,
        });

        if (res.success) {
            showToast('<?= __('success') ?> Shift closed!', 'success');

            // Show results modal
            document.getElementById('result-revenue').textContent = '৳' + (res.total_revenue || 0).toLocaleString('en-IN');
            document.getElementById('result-cash').textContent = '৳' + (res.cash_in_drawer || 0).toLocaleString('en-IN');
            const profitEl = document.getElementById('result-profit');
            profitEl.textContent = '৳' + (res.net_profit || 0).toLocaleString('en-IN');
            profitEl.className = 'font-bold text-lg ' + ((res.net_profit || 0) >= 0 ? 'text-emerald-400' : 'text-red-400');
            document.getElementById('results-modal').style.display = 'flex';

            btn.innerHTML = '<i class="fas fa-check mr-2"></i> Closed';
        } else {
            showToast(res.error || '<?= __('error') ?>', 'error');
            btn.innerHTML = '<i class="fas fa-lock mr-2"></i> <?= __('close_shift') ?>';
            btn.disabled = false;
        }
    });
});
</script>
