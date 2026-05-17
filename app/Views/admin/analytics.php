<?php
$topItem = $topItem ?? [];
$wastageCost = $wastageCost ?? 0;
$suppliers = $suppliers ?? [];
$customerDues = $customerDues ?? [];
?>

<style>
    .stat-widget { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; margin-bottom: 1rem; text-align: center; }
    .stat-widget .label { font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.5rem; }
    .stat-widget .value { font-size: 1.75rem; font-weight: 800; color: var(--accent); }
    .stat-widget .sub { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; }
    .debt-section { margin-top: 1.5rem; }
    .debt-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 0.75rem; padding: 1rem; }
    .debt-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
    .debt-name { font-weight: 700; font-size: 0.95rem; }
    .debt-amount { font-size: 1.1rem; font-weight: 800; color: var(--accent); }
    .debt-meta { font-size: 0.7rem; color: var(--text-muted); }
    .settle-form { display: flex; gap: 0.5rem; margin-top: 0.75rem; }
    .settle-input { flex: 1; min-height: 40px; padding: 0 0.75rem; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-primary); font-size: 0.9rem; }
    .settle-btn { padding: 0 1rem; background: var(--accent); color: #fff; border: none; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
    .settle-btn:active { opacity: 0.8; }
    .empty-state { color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 1.5rem; }
    .success-msg { background: rgba(46,204,113,0.2); border: 1px solid var(--success); color: var(--success); padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.8rem; margin-bottom: 0.5rem; display: none; }
</style>

<p class="section-title">📊 Owner Analytics</p>

<div class="stat-widget">
    <div class="label">Item of the Month</div>
    <div class="value"><?= htmlspecialchars($topItem['item_name'] ?? 'No data') ?></div>
    <div class="sub"><?= number_format($topItem['total_sold'] ?? 0) ?> units sold (30 days)</div>
</div>

<div class="stat-widget">
    <div class="label">Total Wastage Loss</div>
    <div class="value">৳<?= number_format($wastageCost, 0) ?></div>
    <div class="sub">This month only</div>
</div>

<div class="debt-section">
    <p class="section-title">💳 Supplier Dues</p>

    <?php if (empty($suppliers)): ?>
        <div class="empty-state">No outstanding supplier dues</div>
    <?php else: ?>
        <?php foreach ($suppliers as $sup): ?>
            <div class="debt-card">
                <div class="debt-header">
                    <div>
                        <div class="debt-name"><?= htmlspecialchars($sup['name']) ?></div>
                        <div class="debt-meta"><?= htmlspecialchars($sup['contact'] ?? 'No contact') ?></div>
                    </div>
                    <div class="debt-amount">৳<?= number_format($sup['total_due'], 0) ?></div>
                </div>
                <div class="settle-form">
                    <input type="number" class="settle-input" placeholder="Amount" id="supp-<?= $sup['id'] ?>">
                    <button class="settle-btn" onclick="settleSupplier(<?= $sup['id'] ?>)">Pay</button>
                </div>
                <div class="success-msg" id="msg-sup-<?= $sup['id'] ?>">Payment recorded!</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="debt-section">
    <p class="section-title">👤 Customer Dues</p>

    <?php if (empty($customerDues)): ?>
        <div class="empty-state">No outstanding customer dues</div>
    <?php else: ?>
        <?php foreach ($customerDues as $cust): ?>
            <div class="debt-card">
                <div class="debt-header">
                    <div>
                        <div class="debt-name"><?= htmlspecialchars($cust['customer_name']) ?></div>
                        <div class="debt-meta"><?= htmlspecialchars($cust['phone'] ?? '') ?> • <?= $cust['log_date'] ?></div>
                    </div>
                    <div class="debt-amount">৳<?= number_format($cust['due_amount'], 0) ?></div>
                </div>
                <div class="settle-form">
                    <button class="settle-btn" style="width: 100%;" onclick="settleCustomer(<?= $cust['id'] ?>)">Mark as Paid</button>
                </div>
                <div class="success-msg" id="msg-cust-<?= $cust['id'] ?>">Settled!</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    async function settleSupplier(supplierId) {
        const input = document.getElementById('supp-' + supplierId);
        const amount = parseFloat(input.value);

        if (!amount || amount <= 0) {
            alert('Enter a valid amount');
            return;
        }

        try {
            const res = await fetch('?url=admin/settleSupplierDue', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ supplier_id: supplierId, amount_paid: amount })
            });
            const data = await res.json();

            if (data.success) {
                const msg = document.getElementById('msg-sup-' + supplierId);
                msg.style.display = 'block';
                setTimeout(() => location.reload(), 1500);
            } else {
                alert(data.error || 'Error processing payment');
            }
        } catch (e) {
            alert('Network error');
        }
    }

    async function settleCustomer(customerDueId) {
        if (!confirm('Mark this customer due as paid?')) return;

        try {
            const res = await fetch('?url=admin/settleCustomerDue', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ customer_due_id: customerDueId })
            });
            const data = await res.json();

            if (data.success) {
                const msg = document.getElementById('msg-cust-' + customerDueId);
                msg.style.display = 'block';
                setTimeout(() => location.reload(), 1500);
            } else {
                alert(data.error || 'Error settling due');
            }
        } catch (e) {
            alert('Network error');
        }
    }
</script>