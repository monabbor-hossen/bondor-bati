<?php
/**
 * Day Ledger View — Unified Closing System
 * Variables: $menuItems, $todayItems, $todayDues, $businessDate, $currentShift
 */
?>

<div class="mb-4 animate-slideUp">
    <div class="flex items-center justify-between mb-1">
        <h2 class="text-lg font-black">
            <i class="fas fa-book text-indigo-400 mr-1"></i>
            <?= __('day_ledger') ?>
        </h2>
        <span class="text-xs font-semibold text-text-muted bg-card border border-border px-2.5 py-1 rounded-full">
            <?= date('d M, Y', strtotime($businessDate)) ?>
        </span>
    </div>
    <div class="flex items-center gap-2 mt-1">
        <span class="text-[0.65rem] font-bold text-accent uppercase tracking-widest">
            <i class="fas fa-clock mr-1"></i>
            <?= __($currentShift) ?>
            <?= __('shift') ?>
        </span>
    </div>
</div>

<button type="button" onclick="document.getElementById('consumablesModal').classList.remove('hidden')"
    class="w-full mb-6 bg-surface border border-info/50 text-info font-bold py-3 rounded-xl flex items-center justify-center gap-2 transition-all hover:bg-info/10">
    <i class="fas fa-fire-burner"></i>
    <?= __('log_consumables') ?>
