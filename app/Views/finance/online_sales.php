<?php
/**
 * Online Delivery Platforms — Sales Ledger
 * Variables: $balances (array), $logs (array)
 */

// Platform visual config
$platformMeta = [
    'Foodpanda' => ['color' => 'pink',   'hex' => '#f472b6', 'icon' => 'fa-utensils',  'ring' => 'ring-pink-500/30',   'bg' => 'bg-pink-500/10',   'text' => 'text-pink-400',   'border' => 'border-pink-500/30'],
    'Pathao'    => ['color' => 'red',    'hex' => '#f87171', 'icon' => 'fa-motorcycle', 'ring' => 'ring-red-500/30',    'bg' => 'bg-red-500/10',    'text' => 'text-red-400',    'border' => 'border-red-500/30'],
    'Foodi'     => ['color' => 'orange', 'hex' => '#fb923c', 'icon' => 'fa-box-open',   'ring' => 'ring-orange-500/30', 'bg' => 'bg-orange-500/10', 'text' => 'text-orange-400', 'border' => 'border-orange-500/30'],
];
$today = date('Y-m-d');
?>

<!-- ══ Page Title ═══════════════════════════════════════════════════ -->
<div class="mb-5 animate-slideUp flex items-center gap-3">
    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center">
        <i class="fas fa-motorcycle text-indigo-400"></i>
    </div>
    <div>
        <h2 class="text-xl font-black tracking-tight"><?= __('online_platforms') ?></h2>
        <p class="text-[0.65rem] text-text-muted uppercase tracking-widest">Foodpanda · Pathao · Foodi</p>
    </div>
</div>

<!-- ══ 1. Balance Cards ══════════════════════════════════════════════ -->
<div class="grid grid-cols-3 gap-3 mb-6 stagger">
    <?php foreach ($balances as $row):
        $p    = $row['platform'];
        $meta = $platformMeta[$p] ?? ['icon'=>'fa-circle','bg'=>'bg-surface','text'=>'text-info','border'=>'border-border','ring'=>'ring-border'];
        $bal  = (float)$row['balance'];
        $isPositive = $bal > 0;
    ?>
    <div class="glass rounded-2xl p-3.5 ring-1 <?= $meta['ring'] ?> flex flex-col gap-1.5 <?= $meta['bg'] ?>/5">
        <div class="flex items-center justify-between">
            <i class="fas <?= $meta['icon'] ?> <?= $meta['text'] ?> text-base"></i>
            <?php if ($isPositive): ?>
                <span class="text-[0.55rem] font-black uppercase tracking-widest text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded-full">Due</span>
            <?php else: ?>
                <span class="text-[0.55rem] font-black uppercase tracking-widest text-text-muted bg-surface px-1.5 py-0.5 rounded-full border border-border">Clear</span>
            <?php endif; ?>
        </div>
        <div>
            <div class="text-[0.6rem] font-bold text-text-muted uppercase tracking-wide"><?= htmlspecialchars($p) ?></div>
            <div class="text-base font-black <?= $isPositive ? $meta['text'] : 'text-text-muted' ?>">
                ৳<?= number_format(abs($bal), 0) ?>
            </div>
            <div class="text-[0.55rem] text-text-muted"><?= __('platform_balance') ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ══ 2. Action Tabs ════════════════════════════════════════════════ -->
