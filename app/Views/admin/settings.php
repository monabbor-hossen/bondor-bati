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
            <div class="grid grid-cols-3 gap-x-3">
                <div class="relative">
                    <input type="number" id="it_sell" step="0.01" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Sell Price">
                    <label for="it_sell" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Sell Price</label>
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
                        <option class="bg-card text-text-primary" value="<?= htmlspecialchars($raw['item_name']) ?>" data-price="<?= $raw['avg_unit_price'] ?>"><?= htmlspecialchars($raw['item_name']) ?> (৳<?= $raw['avg_unit_price'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-2 top-3 text-xs text-text-muted pointer-events-none"></i>
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
                            $displayCost = (float)$item['raw_price'] + (float)($item['additional_cost'] ?? 0);
                        }
                    ?>
                    Sell: <span class="text-accent font-bold">৳<?= $item['selling_price'] ?></span> | 
                    Total Cost: <span class="text-orange-400 font-bold">৳<?= number_format($displayCost, 2, '.', '') ?></span> | 
                    Add. Cost: ৳<?= $item['additional_cost'] ?? 0 ?>
                    <?php if (!empty($item['linked_raw_item'])): ?>
                    <br><span class="text-blue-400"><i class="fas fa-link"></i> <?= htmlspecialchars($item['linked_raw_item']) ?></span>
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

    const calcTotalCost = () => {
        const sel = document.getElementById('it_linked_raw');
        const opt = sel.options[sel.selectedIndex];
        const rawPrice = opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0;
        const addCost = parseFloat(document.getElementById('it_additional_cost').value) || 0;
        document.getElementById('it_cost').value = (rawPrice + addCost).toFixed(2);
    };
    
    document.getElementById('it_linked_raw').addEventListener('change', calcTotalCost);
    document.getElementById('it_additional_cost').addEventListener('input', calcTotalCost);

    setupForm(
        'form-items', 
        'btn-submit-item', 
        '.btn-edit-item', 
        (item) => {
            document.getElementById('it_name').value = item.item_name;
            document.getElementById('it_name_bn').value = item.item_name_bn;
            document.getElementById('it_sell').value = item.selling_price;
            document.getElementById('it_cost').value = item.cost_price;
            document.getElementById('it_additional_cost').value = item.additional_cost || 0;
            document.getElementById('it_active').checked = item.is_active == 1;
            document.getElementById('it_linked_raw').value = item.linked_raw_item || '';
            calcTotalCost();
        },
        () => ({
            item_name: document.getElementById('it_name').value,
            item_name_bn: document.getElementById('it_name_bn').value,
            selling_price: parseFloat(document.getElementById('it_sell').value) || 0,
            cost_price: parseFloat(document.getElementById('it_cost').value) || 0,
            additional_cost: parseFloat(document.getElementById('it_additional_cost').value) || 0,
            is_active: document.getElementById('it_active').checked ? 1 : 0,
            linked_raw_item: document.getElementById('it_linked_raw').value.trim()
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
});
</script>
