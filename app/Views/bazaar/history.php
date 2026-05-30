<?php
/**
 * Bazaar & Sales History View
 * Variables: $selectedDate, $ledgers, $salesRows, $availableDates
 */
$totalBazaarSpent = array_sum(array_column($ledgers, 'total_spent'));
$totalSalesRev = array_sum(array_column($salesRows, 'total_sales'));
$totalSoldUnits = array_sum(array_column($salesRows, 'total_sold'));
?>

<!-- ── Page Header ─────────────────────────────────────────────────── -->
<div class="mb-5 animate-slideUp">
    <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-3">
            <a href="?url=settings" class="text-text-muted hover:text-accent transition-colors">
                <i class="fas fa-chevron-left text-sm"></i>
            </a>
            <h2 class="text-lg font-black">
                <i class="fas fa-history text-purple-400 mr-1"></i> Bazaar &amp; Sales History
            </h2>
        </div>
    </div>
</div>

<!-- ── Date Picker Row ─────────────────────────────────────────────── -->
<div class="bg-card border border-border/50 rounded-xl p-3 mb-4 animate-slideUp">
    <div class="flex items-center gap-3">
        <i class="fas fa-calendar-alt text-accent text-sm"></i>
        <label class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest shrink-0">Select Date</label>
        <select id="history-date-select"
            class="flex-1 bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:border-accent focus:outline-none appearance-none cursor-pointer"
            onchange="window.location.href='?url=bazaar/history&date='+this.value">
            <?php
            // Ensure selected date is in list
            $allDates = $availableDates;
            if (!in_array($selectedDate, $allDates)) {
                array_unshift($allDates, $selectedDate);
            }
            foreach ($allDates as $d):
                $label = date('D, d M Y', strtotime($d));
                $isSel = $d === $selectedDate ? 'selected' : '';
                ?>
                <option value="<?= $d ?>" <?= $isSel ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- ── Quick Summary Cards ────────────────────────────────────────── -->
<div class="grid grid-cols-3 gap-3 mb-5 animate-slideUp">
    <div class="bg-card border border-border/50 rounded-xl p-3 text-center">
        <div class="text-[0.6rem] font-bold text-text-muted uppercase tracking-widest mb-1">Purchases</div>
        <div class="text-base font-black text-rose-400">৳<?= number_format($totalBazaarSpent) ?></div>
    </div>
    <div class="bg-card border border-border/50 rounded-xl p-3 text-center">
        <div class="text-[0.6rem] font-bold text-text-muted uppercase tracking-widest mb-1">Revenue</div>
        <div class="text-base font-black text-emerald-400">৳<?= number_format($totalSalesRev) ?></div>
    </div>
    <div class="bg-card border border-border/50 rounded-xl p-3 text-center">
        <div class="text-[0.6rem] font-bold text-text-muted uppercase tracking-widest mb-1">Units Sold</div>
        <div class="text-base font-black text-amber-400"><?= number_format($totalSoldUnits) ?></div>
    </div>
</div>

<!-- ── Tabs ────────────────────────────────────────────────────────── -->
<div class="flex gap-2 mb-4 overflow-x-auto pb-1 no-scrollbar animate-slideUp">
    <button
        class="history-tab active flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold border transition-all bg-purple-500/10 border-purple-500/40 text-purple-400"
        data-target="tab-bazaar">
        <i class="fas fa-shopping-basket mr-1"></i> Bazaar List
    </button>
    <button
        class="history-tab flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold border transition-all bg-card border-border text-text-muted hover:text-text-primary"
        data-target="tab-sales">
        <i class="fas fa-receipt mr-1"></i> Selling Products
    </button>
</div>

