<?php
/**
 * Morning Prep View
 * Variables: $prepData, $logDate
 * Formula: Opening Qty = (Carry Forward - Wastage) + Fresh Processed
 */
?>

<div class="mb-4 animate-slideUp">
    <div class="flex items-center justify-between mb-1">
        <h2 class="text-lg font-black">
            <i class="fas fa-sun text-amber-400 mr-1"></i> <?= __('morning_prep') ?>
        </h2>
        <span class="text-xs font-semibold text-text-muted bg-card border border-border px-2.5 py-1 rounded-full">
            <?= date('D, d M', strtotime($logDate)) ?>
        </span>
    </div>
    <p class="text-text-muted text-xs"><?= __('opening_stock') ?> = (<?= __('carry_forward') ?> - <?= __('wastage') ?>) + <?= __('fresh_processed') ?></p>
</div>

<form id="prep-form" class="space-y-3 stagger">
    <input type="hidden" id="prep-log-date" value="<?= $logDate ?>">

    <?php foreach ($prepData as $item): ?>
    <div class="bg-card border border-border rounded-xl p-4 item-row"
         data-item-id="<?= $item['item_id'] ?>">

        <!-- Item Header -->
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-sm">
                <?= htmlspecialchars(currentLang() === 'bn' ? $item['item_name_bn'] : $item['item_name']) ?>
            </h3>
            <?php if ($item['is_saved']): ?>
            <span class="text-[0.6rem] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full">
                <i class="fas fa-check mr-0.5"></i> Saved
            </span>
            <?php endif; ?>
        </div>

        <!-- Input Grid -->
        <div class="grid grid-cols-3 gap-2.5">
            <!-- Carry Forward (read-only) -->
            <div>
                <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-wider mb-1"><?= __('carry_forward') ?></label>
                <input type="number" step="0.5" min="0"
                       class="w-full bg-surface/50 border border-border rounded-lg px-3 py-2.5 text-sm font-semibold text-text-primary text-center prep-cf"
                       value="<?= $item['carry_forward'] ?>" readonly>
            </div>

            <!-- Wastage -->
            <div>
                <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-wider mb-1"><?= __('wastage') ?></label>
                <input type="number" step="0.5" min="0"
                       class="w-full bg-surface border border-border rounded-lg px-3 py-2.5 text-sm font-semibold text-text-primary text-center focus:border-accent prep-wastage"
                       value="<?= $item['wastage'] ?>" placeholder="0">
            </div>

            <!-- Fresh Processed -->
            <div>
                <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-wider mb-1"><?= __('fresh_processed') ?></label>
                <input type="number" step="0.5" min="0"
                       class="w-full bg-surface border border-border rounded-lg px-3 py-2.5 text-sm font-semibold text-text-primary text-center focus:border-accent prep-fresh"
                       value="<?= $item['fresh_processed'] ?>" placeholder="0">
            </div>
        </div>

        <!-- Calculated Opening -->
        <div class="mt-3 flex items-center justify-between bg-accent/5 border border-accent/20 rounded-lg px-3 py-2">
            <span class="text-xs font-bold text-text-muted uppercase"><?= __('opening_stock') ?></span>
            <span class="text-lg font-black text-accent prep-opening"><?= $item['opening_qty'] ?></span>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Submit Button -->
    <button type="button" id="btn-save-prep"
            class="w-full bg-accent text-white font-bold py-3.5 rounded-xl
                   hover:bg-accent-light transition-all active:scale-[0.97] text-sm mt-4">
        <i class="fas fa-check-circle mr-2"></i> <?= __('save_prep') ?>
    </button>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('.item-row');

    // Live calculation: Opening = (CF - Wastage) + Fresh
    rows.forEach(row => {
        const cfInput = row.querySelector('.prep-cf');
        const wastageInput = row.querySelector('.prep-wastage');
        const freshInput = row.querySelector('.prep-fresh');
        const openingDisplay = row.querySelector('.prep-opening');

        function recalc() {
            const cf = parseFloat(cfInput.value) || 0;
            const w  = parseFloat(wastageInput.value) || 0;
            const f  = parseFloat(freshInput.value) || 0;
            const opening = Math.max(0, (cf - w) + f);
            openingDisplay.textContent = opening % 1 === 0 ? opening : opening.toFixed(1);
        }

        [wastageInput, freshInput].forEach(el => el.addEventListener('input', recalc));
    });

    // Save
    document.getElementById('btn-save-prep').addEventListener('click', async () => {
        const btn = document.getElementById('btn-save-prep');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

        const items = [];
        rows.forEach(row => {
            items.push({
                item_id: row.dataset.itemId,
                carry_forward: parseFloat(row.querySelector('.prep-cf').value) || 0,
                wastage: parseFloat(row.querySelector('.prep-wastage').value) || 0,
                fresh_processed: parseFloat(row.querySelector('.prep-fresh').value) || 0,
            });
        });

        const res = await apiPost('?url=inventory/saveDailyPrep', {
            log_date: document.getElementById('prep-log-date').value,
            items: items,
        });

        if (res.success) {
            showToast('<?= __('success') ?> <?= __('morning_prep') ?>', 'success');
            btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> <?= __('save_prep') ?>';
        } else {
            showToast(res.error || '<?= __('error') ?>', 'error');
            btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> <?= __('save_prep') ?>';
        }
        btn.disabled = false;
    });
});
</script>
