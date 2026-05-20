<?php
/**
 * Bazaar Requisition Ledger View
 * Variables: $logDate, $ledger, $bazaarItems, $yesterdayCF
 */
$isExisting = !empty($ledger);
$advanceCash = $isExisting ? (float)$ledger['advance_cash'] : 0;
?>

<div class="mb-4 animate-slideUp">
    <div class="flex items-center justify-between mb-1">
        <h2 class="text-lg font-black">
            <i class="fas fa-cart-shopping text-emerald-400 mr-1"></i> <?= __('bazaar') ?>
        </h2>
        <span class="text-xs font-semibold text-text-muted bg-card border border-border px-2.5 py-1 rounded-full">
            <?= date('D, d M', strtotime($logDate)) ?>
        </span>
    </div>
</div>

<!-- Yesterday's Carry Forward -->
<?php if ($yesterdayCF > 0): ?>
<div class="bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 px-4 py-3 rounded-xl text-sm font-medium mb-4 animate-slideUp">
    <i class="fas fa-arrow-right mr-1"></i>
    <?= __('carry_forward') ?>: <strong>৳<?= number_format($yesterdayCF) ?></strong>
</div>
<?php endif; ?>

<form id="bazaar-form" class="space-y-4 stagger">
    <input type="hidden" id="bazaar-log-date" value="<?= $logDate ?>">

    <!-- Advance Cash -->
    <div class="bg-card border border-border rounded-xl p-4">
        <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-2">
            <i class="fas fa-money-bill-wave text-emerald-400 mr-1"></i> <?= __('advance') ?> (<?= __('tk') ?>)
        </label>
        <input type="number" id="bazaar-advance" step="1" min="0"
               class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-xl font-black text-center text-emerald-400 focus:border-emerald-400"
               value="<?= $advanceCash ?>" placeholder="0">
    </div>

    <!-- Bazaar Items -->
    <div class="bg-card border border-border rounded-xl p-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest"><?= __('item') ?></p>
            <button type="button" id="btn-add-bazaar-item"
                    class="text-xs font-bold text-accent bg-accent/10 border border-accent/30 px-2.5 py-1 rounded-lg
                           hover:bg-accent/20 transition-all">
                <i class="fas fa-plus mr-0.5"></i> <?= __('add') ?>
            </button>
        </div>

        <div id="bazaar-items-list" class="space-y-2.5">
            <?php if (!empty($bazaarItems)): ?>
                <?php foreach ($bazaarItems as $bi): ?>
                <div class="bazaar-item bg-surface border border-border rounded-lg p-3">
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <input type="text" placeholder="<?= __('item') ?>" value="<?= htmlspecialchars($bi['item_name']) ?>"
                               class="bi-name col-span-2 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary">
                        <input type="text" placeholder="<?= __('unit') ?>" value="<?= htmlspecialchars($bi['unit']) ?>"
                               class="bi-unit bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" placeholder="<?= __('qty') ?>" value="<?= $bi['bought_qty'] ?>"
                               class="bi-qty bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="0.5">
                        <input type="number" placeholder="৳/<?= __('unit') ?>" value="<?= $bi['unit_price'] ?>"
                               class="bi-up bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="1">
                        <div class="flex items-center gap-1">
                            <span class="bi-total text-sm font-bold text-accent flex-1 text-center">৳<?= number_format($bi['total_price']) ?></span>
                            <button type="button" onclick="this.closest('.bazaar-item').remove(); recalcBazaar();"
                                    class="text-red-400 text-xs p-1"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default empty row -->
                <div class="bazaar-item bg-surface border border-border rounded-lg p-3">
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <input type="text" placeholder="<?= __('item') ?>"
                               class="bi-name col-span-2 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary">
                        <input type="text" placeholder="<?= __('unit') ?>" value="kg"
                               class="bi-unit bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" placeholder="<?= __('qty') ?>"
                               class="bi-qty bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="0.5">
                        <input type="number" placeholder="৳/<?= __('unit') ?>"
                               class="bi-up bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="1">
                        <div class="flex items-center gap-1">
                            <span class="bi-total text-sm font-bold text-accent flex-1 text-center">৳0</span>
                            <button type="button" onclick="this.closest('.bazaar-item').remove(); recalcBazaar();"
                                    class="text-red-400 text-xs p-1"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Returned Cash -->
    <div class="bg-card border border-border rounded-xl p-4">
        <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-2">
            <i class="fas fa-rotate-left text-amber-400 mr-1"></i> <?= __('returned') ?> (<?= __('tk') ?>)
        </label>
        <input type="number" id="bazaar-returned" step="1" min="0"
               class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-base font-bold text-center focus:border-amber-400"
               value="<?= $isExisting ? $ledger['returned_cash'] : 0 ?>" placeholder="0">
    </div>

    <!-- Balance Summary -->
    <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3"><?= __('balance') ?></p>
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-text-muted"><?= __('advance') ?></span>
                <span class="font-bold" id="bal-advance">৳0</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-text-muted"><?= __('spent') ?></span>
                <span class="font-bold text-accent" id="bal-spent">৳0</span>
            </div>
            <div class="flex justify-between text-sm border-t border-border pt-2">
                <span class="text-text-muted"><?= __('balance') ?></span>
                <span class="font-bold text-lg" id="bal-balance">৳0</span>
            </div>
            <div class="flex justify-between text-sm" id="bal-due-row" style="display:none;">
                <span class="text-amber-400"><?= __('due_to_staff') ?></span>
                <span class="font-bold text-amber-400" id="bal-due">৳0</span>
            </div>
            <div class="flex justify-between text-sm" id="bal-cf-row" style="display:none;">
                <span class="text-indigo-400"><?= __('carry_forward') ?></span>
                <span class="font-bold text-indigo-400" id="bal-cf">৳0</span>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <button type="button" id="btn-save-bazaar"
            class="w-full bg-emerald-600 text-white font-bold py-3.5 rounded-xl
                   hover:bg-emerald-500 transition-all active:scale-[0.97] text-sm">
        <i class="fas fa-check-circle mr-2"></i> <?= __('save') ?> <?= __('bazaar') ?>
    </button>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const itemsList = document.getElementById('bazaar-items-list');

    // ── Add Item Row ──────────────────────────────────────────
    document.getElementById('btn-add-bazaar-item').addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = 'bazaar-item bg-surface border border-border rounded-lg p-3';
        div.innerHTML = `
            <div class="grid grid-cols-3 gap-2 mb-2">
                <input type="text" placeholder="<?= __('item') ?>" class="bi-name col-span-2 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary">
                <input type="text" placeholder="<?= __('unit') ?>" value="kg" class="bi-unit bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center">
            </div>
            <div class="grid grid-cols-3 gap-2">
                <input type="number" placeholder="<?= __('qty') ?>" class="bi-qty bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="0.5">
                <input type="number" placeholder="৳/<?= __('unit') ?>" class="bi-up bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="1">
                <div class="flex items-center gap-1">
                    <span class="bi-total text-sm font-bold text-accent flex-1 text-center">৳0</span>
                    <button type="button" onclick="this.closest('.bazaar-item').remove(); recalcBazaar();" class="text-red-400 text-xs p-1"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
        itemsList.appendChild(div);
        bindBazaarInputs(div);
    });

    // ── Live Calculation ──────────────────────────────────────
    function bindBazaarInputs(row) {
        const qty = row.querySelector('.bi-qty');
        const up  = row.querySelector('.bi-up');
        const tot = row.querySelector('.bi-total');

        [qty, up].forEach(el => el.addEventListener('input', () => {
            const total = (parseFloat(qty.value) || 0) * (parseFloat(up.value) || 0);
            tot.textContent = '৳' + total.toLocaleString('en-IN');
            recalcBazaar();
        }));
    }

    // Bind all existing rows
    document.querySelectorAll('.bazaar-item').forEach(bindBazaarInputs);

    window.recalcBazaar = function() {
        const advance = parseFloat(document.getElementById('bazaar-advance').value) || 0;
        const returned = parseFloat(document.getElementById('bazaar-returned').value) || 0;

        let totalSpent = 0;
        document.querySelectorAll('.bazaar-item').forEach(row => {
            const q = parseFloat(row.querySelector('.bi-qty').value) || 0;
            const p = parseFloat(row.querySelector('.bi-up').value) || 0;
            totalSpent += q * p;
        });

        const balance = advance - totalSpent;

        document.getElementById('bal-advance').textContent = '৳' + advance.toLocaleString('en-IN');
        document.getElementById('bal-spent').textContent = '৳' + totalSpent.toLocaleString('en-IN');

        const balEl = document.getElementById('bal-balance');
        balEl.textContent = '৳' + Math.abs(balance).toLocaleString('en-IN');
        balEl.className = 'font-bold text-lg ' + (balance >= 0 ? 'text-emerald-400' : 'text-red-400');

        const dueRow = document.getElementById('bal-due-row');
        const cfRow  = document.getElementById('bal-cf-row');

        if (balance < 0) {
            dueRow.style.display = 'flex';
            cfRow.style.display = 'none';
            document.getElementById('bal-due').textContent = '৳' + Math.abs(balance).toLocaleString('en-IN');
        } else {
            dueRow.style.display = 'none';
            const cf = Math.max(0, balance - returned);
            cfRow.style.display = cf > 0 ? 'flex' : 'none';
            document.getElementById('bal-cf').textContent = '৳' + cf.toLocaleString('en-IN');
        }
    };

    document.getElementById('bazaar-advance').addEventListener('input', recalcBazaar);
    document.getElementById('bazaar-returned').addEventListener('input', recalcBazaar);
    recalcBazaar();

    // ── Save ──────────────────────────────────────────────────
    document.getElementById('btn-save-bazaar').addEventListener('click', async () => {
        const btn = document.getElementById('btn-save-bazaar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

        const items = [];
        document.querySelectorAll('.bazaar-item').forEach(row => {
            const name = row.querySelector('.bi-name').value;
            if (!name) return;
            items.push({
                item_name: name,
                bought_qty: parseFloat(row.querySelector('.bi-qty').value) || 0,
                unit: row.querySelector('.bi-unit').value || 'kg',
                unit_price: parseFloat(row.querySelector('.bi-up').value) || 0,
                total_price: (parseFloat(row.querySelector('.bi-qty').value) || 0) * (parseFloat(row.querySelector('.bi-up').value) || 0),
            });
        });

        const res = await apiPost('?url=bazaar/save', {
            log_date: document.getElementById('bazaar-log-date').value,
            advance_cash: parseFloat(document.getElementById('bazaar-advance').value) || 0,
            returned_cash: parseFloat(document.getElementById('bazaar-returned').value) || 0,
            items: items,
        });

        if (res.success) {
            showToast('<?= __('success') ?> <?= __('bazaar') ?> saved!', 'success');
        } else {
            showToast(res.error || '<?= __('error') ?>', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> <?= __('save') ?> <?= __('bazaar') ?>';
    });
});
</script>
