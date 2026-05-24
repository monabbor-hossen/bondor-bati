<?php
/**
 * Online Delivery Platforms — Sales Ledger (Item-Level)
 * Variables: $balances, $logs, $menuItems
 */
$platformMeta = [
    'Foodpanda' => ['icon'=>'fa-utensils',  'ring'=>'ring-pink-500/30',   'bg'=>'bg-pink-500/10',   'text'=>'text-pink-400',   'border'=>'border-pink-500/30'],
    'Pathao'    => ['icon'=>'fa-motorcycle','ring'=>'ring-red-500/30',    'bg'=>'bg-red-500/10',    'text'=>'text-red-400',    'border'=>'border-red-500/30'],
    'Foodi'     => ['icon'=>'fa-box-open',  'ring'=>'ring-orange-500/30', 'bg'=>'bg-orange-500/10', 'text'=>'text-orange-400', 'border'=>'border-orange-500/30'],
];
$today = date('Y-m-d');
// Build JS menu item map: { "Item Name": price, ... }
$menuMap = [];
foreach ($menuItems as $mi) { $menuMap[$mi['name']] = (float)$mi['price']; }
?>

<!-- Page Title -->
<div class="mb-5 animate-slideUp flex items-center gap-3">
    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center">
        <i class="fas fa-motorcycle text-indigo-400"></i>
    </div>
    <div>
        <h2 class="text-xl font-black tracking-tight"><?= __('online_platforms') ?></h2>
        <p class="text-[0.65rem] text-text-muted uppercase tracking-widest">Foodpanda · Pathao · Foodi</p>
    </div>
</div>

<!-- 1. Balance Cards -->
<div class="grid grid-cols-3 gap-2 mb-6 stagger">
    <?php foreach ($balances as $row):
        $p = $row['platform'];
        $meta = $platformMeta[$p] ?? ['icon'=>'fa-circle','bg'=>'bg-surface','text'=>'text-info','border'=>'border-border','ring'=>'ring-border'];
        $bal = (float)$row['balance'];
    ?>
    <div class="rounded-2xl p-3 border <?= $meta['border'] ?> <?= $meta['bg'] ?> flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <div class="w-7 h-7 rounded-lg <?= $meta['bg'] ?> border <?= $meta['border'] ?> flex items-center justify-center">
                <i class="fas <?= $meta['icon'] ?> <?= $meta['text'] ?> text-xs"></i>
            </div>
            <?php if ($bal > 0): ?>
                <span class="text-[0.5rem] font-black uppercase text-emerald-400 bg-emerald-500/10 px-1 py-0.5 rounded-full leading-none">Due</span>
            <?php else: ?>
                <span class="text-[0.5rem] font-black uppercase text-text-muted bg-surface px-1 py-0.5 rounded-full border border-border leading-none">Clear</span>
            <?php endif; ?>
        </div>
        <div>
            <div class="text-[0.55rem] font-bold text-text-muted uppercase tracking-wide truncate"><?= htmlspecialchars($p) ?></div>
            <div class="text-sm font-black <?= $bal > 0 ? $meta['text'] : 'text-text-muted' ?> leading-tight">৳<?= number_format(abs($bal), 0) ?></div>
            <div class="text-[0.5rem] text-text-muted leading-tight"><?= __('platform_balance') ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- 2. Action Tabs -->
