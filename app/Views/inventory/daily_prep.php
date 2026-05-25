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
        <div class="grid grid-cols-3 gap-x-2 gap-y-4 pt-2 mb-3">
            <!-- Carry Forward -->
            <div class="relative">
                <input type="number" step="0.5" min="0"
                       class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-center text-sm font-semibold transition-colors focus:outline-none placeholder-transparent prep-cf"
                       value="<?= $item['carry_forward'] !== '' ? (float)$item['carry_forward'] : '' ?>" placeholder="<?= __('carry_forward') ?>">
                <label class="absolute left-0 -top-3.5 text-[0.6rem] sm:text-xs text-text-muted transition-all peer-placeholder-shown:text-[0.7rem] peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-[0.6rem] sm:peer-focus:text-[0.65rem] peer-focus:text-accent uppercase font-bold text-center w-full truncate pointer-events-none"><?= __('carry_forward') ?></label>
            </div>

            <!-- Wastage -->
            <div class="relative">
                <input type="number" step="0.5" min="0"
                       class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-center text-sm font-semibold transition-colors focus:outline-none placeholder-transparent prep-wastage"
                       value="<?= $item['wastage'] !== '' ? (float)$item['wastage'] : '' ?>" placeholder="<?= __('wastage') ?>">
                <label class="absolute left-0 -top-3.5 text-[0.6rem] sm:text-xs text-text-muted transition-all peer-placeholder-shown:text-[0.7rem] peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-[0.6rem] sm:peer-focus:text-[0.65rem] peer-focus:text-accent uppercase font-bold text-center w-full truncate pointer-events-none"><?= __('wastage') ?></label>
            </div>

            <!-- Fresh Processed -->
            <div class="relative">
                <input type="number" step="0.5" min="0"
                       class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-center text-sm font-semibold transition-colors focus:outline-none placeholder-transparent prep-fresh"
                       value="<?= $item['fresh_processed'] !== '' ? (float)$item['fresh_processed'] : '' ?>" placeholder="<?= __('fresh_processed') ?>">
                <label class="absolute left-0 -top-3.5 text-[0.6rem] sm:text-xs text-text-muted transition-all peer-placeholder-shown:text-[0.7rem] peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-[0.6rem] sm:peer-focus:text-[0.65rem] peer-focus:text-accent uppercase font-bold text-center w-full truncate pointer-events-none"><?= __('fresh_processed') ?></label>
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

        [cfInput, wastageInput, freshInput].forEach(el => el.addEventListener('input', recalc));
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
