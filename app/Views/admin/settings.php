<?php
/**
 * Admin Settings Dashboard
 * Variables: $items, $rawInventory, $users, $fixedCosts
 */
?>

<div class="mb-4 animate-slideUp">
    <h2 class="text-lg font-black">
        <i class="fas fa-cog text-text-muted mr-1"></i> Settings
    </h2>
</div>

<!-- Tabs -->
<div class="flex gap-2 mb-4 overflow-x-auto pb-1 no-scrollbar animate-slideUp">
    <button class="settings-tab active flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold bg-accent/10 border-accent/40 text-accent border transition-all" data-target="tab-items">Menu Items</button>
    <button class="settings-tab flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold bg-card border-border text-text-muted border hover:text-text-primary transition-all" data-target="tab-raw">Raw Inventory</button>
    <button class="settings-tab flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold bg-card border-border text-text-muted border hover:text-text-primary transition-all" data-target="tab-addons">Online Addons</button>
</div>



<!-- Tab: Menu Items -->
<div id="tab-items" class="settings-pane block space-y-4 stagger">
    <div class="bg-card border border-border/50 rounded-xl p-4">
        <form id="form-items" data-id="0" class="space-y-6">
            <div class="grid grid-cols-2 gap-x-3 pt-2">
                <div class="relative">
                    <input type="text" id="it_name" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="English Name">
                    <label for="it_name" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">English Name</label>
                </div>
                <div class="relative">
                    <input type="text" id="it_name_bn" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Bangla Name">
                    <label for="it_name_bn" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Bangla Name</label>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-x-3 gap-y-4">
                <div class="relative">
                    <input type="number" id="it_sell" step="0.01" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Sell Price">
                    <label for="it_sell" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Sell Price</label>
                </div>
                <div class="relative">
                    <input type="number" id="it_online_price" step="0.01" class="peer w-full bg-transparent border-b border-border focus:border-indigo-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Online Price">
                    <label for="it_online_price" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-indigo-400">Online Price</label>
                </div>
                <div class="relative">
                    <input type="number" id="it_cost" step="0.01" readonly class="peer w-full bg-transparent border-b border-border text-orange-400 font-bold py-2 px-1 text-sm transition-colors focus:outline-none placeholder-transparent cursor-not-allowed" placeholder="Cost Price">
                    <label for="it_cost" class="absolute left-1 -top-3.5 text-xs text-text-muted">Total Cost Price</label>
                </div>
                <div class="relative">
                    <input type="number" id="it_additional_cost" step="0.01" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Additional Cost">
                    <label for="it_additional_cost" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Additional Cost</label>
                </div>
            </div>
            <div class="relative">
                <label class="text-[0.65rem] text-text-muted mb-1 block uppercase tracking-widest font-bold px-1 absolute -top-4 left-0"><?= __('link_raw_ingredient') ?></label>
                <select id="it_linked_raw" class="w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none appearance-none cursor-pointer">
                    <option value="" class="bg-card text-text-primary"><?= __('select_raw_item') ?>...</option>
                    <?php foreach ($rawInventory as $raw): ?>
                        <option class="bg-card text-text-primary" value="<?= htmlspecialchars($raw['item_name']) ?>" data-price="<?= $raw['avg_unit_price'] ?>" data-unit="<?= htmlspecialchars($raw['unit'] ?? 'kg') ?>"><?= htmlspecialchars($raw['item_name']) ?> (৳<?= $raw['avg_unit_price'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-2 top-3 text-xs text-text-muted pointer-events-none"></i>
            </div>
            <div class="mt-5 pt-3 border-t border-border/30">
                <button type="button" id="btn-toggle-conversion" class="text-xs font-bold text-amber-400 bg-amber-500/10 border border-amber-500/30 px-3 py-1.5 rounded-lg hover:bg-amber-500/20 transition-all flex items-center gap-1 mb-2">
                    <i class="fas fa-calculator"></i> <?= __('add_conversion') ?>
                </button>
                <div id="conversion-section" class="hidden space-y-2">
                    <label class="text-[0.65rem] text-amber-400/80 block uppercase tracking-widest font-bold"><?= __('raw_usage_per_unit') ?></label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="number" id="it_raw_usage" step="0.001" min="0.001"
                                   class="peer w-full bg-transparent border-b border-amber-500/30 focus:border-amber-400 py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent"
                                   placeholder="1.000" value="1.000">
                            <label for="it_raw_usage" class="absolute left-1 -top-3.5 text-xs text-amber-400/70 transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-amber-400"><?= __('amount') ?></label>
                        </div>
                        <div class="relative w-24 shrink-0 mt-1">
                            <select id="it_raw_usage_unit" class="w-full bg-transparent border-b border-amber-500/30 focus:border-amber-400 py-1.5 px-1 text-sm text-amber-400 transition-colors focus:outline-none appearance-none cursor-pointer">
                                <option class="bg-card text-text-primary" value="kg">kg</option>
                                <option class="bg-card text-text-primary" value="g">g</option>
                                <option class="bg-card text-text-primary" value="L">L</option>
                                <option class="bg-card text-text-primary" value="ml">ml</option>
                                <option class="bg-card text-text-primary" value="pcs">pcs</option>
                                <option class="bg-card text-text-primary" value="plate">plate</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-1 top-2.5 text-[0.6rem] text-amber-400/70 pointer-events-none"></i>
                        </div>
                    </div>
                    <p class="text-[0.6rem] text-text-muted mt-1"><?= __('raw_usage_hint') ?></p>
                </div>
            </div>
            <div class="flex items-center justify-between pt-2">
                <label class="flex items-center gap-2 text-sm text-text-muted">
                    <input type="checkbox" id="it_active" checked class="accent-accent"> Active
                </label>
                <button type="submit" id="btn-submit-item" class="text-xs font-bold bg-accent/20 text-accent px-4 py-2 rounded-lg hover:bg-accent/30 transition-colors"><i class="fas fa-plus mr-1"></i> <?= __('add') ?></button>
            </div>
        </form>
    </div>

    <div class="space-y-2">
        <?php foreach ($items as $item): ?>
        <div class="flex justify-between items-center p-3 bg-card border border-border/50 rounded-xl">
            <div>
                <div class="text-sm font-bold"><?= htmlspecialchars($item['item_name']) ?> <span class="text-xs font-normal text-text-muted">(<?= htmlspecialchars($item['item_name_bn']) ?>)</span></div>
                <div class="text-[0.65rem] text-text-muted mt-1 uppercase tracking-wider">
                    <?php 
                        $displayCost = $item['cost_price'];
                        if (!empty($item['linked_raw_item']) && isset($item['raw_price'])) {
                            $rawUsageVal = max(0.001, (float)($item['raw_usage'] ?? 1.0));
                            $rUnit = strtolower($item['raw_usage_unit'] ?? 'kg');
                            $rawU = strtolower($item['raw_unit'] ?? 'kg');
                            if ($rawU === 'kg' && ($rUnit === 'g' || $rUnit === 'gm')) $rawUsageVal /= 1000;
                            if ($rawU === 'l' && $rUnit === 'ml') $rawUsageVal /= 1000;
                            $displayCost = ((float)$item['raw_price'] * $rawUsageVal) + (float)($item['additional_cost'] ?? 0);
                        }
                    ?>
                    Sell: <span class="text-accent font-bold">৳<?= $item['selling_price'] ?></span> | 
                    Online: <span class="text-indigo-400 font-bold">৳<?= $item['online_price'] ?? 0 ?></span> | 
                    Total Cost: <span class="text-orange-400 font-bold">৳<?= number_format($displayCost, 2, '.', '') ?></span> | 
                    Add. Cost: ৳<?= $item['additional_cost'] ?? 0 ?>
                    <?php if (!empty($item['linked_raw_item'])): ?>
                    <br><span class="text-blue-400"><i class="fas fa-link"></i> <?= htmlspecialchars($item['linked_raw_item']) ?></span>
                    <?php if (isset($item['raw_usage']) && (float)$item['raw_usage'] > 0 && (float)$item['raw_usage'] != 1.0): ?>
                    <span class="text-amber-400 ml-1 text-[0.6rem] bg-amber-500/10 px-1.5 py-0.5 rounded border border-amber-500/20"><i class="fas fa-exchange-alt"></i> <?= (float)$item['raw_usage'] ?> <?= htmlspecialchars($item['raw_usage_unit'] ?? 'kg') ?>/unit</span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button class="btn-edit-item p-2 text-text-muted hover:text-accent transition-colors" data-item='<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>'>
                    <i class="fas fa-pencil-alt"></i>
                </button>
                <button class="btn-delete-item p-2 text-text-muted hover:text-red-400 transition-colors" data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['item_name']) ?>">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tab: Raw Inventory -->
<div id="tab-raw" class="settings-pane hidden space-y-4 stagger">
    <div class="bg-card border border-border/50 rounded-xl p-4">
        <form id="form-raw" data-id="0" class="space-y-6">
            <div class="grid grid-cols-2 gap-x-3 pt-2">
                <div class="relative">
                    <input type="text" id="raw_name" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="English Name">
                    <label for="raw_name" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">English Name</label>
                </div>
                <div class="relative">
                    <input type="text" id="raw_name_bn" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Bangla Name">
                    <label for="raw_name_bn" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Bangla Name</label>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-x-3">
                <div class="relative">
                    <label class="text-[0.65rem] text-text-muted mb-1 block uppercase tracking-widest font-bold px-1 absolute -top-4 left-0">Unit</label>
                    <select id="raw_unit" required class="w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none appearance-none cursor-pointer">
                        <option class="bg-card text-text-primary" value="kg">kg</option>
                        <option class="bg-card text-text-primary" value="l">l</option>
                        <option class="bg-card text-text-primary" value="pcs">pcs</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-2 top-3 text-xs text-text-muted pointer-events-none"></i>
                </div>
                <div class="relative">
                    <input type="number" id="raw_price" step="0.01" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Avg Price">
                    <label for="raw_price" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Avg Price</label>
                </div>
                <div class="relative">
                    <input type="number" id="raw_min" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Min Stock">
                    <label for="raw_min" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Min Stock</label>
                </div>
            </div>
            <div class="relative">
                <input type="number" id="raw_qty" step="0.01" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="<?= __('opening_current_stock') ?>">
                <label for="raw_qty" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent"><?= __('opening_current_stock') ?></label>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" id="btn-submit-raw" class="text-xs font-bold bg-accent/20 text-accent px-4 py-2 rounded-lg hover:bg-accent/30 transition-colors"><i class="fas fa-plus mr-1"></i> <?= __('add') ?></button>
            </div>
        </form>
    </div>

    <div class="space-y-2">
        <?php foreach ($rawInventory as $raw): ?>
        <div class="flex justify-between items-center p-3 bg-card border border-border/50 rounded-xl">
            <div>
                <div class="text-sm font-bold"><?= htmlspecialchars($raw['item_name']) ?> <span class="text-xs font-normal text-text-muted">(<?= htmlspecialchars($raw['item_name_bn']) ?>)</span></div>
                <div class="text-[0.65rem] text-text-muted mt-1 uppercase tracking-wider">
                    Avg Price: <span class="text-accent font-bold">৳<?= $raw['avg_unit_price'] ?></span> / <?= htmlspecialchars($raw['unit']) ?> |
                    Qty: <span class="text-emerald-400 font-bold"><?= $raw['current_qty'] ?? 0 ?></span> |
                    Min: <?= $raw['min_stock_threshold'] ?>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button class="btn-edit-raw p-2 text-text-muted hover:text-accent transition-colors" data-item='<?= htmlspecialchars(json_encode($raw), ENT_QUOTES, 'UTF-8') ?>'>
                    <i class="fas fa-pencil-alt"></i>
                </button>
                <button class="btn-delete-raw p-2 text-text-muted hover:text-red-400 transition-colors" data-id="<?= $raw['id'] ?>" data-name="<?= htmlspecialchars($raw['item_name']) ?>" title="<?= __('action_delete_item') ?>">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tab: Online Addons -->
<div id="tab-addons" class="settings-pane hidden space-y-4 stagger">
    <div class="bg-card border border-border/50 rounded-xl p-4">
        <form id="form-addons" class="space-y-4">
            <div class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest flex justify-between items-center mb-2">
                <span>Manage Online Addons</span>
                <button type="button" onclick="addAddonRow()" class="text-indigo-400 bg-indigo-500/10 px-2.5 py-1.5 rounded-lg hover:bg-indigo-500/20 transition-all border border-indigo-500/30"><i class="fas fa-plus"></i> Add</button>
            </div>
            
            <div id="addons-container" class="space-y-3">
                <?php foreach ($onlineAddons as $addon): ?>
                <div class="addon-row bg-surface border border-border/50 rounded-xl p-3 space-y-2">
                    <div class="flex gap-2 items-center">
                        <input type="text" class="addon-name flex-1 bg-transparent border-b border-border focus:border-accent py-1.5 px-1 text-sm text-text-primary transition-colors focus:outline-none" value="<?= htmlspecialchars($addon['name']) ?>" placeholder="e.g. + Cheese">
                        <input type="number" class="addon-price w-20 bg-transparent border-b border-border focus:border-accent py-1.5 px-1 text-sm text-text-primary text-right transition-colors focus:outline-none" value="<?= $addon['price'] ?>" placeholder="৳">
                        <button type="button" onclick="this.closest('.addon-row').remove()" class="text-text-muted hover:text-red-400 p-1.5"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="flex gap-2 items-center">
                        <select class="addon-raw flex-1 bg-transparent border-b border-border focus:border-accent py-1.5 px-1 text-xs text-text-muted transition-colors focus:outline-none appearance-none cursor-pointer">
                            <option value="" class="bg-surface">— Raw Item (optional) —</option>
                            <?php foreach ($rawInventory as $raw): ?>
                                <option value="<?= htmlspecialchars($raw['item_name']) ?>" class="bg-surface" <?= ($addon['raw_item'] ?? '') === $raw['item_name'] ? 'selected' : '' ?>><?= htmlspecialchars($raw['item_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="flex items-center gap-1 shrink-0">
                            <input type="number" class="addon-gram w-16 bg-transparent border-b border-border focus:border-accent py-1.5 px-1 text-xs text-text-primary text-right transition-colors focus:outline-none" value="<?= $addon['gram'] ?? '' ?>" placeholder="0" step="1" min="0">
                            <span class="text-[0.6rem] text-text-muted font-bold">g</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="flex justify-end pt-3 border-t border-border/50 mt-4">
                <button type="submit" id="btn-save-addons" class="text-xs font-bold bg-accent/20 text-accent px-4 py-2.5 rounded-lg hover:bg-accent/30 transition-colors"><i class="fas fa-save mr-1"></i> Save Addons</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Tab Switching
    document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.settings-tab').forEach(t => {
                t.className = 'settings-tab flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold bg-card border-border text-text-muted border hover:text-text-primary transition-all';
            });
            tab.className = 'settings-tab active flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold bg-accent/10 border-accent/40 text-accent border transition-all';
            
            document.querySelectorAll('.settings-pane').forEach(p => p.classList.add('hidden'));
            document.getElementById(tab.dataset.target).classList.remove('hidden');
        });
    });

    const setupForm = (formId, btnSubmitId, editBtnClass, populateFn, getFieldsFn, entity) => {
        const form = document.getElementById(formId);
        if (!form) return;
        const btnSubmit = document.getElementById(btnSubmitId);
        
        document.querySelectorAll(editBtnClass).forEach(btn => {
            btn.addEventListener('click', () => {
                const item = JSON.parse(btn.dataset.item);
                form.dataset.id = item.id;
                populateFn(item);
                btnSubmit.innerHTML = '<i class="fas fa-save mr-1"></i> <?= __("btn_update") ?? "Update" ?>';
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const originalHtml = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btnSubmit.disabled = true;

            const fields = getFieldsFn();
            const id = form.dataset.id;
            const res = await apiPost('?url=admin/saveEntity', { entity, id, fields });

            if (res.success) {
                showToast(id === "0" ? 'Added successfully!' : '<?= __("update_success") ?? "Updated successfully." ?>', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(res.error || 'Failed to save', 'error');
                btnSubmit.innerHTML = originalHtml;
                btnSubmit.disabled = false;
            }
        });
    };

    const btnToggleConv = document.getElementById('btn-toggle-conversion');
    if (btnToggleConv) {
        btnToggleConv.addEventListener('click', () => {
            document.getElementById('conversion-section').classList.toggle('hidden');
        });
    }

    const calcTotalCost = () => {
        const sel = document.getElementById('it_linked_raw');
        const opt = sel.options[sel.selectedIndex];
        const rawPrice  = opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0;
        const rawUnit   = opt && opt.dataset.unit ? opt.dataset.unit.toLowerCase() : 'kg';
        
        const rawUsage  = parseFloat(document.getElementById('it_raw_usage').value) || 1.0;
        const rawUsageUnit = document.getElementById('it_raw_usage_unit').value || 'kg';
        
        let normalizedUsage = rawUsage;
        if (rawUnit === 'kg' && (rawUsageUnit === 'g' || rawUsageUnit === 'gm')) normalizedUsage = rawUsage / 1000;
        if (rawUnit === 'l' && rawUsageUnit === 'ml') normalizedUsage = rawUsage / 1000;

        const addCost   = parseFloat(document.getElementById('it_additional_cost').value) || 0;
        document.getElementById('it_cost').value = ((rawPrice * normalizedUsage) + addCost).toFixed(2);
    };
    
    document.getElementById('it_linked_raw').addEventListener('change', calcTotalCost);
    document.getElementById('it_additional_cost').addEventListener('input', calcTotalCost);
    document.getElementById('it_raw_usage').addEventListener('input', calcTotalCost);
    document.getElementById('it_raw_usage_unit').addEventListener('change', calcTotalCost);

    setupForm(
        'form-items', 
        'btn-submit-item', 
        '.btn-edit-item', 
        (item) => {
            document.getElementById('it_name').value = item.item_name;
            document.getElementById('it_name_bn').value = item.item_name_bn;
            document.getElementById('it_sell').value = item.selling_price;
            document.getElementById('it_online_price').value = item.online_price || 0;
            document.getElementById('it_cost').value = item.cost_price;
            document.getElementById('it_additional_cost').value = item.additional_cost || 0;
            document.getElementById('it_active').checked = item.is_active == 1;
            document.getElementById('it_linked_raw').value = item.linked_raw_item || '';
            document.getElementById('it_raw_usage').value = item.raw_usage || 1.000;
            document.getElementById('it_raw_usage_unit').value = item.raw_usage_unit || 'kg';
            if ((item.raw_usage || 1.0) != 1.0) {
                document.getElementById('conversion-section').classList.remove('hidden');
            } else {
                document.getElementById('conversion-section').classList.add('hidden');
            }
            calcTotalCost();
        },
        () => ({
            item_name: document.getElementById('it_name').value,
            item_name_bn: document.getElementById('it_name_bn').value,
            selling_price: parseFloat(document.getElementById('it_sell').value) || 0,
            online_price: parseFloat(document.getElementById('it_online_price').value) || 0,
            cost_price: parseFloat(document.getElementById('it_cost').value) || 0,
            additional_cost: parseFloat(document.getElementById('it_additional_cost').value) || 0,
            is_active: document.getElementById('it_active').checked ? 1 : 0,
            linked_raw_item: document.getElementById('it_linked_raw').value.trim(),
            raw_usage: parseFloat(document.getElementById('it_raw_usage').value) || 1.000,
            raw_usage_unit: document.getElementById('it_raw_usage_unit').value || 'kg'
        }),
        'items'
    );

    setupForm(
        'form-raw', 
        'btn-submit-raw', 
        '.btn-edit-raw', 
        (item) => {
            document.getElementById('raw_name').value = item.item_name;
            document.getElementById('raw_name_bn').value = item.item_name_bn;
            document.getElementById('raw_unit').value = item.unit;
            document.getElementById('raw_price').value = item.avg_unit_price;
            document.getElementById('raw_min').value = item.min_stock_threshold;
            document.getElementById('raw_qty').value = item.current_qty;
        },
        () => ({
            item_name: document.getElementById('raw_name').value,
            item_name_bn: document.getElementById('raw_name_bn').value,
            unit: document.getElementById('raw_unit').value,
            avg_unit_price: parseFloat(document.getElementById('raw_price').value) || 0,
            min_stock_threshold: parseFloat(document.getElementById('raw_min').value) || 0,
            current_qty: parseFloat(document.getElementById('raw_qty').value) || 0
        }),
        'raw_inventory'
    );

    // ── Delete Menu Items ──────────────────────────────────────
    document.querySelectorAll('.btn-delete-item').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            if (!confirm(`<?= __('confirm_delete') ?>`)) return;

            const res = await apiPost('?url=admin/deleteEntity', { entity: 'items', id });
            if (res.success) {
                btn.closest('.flex.justify-between').remove();
                showToast(name + ' deleted!', 'success');
            } else {
                showToast(res.error || '<?= __("error") ?>', 'error');
            }
        });
    });

    // ── Delete Raw Inventory ───────────────────────────────────
    document.querySelectorAll('.btn-delete-raw').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            if (!confirm(`<?= __('confirm_delete') ?>`)) return;

            const res = await apiPost('?url=admin/deleteEntity', { entity: 'raw_inventory', id });
            if (res.success) {
                btn.closest('.flex.justify-between').remove();
                showToast(name + ' deleted!', 'success');
            } else {
                showToast(res.error || '<?= __("error") ?>', 'error');
            }
        });
    });

    // ── Manage Addons ──────────────────────────────────────────
    function addAddonRow() {
        const rawOpts = `<?php foreach ($rawInventory as $raw): ?><option value="<?= htmlspecialchars($raw['item_name']) ?>" class="bg-surface"><?= htmlspecialchars($raw['item_name']) ?></option><?php endforeach; ?>`;
        const row = document.createElement('div');
        row.className = 'addon-row bg-surface border border-border/50 rounded-xl p-3 space-y-2';
        row.innerHTML = `
            <div class="flex gap-2 items-center">
                <input type="text" class="addon-name flex-1 bg-transparent border-b border-border focus:border-accent py-1.5 px-1 text-sm text-text-primary transition-colors focus:outline-none" placeholder="e.g. + Cheese">
                <input type="number" class="addon-price w-20 bg-transparent border-b border-border focus:border-accent py-1.5 px-1 text-sm text-text-primary text-right transition-colors focus:outline-none" placeholder="৳">
                <button type="button" onclick="this.closest('.addon-row').remove()" class="text-text-muted hover:text-red-400 p-1.5"><i class="fas fa-times"></i></button>
            </div>
            <div class="flex gap-2 items-center">
                <select class="addon-raw flex-1 bg-transparent border-b border-border focus:border-accent py-1.5 px-1 text-xs text-text-muted transition-colors focus:outline-none appearance-none cursor-pointer">
                    <option value="" class="bg-surface">— Raw Item (optional) —</option>
                    ${rawOpts}
                </select>
                <div class="flex items-center gap-1 shrink-0">
                    <input type="number" class="addon-gram w-16 bg-transparent border-b border-border focus:border-accent py-1.5 px-1 text-xs text-text-primary text-right transition-colors focus:outline-none" placeholder="0" step="1" min="0">
                    <span class="text-[0.6rem] text-text-muted font-bold">g</span>
                </div>
            </div>
        `;
        document.getElementById('addons-container').appendChild(row);
    }
    window.addAddonRow = addAddonRow;

    const formAddons = document.getElementById('form-addons');
    if (formAddons) {
        formAddons.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-save-addons');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const addons = [];
            document.querySelectorAll('.addon-row').forEach(row => {
                const name = row.querySelector('.addon-name').value.trim();
                const price = parseFloat(row.querySelector('.addon-price').value) || 0;
                const raw_item = row.querySelector('.addon-raw')?.value?.trim() || '';
                const gram = parseFloat(row.querySelector('.addon-gram')?.value) || 0;
                if (name) addons.push({ name, price, raw_item, gram });
            });

            const res = await apiPost('?url=admin/saveOnlineAddons', { addons });
            if (res.success) {
                showToast('Addons saved successfully!', 'success');
                setTimeout(() => window.location.reload(), 800);
            } else {
                showToast(res.error || 'Failed to save', 'error');
                btn.innerHTML = orig;
                btn.disabled = false;
            }
        });
    }
});
</script>
