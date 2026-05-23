<?php
/**
 * Pricing Calculator View
 * Variables: $rawItems
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
                        <option value="<?= (float) $item['avg_unit_price'] ?>"
                            data-unit="<?= htmlspecialchars($item['unit']) ?>">
                            <?= htmlspecialchars($item['item_name']) ?>
                            (<?= number_format((float) $item['avg_unit_price'], 2) ?> ৳/<?= htmlspecialchars($item['unit']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <i
                class="fas fa-chevron-down absolute right-1 top-1/2 -translate-y-1/2 text-xs text-text-muted pointer-events-none"></i>
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
            <i
                class="fas fa-chevron-down absolute right-1 top-1/2 -translate-y-1/2 text-xs text-text-muted pointer-events-none"></i>
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
        <span
            class="text-[0.6rem] font-bold px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20"
            id="markupBadge">× 2.89</span>
    </div>

    <!-- Final Price — Hero -->
    <div
        class="bg-gradient-to-br from-rose-500/10 to-rose-600/5 border border-rose-500/20 rounded-xl px-5 py-4 text-center">
        <p class="text-[0.6rem] font-bold text-rose-400/70 uppercase tracking-widest mb-1">
            <?= __('suggested_selling_price') ?></p>
        <div id="finalPriceDisplay"
            class="text-4xl font-black text-rose-400 drop-shadow-[0_0_16px_rgba(251,113,133,0.4)]">
            ৳0.00
        </div>
        <p class="text-[0.6rem] text-text-muted mt-1" id="formulaDisplay">rawPrice × 2.89</p>
    </div>
</div>

<!-- Markup Reference Card -->
<div class="bg-card border border-border/50 rounded-2xl p-4 animate-slideUp stagger" style="animation-delay:0.1s">
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const itemSelect = document.getElementById('rawItemSelect');
        const catSelect = document.getElementById('categorySelect');
        const rawDisplay = document.getElementById('rawPriceDisplay');
        const finalDisplay = document.getElementById('finalPriceDisplay');
        const markupLabel = document.getElementById('markupLabel');
        const markupBadge = document.getElementById('markupBadge');
        const formulaDisp = document.getElementById('formulaDisplay');

        function calculateMarkup() {
            const rawPrice = parseFloat(itemSelect.value) || 0;
            const category = catSelect.value;
            let finalPrice = 0;
            let formula = '';

            rawDisplay.textContent = '৳' + rawPrice.toFixed(2);

            if (category === 'fish') {
                // Fish / White Meat: Raw × 289%
                finalPrice = rawPrice * 2.89;
                formula = `৳${rawPrice.toFixed(2)} × 2.89`;
                markupLabel.textContent = '<?= __('cat_fish') ?> × 289%';
                markupBadge.textContent = '× 2.89';
                markupBadge.className = 'text-[0.6rem] font-bold px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20';
            } else if (category === 'red_meat') {
                // Red Meat: Raw + (Raw × 289%)
                finalPrice = rawPrice + (rawPrice * 2.89);
                formula = `৳${rawPrice.toFixed(2)} + (৳${rawPrice.toFixed(2)} × 2.89)`;
                markupLabel.textContent = '<?= __('cat_red_meat') ?> Raw + 289%';
                markupBadge.textContent = '+ 289%';
                markupBadge.className = 'text-[0.6rem] font-bold px-2 py-0.5 rounded-full bg-red-500/10 text-red-400 border border-red-500/20';
            }

            finalDisplay.textContent = '৳' + finalPrice.toFixed(2);
            formulaDisp.textContent = formula;
        }

        itemSelect.addEventListener('change', calculateMarkup);
        catSelect.addEventListener('change', calculateMarkup);

        // Initialize on load
        calculateMarkup();
    });
</script>