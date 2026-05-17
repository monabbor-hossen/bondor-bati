<?php
$staffList = $staffList ?? [];
$payrollData = $payrollData ?? [];
$currentMonth = $currentMonth ?? date('m');
$currentYear = $currentYear ?? date('Y');

$monthName = date('F', mktime(0, 0, 0, $currentMonth, 1));
?>

<style>
    .form-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; margin-bottom: 1.25rem; }
    .form-row { display: flex; gap: 0.75rem; margin-bottom: 0.75rem; }
    .form-select, .form-input { flex: 1; min-height: 44px; padding: 0 0.75rem; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-primary); font-size: 0.9rem; }
    .form-btn { padding: 0 1.25rem; background: var(--accent); color: #fff; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
    .form-btn:active { opacity: 0.8; }
    .success-msg { background: rgba(46,204,113,0.2); border: 1px solid var(--success); color: var(--success); padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.8rem; margin-bottom: 0.75rem; display: none; }
    .payroll-table { width: 100%; border-collapse: collapse; }
    .payroll-table th { text-align: left; font-size: 0.65rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; padding: 0.75rem 0.5rem; border-bottom: 1px solid var(--border); }
    .payroll-table td { padding: 0.85rem 0.5rem; font-size: 0.85rem; border-bottom: 1px solid var(--border); }
    .payroll-table tr:last-child td { border-bottom: none; }
    .payroll-table .name { font-weight: 600; }
    .payroll-table .salary { color: var(--text-muted); }
    .payroll-table .absences { color: var(--warning); font-weight: 700; }
    .payroll-table .final-pay { color: var(--success); font-weight: 800; font-size: 1rem; }
    .month-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .month-nav a { color: var(--accent); text-decoration: none; font-size: 0.9rem; font-weight: 600; }
    .month-nav .current { font-size: 1.1rem; font-weight: 800; }
    .empty-state { color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 1.5rem; }
    @media (max-width: 480px) {
        .form-row { flex-direction: column; }
    }
</style>

<p class="section-title">👥 Staff Attendance & Payroll</p>

<div class="form-card">
    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem;">Log Absence</div>
    <div class="form-row">
        <select class="form-select" id="staffSelect">
            <option value="">Select Staff Member</option>
            <?php foreach ($staffList as $staff): ?>
                <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" class="form-input" id="absentDate" value="<?= date('Y-m-d') ?>">
        <button class="form-btn" onclick="logAbsence()">Log</button>
    </div>
    <div class="success-msg" id="absenceMsg">Absence recorded successfully!</div>
</div>

<div class="month-nav">
    <a href="?url=hr/hrPayroll?month=<?= $currentMonth == 1 ? 12 : $currentMonth - 1 ?>&year=<?= $currentMonth == 1 ? $currentYear - 1 : $currentYear ?>">← Prev</a>
    <span class="current"><?= $monthName . ' ' . $currentYear ?></span>
    <a href="?url=hr/hrPayroll?month=<?= $currentMonth == 12 ? 1 : $currentMonth + 1 ?>&year=<?= $currentMonth == 12 ? $currentYear + 1 : $currentYear ?>">Next →</a>
</div>

<div class="form-card" style="padding: 0; overflow: hidden;">
    <table class="payroll-table">
        <thead>
            <tr>
                <th>Staff</th>
                <th>Base</th>
                <th>Absent</th>
                <th>Final Pay</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payrollData)): ?>
                <tr><td colspan="4" class="empty-state">No staff found</td></tr>
            <?php else: ?>
                <?php foreach ($payrollData as $row): ?>
                    <tr>
                        <td class="name"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="salary">৳<?= number_format($row['monthly_salary'], 0) ?></td>
                        <td class="absences"><?= $row['absent_days'] ?> day<?= $row['absent_days'] != 1 ? 's' : '' ?></td>
                        <td class="final-pay">৳<?= number_format($row['final_pay'], 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    async function logAbsence() {
        const staffId = document.getElementById('staffSelect').value;
        const date = document.getElementById('absentDate').value;

        if (!staffId || !date) {
            alert('Please select staff and date');
            return;
        }

        try {
            const res = await fetch('?url=hr/logAbsence', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: staffId, absent_date: date })
            });
            const data = await res.json();

            if (data.success) {
                const msg = document.getElementById('absenceMsg');
                msg.style.display = 'block';
                setTimeout(() => {
                    msg.style.display = 'none';
                    location.reload();
                }, 1500);
            } else {
                alert(data.error || 'Error logging absence');
            }
        } catch (e) {
            alert('Network error');
        }
    }
</script>