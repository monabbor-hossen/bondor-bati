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

<!-- Inventory Auto-Suggest Datalist -->
<datalist id="inventory-list">
    <?php foreach ($inventoryNames as $invName): ?>
    <option value="<?= htmlspecialchars($invName) ?>">
    <?php endforeach; ?>
</datalist>

<!-- Multi-Ledger Tabs -->
<div class="flex items-center gap-2 mb-4 overflow-x-auto no-scrollbar animate-slideUp">
    <?php $listCount = 1; foreach ($ledgers as $l): ?>
        <a href="?url=bazaar&ledger_id=<?= $l['id'] ?>" class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all border <?= $l['id'] == $activeLedgerId ? 'bg-accent/10 border-accent/50 text-accent' : 'bg-surface border-border text-text-muted hover:text-text-primary' ?>">
            <?= __('bazaar_list') ?> #<?= $listCount++ ?>
        </a>
    <?php endforeach; ?>
    <button type="button" id="btn-new-ledger" class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all border border-emerald-500/30 text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/20 whitespace-nowrap">
        <i class="fas fa-plus mr-1"></i> <?= __('new_list') ?>
    </button>
</div>

<form id="bazaar-form" class="space-y-4 stagger">
    <input type="hidden" id="bazaar-log-date" value="<?= $logDate ?>">
    <input type="hidden" id="bazaar-ledger-id" value="<?= $activeLedgerId ?>">
    
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <div class="bg-card border border-border rounded-xl p-4 flex items-center justify-between">
            <label class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-user-tie text-accent"></i> <?= __('assigned_staff') ?>
            </label>
            <div class="flex items-center gap-3">
                <select id="bazaar-assigned-staff" class="bg-surface border border-border rounded-lg px-3 py-1.5 text-sm font-semibold focus:outline-none focus:border-accent appearance-none cursor-pointer">
                    <option value="0">-- Select --</option>
                    <?php foreach ($staffList as $staff): ?>
                        <option value="<?= $staff['id'] ?>" <?= $staff['id'] == $assignedStaffId ? 'selected' : '' ?>><?= htmlspecialchars($staff['name']) ?> (<?= ucfirst($staff['role']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="btn-set-default" class="text-xs text-accent hover:underline font-bold bg-transparent border-none cursor-pointer">
                    <?= __('set_default') ?>
                </button>
            </div>
        </div>
    <?php else: ?>
        <input type="hidden" id="bazaar-assigned-staff" value="<?= $assignedStaffId ?>">
    <?php endif; ?>

    <!-- Extra Cash & Budget Summary -->
    <div class="bg-card border border-border rounded-xl p-4">
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-border/50">
                <span class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest"><?= __('floating_cash') ?>:</span>
                <div>
                    <span id="floatingCashAmt" class="text-indigo-400 font-bold"><?= (float)$pastCarryForward ?></span> <span class="text-xs text-indigo-400">Tk</span>
                </div>
            </div>
        <?php endif; ?>
        
        <input type="hidden" id="bazaar-past-cf" value="<?= (float)$pastCarryForward ?>">

        <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-2">
            <i class="fas fa-money-bill-wave text-emerald-400 mr-1"></i> <?= __('new_extra_cash') ?> (<?= __('tk') ?>)
        </label>
        <input type="number" id="bazaar-advance" step="1" min="0"
               class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-xl font-black text-center text-emerald-400 focus:border-emerald-400 <?= ($_SESSION['role'] ?? '') === 'staff' ? 'opacity-50 pointer-events-none' : '' ?>"
               value="<?= (empty($advanceCash) || $advanceCash == 0) ? '' : (float)$advanceCash ?>" placeholder="0" <?= ($_SESSION['role'] ?? '') === 'staff' ? 'readonly' : '' ?>>
               
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <div class="text-center mt-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg py-2">
                <span class="text-[0.65rem] font-bold text-emerald-500 uppercase tracking-widest"><?= __('total_budget') ?>:</span>
                <span id="totalBudgetAmt" class="text-emerald-400 font-black tracking-wider text-lg ml-1">0</span> <span class="text-xs font-bold text-emerald-400">Tk</span>
            </div>
        <?php endif; ?>
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
                        <input type="text" placeholder="<?= __('item') ?>" value="<?= htmlspecialchars($bi['item_name']) ?>" list="inventory-list"
                               class="bi-name col-span-2 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary <?= ($_SESSION['role'] ?? '') === 'staff' ? 'opacity-50 pointer-events-none' : '' ?>"
                               <?= ($_SESSION['role'] ?? '') === 'staff' ? 'readonly' : '' ?>>
                        <select class="bi-unit bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center appearance-none cursor-pointer focus:border-accent focus:outline-none">
                            <option value="kg" <?= $bi['unit'] === 'kg' ? 'selected' : '' ?>><?= __('unit_kg') ?? 'kg' ?></option>
                            <option value="L" <?= $bi['unit'] === 'L' ? 'selected' : '' ?>><?= __('unit_l') ?? 'L' ?></option>
                            <option value="pcs" <?= $bi['unit'] === 'pcs' ? 'selected' : '' ?>><?= __('unit_pcs') ?? 'pcs' ?></option>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" placeholder="<?= __('qty') ?>" value="<?= (empty($bi['bought_qty']) || $bi['bought_qty'] == 0) ? '' : (float)$bi['bought_qty'] ?>"
                               class="bi-qty bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="0.5">
                        <input type="number" placeholder="৳/<?= __('unit') ?>" value="<?= (empty($bi['unit_price']) || $bi['unit_price'] == 0) ? '' : (float)$bi['unit_price'] ?>"
                               class="bi-up bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="1">
                        <div class="flex items-center gap-1">
                            <input type="number" placeholder="Total" value="<?= (empty($bi['total_price']) || $bi['total_price'] == 0) ? '' : (float)$bi['total_price'] ?>" class="bi-total w-full bg-card border border-border rounded-lg px-2 py-2 text-sm font-bold text-accent text-center focus:border-accent focus:outline-none" step="1">
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
                        <input type="text" placeholder="<?= __('item') ?>" list="inventory-list"
                               class="bi-name col-span-2 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary">
                        <select class="bi-unit bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center appearance-none cursor-pointer focus:border-accent focus:outline-none">
                            <option value="kg"><?= __('unit_kg') ?? 'kg' ?></option>
                            <option value="L"><?= __('unit_l') ?? 'L' ?></option>
                            <option value="pcs"><?= __('unit_pcs') ?? 'pcs' ?></option>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" placeholder="<?= __('qty') ?>" value=""
                               class="bi-qty bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="0.5">
                        <input type="number" placeholder="৳/<?= __('unit') ?>" value=""
                               class="bi-up bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="1">
                        <div class="flex items-center gap-1">
                            <input type="number" placeholder="Total" value="" class="bi-total w-full bg-card border border-border rounded-lg px-2 py-2 text-sm font-bold text-accent text-center focus:border-accent focus:outline-none" step="1">
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
               value="<?= (!empty($ledger['returned_cash']) && $ledger['returned_cash'] > 0) ? (float)$ledger['returned_cash'] : '' ?>" placeholder="0">
    </div>

    <!-- Balance Summary -->
    <div class="bg-card border border-border rounded-xl p-4">
        <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3"><?= __('balance') ?></p>
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-text-muted"><?= __('total_budget') ?></span>
                <span><span id="bottomTotalBudget" class="text-indigo-400 font-bold">0</span> Tk</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-text-muted"><?= __('total_spent') ?></span>
                <span><span id="bottomTotalSpent" class="text-accent font-bold">0</span> Tk</span>
            </div>
            <div class="flex justify-between text-sm border-t border-border pt-2">
                <span class="text-text-muted"><?= __('final_carry_forward') ?></span>
                <span><span id="bottomCarryForward" class="text-emerald-400 font-black text-lg tracking-wider">0</span> Tk</span>
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

    // ── Create New Ledger ─────────────────────────────────────
    const btnNewLedger = document.getElementById('btn-new-ledger');
    if (btnNewLedger) {
        btnNewLedger.addEventListener('click', async () => {
            const originalHtml = btnNewLedger.innerHTML;
            btnNewLedger.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btnNewLedger.disabled = true;

            const res = await apiPost('?url=bazaar/createNewLedger', {});
            if (res.success) {
                window.location.href = '?url=bazaar&ledger_id=' + res.ledger_id;
            } else {
                showToast(res.error || 'Error creating list', 'error');
                btnNewLedger.innerHTML = originalHtml;
                btnNewLedger.disabled = false;
            }
        });
    }

    // ── Add Item Row ──────────────────────────────────────────
    document.getElementById('btn-add-bazaar-item').addEventListener('click', () => {
        const div = document.createElement('div');
        div.className = 'bazaar-item bg-surface border border-border rounded-lg p-3';
        div.innerHTML = `
            <div class="grid grid-cols-3 gap-2 mb-2">
                <input type="text" placeholder="<?= __('item') ?>" list="inventory-list" class="bi-name col-span-2 bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:border-accent focus:outline-none">
                <select class="bi-unit bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center appearance-none cursor-pointer focus:border-accent focus:outline-none">
                    <option value="kg"><?= __('unit_kg') ?? 'kg' ?></option>
                    <option value="L"><?= __('unit_l') ?? 'L' ?></option>
                    <option value="pcs"><?= __('unit_pcs') ?? 'pcs' ?></option>
                </select>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <input type="number" placeholder="<?= __('qty') ?>" value="" class="bi-qty bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="0.5">
                <input type="number" placeholder="৳/<?= __('unit') ?>" value="" class="bi-up bg-card border border-border rounded-lg px-3 py-2 text-sm text-text-primary text-center" step="1">
                <div class="flex items-center gap-1">
                    <input type="number" placeholder="Total" value="" class="bi-total w-full bg-card border border-border rounded-lg px-2 py-2 text-sm font-bold text-accent text-center focus:border-accent focus:outline-none" step="1">
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
            const q = parseFloat(qty.value) || 0;
            const p = parseFloat(up.value) || 0;
            if (q > 0 && p > 0) {
                tot.value = Math.round(q * p);
            } else if (!p) {
                tot.value = '';
            }
            recalcBazaar();
        }));

        tot.addEventListener('input', () => {
            const q = parseFloat(qty.value) || 0;
            const t = parseFloat(tot.value) || 0;
            if (q > 0 && t > 0) {
                const newUp = (t / q).toFixed(2);
                up.value = newUp.replace(/\.00$/, ''); // removes .00 for clean display
            } else if (!t) {
                up.value = '';
            }
            recalcBazaar();
        });
    }

    // Bind all existing rows
    document.querySelectorAll('.bazaar-item').forEach(bindBazaarInputs);

    window.recalcBazaar = function() {
        const advance = parseFloat(document.getElementById('bazaar-advance').value) || 0;
        const pastCf  = parseFloat(document.getElementById('bazaar-past-cf').value) || 0;
        
        // 1. Calculate Budget
        const totalBudget = advance + pastCf;

        // 2. Calculate Total Spent
        let totalSpent = 0;
        document.querySelectorAll('.bi-total').forEach(input => {
            totalSpent += parseFloat(input.value) || 0;
        });

        // 3. Calculate Final Carry Forward
        const returned = parseFloat(document.getElementById('bazaar-returned').value) || 0;
        const finalCarryForward = totalBudget - totalSpent - returned;

        // 4. Update UI (Top and Bottom)
        const topBudgetEl = document.getElementById('totalBudgetAmt');
        if (topBudgetEl) topBudgetEl.innerText = totalBudget.toLocaleString('en-IN');

        const botTotalBudget = document.getElementById('bottomTotalBudget');
        if (botTotalBudget) botTotalBudget.innerText = totalBudget.toLocaleString('en-IN');
        
        const botTotalSpent = document.getElementById('bottomTotalSpent');
        if (botTotalSpent) botTotalSpent.innerText = totalSpent.toLocaleString('en-IN');
        
        const carryForwardEl = document.getElementById('bottomCarryForward');
        if (carryForwardEl) {
            carryForwardEl.innerText = finalCarryForward.toLocaleString('en-IN');
            
            // Visual warning if they overspent
            if (finalCarryForward < 0) {
                carryForwardEl.classList.remove('text-emerald-400');
                carryForwardEl.classList.add('text-red-400');
            } else {
                carryForwardEl.classList.remove('text-red-400');
                carryForwardEl.classList.add('text-emerald-400');
            }
        }
    };

    document.getElementById('bazaar-advance').addEventListener('input', recalcBazaar);
    document.getElementById('bazaar-returned').addEventListener('input', recalcBazaar);
    recalcBazaar();

    // Set Default Staff
    const btnSetDefault = document.getElementById('btn-set-default');
    if (btnSetDefault) {
        btnSetDefault.addEventListener('click', async () => {
            const staffId = document.getElementById('bazaar-assigned-staff').value;
            const res = await apiPost('?url=bazaar/setDefaultStaff', { staff_id: staffId });
            if (res.success) {
                showToast('<?= __("default_set_success") ?? "Default shopper updated." ?>', 'success');
            } else {
                showToast(res.error || 'Error', 'error');
            }
        });
    }

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
            ledger_id: parseInt(document.getElementById('bazaar-ledger-id').value) || 0,
            log_date: document.getElementById('bazaar-log-date').value,
            advance_cash: parseFloat(document.getElementById('bazaar-advance').value) || 0,
            assigned_staff_id: parseInt(document.getElementById('bazaar-assigned-staff').value) || 0,
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
