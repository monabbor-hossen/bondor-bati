<?php
/**
 * Pricing Calculator View
 * Variables: $rawItems, $logs
 * Rules: Fish/White Meat → rawPrice * 2.89 | Red Meat → rawPrice + (rawPrice * 2.89)
 */
?>

<!-- Page Header -->
<div class="mb-6 animate-slideUp">
    <div class="flex items-center gap-3 mb-1">
        <a href="?url=settings" class="text-text-muted hover:text-text-primary transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-xl font-black tracking-tight flex items-center gap-2">
            <i class="fas fa-calculator text-rose-400 drop-shadow-[0_0_12px_rgba(251,113,133,0.6)]"></i>
            <?= __('price_calculator') ?>
        </h2>
    </div>
    <p class="text-xs text-text-muted ml-7"><?= __('suggested_selling_price') ?></p>
</div>

<!-- Calculator Card -->
<div class="bg-card border border-border/50 rounded-2xl p-5 mb-5 animate-slideUp stagger">

    <!-- Raw Material Select -->
    <div class="mb-6">
        <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-widest mb-1">
            <?= __('select_raw_item') ?>
        </label>
        <div class="relative">
            <select id="rawItemSelect"
                class="w-full bg-transparent border-b border-border focus:border-rose-400 py-2.5 px-1 text-sm text-text-primary transition-colors focus:outline-none appearance-none cursor-pointer">
                <?php if (empty($rawItems)): ?>
                    <option value="0">(No raw items found)</option>
                <?php else: ?>
                    <?php foreach ($rawItems as $item): ?>
                        <option value="<?= (int)$item['id'] ?>"
                            data-price="<?= number_format((float)$item['avg_unit_price'], 2, '.', '') ?>"
                            data-name="<?= htmlspecialchars($item['item_name']) ?>"
                            data-unit="<?= htmlspecialchars($item['unit']) ?>">
                            <?= htmlspecialchars($item['item_name']) ?>
                            (<?= number_format((float)$item['avg_unit_price'], 2) ?> ৳/<?= htmlspecialchars($item['unit']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <i class="fas fa-chevron-down absolute right-1 top-1/2 -translate-y-1/2 text-xs text-text-muted pointer-events-none"></i>
        </div>
    </div>

    <!-- Category Select -->
    <div class="mb-6">
        <label class="block text-[0.6rem] font-bold text-text-muted uppercase tracking-widest mb-1">
            <?= __('select_category') ?>
        </label>
        <div class="relative">
            <select id="categorySelect"
                class="w-full bg-transparent border-b border-border focus:border-rose-400 py-2.5 px-1 text-sm text-text-primary transition-colors focus:outline-none appearance-none cursor-pointer">
                <option value="fish"><?= __('cat_fish') ?></option>
                <option value="red_meat"><?= __('cat_red_meat') ?></option>
            </select>
            <i class="fas fa-chevron-down absolute right-1 top-1/2 -translate-y-1/2 text-xs text-text-muted pointer-events-none"></i>
        </div>
    </div>

    <!-- Divider -->
    <div class="border-t border-border/40 my-4"></div>

    <!-- Raw Cost Display -->
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-text-muted uppercase tracking-widest"><?= __('current_raw_cost') ?></span>
        <span id="rawPriceDisplay" class="text-sm font-black text-text-primary">৳0.00</span>
    </div>

    <!-- Markup Rule Display -->
    <div class="flex items-center justify-between mb-4">
        <span class="text-xs text-text-muted" id="markupLabel">Fish × 289%</span>
        <span class="text-[0.6rem] font-bold px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20"
            id="markupBadge">× 2.89</span>
    </div>

    <!-- Final Price — Hero -->
    <div class="bg-gradient-to-br from-rose-500/10 to-rose-600/5 border border-rose-500/20 rounded-xl px-5 py-4 text-center">
        <p class="text-[0.6rem] font-bold text-rose-400/70 uppercase tracking-widest mb-1">
            <?= __('suggested_selling_price') ?></p>
        <div id="finalPriceDisplay"
            class="text-4xl font-black text-rose-400 drop-shadow-[0_0_16px_rgba(251,113,133,0.4)]">
            ৳0.00
        </div>
        <p class="text-[0.6rem] text-text-muted mt-1" id="formulaDisplay">rawPrice × 2.89</p>
    </div>

    <!-- Save Button -->
    <button id="savePriceBtn"
        class="w-full mt-5 bg-accent hover:bg-accent-light text-white font-bold py-3 rounded-xl transition-colors shadow-[0_0_15px_rgba(244,63,94,0.3)] flex items-center justify-center gap-2">
        <i class="fas fa-save"></i>
        <?= __('save_todays_price') ?>
    </button>
</div>

<!-- Markup Reference Card -->
<div class="bg-card border border-border/50 rounded-2xl p-4 mb-5 animate-slideUp stagger" style="animation-delay:0.1s">
    <h3 class="text-[0.6rem] font-bold text-text-muted uppercase tracking-widest mb-3">Markup Rules</h3>
    <div class="space-y-3">
        <div class="flex justify-between items-center py-2 border-b border-border/30">
            <div class="flex items-center gap-2">
                <i class="fas fa-fish text-blue-400 text-xs w-4"></i>
                <span class="text-sm font-semibold"><?= __('cat_fish') ?></span>
            </div>
            <span class="text-sm font-black text-blue-400">Raw × 289%</span>
        </div>
        <div class="flex justify-between items-center py-2">
            <div class="flex items-center gap-2">
                <i class="fas fa-drumstick-bite text-red-400 text-xs w-4"></i>
                <span class="text-sm font-semibold"><?= __('cat_red_meat') ?></span>
            </div>
            <span class="text-sm font-black text-red-400">Raw + 289%</span>
        </div>
    </div>
</div>

<!-- ── Recent Price Logs ───────────────────────────────────────── -->
<div class="animate-slideUp stagger" style="animation-delay:0.2s">
    <h3 class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3 px-1">
        <i class="fas fa-history mr-1"></i> <?= __('recent_price_logs') ?>
    </h3>

    <?php if (empty($logs)): ?>
        <div class="bg-card border border-border/30 rounded-xl p-6 text-center">
            <i class="fas fa-clipboard-list text-2xl text-text-muted/30 mb-2"></i>
            <p class="text-sm text-text-muted"><?= __('no_data') ?></p>
        </div>
    <?php else: ?>
        <div class="bg-card border border-border/50 rounded-2xl divide-y divide-border/40 overflow-hidden">
            <?php foreach ($logs as $log): ?>
            <div class="flex items-center justify-between px-4 py-3 hover:bg-surface/50 transition-colors">
                <div class="flex-1 min-w-0 mr-3">
                    <p class="text-xs font-black text-text-primary truncate">
                        <?= htmlspecialchars($log['item_name']) ?>
                    </p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[0.6rem] text-text-muted">
                            <?= date('d M', strtotime($log['log_date'])) ?>
                        </span>
                        <span class="text-[0.6rem] font-bold px-1.5 py-px rounded <?= $log['category'] === 'fish' ? 'bg-blue-500/10 text-blue-400' : 'bg-red-500/10 text-red-400' ?>">
                            <?= $log['category'] === 'fish' ? __('cat_fish') : __('cat_red_meat') ?>
                        </span>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xs font-black text-success">৳<?= number_format((float)$log['final_price'], 2) ?></p>
                    <p class="text-[0.6rem] text-info">৳<?= number_format((float)$log['raw_price'], 2) ?> <?= __('raw_cost') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var itemSelect  = document.getElementById('rawItemSelect');
    var catSelect   = document.getElementById('categorySelect');
    var rawDisplay  = document.getElementById('rawPriceDisplay');
    var finalDisplay= document.getElementById('finalPriceDisplay');
    var markupLabel = document.getElementById('markupLabel');
    var markupBadge = document.getElementById('markupBadge');
    var formulaDisp = document.getElementById('formulaDisplay');
    var saveBtn     = document.getElementById('savePriceBtn');

    var currentRaw   = 0;
    var currentFinal = 0;

    function calculateMarkup() {
        var selectedOpt = itemSelect.options[itemSelect.selectedIndex];
        currentRaw = selectedOpt ? (parseFloat(selectedOpt.getAttribute('data-price')) || 0) : 0;
        var category = catSelect.value;
        var formula  = '';

        rawDisplay.textContent = '৳' + currentRaw.toFixed(2);

        if (category === 'fish') {
            currentFinal = currentRaw * 2.89;
            formula = '৳' + currentRaw.toFixed(2) + ' × 2.89';
            markupLabel.textContent = <?= json_encode(__('cat_fish')) ?> + ' × 289%';
            markupBadge.textContent = '× 2.89';
            markupBadge.className   = 'text-[0.6rem] font-bold px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20';
        } else if (category === 'red_meat') {
            currentFinal = currentRaw + (currentRaw * 2.89);
            formula = '৳' + currentRaw.toFixed(2) + ' + (৳' + currentRaw.toFixed(2) + ' × 2.89)';
            markupLabel.textContent = <?= json_encode(__('cat_red_meat')) ?> + ' Raw + 289%';
            markupBadge.textContent = '+ 289%';
            markupBadge.className   = 'text-[0.6rem] font-bold px-2 py-0.5 rounded-full bg-red-500/10 text-red-400 border border-red-500/20';
        }

        finalDisplay.textContent = '৳' + currentFinal.toFixed(2);
        formulaDisp.textContent  = formula;
    }

    itemSelect.addEventListener('change', calculateMarkup);
    catSelect.addEventListener('change', calculateMarkup);
    calculateMarkup(); // runs immediately — all elements are above this script tag

    if (saveBtn) {
        saveBtn.addEventListener('click', async function () {
            var selectedOpt = itemSelect.options[itemSelect.selectedIndex];
            var itemName    = selectedOpt ? (selectedOpt.getAttribute('data-name') || selectedOpt.text.split('(')[0].trim()) : '';

            if (!itemName || currentFinal <= 0) {
                showToast(<?= json_encode(__('select_raw_item')) ?>, 'error');
                return;
            }

            var originalHtml = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>' + <?= json_encode(__('save_todays_price')) ?>;

            var res = await apiPost('?url=settings/savePriceLog', {
                item_name:   itemName,
                category:    catSelect.value,
                raw_price:   currentRaw,
                final_price: currentFinal
            });

            if (res.success) {
                showToast(<?= json_encode(__('save_todays_price')) ?> + ' ✓', 'success');
                setTimeout(function () { window.location.reload(); }, 600);
            } else {
                showToast(res.error || <?= json_encode(__('error')) ?>, 'error');
                saveBtn.innerHTML = originalHtml;
                saveBtn.disabled  = false;
            }
        });
    }
})();
</script>