<?php
/**
 * Smart Dashboard View
 * Available variables: $userName, $gasInfo, $bazaarSuggestions, $supplierDues, $pendingOrders
 */
?>

<!-- Welcome Strip -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
    <div>
        <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0.1rem;">Welcome back,</p>
        <h2 style="font-size:1.25rem; font-weight:800;"><?= htmlspecialchars($userName ?? 'Staff') ?> 👋</h2>
    </div>
    <a href="?url=auth/logout"
       style="font-size:0.75rem; color:var(--accent); text-decoration:none; font-weight:600;
              background:rgba(233,69,96,0.12); border:1px solid rgba(233,69,96,0.3);
              padding:0.4rem 0.75rem; border-radius:20px;">
        Sign Out
    </a>
</div>

<!-- Pending Orders Banner -->
<?php if (($pendingOrders ?? 0) > 0): ?>
    <div class="alert alert-warning" style="margin-bottom:1rem;">
        📦 <strong><?= $pendingOrders ?> advance order<?= $pendingOrders > 1 ? 's' : '' ?></strong>
        due for delivery <strong>today</strong>!
    </div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════
     WIDGET 1 — GAS STATUS
════════════════════════════════════════════════════ -->
<p class="section-title">🔥 Gas Status</p>
<div class="card" style="margin-bottom:1rem;">
    <?php
    $gas    = $gasInfo ?? [];
    $status = $gas['status'] ?? 'no_data';

    $statusColor = match($status) {
        'critical' => 'var(--accent)',
        'warning'  => 'var(--warning)',
        'ok'       => 'var(--success)',
        default    => 'var(--text-muted)',
    };
    $statusLabel = match($status) {
        'critical' => 'CRITICAL — Refill Soon!',
        'warning'  => 'Low — Plan Refill',
        'ok'       => 'Sufficient',
        default    => 'No gas expense on record',
    };
    ?>

    <?php if ($status === 'no_data'): ?>
        <p style="color:var(--text-muted); font-size:0.9rem;">
            No active gas spread expense found. Add one via Expenses to enable forecasting.
        </p>
    <?php else: ?>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem;">
            <div>
                <p style="font-size:2rem; font-weight:800; color:<?= $statusColor ?>; line-height:1;">
                    <?= $gas['days_remaining'] ?> <span style="font-size:1rem; font-weight:500;">days</span>
                </p>
                <p style="font-size:0.78rem; color:var(--text-muted); margin-top:0.2rem;">Until refill needed</p>
            </div>
            <span style="font-size:0.72rem; font-weight:700; color:<?= $statusColor ?>;
                         background:rgba(0,0,0,0.3); border:1px solid <?= $statusColor ?>;
                         border-radius:20px; padding:0.3rem 0.65rem;">
                <?= $statusLabel ?>
            </span>
        </div>

        <!-- Progress bar -->
        <?php $pct = min(100, ($gas['days_remaining'] / 30) * 100); ?>
        <div style="background:var(--border); border-radius:99px; height:8px; overflow:hidden;">
            <div style="width:<?= $pct ?>%; background:<?= $statusColor ?>; height:100%; border-radius:99px; transition:width 0.4s ease;"></div>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:0.5rem;">
            <span style="font-size:0.72rem; color:var(--text-muted);">Remaining: ৳<?= number_format($gas['remaining_bdt'], 2) ?></span>
            <span style="font-size:0.72rem; color:var(--text-muted);">Predicted refill: <strong style="color:var(--text-primary);"><?= $gas['refill_date'] ?></strong></span>
        </div>
    <?php endif; ?>
</div>


<!-- ═══════════════════════════════════════════════════
     WIDGET 2 — SMART BAZAAR SUGGESTIONS