<div class="glass rounded-2xl mb-6 overflow-hidden border border-border/50 animate-slideUp" style="animation-delay:0.08s">

    <div class="flex border-b border-border/50">
        <button id="tab-sale" onclick="switchTab('sale')"
                class="tab-btn flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold uppercase tracking-widest transition-colors duration-200 text-indigo-400 border-b-2 border-indigo-500">
            <i class="fas fa-receipt text-sm"></i> <?= __('log_online_sale') ?>
        </button>
        <button id="tab-payout" onclick="switchTab('payout')"
                class="tab-btn flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold uppercase tracking-widest transition-colors duration-200 text-text-muted border-b-2 border-transparent hover:text-text-primary">
            <i class="fas fa-hand-holding-dollar text-sm"></i> <?= __('log_payout') ?>
        </button>
    </div>

    <!-- Sale Form -->
    <div id="panel-sale" class="p-4">
        <form id="saleForm">
        <input type="hidden" id="edit-log-id" value="0">
        <!-- Edit mode banner -->
        <div id="sale-edit-banner" class="hidden flex items-center justify-between bg-amber-500/10 border border-amber-500/30 rounded-xl px-3 py-2 mb-3">
            <span class="text-xs font-bold text-amber-400"><i class="fas fa-pen mr-1"></i>Editing entry</span>
            <button type="button" onclick="resetSaleForm()" class="text-xs font-bold text-text-muted hover:text-accent"><i class="fas fa-times mr-1"></i>New</button>
        </div>

            <!-- Platform + Date row -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5">Platform</label>
                    <div class="relative">
                        <select id="sale-platform" class="w-full bg-surface border border-border rounded-xl px-3 py-2.5 text-sm font-semibold text-text-primary appearance-none focus:border-indigo-500 transition-colors pr-7">
                            <option value="Foodpanda">🍔 Foodpanda</option>
                            <option value="Pathao">🏍️ Pathao</option>
                            <option value="Foodi">📦 Foodi</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-text-muted text-xs pointer-events-none"></i>
                    </div>
                </div>
                <div>
                    <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('date') ?></label>
                    <input type="date" id="sale-date" value="<?= $today ?>"
                           class="w-full bg-surface border border-border rounded-xl px-3 py-2.5 text-sm font-semibold text-text-primary focus:border-indigo-500 transition-colors">
                </div>
            </div>

            <!-- Item Repeater -->
            <div class="mb-3">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest"><?= __('item') ?></label>
                    <button type="button" onclick="addOnlineItemRow()"
                            class="flex items-center gap-1.5 text-xs font-bold text-indigo-400 border border-indigo-500/40 bg-indigo-500/10 px-2.5 py-1 rounded-lg hover:bg-indigo-500/20 transition-all">
                        <i class="fas fa-plus text-[0.65rem]"></i> <?= __('add_item') ?>
                    </button>
                </div>

                <!-- Header row -->
                <div class="grid grid-cols-12 gap-1 mb-1 px-1">
                    <span class="col-span-5 text-[0.55rem] font-bold text-text-muted uppercase tracking-wide"><?= __('item') ?></span>
                    <span class="col-span-2 text-[0.55rem] font-bold text-text-muted uppercase tracking-wide text-center"><?= __('qty') ?></span>
                    <span class="col-span-3 text-[0.55rem] font-bold text-text-muted uppercase tracking-wide text-right"><?= __('unit_price') ?></span>
                    <span class="col-span-2 text-[0.55rem] font-bold text-text-muted uppercase tracking-wide text-right pr-1"><?= __('total') ?></span>
                </div>

                <div id="onlineItemsContainer" class="space-y-2"></div>

                <!-- Empty state hint -->
                <div id="itemsEmptyHint" class="text-center py-4 text-text-muted border border-dashed border-border/50 rounded-xl">
                    <i class="fas fa-layer-group text-xl mb-1 block opacity-30"></i>
                    <span class="text-xs"><?= __('add_item') ?></span>
                </div>
            </div>

            <!-- Gross auto-total -->
            <div class="flex items-center justify-between bg-surface border border-border/50 rounded-xl px-4 py-2.5 mb-3">
                <span class="text-xs font-bold text-text-muted uppercase tracking-widest"><?= __('gross_sales') ?></span>
                <span id="autoGrossAmt" class="text-base font-black text-text-primary">৳0</span>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <!-- Commission -->
                <div>
                    <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('platform_commission') ?></label>
                    <div class="flex bg-surface border border-border rounded-xl overflow-hidden focus-within:border-red-500 transition-colors">
                        <input type="number" id="sale-commission" min="0" step="any" inputmode="decimal" placeholder="0"
                               class="w-full bg-transparent px-3 py-2.5 text-base font-black text-red-400 focus:outline-none text-right">
                        <select id="sale-commission-type" class="bg-surface border-l border-border px-2 text-xs font-bold text-text-muted focus:outline-none appearance-none cursor-pointer">
                            <option value="flat">৳</option>
                            <option value="percent">%</option>
                        </select>
                    </div>
                </div>
                <!-- Discount -->
                <div>
                    <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('discount') ?></label>
                    <div class="flex bg-surface border border-border rounded-xl overflow-hidden focus-within:border-orange-500 transition-colors">
                        <input type="number" id="sale-discount" min="0" step="any" inputmode="decimal" placeholder="0"
                               class="w-full bg-transparent px-3 py-2.5 text-base font-black text-orange-400 focus:outline-none text-right">
                        <select id="sale-discount-type" class="bg-surface border-l border-border px-2 text-xs font-bold text-text-muted focus:outline-none appearance-none cursor-pointer">
                            <option value="flat">৳</option>
                            <option value="percent">%</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Net Receivable -->
            <div class="flex items-center justify-between bg-indigo-500/10 border border-indigo-500/20 rounded-xl px-4 py-3 mb-4">
                <span class="text-xs font-bold text-text-muted uppercase tracking-widest"><?= __('net_receivable') ?></span>
                <span id="net-display" class="text-lg font-black text-indigo-400">৳0</span>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-500/40 font-black py-3.5 rounded-xl transition-all duration-200 text-sm uppercase tracking-widest flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> <?= __('save') ?>
            </button>
        </form>

        <!-- Sales History -->
        <div class="mt-8">
            <h3 class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3 flex items-center gap-2">
                <i class="fas fa-scroll text-info text-xs"></i> Sales History
            </h3>
            <?php if (empty($salesLogs)): ?>
            <div class="text-center py-10 text-text-muted">
                <i class="fas fa-inbox text-3xl mb-3 block opacity-30"></i>
                <span class="text-sm"><?= __('no_data') ?></span>
            </div>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($salesLogs as $log):
                    $p    = $log['platform'] ?? 'Unknown';
                    $meta = $platformMeta[$p] ?? ['icon'=>'fa-circle','text'=>'text-info','bg'=>'bg-surface','border'=>'border-border'];
                    $dateLabel = date('d M', strtotime($log['entry_date']));
                    $hasItems = !empty($log['items']);
                    $entryData = ['log_id'=>$log['log_id'],'platform'=>$p,'date'=>$log['entry_date'],'commission'=>$log['commission_amount'],'discount'=>$log['discount_amount'],'items'=>$log['items']];
                    $entryJson = htmlspecialchars(json_encode($entryData, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                ?>
                <details class="group bg-card border border-border/40 rounded-xl overflow-hidden">
                    <summary class="flex items-center gap-2 py-2.5 px-3 cursor-pointer list-none">
                        <!-- Platform icon -->
                        <div class="w-7 h-7 rounded-lg <?= $meta['bg'] ?> border <?= $meta['border'] ?> flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-receipt <?= $meta['text'] ?> text-[0.6rem]"></i>
                        </div>

                        <!-- Info block -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1 flex-wrap">
                                <span class="text-xs font-bold <?= $meta['text'] ?> truncate"><?= htmlspecialchars($p) ?></span>
                                <span class="text-[0.5rem] font-semibold uppercase px-1 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400">
                                    <?= __('log_online_sale') ?>
                                </span>
                            </div>
                            <div class="text-[0.55rem] text-text-muted"><?= $dateLabel ?><?= $hasItems ? ' · '.count($log['items']).' items' : '' ?></div>
                        </div>

                        <!-- Amount -->
                        <div class="text-right flex-shrink-0">
                            <div class="text-xs font-black text-text-primary">৳<?= number_format((float)$log['net_amount'], 0) ?></div>
                            <div class="text-[0.5rem] text-text-muted">
                                Gross ৳<?= number_format((float)$log['gross_amount'], 0) ?>
                                <?php if ($log['discount_amount'] > 0): ?> · Disc ৳<?= number_format((float)$log['discount_amount'], 0) ?><?php endif; ?>
                            </div>
                        </div>

                        <!-- Edit / Delete -->
                        <div class="flex items-center gap-0.5 flex-shrink-0 border-l border-border/40 pl-2 ml-1" onclick="event.stopPropagation(); event.preventDefault();">
                            <button type="button"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-indigo-500/10 text-text-muted hover:text-indigo-400 transition-all"
                                    onclick="onlineEditEntry('sale', <?= $entryJson ?>)">
                                <i class="fas fa-pen text-[0.6rem]"></i>
                            </button>
                            <button type="button"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-red-500/10 text-text-muted hover:text-red-400 transition-all"
                                    onclick="onlineDeleteEntry('sale', <?= $log['log_id'] ?>)">
                                <i class="fas fa-trash text-[0.6rem]"></i>
                            </button>
                        </div>

                        <?php if ($hasItems): ?>
                            <i class="fas fa-chevron-down text-text-muted text-[0.6rem] transition-transform duration-200 group-open:rotate-180 flex-shrink-0"></i>
                        <?php endif; ?>
                    </summary>
                    <?php if ($hasItems): ?>
                    <div class="px-3.5 pb-3 border-t border-border/30">
                        <div class="mt-2 space-y-1">
                            <?php foreach ($log['items'] as $itm): ?>
                            <div class="grid grid-cols-12 gap-1 text-xs py-1 border-b border-border/20 last:border-0">
                                <span class="col-span-6 text-text-muted truncate"><?= htmlspecialchars($itm['item_name']) ?></span>
                                <span class="col-span-2 text-center text-text-muted"><?= (float)$itm['qty'] ?></span>
                                <span class="col-span-2 text-right text-text-muted">৳<?= number_format((float)$itm['unit_price'], 0) ?></span>
                                <span class="col-span-2 text-right font-bold text-text-primary">৳<?= number_format((float)$itm['total_price'], 0) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </details>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payout Form -->
    <div id="panel-payout" class="p-4 hidden">
        <form id="payoutForm">
        <input type="hidden" id="edit-payout-id" value="0">
        <div id="payout-edit-banner" class="hidden flex items-center justify-between bg-amber-500/10 border border-amber-500/30 rounded-xl px-3 py-2 mb-3">
            <span class="text-xs font-bold text-amber-400"><i class="fas fa-pen mr-1"></i>Editing payout</span>
            <button type="button" onclick="resetPayoutForm()" class="text-xs font-bold text-text-muted hover:text-accent"><i class="fas fa-times mr-1"></i>New</button>
        </div>
            <div class="mb-4">
                <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5">Platform</label>
                <div class="relative">
                    <select id="payout-platform" class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-sm font-semibold text-text-primary appearance-none focus:border-emerald-500 transition-colors pr-8">
                        <option value="Foodpanda">🍔 Foodpanda</option>
                        <option value="Pathao">🏍️ Pathao</option>
                        <option value="Foodi">📦 Foodi</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-text-muted text-xs pointer-events-none"></i>
                </div>
                <div id="payout-hint" class="mt-2 text-[0.6rem] font-bold text-text-muted px-1 flex items-center justify-between">
                    <span>Last payout: <span id="payout-hint-days" class="text-text-primary">Unknown</span></span>
                    <span>Pending: <span id="payout-hint-due" class="text-emerald-400 font-black">৳0</span></span>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('date') ?></label>
                <input type="date" id="payout-date" value="<?= $today ?>"
                       class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-sm font-semibold text-text-primary focus:border-emerald-500 transition-colors">
            </div>
            <div class="mb-4">
                <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('payout_amount') ?> (৳)</label>
                <input type="number" id="payout-amount" min="1" step="1" inputmode="decimal" placeholder="0"
                       class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-2xl font-black text-emerald-400 focus:border-emerald-500 transition-colors text-right">
            </div>
            <button type="submit"
                    class="w-full bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 font-black py-3.5 rounded-xl transition-all duration-200 text-sm uppercase tracking-widest flex items-center justify-center gap-2">
                <i class="fas fa-check-circle"></i> <?= __('log_payout') ?>
            </button>
        </form>

        <!-- Payout History -->
        <div class="mt-8">
            <h3 class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3 flex items-center gap-2">
                <i class="fas fa-scroll text-info text-xs"></i> Payout History
            </h3>
            <?php if (empty($payoutLogs)): ?>
            <div class="text-center py-10 text-text-muted">
                <i class="fas fa-inbox text-3xl mb-3 block opacity-30"></i>
                <span class="text-sm"><?= __('no_data') ?></span>
            </div>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($payoutLogs as $log):
                    $p    = $log['platform'] ?? 'Unknown';
                    $meta = $platformMeta[$p] ?? ['icon'=>'fa-circle','text'=>'text-info','bg'=>'bg-surface','border'=>'border-border'];
                    $dateLabel = date('d M', strtotime($log['entry_date']));
                    $entryData = ['payout_id'=>$log['payout_id'],'platform'=>$p,'date'=>$log['entry_date'],'amount'=>$log['payout_amount']];
                    $entryJson = htmlspecialchars(json_encode($entryData, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                ?>
                <div class="bg-card border border-border/40 rounded-xl overflow-hidden flex items-center gap-2 py-2.5 px-3">
                    <!-- Platform icon -->
                    <div class="w-7 h-7 rounded-lg <?= $meta['bg'] ?> border <?= $meta['border'] ?> flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-money-bill-transfer <?= $meta['text'] ?> text-[0.6rem]"></i>
                    </div>

                    <!-- Info block -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1 flex-wrap">
                            <span class="text-xs font-bold <?= $meta['text'] ?> truncate"><?= htmlspecialchars($p) ?></span>
                            <span class="text-[0.5rem] font-semibold uppercase px-1 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">
                                <?= __('log_payout') ?>
                            </span>
                        </div>
                        <div class="text-[0.55rem] text-text-muted"><?= $dateLabel ?></div>
                    </div>

                    <!-- Amount -->
                    <div class="text-right flex-shrink-0">
                        <div class="text-xs font-black text-emerald-400">−৳<?= number_format((float)$log['payout_amount'], 0) ?></div>
                        <div class="text-[0.5rem] text-text-muted"><?= __('payout_amount') ?></div>
                    </div>

                    <!-- Edit / Delete -->
                    <div class="flex items-center gap-0.5 flex-shrink-0 border-l border-border/40 pl-2 ml-1">
                        <button type="button"
                                class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-indigo-500/10 text-text-muted hover:text-indigo-400 transition-all"
                                onclick="onlineEditEntry('payout', <?= $entryJson ?>)">
                            <i class="fas fa-pen text-[0.6rem]"></i>
                        </button>
                        <button type="button"
                                class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-red-500/10 text-text-muted hover:text-red-400 transition-all"
                                onclick="onlineDeleteEntry('payout', <?= $log['payout_id'] ?>)">
                            <i class="fas fa-trash text-[0.6rem]"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JS -->
<script>
(function () {
    // ── Menu item price map (PHP → JS) ───────────────────────
    const MENU_PRICES = <?= json_encode($menuMap, JSON_UNESCAPED_UNICODE) ?>;
    const MENU_NAMES  = Object.keys(MENU_PRICES).sort();
    const PLATFORM_STATS = <?= json_encode($balances) ?>;

    // ── Payout Hint Logic ─────────────────────────────────────
    function updatePayoutHint() {
        const p = document.getElementById('payout-platform').value;
        const stat = PLATFORM_STATS.find(s => s.platform === p);
        if (stat) {
            const bal = parseFloat(stat.balance) || 0;
            document.getElementById('payout-hint-due').textContent = '৳' + bal.toLocaleString('en-IN');
            
            if (stat.last_payout_date) {
                // Ensure correct date parsing without timezone offset shifting it to yesterday
                const payoutDate = new Date(stat.last_payout_date + 'T00:00:00');
                const today = new Date();
                today.setHours(0,0,0,0);
                const diffTime = today - payoutDate;
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                document.getElementById('payout-hint-days').textContent = diffDays === 0 ? 'Today' : diffDays + ' days ago';
            } else {
                document.getElementById('payout-hint-days').textContent = 'Never';
            }
        }
    }
    document.getElementById('payout-platform').addEventListener('change', updatePayoutHint);
    updatePayoutHint();

    let rowCount = 0;

    // ── Tab switcher ──────────────────────────────────────────
    function switchTab(tab) {
        ['sale','payout'].forEach(t => {
            const btn   = document.getElementById('tab-' + t);
            const panel = document.getElementById('panel-' + t);
            const active = t === tab;
            btn.classList.toggle('text-indigo-400', active);
            btn.classList.toggle('border-indigo-500', active);
            btn.classList.toggle('text-text-muted', !active);
            btn.classList.toggle('border-transparent', !active);
            panel.classList.toggle('hidden', !active);
        });
    }
    window.switchTab = switchTab;

    // ── Add item row ──────────────────────────────────────────
    function addOnlineItemRow(name = '', qty = 1, price = '') {
        rowCount++;
        const id = rowCount;
        document.getElementById('itemsEmptyHint').classList.add('hidden');

        const row = document.createElement('div');
        row.id = 'item-row-' + id;
        row.className = 'grid grid-cols-12 gap-1 items-center bg-surface border border-border/50 rounded-xl px-2 py-2';

        // Build datalist options
        const dlId = 'dl-' + id;
        const opts = MENU_NAMES.map(n => `<option value="${n.replace(/"/g,'&quot;')}">`).join('');

        row.innerHTML = `
            <datalist id="${dlId}">${opts}</datalist>
            <input list="${dlId}" placeholder="<?= __('item') ?>..."
                   class="item-name col-span-5 bg-transparent border-b border-border text-xs text-text-primary py-1 px-0.5 focus:border-indigo-400 focus:outline-none placeholder-text-muted/50 truncate"
                   value="${name.replace(/"/g,'&quot;')}" autocomplete="off">
            <input type="number" min="0.5" step="0.5" inputmode="decimal" placeholder="1"
                   class="item-qty col-span-2 bg-transparent border-b border-border text-xs text-text-primary py-1 px-0.5 text-center focus:border-indigo-400 focus:outline-none"
                   value="${qty}">
            <input type="number" min="0" step="1" inputmode="decimal" placeholder="0"
                   class="item-price col-span-3 bg-transparent border-b border-border text-xs text-text-primary py-1 px-0.5 text-right focus:border-indigo-400 focus:outline-none"
                   value="${price}">
            <span class="item-total col-span-1 text-[0.65rem] font-black text-indigo-400 text-right pr-0.5">0</span>
            <button type="button" onclick="removeOnlineItemRow(${id})"
                    class="col-span-1 flex items-center justify-center text-text-muted hover:text-red-400 transition-colors">
                <i class="fas fa-times text-xs"></i>
            </button>
        `;

        document.getElementById('onlineItemsContainer').appendChild(row);

        // Auto-fill price when name matches menu
        row.querySelector('.item-name').addEventListener('change', function () {
            const p = MENU_PRICES[this.value];
            if (p !== undefined) row.querySelector('.item-price').value = p;
            calculateOnlineTotals();
        });
        row.querySelector('.item-qty').addEventListener('input', calculateOnlineTotals);
        row.querySelector('.item-price').addEventListener('input', calculateOnlineTotals);

        calculateOnlineTotals();
    }
    window.addOnlineItemRow = addOnlineItemRow;

    // ── Remove row ────────────────────────────────────────────
    function removeOnlineItemRow(id) {
        const el = document.getElementById('item-row-' + id);
        if (el) el.remove();
        if (!document.getElementById('onlineItemsContainer').children.length) {
            document.getElementById('itemsEmptyHint').classList.remove('hidden');
        }
        calculateOnlineTotals();
    }
    window.removeOnlineItemRow = removeOnlineItemRow;

    // ── Calculate totals ──────────────────────────────────────
    function calculateOnlineTotals() {
        let gross = 0;
        document.querySelectorAll('#onlineItemsContainer > div').forEach(row => {
            const qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const total = qty * price;
            row.querySelector('.item-total').textContent = total > 0 ? '৳' + Math.round(total) : '0';
            gross += total;
        });
        gross = Math.round(gross);

        const commIn = parseFloat(document.getElementById('sale-commission').value) || 0;
        const commType = document.getElementById('sale-commission-type').value;
        const comm = Math.round(commType === 'percent' ? (gross * commIn) / 100 : commIn);

        const discIn = parseFloat(document.getElementById('sale-discount').value) || 0;
        const discType = document.getElementById('sale-discount-type').value;
        const disc = Math.round(discType === 'percent' ? (gross * discIn) / 100 : discIn);

        document.getElementById('sale-commission').dataset.calculated = comm;
        document.getElementById('sale-discount').dataset.calculated = disc;

        const net  = gross - comm - disc;

        document.getElementById('autoGrossAmt').textContent = '৳' + gross.toLocaleString('en-IN');
        const nd = document.getElementById('net-display');
        nd.textContent = '৳' + Math.max(0, net).toLocaleString('en-IN');
        nd.classList.toggle('text-red-400',    net < 0);
        nd.classList.toggle('text-indigo-400', net >= 0);

        // Save preferences if not in edit mode
        if (document.getElementById('edit-log-id').value === '0') {
            const p = document.getElementById('sale-platform').value;
            localStorage.setItem('online_prefs_' + p, JSON.stringify({
                cv: commIn, ct: commType, dv: discIn, dt: discType
            }));
        }
    }
    window.calculateOnlineTotals = calculateOnlineTotals;

    document.getElementById('sale-commission').addEventListener('input', calculateOnlineTotals);
    document.getElementById('sale-commission-type').addEventListener('change', calculateOnlineTotals);
    document.getElementById('sale-discount').addEventListener('input', calculateOnlineTotals);
    document.getElementById('sale-discount-type').addEventListener('change', calculateOnlineTotals);

    // ── Platform preferences loader ───────────────────────────
    function loadPlatformPreferences() {
        if (document.getElementById('edit-log-id').value !== '0') return;
        const p = document.getElementById('sale-platform').value;
        try {
            const prefs = JSON.parse(localStorage.getItem('online_prefs_' + p));
            if (prefs) {
                document.getElementById('sale-commission').value = prefs.cv ? prefs.cv : '';
                document.getElementById('sale-commission-type').value = prefs.ct || 'flat';
                document.getElementById('sale-discount').value = prefs.dv ? prefs.dv : '';
                document.getElementById('sale-discount-type').value = prefs.dt || 'flat';
            } else {
                document.getElementById('sale-commission').value = '';
                document.getElementById('sale-commission-type').value = 'flat';
                document.getElementById('sale-discount').value = '';
                document.getElementById('sale-discount-type').value = 'flat';
            }
        } catch(e) {}
        calculateOnlineTotals();
    }
    document.getElementById('sale-platform').addEventListener('change', loadPlatformPreferences);

    // Init preferences FIRST, then add row
    loadPlatformPreferences();
    addOnlineItemRow();

    // ── Sale Form submit ──────────────────────────────────────
    document.getElementById('saleForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn  = e.target.querySelector('button[type="submit"]');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const items = [];
        document.querySelectorAll('#onlineItemsContainer > div').forEach(row => {
            const name  = row.querySelector('.item-name').value.trim();
            const qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            if (name && qty > 0) items.push({ item_name: name, qty, unit_price: price, total_price: Math.round(qty * price * 100) / 100 });
        });

        if (!items.length) {
            showToast('<?= __("add_item") ?>', 'warning');
            btn.innerHTML = orig; btn.disabled = false; return;
        }

        const res = await apiPost('?url=onlineSales/logDailySale', {
            log_id:            parseInt(document.getElementById('edit-log-id').value) || 0,
            platform:          document.getElementById('sale-platform').value,
            log_date:          document.getElementById('sale-date').value,
            commission_amount: parseFloat(document.getElementById('sale-commission').dataset.calculated) || 0,
            discount_amount:   parseFloat(document.getElementById('sale-discount').dataset.calculated) || 0,
            items
        });

        if (res.success) {
            showToast('<?= __("success") ?>', 'success');
            setTimeout(() => window.location.reload(), 900);
        } else {
            showToast(res.error || '<?= __("error") ?>', 'error');
            btn.innerHTML = orig; btn.disabled = false;
        }
    });

    // ── Payout Form submit ────────────────────────────────────
    document.getElementById('payoutForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn  = e.target.querySelector('button[type="submit"]');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const res = await apiPost('?url=onlineSales/logPayout', {
            payout_id:   parseInt(document.getElementById('edit-payout-id').value) || 0,
            platform:    document.getElementById('payout-platform').value,
            payout_date: document.getElementById('payout-date').value,
            amount:      parseFloat(document.getElementById('payout-amount').value) || 0
        });

        if (res.success) {
            showToast('<?= __("success") ?>', 'success');
            setTimeout(() => window.location.reload(), 900);
        } else {
            showToast(res.error || '<?= __("error") ?>', 'error');
            btn.innerHTML = orig; btn.disabled = false;
        }
    });

    // ── Reset sale form to new-entry mode ─────────────────────
    function resetSaleForm() {
        document.getElementById('edit-log-id').value = '0';
        document.getElementById('sale-edit-banner').classList.add('hidden');
        document.getElementById('onlineItemsContainer').innerHTML = '';
        document.getElementById('itemsEmptyHint').classList.remove('hidden');
        loadPlatformPreferences();
        addOnlineItemRow();
    }
    window.resetSaleForm = resetSaleForm;

    function resetPayoutForm() {
        document.getElementById('edit-payout-id').value = '0';
        document.getElementById('payout-edit-banner').classList.add('hidden');
        document.getElementById('payout-amount').value = '';
    }
    window.resetPayoutForm = resetPayoutForm;

    // ── Edit entry: populate form ─────────────────────────────
    function onlineEditEntry(type, data) {
        if (type === 'sale') {
            switchTab('sale');
            document.getElementById('edit-log-id').value = data.log_id;
            document.getElementById('sale-platform').value = data.platform;
            document.getElementById('sale-date').value = data.date;
            document.getElementById('sale-commission-type').value = 'flat';
            document.getElementById('sale-commission').value = data.commission || 0;
            document.getElementById('sale-discount-type').value = 'flat';
            document.getElementById('sale-discount').value = data.discount || 0;
            document.getElementById('sale-edit-banner').classList.remove('hidden');
            // Clear and repopulate items
            document.getElementById('onlineItemsContainer').innerHTML = '';
            document.getElementById('itemsEmptyHint').classList.add('hidden');
            if (data.items && data.items.length) {
                data.items.forEach(it => addOnlineItemRow(it.item_name, it.qty, it.unit_price));
            } else { addOnlineItemRow(); }
            calculateOnlineTotals();
            document.getElementById('panel-sale').scrollIntoView({behavior:'smooth',block:'start'});
        } else {
            switchTab('payout');
            document.getElementById('edit-payout-id').value = data.payout_id;
            document.getElementById('payout-platform').value = data.platform;
            document.getElementById('payout-date').value = data.date;
            document.getElementById('payout-amount').value = data.amount;
            document.getElementById('payout-edit-banner').classList.remove('hidden');
            document.getElementById('panel-payout').scrollIntoView({behavior:'smooth',block:'start'});
        }
    }
    window.onlineEditEntry = onlineEditEntry;

    // ── Delete entry ──────────────────────────────────────────
    async function onlineDeleteEntry(type, id) {
        if (!confirm('<?= __("confirm_delete") ?>')) return;
        const url  = type === 'sale' ? '?url=onlineSales/deleteSaleLog' : '?url=onlineSales/deletePayout';
        const body = type === 'sale' ? {id} : {id};
        const res  = await apiPost(url, body);
        if (res.success) { showToast('Deleted', 'success'); setTimeout(() => window.location.reload(), 700); }
        else showToast(res.error || '<?= __("error") ?>', 'error');
    }
    window.onlineDeleteEntry = onlineDeleteEntry;
})();
</script>