<div class="glass rounded-2xl mb-6 overflow-hidden border border-border/50 animate-slideUp" style="animation-delay:0.08s">

    <!-- Tab Switcher (line-art style) -->
    <div class="flex border-b border-border/50">
        <button id="tab-sale"
                class="tab-btn flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold uppercase tracking-widest transition-colors duration-200
                       text-indigo-400 border-b-2 border-indigo-500"
                onclick="switchTab('sale')">
            <i class="fas fa-chart-line text-sm"></i> <?= __('log_online_sale') ?>
        </button>
        <button id="tab-payout"
                class="tab-btn flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold uppercase tracking-widest transition-colors duration-200
                       text-text-muted border-b-2 border-transparent hover:text-text-primary"
                onclick="switchTab('payout')">
            <i class="fas fa-hand-holding-dollar text-sm"></i> <?= __('log_payout') ?>
        </button>
    </div>

    <!-- ── Sale Form ───────────────────────────────────────────────── -->
    <div id="panel-sale" class="p-5 space-y-5">
        <form id="saleForm">
            <!-- Platform Select -->
            <div class="relative mb-5">
                <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5">Platform</label>
                <div class="relative">
                    <select id="sale-platform"
                            class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-sm font-semibold text-text-primary appearance-none focus:border-indigo-500 transition-colors pr-8">
                        <option value="Foodpanda">🍔 Foodpanda</option>
                        <option value="Pathao">🏍️ Pathao</option>
                        <option value="Foodi">📦 Foodi</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-text-muted text-xs pointer-events-none"></i>
                </div>
            </div>

            <!-- Date -->
            <div class="relative mb-5">
                <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('date') ?></label>
                <input type="date" id="sale-date" value="<?= $today ?>"
                       class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-sm font-semibold text-text-primary focus:border-indigo-500 transition-colors">
            </div>

            <!-- Gross + Commission row -->
            <div class="grid grid-cols-2 gap-3 mb-2">
                <div>
                    <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('gross_sales') ?> (৳)</label>
                    <input type="number" id="sale-gross" min="0" step="1" inputmode="decimal" placeholder="0"
                           class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-base font-black text-text-primary focus:border-indigo-500 transition-colors text-right">
                </div>
                <div>
                    <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('platform_commission') ?> (৳)</label>
                    <input type="number" id="sale-commission" min="0" step="1" inputmode="decimal" placeholder="0"
                           class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-base font-black text-text-primary focus:border-red-500 transition-colors text-right">
                </div>
            </div>

            <!-- Live Net Calc -->
            <div class="flex items-center justify-between bg-indigo-500/10 border border-indigo-500/20 rounded-xl px-4 py-3 mb-5">
                <span class="text-xs font-bold text-text-muted uppercase tracking-widest"><?= __('net_receivable') ?></span>
                <span id="net-display" class="text-lg font-black text-indigo-400">৳0</span>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-500/40 font-black py-3.5 rounded-xl
                           transition-all duration-200 text-sm uppercase tracking-widest flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> <?= __('save') ?>
            </button>
        </form>
    </div>

    <!-- ── Payout Form ─────────────────────────────────────────────── -->
    <div id="panel-payout" class="p-5 space-y-5 hidden">
        <form id="payoutForm">
            <!-- Platform Select -->
            <div class="relative mb-5">
                <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5">Platform</label>
                <div class="relative">
                    <select id="payout-platform"
                            class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-sm font-semibold text-text-primary appearance-none focus:border-emerald-500 transition-colors pr-8">
                        <option value="Foodpanda">🍔 Foodpanda</option>
                        <option value="Pathao">🏍️ Pathao</option>
                        <option value="Foodi">📦 Foodi</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-text-muted text-xs pointer-events-none"></i>
                </div>
            </div>

            <!-- Date -->
            <div class="relative mb-5">
                <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('date') ?></label>
                <input type="date" id="payout-date" value="<?= $today ?>"
                       class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-sm font-semibold text-text-primary focus:border-emerald-500 transition-colors">
            </div>

            <!-- Amount -->
            <div class="mb-5">
                <label class="block text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-1.5"><?= __('payout_amount') ?> (৳)</label>
                <input type="number" id="payout-amount" min="1" step="1" inputmode="decimal" placeholder="0"
                       class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-2xl font-black text-emerald-400 focus:border-emerald-500 transition-colors text-right">
            </div>

            <button type="submit"
                    class="w-full bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 font-black py-3.5 rounded-xl
                           transition-all duration-200 text-sm uppercase tracking-widest flex items-center justify-center gap-2">
                <i class="fas fa-check-circle"></i> <?= __('log_payout') ?>
            </button>
        </form>
    </div>
</div>

