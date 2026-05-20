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
    <button class="settings-tab flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold bg-card border-border text-text-muted border hover:text-text-primary transition-all" data-target="tab-fixed">Fixed Costs</button>
</div>

<!-- Tab: Menu Items -->
<div id="tab-items" class="settings-pane block space-y-3 stagger">
    <?php foreach ($items as $item): ?>
    <div class="bg-card border border-border rounded-xl p-3" data-entity="items" data-id="<?= $item['id'] ?>">
        <div class="grid grid-cols-2 gap-2 mb-2">
            <input type="text" data-field="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="English Name">
            <input type="text" data-field="item_name_bn" value="<?= htmlspecialchars($item['item_name_bn']) ?>" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="Bangla Name">
        </div>
        <div class="grid grid-cols-3 gap-2 mb-2">
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Sell Price</label>
                <input type="number" data-field="selling_price" value="<?= $item['selling_price'] ?>" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Cost Price</label>
                <input type="number" data-field="cost_price" value="<?= $item['cost_price'] ?>" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Min Stock</label>
                <input type="number" data-field="min_stock_threshold" value="<?= $item['min_stock_threshold'] ?>" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
        <div class="flex items-center justify-between mt-2">
            <label class="flex items-center gap-2 text-sm text-text-muted">
                <input type="checkbox" data-field="is_active" <?= $item['is_active'] ? 'checked' : '' ?> class="accent-accent"> Active
            </label>
            <button class="btn-save text-xs font-bold bg-accent/20 text-accent px-4 py-1.5 rounded-lg hover:bg-accent/30"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </div>
    <?php endforeach; ?>
    <button class="btn-add-new w-full py-3 bg-surface border border-dashed border-border rounded-xl text-text-muted text-sm font-bold hover:border-accent hover:text-accent transition-all" data-template="tpl-item">
        <i class="fas fa-plus mr-1"></i> Add New Item
    </button>
</div>

<!-- Tab: Raw Inventory -->
<div id="tab-raw" class="settings-pane hidden space-y-3 stagger">
    <?php foreach ($rawInventory as $raw): ?>
    <div class="bg-card border border-border rounded-xl p-3" data-entity="raw_inventory" data-id="<?= $raw['id'] ?>">
        <div class="grid grid-cols-2 gap-2 mb-2">
            <input type="text" data-field="item_name" value="<?= htmlspecialchars($raw['item_name']) ?>" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="English Name">
            <input type="text" data-field="item_name_bn" value="<?= htmlspecialchars($raw['item_name_bn']) ?>" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="Bangla Name">
        </div>
        <div class="grid grid-cols-3 gap-2 mb-2">
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Unit</label>
                <input type="text" data-field="unit" value="<?= $raw['unit'] ?>" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-center">
            </div>
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Avg Price</label>
                <input type="number" data-field="avg_unit_price" value="<?= $raw['avg_unit_price'] ?>" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Min Stock</label>
                <input type="number" data-field="min_stock_threshold" value="<?= $raw['min_stock_threshold'] ?>" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
        <div class="flex justify-end mt-2">
            <button class="btn-save text-xs font-bold bg-accent/20 text-accent px-4 py-1.5 rounded-lg hover:bg-accent/30"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </div>
    <?php endforeach; ?>
    <button class="btn-add-new w-full py-3 bg-surface border border-dashed border-border rounded-xl text-text-muted text-sm font-bold hover:border-accent hover:text-accent transition-all" data-template="tpl-raw">
        <i class="fas fa-plus mr-1"></i> Add Raw Item
    </button>
</div>

<!-- Tab: Fixed Costs -->
<div id="tab-fixed" class="settings-pane hidden space-y-3 stagger">
    <?php foreach ($fixedCosts as $fc): ?>
    <div class="bg-card border border-border rounded-xl p-3" data-entity="fixed_daily_costs" data-id="<?= $fc['id'] ?>">
        <div class="grid grid-cols-2 gap-2 mb-2">
            <input type="text" data-field="name" value="<?= htmlspecialchars($fc['name']) ?>" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="Name">
            <input type="number" data-field="daily_amount" value="<?= $fc['daily_amount'] ?>" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="Daily Amount (৳)">
        </div>
        <div class="flex items-center justify-between mt-2">
            <label class="flex items-center gap-2 text-sm text-text-muted">
                <input type="checkbox" data-field="is_active" <?= $fc['is_active'] ? 'checked' : '' ?> class="accent-accent"> Active
            </label>
            <button class="btn-save text-xs font-bold bg-accent/20 text-accent px-4 py-1.5 rounded-lg hover:bg-accent/30"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </div>
    <?php endforeach; ?>
    <button class="btn-add-new w-full py-3 bg-surface border border-dashed border-border rounded-xl text-text-muted text-sm font-bold hover:border-accent hover:text-accent transition-all" data-template="tpl-fixed">
        <i class="fas fa-plus mr-1"></i> Add Fixed Cost
    </button>
</div>

