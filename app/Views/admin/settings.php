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
        <form id="form-items" data-id="0" class="space-y-3">
            <div class="grid grid-cols-2 gap-2">
                <div class="relative">
                    <input type="text" id="it_name" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="English Name">
                    <label for="it_name" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">English Name</label>
                </div>
                <div class="relative">
                    <input type="text" id="it_name_bn" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Bangla Name">
                    <label for="it_name_bn" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Bangla Name</label>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 pt-2">
                <div class="relative">
                    <input type="number" id="it_sell" step="0.01" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Sell Price">
                    <label for="it_sell" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Sell Price</label>
                </div>
                <div class="relative">
                    <input type="number" id="it_cost" step="0.01" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Cost Price">
                    <label for="it_cost" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Cost Price</label>
                </div>
                <div class="relative">
                    <input type="number" id="it_min" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Min Stock">
                    <label for="it_min" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Min Stock</label>
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
                    Sell: <span class="text-accent font-bold">৳<?= $item['selling_price'] ?></span> | 
                    Cost: <span class="text-orange-400 font-bold">৳<?= $item['cost_price'] ?></span> | 
                    Min: <?= $item['min_stock_threshold'] ?>
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
        <form id="form-raw" data-id="0" class="space-y-3">
            <div class="grid grid-cols-2 gap-2">
                <div class="relative">
                    <input type="text" id="raw_name" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="English Name">
                    <label for="raw_name" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">English Name</label>
                </div>
                <div class="relative">
                    <input type="text" id="raw_name_bn" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Bangla Name">
                    <label for="raw_name_bn" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Bangla Name</label>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 pt-2">
                <div class="relative">
                    <input type="text" id="raw_unit" required class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="Unit">
                    <label for="raw_unit" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent">Unit</label>
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
            <div class="pt-2">
                <div class="relative">
                    <input type="number" id="raw_qty" step="0.01" class="peer w-full bg-transparent border-b border-border focus:border-accent py-2 px-1 text-sm text-text-primary transition-colors focus:outline-none placeholder-transparent" placeholder="<?= __('opening_current_stock') ?>">
                    <label for="raw_qty" class="absolute left-1 -top-3.5 text-xs text-text-muted transition-all peer-placeholder-shown:text-sm peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-xs peer-focus:text-accent"><?= __('opening_current_stock') ?></label>
                </div>
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
            <button class="btn-edit-raw p-2 text-text-muted hover:text-accent transition-colors" data-item='<?= htmlspecialchars(json_encode($raw), ENT_QUOTES, 'UTF-8') ?>'>
                <i class="fas fa-pencil-alt"></i>
            </button>
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

    setupForm(
        'form-items', 
        'btn-submit-item', 
        '.btn-edit-item', 
        (item) => {
            document.getElementById('it_name').value = item.item_name;
            document.getElementById('it_name_bn').value = item.item_name_bn;
            document.getElementById('it_sell').value = item.selling_price;
            document.getElementById('it_cost').value = item.cost_price;
            document.getElementById('it_min').value = item.min_stock_threshold;
            document.getElementById('it_active').checked = item.is_active == 1;
        },
        () => ({
            item_name: document.getElementById('it_name').value,
            item_name_bn: document.getElementById('it_name_bn').value,
            selling_price: parseFloat(document.getElementById('it_sell').value) || 0,
            cost_price: parseFloat(document.getElementById('it_cost').value) || 0,
            min_stock_threshold: parseFloat(document.getElementById('it_min').value) || 0,
            is_active: document.getElementById('it_active').checked ? 1 : 0
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
});
</script>