<!-- ════════════════════════════════════════════════════════════════ -->
<!-- TAB 1: Bazaar Purchase Lists                                    -->
<!-- ════════════════════════════════════════════════════════════════ -->
<div id="tab-bazaar" class="history-pane block space-y-4 stagger">
    <?php if (empty($ledgers)): ?>
        <div class="bg-card border border-dashed border-border/60 rounded-xl p-8 text-center text-text-muted">
            <i class="fas fa-shopping-cart text-3xl mb-3 opacity-30"></i>
            <p class="text-sm font-semibold">No bazaar lists found for <?= date('D, d M Y', strtotime($selectedDate)) ?></p>
        </div>
    <?php else: ?>
        <?php $listNum = 1;
        foreach ($ledgers as $ledger): ?>
            <div class="bg-card border border-border/50 rounded-xl overflow-hidden">
                <!-- Ledger Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-border/40 bg-surface/50">
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-black text-purple-400 bg-purple-500/15 border border-purple-500/30 px-2.5 py-1 rounded-lg">
                            List #<?= $listNum++ ?>
                        </span>
                        <?php if (!empty($ledger['staff_name'])): ?>
                            <span class="text-xs text-text-muted font-semibold">
                                <i
                                    class="fas fa-user text-[0.6rem] mr-1 opacity-50"></i><?= htmlspecialchars($ledger['staff_name']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <span
                        class="text-[0.6rem] font-bold px-2 py-0.5 rounded-full border 
                    <?= $ledger['status'] === 'closed' ? 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10' : 'text-amber-400 border-amber-500/30 bg-amber-500/10' ?>">
                        <?= ucfirst($ledger['status']) ?>
                    </span>
                </div>

                <!-- Financial Summary Row -->
                <div class="grid grid-cols-3 divide-x divide-border/40 border-b border-border/40">
                    <div class="px-3 py-2 text-center">
                        <div class="text-[0.55rem] font-bold text-text-muted uppercase tracking-widest">Advance</div>
                        <div class="text-sm font-bold text-indigo-400">৳<?= number_format((float) $ledger['advance_cash']) ?>
                        </div>
                    </div>
                    <div class="px-3 py-2 text-center">
                        <div class="text-[0.55rem] font-bold text-text-muted uppercase tracking-widest">Spent</div>
                        <div class="text-sm font-bold text-rose-400">৳<?= number_format((float) $ledger['total_spent']) ?></div>
                    </div>
                    <div class="px-3 py-2 text-center">
                        <div class="text-[0.55rem] font-bold text-text-muted uppercase tracking-widest">Balance</div>
                        <?php $balance = (float) $ledger['advance_cash'] - (float) $ledger['total_spent']; ?>
                        <div class="text-sm font-bold <?= $balance >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">
                            ৳<?= number_format($balance) ?>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <?php if (!empty($ledger['items'])): ?>
                    <div class="divide-y divide-border/30">
                        <!-- Header -->
                        <div class="grid grid-cols-12 gap-1 px-4 py-2 bg-surface/30">
                            <div class="col-span-5 text-[0.6rem] font-bold text-text-muted uppercase tracking-widest">Item</div>
                            <div class="col-span-2 text-[0.6rem] font-bold text-text-muted uppercase tracking-widest text-center">
                                Qty</div>
                            <div class="col-span-2 text-[0.6rem] font-bold text-text-muted uppercase tracking-widest text-right">
                                Rate</div>
                            <div class="col-span-3 text-[0.6rem] font-bold text-text-muted uppercase tracking-widest text-right">
                                Total</div>
                        </div>
                        <?php foreach ($ledger['items'] as $bi): ?>
                            <div class="grid grid-cols-12 gap-1 px-4 py-2.5 hover:bg-surface/40 transition-colors">
                                <div class="col-span-5 text-sm font-semibold text-text-primary truncate">
                                    <?= htmlspecialchars($bi['item_name']) ?>
                                </div>
                                <div class="col-span-2 text-sm text-center text-text-muted">
                                    <?= (float) $bi['bought_qty'] ?> <span
                                        class="text-[0.6rem]"><?= htmlspecialchars($bi['unit']) ?></span>
                                </div>
                                <div class="col-span-2 text-sm text-right text-text-muted">
                                    ৳<?= number_format((float) $bi['unit_price'], 0) ?>
                                </div>
                                <div class="col-span-3 text-sm font-bold text-right text-accent">
                                    ৳<?= number_format((float) $bi['total_price']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <!-- Sub-total row -->
                        <div class="flex justify-between items-center px-4 py-2.5 bg-surface/30">
                            <span class="text-[0.65rem] font-bold text-text-muted uppercase tracking-widest">
                                <?= count($ledger['items']) ?> items
                            </span>
                            <span class="text-sm font-black text-accent">৳<?= number_format((float) $ledger['total_spent']) ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="px-4 py-6 text-center text-xs text-text-muted opacity-60">
                        <i class="fas fa-inbox mr-1"></i> No items recorded
                    </div>
                <?php endif; ?>

                <?php if (!empty($ledger['returned_cash']) && (float) $ledger['returned_cash'] > 0): ?>
                    <div class="px-4 py-2 border-t border-border/40 flex justify-between items-center bg-amber-500/5">
                        <span class="text-[0.65rem] font-bold text-amber-400 uppercase tracking-widest">
                            <i class="fas fa-rotate-left mr-1"></i> Cash Returned
                        </span>
                        <span class="text-sm font-bold text-amber-400">৳<?= number_format((float) $ledger['returned_cash']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Grand Total -->
        <div class="bg-rose-500/10 border border-rose-500/30 rounded-xl px-4 py-3 flex justify-between items-center">
            <span class="text-sm font-bold text-rose-400"><i class="fas fa-sigma mr-1"></i> Total Purchases</span>
            <span class="text-lg font-black text-rose-400">৳<?= number_format($totalBazaarSpent) ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════ -->
<!-- TAB 2: Selling Product List                                     -->
<!-- ════════════════════════════════════════════════════════════════ -->
<div id="tab-sales" class="history-pane hidden space-y-4 stagger">
    <?php if (empty($salesRows)): ?>
        <div class="bg-card border border-dashed border-border/60 rounded-xl p-8 text-center text-text-muted">
            <i class="fas fa-store text-3xl mb-3 opacity-30"></i>
            <p class="text-sm font-semibold">No sales recorded for <?= date('D, d M Y', strtotime($selectedDate)) ?></p>
        </div>
    <?php else: ?>

        <!-- Items -->
        <div class="bg-card border border-border/50 rounded-xl overflow-hidden">
            <!-- Table header -->
            <div class="grid grid-cols-12 gap-1 px-4 py-2.5 border-b border-border/40 bg-surface/50">
                <div class="col-span-4 text-[0.6rem] font-bold text-text-muted uppercase tracking-widest">Product</div>
                <div class="col-span-2 text-[0.6rem] font-bold text-text-muted uppercase tracking-widest text-center">Sold</div>
                <div class="col-span-2 text-[0.6rem] font-bold text-text-muted uppercase tracking-widest text-center">Comp</div>
                <div class="col-span-2 text-[0.6rem] font-bold text-red-400/70 uppercase tracking-widest text-center">Waste</div>
                <div class="col-span-2 text-[0.6rem] font-bold text-text-muted uppercase tracking-widest text-right">Revenue</div>
            </div>

            <?php foreach ($salesRows as $i => $row): ?>
                <div class="border-b border-border/30 last:border-0">
                    <!-- Main row -->
                    <div class="grid grid-cols-12 gap-1 px-4 py-3 hover:bg-surface/40 transition-colors cursor-pointer sales-row-toggle"
                        data-target="shift-<?= $i ?>">
                        <div class="col-span-4">
                            <div class="text-sm font-semibold text-text-primary"><?= htmlspecialchars($row['item_name']) ?></div>
                            <?php if (!empty($row['item_name_bn'])): ?>
                                <div class="text-[0.6rem] text-text-muted"><?= htmlspecialchars($row['item_name_bn']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-span-2 text-center">
                            <span class="text-sm font-black text-emerald-400"><?= (int)$row['total_sold'] ?></span>
                        </div>
                        <div class="col-span-2 text-center">
                            <?php if ((int)$row['total_comp'] > 0): ?>
                                <span class="text-sm font-semibold text-amber-400"><?= (int)$row['total_comp'] ?></span>
                            <?php else: ?>
                                <span class="text-sm text-text-muted opacity-30">—</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-span-2 text-center">
                            <?php if ((float)$row['wastage_qty'] > 0): ?>
                                <span class="text-sm font-bold text-red-400"><?= (float)$row['wastage_qty'] ?></span>
                            <?php else: ?>
                                <span class="text-sm text-text-muted opacity-30">—</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-span-2 text-right">
                            <span class="text-sm font-black text-accent">৳<?= number_format((float)$row['total_sales']) ?></span>
                            <i class="fas fa-chevron-down text-[0.55rem] text-text-muted ml-1 shift-arrow-<?= $i ?> transition-transform"></i>
                        </div>
                    </div>

                    <!-- Shift breakdown (collapsed by default) -->
                    <div id="shift-<?= $i ?>" class="hidden bg-surface/30 px-5 py-3 border-t border-border/30">
                        <div class="text-[0.6rem] font-bold text-text-muted uppercase tracking-widest mb-2">Shift Breakdown
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $parts = explode(', ', $row['shift_breakdown'] ?? '');
                            foreach ($parts as $part):
                                [$shiftName, $qty] = explode(':', $part) + [null, null];
                                if (!$shiftName || $qty === null)
                                    continue;
                                $qty = (int) $qty;
                                $color = match (strtolower(trim($shiftName))) {
                                    'morning' => 'text-amber-400 border-amber-500/30 bg-amber-500/10',
                                    'evening' => 'text-orange-400 border-orange-500/30 bg-orange-500/10',
                                    'night' => 'text-indigo-400 border-indigo-500/30 bg-indigo-500/10',
                                    default => 'text-text-muted border-border bg-surface',
                                };
                                ?>
                                <span class="text-xs font-bold px-2.5 py-1 rounded-lg border <?= $color ?>">
                                    <?= ucfirst(trim($shiftName)) ?>: <?= $qty ?>
                                </span>
                            <?php endforeach; ?>
                            <span class="text-xs text-text-muted px-2 py-1">
                                @ ৳<?= number_format((float) $row['selling_price']) ?>/unit
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Revenue Summary -->
        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4">
            <div class="flex justify-between items-center mb-3 pb-3 border-b border-emerald-500/20">
                <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest">
                    <i class="fas fa-sigma mr-1"></i> Day Revenue Summary
                </span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-text-muted">Total Units Sold</span>
                    <span class="font-black text-emerald-400"><?= number_format($totalSoldUnits) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-text-muted">Total Revenue</span>
                    <span class="font-black text-emerald-400 text-base">৳<?= number_format($totalSalesRev) ?></span>
                </div>
                <?php
                $totalComp   = array_sum(array_column($salesRows, 'total_comp'));
                $totalWaste  = array_sum(array_column($salesRows, 'wastage_qty'));
                if ($totalComp > 0): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-text-muted">Complimentary</span>
                        <span class="font-semibold text-amber-400"><?= number_format($totalComp) ?> units</span>
                    </div>
                <?php endif; ?>
                <?php if ($totalWaste > 0): ?>
                    <div class="flex justify-between text-sm border-t border-emerald-500/20 pt-2 mt-1">
                        <span class="text-text-muted flex items-center gap-1"><i class="fas fa-trash-alt text-red-400 text-[0.6rem]"></i> Total Wastage</span>
                        <span class="font-semibold text-red-400"><?= number_format($totalWaste, 2) ?> units</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // ── Tab switching ──────────────────────────────────────────
        const ACTIVE = 'bg-purple-500/10 border-purple-500/40 text-purple-400';
        const INACTIVE = 'bg-card border-border text-text-muted hover:text-text-primary';

        document.querySelectorAll('.history-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.history-tab').forEach(t => {
                    t.className = 'history-tab flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold border transition-all ' + INACTIVE;
                });
                tab.className = 'history-tab flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold border transition-all ' + ACTIVE;

                document.querySelectorAll('.history-pane').forEach(p => p.classList.add('hidden'));
                document.getElementById(tab.dataset.target).classList.remove('hidden');
            });
        });

        // ── Shift breakdown toggle ────────────────────────────────
        document.querySelectorAll('.sales-row-toggle').forEach(row => {
            row.addEventListener('click', () => {
                const target = document.getElementById(row.dataset.target);
                if (!target) return;
                const idx = row.dataset.target.replace('shift-', '');
                const arrow = document.querySelector('.shift-arrow-' + idx);
                target.classList.toggle('hidden');
                if (arrow) arrow.classList.toggle('rotate-180');
            });
        });
    });
</script>