<!-- ══ 3. History Log ════════════════════════════════════════════════ -->
<div class="animate-slideUp" style="animation-delay:0.14s">
    <h3 class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest mb-3 flex items-center gap-2">
        <i class="fas fa-scroll text-info text-xs"></i> History
    </h3>

    <?php if (empty($logs)): ?>
    <div class="text-center py-10 text-text-muted">
        <i class="fas fa-inbox text-3xl mb-3 block opacity-30"></i>
        <span class="text-sm"><?= __('no_data') ?></span>
    </div>
    <?php else: ?>
    <div class="space-y-2">
        <?php foreach ($logs as $log):
            $p    = $log['platform'] ?? 'Unknown';
            $meta = $platformMeta[$p] ?? ['icon'=>'fa-circle','text'=>'text-info','bg'=>'bg-surface','border'=>'border-border'];
            $isSale   = $log['entry_type'] === 'sale';
            $dateLabel = date('d M', strtotime($log['entry_date']));
        ?>
        <div class="flex items-center gap-3 py-2.5 px-3.5 bg-card border border-border/40 rounded-xl">
            <!-- Icon -->
            <div class="w-8 h-8 rounded-lg <?= $meta['bg'] ?> border <?= $meta['border'] ?> flex items-center justify-center flex-shrink-0">
                <i class="fas <?= $isSale ? 'fa-receipt' : 'fa-money-bill-transfer' ?> <?= $meta['text'] ?> text-xs"></i>
            </div>
            <!-- Info -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold <?= $meta['text'] ?>"><?= htmlspecialchars($p) ?></span>
                    <span class="text-[0.55rem] font-semibold uppercase px-1.5 py-0.5 rounded-full
                        <?= $isSale ? 'bg-indigo-500/10 text-indigo-400' : 'bg-emerald-500/10 text-emerald-400' ?>">
                        <?= $isSale ? __('log_online_sale') : __('log_payout') ?>
                    </span>
                </div>
                <div class="text-[0.6rem] text-text-muted"><?= $dateLabel ?></div>
            </div>
            <!-- Amount -->
            <div class="text-right flex-shrink-0">
                <?php if ($isSale): ?>
                    <div class="text-sm font-black text-text-primary">৳<?= number_format((float)$log['net_amount'], 0) ?></div>
                    <div class="text-[0.55rem] text-text-muted">Net · Gross ৳<?= number_format((float)$log['gross_amount'], 0) ?></div>
                <?php else: ?>
                    <div class="text-sm font-black text-emerald-400">−৳<?= number_format((float)$log['payout_amount'], 0) ?></div>
                    <div class="text-[0.55rem] text-text-muted"><?= __('payout_amount') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ══ JS ════════════════════════════════════════════════════════════ -->
<script>
(function () {
    // ── Tab switcher ──────────────────────────────────────────────
    function switchTab(tab) {
        const tabs   = ['sale', 'payout'];
        tabs.forEach(t => {
            const btn   = document.getElementById('tab-' + t);
            const panel = document.getElementById('panel-' + t);
            if (t === tab) {
                btn.classList.replace('text-text-muted', 'text-indigo-400');
                btn.classList.remove('border-transparent');
                btn.classList.add('border-indigo-500');
                panel.classList.remove('hidden');
            } else {
                btn.classList.replace('text-indigo-400', 'text-text-muted');
                btn.classList.add('border-transparent');
                btn.classList.remove('border-indigo-500');
                panel.classList.add('hidden');
            }
        });
    }
    window.switchTab = switchTab;

    // ── Live Net = Gross − Commission ─────────────────────────────
    const grossInput      = document.getElementById('sale-gross');
    const commissionInput = document.getElementById('sale-commission');
    const netDisplay      = document.getElementById('net-display');

    function calcNet() {
        const gross = parseFloat(grossInput.value) || 0;
        const comm  = parseFloat(commissionInput.value) || 0;
        const net   = gross - comm;
        netDisplay.textContent = '৳' + Math.max(0, net).toLocaleString('en-IN', {maximumFractionDigits: 0});
        netDisplay.classList.toggle('text-red-400',  net < 0);
        netDisplay.classList.toggle('text-indigo-400', net >= 0);
    }
    grossInput.addEventListener('input', calcNet);
    commissionInput.addEventListener('input', calcNet);

    // ── Sale Form submit ──────────────────────────────────────────
    document.getElementById('saleForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const gross = parseFloat(grossInput.value) || 0;
        const comm  = parseFloat(commissionInput.value) || 0;

        const res = await apiPost('?url=onlineSales/logDailySale', {
            platform:          document.getElementById('sale-platform').value,
            log_date:          document.getElementById('sale-date').value,
            gross_amount:      gross,
            commission_amount: comm
        });

        if (res.success) {
            showToast('<?= __("success") ?>', 'success');
            setTimeout(() => window.location.reload(), 900);
        } else {
            showToast(res.error || '<?= __("error") ?>', 'error');
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    });

    // ── Payout Form submit ────────────────────────────────────────
    document.getElementById('payoutForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const res = await apiPost('?url=onlineSales/logPayout', {
            platform:    document.getElementById('payout-platform').value,
            payout_date: document.getElementById('payout-date').value,
            amount:      parseFloat(document.getElementById('payout-amount').value) || 0
        });

        if (res.success) {
            showToast('<?= __("success") ?>', 'success');
            setTimeout(() => window.location.reload(), 900);
        } else {
            showToast(res.error || '<?= __("error") ?>', 'error');
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    });
})();
</script>