</button>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  SECTION 1: Add Item to Today's Ledger                   -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="bg-card border border-border rounded-xl p-4 mb-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3">
        <i class="fas fa-plus-circle text-accent mr-1"></i>
        <?= __('add_to_day') ?>
    </p>
    <div class="flex gap-3 items-center pt-2">
        <div class="relative flex-1 min-w-0">
            <label
                class="text-[0.65rem] text-text-muted mb-1 block uppercase tracking-widest font-bold px-1 absolute -top-4 left-0">
                <?= __('select_menu_item') ?>
            </label>
            <select id="add-item-select"
                class="w-full bg-transparent border-b border-border py-2 px-1 text-[0.8rem] sm:text-sm text-text-primary focus:outline-none focus:border-accent appearance-none cursor-pointer truncate">
                <option value="" class="bg-card text-text-primary">...</option>
                <?php foreach ($menuItems as $mi): ?>
                    <option class="bg-card text-text-primary" value="<?= $mi['id'] ?>"
                        data-price="<?= $mi['selling_price'] ?>" data-raw-qty="<?= (float) $mi['raw_qty'] ?>"
                        data-unit="<?= htmlspecialchars($mi['unit'] ?? 'pcs') ?>">
                        <?= htmlspecialchars(currentLang() === 'bn' ? ($mi['item_name_bn'] ?? $mi['item_name']) : $mi['item_name']) ?>
                        — ৳
                        <?= $mi['selling_price'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <i class="fas fa-chevron-down absolute right-2 top-3 text-xs text-text-muted pointer-events-none"></i>
        </div>
        <div class="relative w-20 sm:w-24 shrink-0 mt-1">
            <input type="number" id="add-item-opening" step="0.5" min="0" placeholder="<?= __('opening_qty') ?>"
                class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-center text-sm transition-colors focus:outline-none placeholder-transparent">
            <label
                class="absolute left-1 -top-3.5 text-[0.6rem] text-text-muted transition-all peer-placeholder-shown:text-[0.7rem] peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-[0.6rem] peer-focus:text-accent uppercase font-bold text-center w-full truncate pointer-events-none">
                <?= __('opening_qty') ?>
            </label>
        </div>
        <button type="button" id="btn-add-day-item"
            class="shrink-0 text-xs font-bold text-accent bg-accent/10 border border-accent/30 px-3 py-2 rounded-lg hover:bg-accent/20 transition-all mt-1">
            <i class="fas fa-plus"></i>
        </button>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  SECTION 2: Today's Active Items                          -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="space-y-3 mb-4 stagger" id="day-items-list">
    <?php if (!empty($todayItems)): ?>
        <?php foreach ($todayItems as $item):
            // Determine display units dynamically
            $hasConversion = (!empty($item['raw_usage']) && (float)$item['raw_usage'] !== 1.000);
            // Closing is always weighed in the raw unit (e.g. kg) so staff weighs the pot
            $closingUnit = !empty($item['raw_unit']) ? $item['raw_unit'] : ($item['unit'] ?? 'pcs');
            // Complimentary: if conversion exists → use item's selling unit (e.g. plate)
            //                no conversion     → use raw_unit (e.g. pcs/kg), not the item unit
            $compUnit = $hasConversion
                ? ($item['unit'] ?? 'pcs')
                : ($item['raw_unit'] ?? $item['unit'] ?? 'pcs');
        ?>
            <div class="bg-card border border-border rounded-xl p-4 day-item" data-item-id="<?= $item['item_id'] ?>"
                data-selling-price="<?= $item['selling_price'] ?>" data-unit="<?= htmlspecialchars($item['unit'] ?? 'pcs') ?>"
                data-raw-usage="<?= (float) ($item['raw_usage'] ?? 1.0) ?>"
                data-raw-usage-unit="<?= htmlspecialchars($item['raw_usage_unit'] ?? 'kg') ?>"
                data-raw-unit="<?= htmlspecialchars($item['raw_unit'] ?? $item['unit'] ?? 'kg') ?>">

                <!-- Item Header -->
                <div class="flex items-start sm:items-center justify-between mb-3 gap-2">
                    <h3 class="font-bold text-sm leading-tight flex-1">
                        <?= htmlspecialchars(currentLang() === 'bn' ? $item['item_name_bn'] : $item['item_name']) ?>
                    </h3>
                    <div class="flex items-center gap-1 shrink-0 mt-0.5 sm:mt-0">
                        <span class="text-xs text-text-muted mr-1">৳
                            <?= number_format($item['selling_price']) ?>/
                            <?= __('unit') ?>
                        </span>
                        <?php if ((float) ($item['raw_usage'] ?? 1.0) != 1.0): ?>
                            <span
                                class="text-[0.6rem] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-1.5 py-0.5 rounded font-bold mr-1">
                                <?= (float) $item['raw_usage'] ?>
                                <?= htmlspecialchars($item['raw_usage_unit'] ?? $item['raw_unit'] ?? 'kg') ?>/u
                            </span>
                        <?php endif; ?>
                        <button type="button" class="btn-remove-item text-text-muted hover:text-red-400 transition-colors p-1"
                            title="<?= __('delete') ?>">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Input Grid -->
                <div class="grid grid-cols-3 gap-x-2 gap-y-4 pt-2 mb-3">
                    <!-- Opening -->
                    <div class="relative">
                        <input type="number" step="any" min="0"
                            class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 pl-1 pr-6 text-center text-sm font-semibold transition-colors focus:outline-none placeholder-transparent di-opening"
                            value="<?= (float) $item['opening_qty'] ?>" placeholder="<?= __('opening_qty') ?>">
                        <label
                            class="absolute left-0 -top-3.5 text-[0.6rem] sm:text-xs text-text-muted transition-all peer-placeholder-shown:text-[0.7rem] peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-[0.6rem] sm:peer-focus:text-[0.65rem] peer-focus:text-accent uppercase font-bold text-center w-full truncate pointer-events-none">
                            <?= __('opening_qty') ?>
                        </label>
                        <span
                            class="absolute right-0 bottom-2 text-[0.6rem] font-black text-text-muted/60 uppercase tracking-widest pointer-events-none">
                            <?= htmlspecialchars(__('unit_' . strtolower($item['raw_unit'] ?? $item['unit'] ?? 'pcs'))) ?>
                        </span>
                    </div>
                    <!-- Closing -->
                    <div class="relative">
                        <input type="number" step="any" min="0"
                            class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 pl-1 pr-6 text-center text-sm font-semibold transition-colors focus:outline-none placeholder-transparent di-closing"
                            value="<?= $item['closing_qty'] !== '' ? (float) $item['closing_qty'] : '' ?>"
                            placeholder="<?= __('closing_qty') ?>">
                        <label
                            class="absolute left-0 -top-3.5 text-[0.6rem] sm:text-xs text-text-muted transition-all peer-placeholder-shown:text-[0.7rem] peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-[0.6rem] sm:peer-focus:text-[0.65rem] peer-focus:text-accent uppercase font-bold text-center w-full truncate pointer-events-none">
                            <?= __('closing_qty') ?>
                        </label>
                        <span
                            class="absolute right-0 bottom-2 text-[0.6rem] font-black text-info/70 uppercase tracking-widest pointer-events-none">
                            <?= htmlspecialchars(strtolower($closingUnit)) ?>
                        </span>
                    </div>
                    <!-- Complimentary -->
                    <div class="relative">
                        <input type="number" step="any" min="0"
                            class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 pl-1 pr-6 text-center text-sm font-semibold transition-colors focus:outline-none placeholder-transparent di-comp"
                            value="<?= (float) $item['complimentary_qty'] ?: '' ?>" placeholder="<?= __('comp_qty') ?>">
                        <label
                            class="absolute left-0 -top-3.5 text-[0.6rem] sm:text-xs text-text-muted transition-all peer-placeholder-shown:text-[0.7rem] peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-[0.6rem] sm:peer-focus:text-[0.65rem] peer-focus:text-accent uppercase font-bold text-center w-full truncate pointer-events-none">
                            <?= __('comp_qty') ?>
                        </label>
                        <span
                            class="absolute right-0 bottom-2 text-[0.6rem] font-black text-amber-400/70 uppercase tracking-widest pointer-events-none">
                            <?= htmlspecialchars(strtolower($compUnit)) ?>
                        </span>
                    </div>
                </div>

                <!-- Calculated Sold -->
                <div class="flex items-center justify-between bg-accent/5 border border-accent/20 rounded-lg px-3 py-2">
                    <span class="text-xs font-bold text-text-muted uppercase">
                        <?= __('sold') ?>
                    </span>
                    <div class="text-right">
                        <span class="text-lg font-black text-accent di-sold">0</span>
                        <span class="text-xs text-text-muted ml-1">= ৳<span
                                class="di-revenue text-accent font-bold">0</span></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div id="empty-state" class="bg-card border border-border/30 rounded-xl p-8 text-center animate-slideUp">
            <i class="fas fa-clipboard-list text-3xl text-text-muted/30 mb-2"></i>
            <p class="text-sm text-text-muted">
                <?= __('no_data') ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Update Ledger Button -->
    <?php if (!empty($todayItems)): ?>
        <button type="button" id="btn-update-ledger" class="w-full mt-4 bg-indigo-600 text-white font-bold py-3.5 rounded-xl
                   hover:bg-indigo-500 transition-all active:scale-[0.97] text-sm">
            <i class="fas fa-check-circle mr-2"></i> Update Day Ledger
        </button>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  SECTION 3: Customer Dues (Baki) Modal Trigger            -->
<!-- ══════════════════════════════════════════════════════════ -->
<button type="button" onclick="document.getElementById('dues-modal').style.display='flex'" class="w-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-bold py-3.5 rounded-xl mb-4
               hover:bg-amber-500/20 transition-all active:scale-[0.97] text-sm animate-slideUp">
    <i class="fas fa-hand-holding-dollar mr-2"></i>
    <?= __('customer_dues_baki') ?>
</button>

<!-- Dues Modal -->
<div id="dues-modal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center sm:px-4"
    style="display:none;">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="this.parentElement.style.display='none'"></div>
    <div
        class="relative bg-card border border-amber-500/20 rounded-t-2xl sm:rounded-2xl p-4 sm:p-6 w-full max-w-lg max-h-[85vh] overflow-y-auto z-10 animate-slideUp">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-black text-amber-400">
                <i class="fas fa-hand-holding-dollar mr-1"></i>
                <?= __('customer_dues_baki') ?>
            </h3>
            <button type="button" onclick="document.getElementById('dues-modal').style.display='none'"
                class="text-text-muted hover:text-white transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Add Due Form -->
        <div class="space-y-6 pt-2 mb-6">
            <div class="relative">
                <input type="text" id="due-name" placeholder="<?= __('customer_name_req') ?>"
                    class="peer w-full bg-transparent border-b border-border focus:border-amber-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent">
                <label
                    class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-amber-400 pointer-events-none">
                    <?= __('customer_name_req') ?>
                </label>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="relative">
                    <input type="number" id="due-amount" step="1" min="1" placeholder="<?= __('due_amount_req') ?>"
                        class="peer w-full bg-transparent border-b border-border focus:border-amber-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent">
                    <label
                        class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-amber-400 pointer-events-none">
                        <?= __('due_amount_req') ?>
                    </label>
                </div>
                <div class="relative">
                    <input type="tel" id="due-phone" placeholder="<?= __('phone') ?>"
                        class="peer w-full bg-transparent border-b border-border focus:border-amber-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent">
                    <label
                        class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-amber-400 pointer-events-none">
                        <?= __('phone') ?>
                    </label>
                </div>
            </div>
            <div id="due-items-container" class="space-y-3">
                <div class="flex gap-2 items-center due-item-row">
                    <div class="relative flex-1">
                        <label
                            class="text-[0.65rem] text-text-muted mb-1 block uppercase tracking-widest font-bold px-1 absolute -top-4 left-0">Select
                            Item</label>
                        <select class="due-item-id w-full bg-transparent border-b border-border focus:border-amber-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none appearance-none cursor-pointer">
                            <option value="" class="bg-card text-text-primary">(Optional) Select Item...</option>
                            <?php foreach ($menuItems as $mi): ?>
                                <option class="bg-card text-text-primary" value="<?= $mi['id'] ?>">
                                    <?= htmlspecialchars(currentLang() === 'bn' ? ($mi['item_name_bn'] ?? $mi['item_name']) : $mi['item_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-2 top-3 text-xs text-text-muted pointer-events-none"></i>
                    </div>
                    <div class="relative w-20 shrink-0 mt-1">
                        <input type="number" step="0.5" min="0" placeholder="Qty"
                            class="due-item-qty peer w-full bg-transparent border-b border-border focus:border-amber-400 py-2 px-1 text-center text-sm transition-colors focus:outline-none placeholder-transparent">
                        <label
                            class="absolute left-1 -top-3.5 text-[0.65rem] text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-[0.65rem] peer-focus:text-amber-400 text-center w-full truncate pointer-events-none">Qty</label>
                    </div>
                    <button type="button" class="btn-remove-due-row text-red-500 hover:text-red-400 p-2 mt-1 hidden"><i class="fas fa-trash text-sm"></i></button>
                </div>
            </div>
            <div class="text-right">
                <button type="button" id="btn-add-due-row" class="text-xs text-amber-400 hover:text-amber-300 font-bold py-2"><i class="fas fa-plus mr-1"></i> Add Another Item</button>
            </div>
            <button type="button" id="btn-add-due" class="w-full text-xs font-bold text-amber-400 bg-amber-500/10 border border-amber-500/30 px-3 py-2.5 rounded-lg
                           hover:bg-amber-500/20 transition-all mt-1">
                <i class="fas fa-plus mr-1"></i>
                <?= __('add_due') ?>
            </button>
        </div>

        <!-- Today's Dues List -->
        <div id="dues-list" class="space-y-2">
            <?php if (!empty($todayDues)): ?>
                <?php foreach ($todayDues as $due): ?>
                    <div class="flex justify-between items-center bg-surface/50 rounded-lg px-3 py-2 due-row"
                        data-due-id="<?= $due['id'] ?>">
                        <div>
                            <span class="text-sm font-semibold">
                                <?= htmlspecialchars($due['customer_name']) ?>
                            </span>
                            <?php if (!empty($due['phone'])): ?>
                                <span class="text-[0.6rem] text-text-muted ml-1">
                                    <?= htmlspecialchars($due['phone']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm font-bold text-amber-400">৳
                            <?= number_format($due['due_amount']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  Summary                                                   -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="bg-card border border-border rounded-xl p-4 animate-slideUp">
    <p class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3">
        <?= __('daily_summary') ?>
    </p>
    <div class="space-y-2">
        <div class="flex justify-between text-sm">
            <span class="text-text-muted">
                <?= __('total_sales') ?>
            </span>
            <span class="font-bold text-accent" id="summary-total-sales">৳0</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-text-muted">
                <?= __('sold') ?> (
                <?= __('qty') ?>)
            </span>
            <span class="font-bold" id="summary-total-sold">0</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-text-muted">
                <?= __('complimentary') ?>
            </span>
            <span class="font-bold text-amber-400" id="summary-total-comp">0</span>
        </div>
    </div>
</div>

<div id="consumablesModal"
    class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-3">
    <div class="glass w-full max-w-sm rounded-xl border border-border p-4 flex flex-col max-h-[92vh]">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-sm font-bold text-text-primary flex items-center gap-2">
                <i class="fas fa-pump-soap text-accent"></i>
                <?= __('log_consumables') ?>
            </h3>
            <button onclick="document.getElementById('consumablesModal').classList.add('hidden')"
                class="text-text-muted hover:text-accent text-base"><i class="fas fa-times"></i></button>
        </div>

        <!-- Add Item Row -->
        <div class="bg-surface/50 rounded-lg p-3 mb-3 border border-border/40">
            <div class="flex gap-2 mb-2">
                <div class="relative flex-1">
                    <select id="conItemName"
                        class="w-full bg-transparent border-b border-border focus:border-accent py-1.5 px-1 text-xs text-text-primary transition-colors focus:outline-none appearance-none cursor-pointer">
                        <option value="" class="bg-surface"><?= __('select_item') ?>...</option>
                        <?php foreach ($rawItems as $item): ?>
                            <option value="<?= htmlspecialchars($item['item_name']) ?>"
                                data-unit="<?= htmlspecialchars($item['unit'] ?? 'pcs') ?>" class="bg-surface">
                                <?= htmlspecialchars($item['item_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-1 top-2 text-[0.5rem] text-text-muted pointer-events-none"></i>
                </div>
                <div class="w-14 shrink-0">
                    <select id="conUnit"
                        class="w-full bg-transparent border-b border-border py-1.5 px-1 font-bold text-xs text-text-muted focus:outline-none appearance-none cursor-pointer text-center">
                        <option value="pcs" class="bg-surface">pcs</option>
                        <option value="kg" class="bg-surface">kg</option>
                        <option value="L" class="bg-surface">L</option>
                    </select>
                </div>
            </div>
            
            <!-- 4-col inputs -->
            <div class="grid grid-cols-4 gap-1.5 mb-2">
                <div>
                    <label class="text-[0.5rem] text-info/70 uppercase font-bold block mb-0.5 text-center">OP</label>
                    <input type="number" id="conOpening" readonly step="0.1" value="0"
                        class="w-full bg-transparent border-b border-border text-info font-bold py-1 text-center outline-none cursor-not-allowed text-xs">
                </div>
                <div>
                    <label class="text-[0.5rem] text-text-muted uppercase font-bold block mb-0.5 text-center">ADD</label>
                    <input type="number" id="conAdded" placeholder="0" step="0.1" min="0" inputmode="decimal"
                        class="w-full bg-transparent border-b border-border focus:border-accent text-text-primary py-1 text-center outline-none text-xs">
                </div>
                <div>
                    <label class="text-[0.5rem] text-text-muted uppercase font-bold block mb-0.5 text-center">CL</label>
                    <input type="number" id="conClosing" placeholder="0" step="0.1" min="0" inputmode="decimal"
                        class="w-full bg-transparent border-b border-border focus:border-accent text-text-primary py-1 text-center outline-none text-xs">
                </div>
                <div>
                    <label class="text-[0.5rem] text-amber-400/80 uppercase font-bold block mb-0.5 text-center">USED</label>
                    <input type="number" id="conUsed" readonly value="0"
                        class="w-full bg-transparent border-b border-amber-500/30 text-amber-400 font-black py-1 text-center outline-none cursor-not-allowed text-xs">
                </div>
            </div>

            <!-- Add to Queue button -->
            <button id="addToQueueBtn"
                class="w-full border border-accent/50 text-accent hover:bg-accent/10 font-bold py-1.5 rounded-lg text-xs transition-colors flex items-center justify-center gap-1.5">
                <i class="fas fa-plus"></i> Add to List
            </button>
        </div>

        <!-- Queue Preview -->
        <div id="conQueue" class="space-y-1.5 mb-3 overflow-y-auto max-h-36 empty:hidden"></div>

        <!-- Save All Button -->
        <button id="saveConBtn"
            class="w-full bg-accent hover:bg-accent-light text-white font-bold py-1.5 rounded-xl mb-3 text-sm transition-colors hidden">
            <i class="fas fa-check mr-1"></i> Save All (<span id="queueCount">0</span>)
        </button>

        <div class="flex-1 overflow-y-auto">
            <h4 class="text-xs font-bold text-text-muted uppercase mb-2">
                <?= __('logged_today') ?>
            </h4>
            <?php if (empty($loggedConsumables)): ?>
                <p class="text-sm text-text-muted italic text-center py-4">
                    <?= __('no_logs_yet') ?>
                </p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($loggedConsumables as $log): ?>
                        <div class="bg-surface p-2.5 rounded-lg border border-border/50 text-sm">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-text-primary font-bold">
                                    <?= htmlspecialchars($log['item_name']) ?>
                                </span>
                                <div class="flex items-center gap-1 border-l border-border/50 pl-2">
                                    <button type="button"
                                        class="text-text-muted hover:text-accent transition-colors p-1 btn-edit-con"
                                        data-name="<?= htmlspecialchars($log['item_name']) ?>"
                                        data-opening="<?= (float) ($log['opening_qty'] ?? 0) ?>"
                                        data-added="<?= (float) ($log['added_qty'] ?? 0) ?>"
                                        data-closing="<?= (float) ($log['closing_qty'] ?? 0) ?>"
                                        data-unit="<?= htmlspecialchars($log['unit'] ?? 'pcs') ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                        class="text-text-muted hover:text-red-400 transition-colors p-1 btn-delete-con"
                                        data-name="<?= htmlspecialchars($log['item_name']) ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-4 gap-1 text-[0.6rem] text-text-muted">
                                <div class="text-center">
                                    <span class="block uppercase tracking-wide opacity-70">
                                        <?= __('opening_qty') ?>
                                    </span>
                                    <span class="block text-info font-bold text-xs">
                                        <?= (float) ($log['opening_qty'] ?? 0) ?>
                                    </span>
                                </div>
                                <div class="text-center">
                                    <span class="block uppercase tracking-wide opacity-70">+
                                        <?= __('added_today') ?>
                                    </span>
                                    <span class="block text-emerald-400 font-bold text-xs">
                                        <?= (float) ($log['added_qty'] ?? 0) ?>
                                    </span>
                                </div>
                                <div class="text-center">
                                    <span class="block uppercase tracking-wide opacity-70">
                                        <?= __('closing_qty') ?>
                                    </span>
                                    <span class="block text-text-primary font-bold text-xs">
                                        <?= (float) ($log['closing_qty'] ?? 0) ?>
                                    </span>
                                </div>
                                <div class="text-center">
                                    <span class="block uppercase tracking-wide opacity-70">
                                        <?= __('used_qty') ?>
                                    </span>
                                    <span class="block text-accent font-black text-xs">
                                        <?= (float) $log['used_qty'] ?>
                                        <?= htmlspecialchars($log['unit'] ?? 'pcs') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
                const comp = parseFloat(row.querySelector('.di-comp').value) || 0;
                const price = parseFloat(row.dataset.sellingPrice) || 0;

                const rawUsage = parseFloat(row.dataset.rawUsage) || 1.0;
                const rawUsageUnit = row.dataset.rawUsageUnit ? row.dataset.rawUsageUnit.toLowerCase() : 'kg';
                const rawUnit = row.dataset.rawUnit ? row.dataset.rawUnit.toLowerCase() : 'kg';
                
                let normUsage = rawUsage;
                if (rawUnit === 'kg' && (rawUsageUnit === 'g' || rawUsageUnit === 'gm')) normUsage /= 1000;
                if (rawUnit === 'l' && rawUsageUnit === 'ml') normUsage /= 1000;

                const usedRaw = Math.max(0, opening - closing);
                const portions = usedRaw > 0 ? Math.floor(usedRaw / normUsage) : 0;
                const sold = Math.max(0, portions - comp);

                const revenue = sold * price;

                row.querySelector('.di-sold').textContent = sold % 1 === 0 ? sold : sold.toFixed(1);
                row.querySelector('.di-revenue').textContent = revenue.toLocaleString('en-IN');

                totalSold += sold;
                totalRevenue += revenue;
                totalComp += comp;
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
        const addItemSelect = document.getElementById('add-item-select');
        const addItemOpening = document.getElementById('add-item-opening');

        // Auto-fill opening qty from raw_inventory when item is selected
        addItemSelect.addEventListener('change', () => {
            const opt = addItemSelect.options[addItemSelect.selectedIndex];
            if (opt && opt.value) {
                const rawQty = parseFloat(opt.dataset.rawQty) || 0;
                addItemOpening.value = rawQty > 0 ? rawQty : '';
                addItemOpening.focus();
            } else {
                addItemOpening.value = '';
            }
        });

        document.getElementById('btn-add-day-item').addEventListener('click', async () => {
            const itemId = addItemSelect.value;
            const openingQty = parseFloat(addItemOpening.value) || 0;

            if (!itemId) return showToast('<?= __("select_menu_item") ?>', 'error');

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

        // ── Customer Dues Dynamic Items ──────────────────────────────
        const dueItemsContainer = document.getElementById('due-items-container');
        document.getElementById('btn-add-due-row').addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'flex gap-2 items-center due-item-row';
            row.innerHTML = `
                <div class="relative flex-1 mt-3">
                    <select class="due-item-id w-full bg-transparent border-b border-border focus:border-amber-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none appearance-none cursor-pointer">
                        ${dueItemsContainer.querySelector('.due-item-id').innerHTML}
                    </select>
                    <i class="fas fa-chevron-down absolute right-2 top-3 text-xs text-text-muted pointer-events-none"></i>
                </div>
                <div class="relative w-20 shrink-0 mt-3">
                    <input type="number" step="0.5" min="0" placeholder="Qty" class="due-item-qty peer w-full bg-transparent border-b border-border focus:border-amber-400 py-2 px-1 text-center text-sm transition-colors focus:outline-none placeholder-transparent">
                    <label class="absolute left-1 -top-3.5 text-[0.65rem] text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-[0.65rem] peer-focus:text-amber-400 text-center w-full truncate pointer-events-none">Qty</label>
                </div>
                <button type="button" class="btn-remove-due-row text-red-500 hover:text-red-400 p-2 mt-3"><i class="fas fa-trash text-sm"></i></button>
            `;
            dueItemsContainer.appendChild(row);
        });

        dueItemsContainer.addEventListener('click', (e) => {
            if (e.target.closest('.btn-remove-due-row')) {
                e.target.closest('.due-item-row').remove();
            }
        });

        // ── Add Customer Due ──────────────────────────────────────
        document.getElementById('btn-add-due').addEventListener('click', async () => {
            const nameInput = document.getElementById('due-name');
            const amountInput = document.getElementById('due-amount');
            const phoneInput = document.getElementById('due-phone');

            const name = nameInput.value.trim();
            const amount = parseFloat(amountInput.value) || 0;
            const phone = phoneInput.value.trim();

            if (!name) return showToast('<?= __("customer_name_req") ?>', 'error');
            if (amount <= 0) return showToast('<?= __("due_amount_req") ?>', 'error');

            const items = [];
            const itemRows = document.querySelectorAll('.due-item-row');
            let itemNamesStr = [];
            itemRows.forEach(row => {
                const select = row.querySelector('.due-item-id');
                const qtyInput = row.querySelector('.due-item-qty');
                const itemId = parseInt(select.value) || 0;
                const qty = parseFloat(qtyInput.value) || 0;
                if (itemId > 0 && qty > 0) {
                    items.push({ item_id: itemId, qty: qty });
                    itemNamesStr.push(`${select.options[select.selectedIndex].text.trim()} (x${qty})`);
                }
            });

            const res = await apiPost('?url=inventory/addCustomerDue', {
                customer_name: name,
                due_amount: amount,
                phone: phone,
                items: items
            });

            if (res.success) {
                // Append to list
                const list = document.getElementById('dues-list');
                const div = document.createElement('div');
                div.className = 'flex justify-between items-center bg-surface/50 rounded-lg px-3 py-2 due-row';

                let itemNameHtml = '';
                if (itemNamesStr.length > 0) {
                    itemNameHtml = ' <br><span class="text-xs text-text-muted bg-card px-1.5 py-0.5 rounded border border-border mt-1 inline-block">' + itemNamesStr.join(', ') + '</span>';
                }

                div.innerHTML = `
                <div>
                    <span class="text-sm font-semibold">${name}</span>
                    ${phone ? `<br><span class="text-[0.6rem] text-text-muted">${phone}</span>` : ''}
                    ${itemNameHtml}
                </div>
                <span class="text-sm font-bold text-amber-400">৳${amount.toLocaleString('en-IN')}</span>
            `;
                list.prepend(div);

                // Clear inputs
                nameInput.value = '';
                amountInput.value = '';
                phoneInput.value = '';
                
                // Reset item rows to just 1 empty row
                const firstRow = document.querySelector('.due-item-row');
                firstRow.querySelector('.due-item-id').value = '';
                firstRow.querySelector('.due-item-qty').value = '';
                
                const allRows = document.querySelectorAll('.due-item-row');
                for (let i = 1; i < allRows.length; i++) {
                    allRows[i].remove();
                }

                showToast('<?= __("success") ?>', 'success');
            } else {
                showToast(res.error || '<?= __("error") ?>', 'error');
            }
        });

        // ── Deductive Used Calculation ──────────────────────────────
        function calcConUsed() {
            const opening = parseFloat(document.getElementById('conOpening').value) || 0;
            const added = parseFloat(document.getElementById('conAdded').value) || 0;
            const closing = parseFloat(document.getElementById('conClosing').value) || 0;
            const used = (opening + added) - closing;
            document.getElementById('conUsed').value = Math.max(0, parseFloat(used.toFixed(2)));
        }
        document.getElementById('conAdded').addEventListener('input', calcConUsed);
        document.getElementById('conClosing').addEventListener('input', calcConUsed);

        // ── Item Selection → Fetch Opening Qty ────────────────────
        document.getElementById('conItemName').addEventListener('change', async (e) => {
            const selected = e.target.options[e.target.selectedIndex];
            const unit = selected.dataset.unit;
            if (unit) {
                document.getElementById('conUnit').value = unit;
            }

            const itemName = e.target.value;
            // Reset fields
            document.getElementById('conOpening').value = 0;
            document.getElementById('conAdded').value = '';
            document.getElementById('conClosing').value = '';
            document.getElementById('conUsed').value = 0;

            if (!itemName) return;

            // Fetch opening from server
            const res = await apiPost('?url=inventory/getConsumableOpening', { item_name: itemName });
            if (res.success) {
                document.getElementById('conOpening').value = res.opening_qty || 0;
                if (res.exists) {
                    // Editing existing record for today
                    document.getElementById('conAdded').value = res.added_qty || '';
                    document.getElementById('conClosing').value = res.closing_qty || '';
                }
                calcConUsed();
            }
        });

        // ── Queue (batch) system ──────────────────────────────────
        let conQueue = [];

        function renderQueue() {
            const qEl = document.getElementById('conQueue');
            const saveBtn = document.getElementById('saveConBtn');
            const countEl = document.getElementById('queueCount');
            qEl.innerHTML = '';
            if (conQueue.length === 0) {
                saveBtn.classList.add('hidden');
                return;
            }
            saveBtn.classList.remove('hidden');
            countEl.textContent = conQueue.length;
            conQueue.forEach((entry, idx) => {
                const used = Math.max(0, (entry.opening + entry.added) - entry.closing);
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between bg-surface rounded-lg px-2.5 py-1.5 border border-border/50';
                row.innerHTML = `
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-bold text-text-primary truncate block">${entry.item}</span>
                        <span class="text-[0.6rem] text-text-muted">
                            OP:${entry.opening} ADD:${entry.added} CL:${entry.closing}
                            <span class="text-amber-400 font-bold ml-1">USED:${used} ${entry.unit}</span>
                        </span>
                    </div>
                    <button data-idx="${idx}" class="btn-rm-queue ml-2 text-text-muted hover:text-red-400 text-xs p-1 shrink-0">
                        <i class="fas fa-times"></i>
                    </button>`;
                qEl.appendChild(row);
            });
            qEl.querySelectorAll('.btn-rm-queue').forEach(b => {
                b.addEventListener('click', () => {
                    conQueue.splice(parseInt(b.dataset.idx), 1);
                    renderQueue();
                });
            });
        }

        // Add to Queue button
        document.getElementById('addToQueueBtn').addEventListener('click', () => {
            const item = document.getElementById('conItemName').value;
            if (!item) { showToast('<?= __("invalid_input") ?>', 'warning'); return; }

            // Prevent duplicates
            if (conQueue.find(e => e.item === item)) {
                showToast('Already in list — remove first to re-add', 'warning'); return;
            }

            conQueue.push({
                item,
                opening: parseFloat(document.getElementById('conOpening').value) || 0,
                added:   parseFloat(document.getElementById('conAdded').value)   || 0,
                closing: parseFloat(document.getElementById('conClosing').value) || 0,
                unit:    document.getElementById('conUnit').value
            });

            // Reset form for next item
            document.getElementById('conItemName').value = '';
            document.getElementById('conOpening').value  = 0;
            document.getElementById('conAdded').value    = '';
            document.getElementById('conClosing').value  = '';
            document.getElementById('conUsed').value     = 0;
            renderQueue();
        });

        // ── Save All ──────────────────────────────────────────────
        document.getElementById('saveConBtn').addEventListener('click', async () => {
            if (conQueue.length === 0) { showToast('<?= __("invalid_input") ?>', 'warning'); return; }

            const btn = document.getElementById('saveConBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
            btn.disabled = true;

            let allOk = true;
            for (const entry of conQueue) {
                const res = await apiPost('?url=inventory/saveConsumableLog', {
                    item_name:   entry.item,
                    opening_qty: entry.opening,
                    added_qty:   entry.added,
                    closing_qty: entry.closing,
                    unit:        entry.unit
                });
                if (!res.success) { allOk = false; }
            }

            if (allOk) {
                showToast('<?= __("success") ?>', 'success');
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast('Some items failed to save', 'error');
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Save All (<span id="queueCount">' + conQueue.length + '</span>)';
                btn.disabled = false;
            }
        });

        // ── Edit Consumable ───────────────────────────────────────
        document.querySelectorAll('.btn-edit-con').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('conItemName').value = btn.dataset.name;
                document.getElementById('conOpening').value = btn.dataset.opening || 0;
                document.getElementById('conAdded').value = btn.dataset.added || '';
                document.getElementById('conClosing').value = btn.dataset.closing || '';
                document.getElementById('conUnit').value = btn.dataset.unit || 'pcs';
                calcConUsed();
                document.getElementById('conAdded').focus();
            });
        });

        // ── Delete Consumable ─────────────────────────────────────
        document.querySelectorAll('.btn-delete-con').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('<?= __("confirm_delete") ?>')) return;

                const name = btn.dataset.name;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;

                const res = await apiPost('?url=inventory/deleteConsumableLog', { item_name: name });

                if (res.success) {
                    showToast('<?= __("success") ?>', 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(res.error || 'Error deleting entry', 'error');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            });
        });
    });
</script>