<!-- Templates for new items -->
<template id="tpl-item">
    <div class="bg-card border border-accent/50 rounded-xl p-3 shadow-[0_0_15px_rgba(244,63,94,0.1)]" data-entity="items" data-id="0">
        <div class="grid grid-cols-2 gap-2 mb-2">
            <input type="text" data-field="item_name" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="English Name">
            <input type="text" data-field="item_name_bn" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="Bangla Name">
        </div>
        <div class="grid grid-cols-3 gap-2 mb-2">
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Sell Price</label>
                <input type="number" data-field="selling_price" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm" value="0">
            </div>
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Cost Price</label>
                <input type="number" data-field="cost_price" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm" value="0">
            </div>
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Min Stock</label>
                <input type="number" data-field="min_stock_threshold" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm" value="0">
            </div>
        </div>
        <div class="flex items-center justify-between mt-2">
            <label class="flex items-center gap-2 text-sm text-text-muted">
                <input type="checkbox" data-field="is_active" checked class="accent-accent"> Active
            </label>
            <button class="btn-save text-xs font-bold bg-accent text-white px-4 py-1.5 rounded-lg hover:bg-accent-light"><i class="fas fa-save mr-1"></i> Save New</button>
        </div>
    </div>
</template>

<template id="tpl-raw">
    <div class="bg-card border border-accent/50 rounded-xl p-3 shadow-[0_0_15px_rgba(244,63,94,0.1)]" data-entity="raw_inventory" data-id="0">
        <div class="grid grid-cols-2 gap-2 mb-2">
            <input type="text" data-field="item_name" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="English Name">
            <input type="text" data-field="item_name_bn" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="Bangla Name">
        </div>
        <div class="grid grid-cols-3 gap-2 mb-2">
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Unit</label>
                <input type="text" data-field="unit" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-center" value="kg">
            </div>
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Avg Price</label>
                <input type="number" data-field="avg_unit_price" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm" value="0">
            </div>
            <div>
                <label class="block text-[0.6rem] text-text-muted font-bold uppercase mb-1">Min Stock</label>
                <input type="number" data-field="min_stock_threshold" class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm" value="0">
            </div>
        </div>
        <div class="flex justify-end mt-2">
            <button class="btn-save text-xs font-bold bg-accent text-white px-4 py-1.5 rounded-lg hover:bg-accent-light"><i class="fas fa-save mr-1"></i> Save New</button>
        </div>
    </div>
</template>

<template id="tpl-fixed">
    <div class="bg-card border border-accent/50 rounded-xl p-3 shadow-[0_0_15px_rgba(244,63,94,0.1)]" data-entity="fixed_daily_costs" data-id="0">
        <div class="grid grid-cols-2 gap-2 mb-2">
            <input type="text" data-field="name" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="Name">
            <input type="number" data-field="daily_amount" class="bg-surface border border-border rounded-lg px-3 py-2 text-sm" placeholder="Daily Amount (৳)" value="0">
        </div>
        <div class="flex items-center justify-between mt-2">
            <label class="flex items-center gap-2 text-sm text-text-muted">
                <input type="checkbox" data-field="is_active" checked class="accent-accent"> Active
            </label>
            <button class="btn-save text-xs font-bold bg-accent text-white px-4 py-1.5 rounded-lg hover:bg-accent-light"><i class="fas fa-save mr-1"></i> Save New</button>
        </div>
    </div>
</template>

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

    // Add New logic
    document.querySelectorAll('.btn-add-new').forEach(btn => {
        btn.addEventListener('click', () => {
            const tplId = btn.dataset.template;
            const tpl = document.getElementById(tplId);
            const clone = tpl.content.cloneNode(true);
            btn.parentElement.insertBefore(clone, btn);
        });
    });

    // Save Event Delegation
    document.body.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-save');
        if (!btn) return;

        const container = btn.closest('[data-entity]');
        const entity = container.dataset.entity;
        const id = container.dataset.id;
        
        const fields = {};
        container.querySelectorAll('[data-field]').forEach(input => {
            if (input.type === 'checkbox') {
                fields[input.dataset.field] = input.checked ? 1 : 0;
            } else {
                const val = input.value;
                fields[input.dataset.field] = input.type === 'number' ? (val === '' ? 0 : parseFloat(val)) : val;
            }
        });

        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const res = await apiPost('?url=admin/saveEntity', { entity, id, fields });
        
        if (res.success) {
            showToast('Saved successfully!', 'success');
            if (id === "0") {
                // Update ID for new items so subsequent saves act as updates
                container.dataset.id = res.id;
                container.classList.remove('border-accent/50', 'shadow-[0_0_15px_rgba(244,63,94,0.1)]');
                container.classList.add('border-border');
                btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save';
                btn.className = 'btn-save text-xs font-bold bg-accent/20 text-accent px-4 py-1.5 rounded-lg hover:bg-accent/30';
            } else {
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Saved';
                setTimeout(() => btn.innerHTML = originalHtml, 2000);
            }
        } else {
            showToast(res.error || 'Failed to save', 'error');
            btn.innerHTML = originalHtml;
        }
        btn.disabled = false;
    });
});
</script>