════════════════════════════════════════════════════ -->
<p class="section-title">🛒 Smart Bazaar List <span style="font-weight:400; font-size:0.7rem;">(for tomorrow)</span></p>
<div class="card" style="margin-bottom:1rem; padding:0; overflow:hidden;">
    <?php if (empty($bazaarSuggestions)): ?>
        <p style="color:var(--text-muted); font-size:0.9rem; padding:1.25rem;">
            No sales history yet. Start logging daily stocks to enable suggestions.
        </p>
    <?php else: ?>
        <!-- Table Header -->
        <div style="display:grid; grid-template-columns:1fr 0.5fr 0.5fr 0.65fr;
                    padding:0.6rem 1rem; background:var(--bg-surface);
                    font-size:0.65rem; font-weight:700; color:var(--text-muted);
                    text-transform:uppercase; letter-spacing:0.06em; border-bottom:1px solid var(--border);">
            <span>Item</span>
            <span style="text-align:center;">7d Avg</span>
            <span style="text-align:center;">Mult.</span>
            <span style="text-align:right;">Suggested</span>
        </div>

        <?php foreach ($bazaarSuggestions as $i => $row): ?>
            <div style="display:grid; grid-template-columns:1fr 0.5fr 0.5fr 0.65fr;
                        padding:0.75rem 1rem; align-items:center;
                        border-bottom:<?= ($i < count($bazaarSuggestions) - 1) ? '1px solid var(--border)' : 'none' ?>;">
                <span style="font-size:0.875rem; font-weight:600;">
                    <?= htmlspecialchars($row['item_name']) ?>
                </span>
                <span style="text-align:center; font-size:0.85rem; color:var(--text-muted);">
                    <?= number_format($row['avg_sold_7d'], 1) ?>
                </span>
                <span style="text-align:center; font-size:0.85rem; color:<?= $row['impact_multiplier'] != 1 ? 'var(--warning)' : 'var(--text-muted)' ?>;">
                    ×<?= number_format($row['impact_multiplier'], 2) ?>
                </span>
                <span style="text-align:right; font-size:1rem; font-weight:800; color:var(--accent);">
                    <?= number_format($row['suggested_qty'], 1) ?>
                </span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


<!-- ═══════════════════════════════════════════════════
     WIDGET 3 — PENDING SUPPLIER DUES
════════════════════════════════════════════════════ -->
<p class="section-title">💰 Pending Supplier Dues</p>
<div class="card" style="padding:0; overflow:hidden;">
    <?php if (empty($supplierDues)): ?>
        <p style="color:var(--success); font-size:0.9rem; padding:1.25rem;">
            ✅ All supplier dues are cleared!
        </p>
    <?php else: ?>
        <?php foreach ($supplierDues as $i => $supplier): ?>
            <div style="display:flex; align-items:center; justify-content:space-between;
                        padding:0.9rem 1.1rem;
                        border-bottom:<?= ($i < count($supplierDues) - 1) ? '1px solid var(--border)' : 'none' ?>;">
                <div>
                    <p style="font-size:0.9rem; font-weight:600;">
                        <?= htmlspecialchars($supplier['name']) ?>
                    </p>
                    <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.1rem;">
                        <?= htmlspecialchars($supplier['contact'] ?? '—') ?>
                    </p>
                </div>
                <span style="font-size:1rem; font-weight:800; color:var(--accent);">
                    ৳<?= number_format($supplier['total_due'], 2) ?>
                </span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<p class="section-title" style="margin-top:1.25rem;">Quick Actions</p>
<a href="?url=inventory/dailyPrep" class="btn btn-secondary" style="justify-content:flex-start; gap:1rem;">
    <span style="font-size:1.4rem;">🌅</span>
    <div>
        <div style="font-size:0.9rem; font-weight:700;">Morning Prep</div>
        <div style="font-size:0.72rem; color:var(--text-muted); font-weight:400;">Log wastage &amp; fresh stock</div>
    </div>
</a>
<a href="?url=inventory/closeDayView" class="btn btn-primary" style="justify-content:flex-start; gap:1rem;">
    <span style="font-size:1.4rem;">🌙</span>
    <div>
        <div style="font-size:0.9rem; font-weight:700;">Night Closing</div>
        <div style="font-size:0.72rem; color:rgba(255,255,255,0.7); font-weight:400;">Finalize today's accounts</div>
    </div>
</a>

<?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
<p class="section-title" style="margin-top:1.25rem;">🛡️ System & Security</p>
<a href="?url=backup/downloadSQLBackup" class="btn btn-secondary" style="justify-content:flex-start; gap:1rem; border-color: var(--accent);">
    <span style="font-size:1.4rem;">💾</span>
    <div>
        <div style="font-size:0.9rem; font-weight:700;">Download Database Backup</div>
        <div style="font-size:0.72rem; color:var(--text-muted); font-weight:400;">Full .sql file (all tables)</div>
    </div>
</a>
<a href="?url=backup/exportMonthlySalesCSV" class="btn btn-secondary" style="justify-content:flex-start; gap:1rem;">
    <span style="font-size:1.4rem;">📊</span>
    <div>
        <div style="font-size:0.9rem; font-weight:700;">Export Monthly Sales</div>
        <div style="font-size:0.72rem; color:var(--text-muted); font-weight:400;">Current month .csv file</div>
    </div>
</a>
<?php endif; ?>
