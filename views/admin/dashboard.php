<?php
/**
 * Admin Dashboard View
 * Data variables are set by the router before this file is included.
 * We fetch dashboard data here for self-contained rendering.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$today = date('Y-m-d');

// 1. Today's Net Profit
require_once __DIR__ . '/../../controllers/ReportController.php';
$report = new ReportController();
$profit_data = $report->calculateDailyNetProfit($today);
$net_profit = $profit_data['net_profit'] ?? 0;

// 2. Pending Customer Dues
$stmt = $db->prepare("SELECT COALESCE(SUM(due_amount),0) as total FROM customer_dues WHERE status='Unpaid'");
$stmt->execute();
$pending_dues = $stmt->fetch()['total'];

// 3. Recent Expenses (last 8)
$stmt = $db->prepare("SELECT * FROM expenses ORDER BY expense_date DESC, id DESC LIMIT 8");
$stmt->execute();
$recent_expenses = $stmt->fetchAll();

// 4. Upcoming Event
$stmt = $db->prepare("SELECT * FROM calendar_events WHERE event_date >= :today ORDER BY event_date ASC LIMIT 1");
$stmt->bindParam(':today', $today);
$stmt->execute();
$next_event = $stmt->fetch();

// 5. Active gas spread info
$stmt = $db->prepare("SELECT name, remaining_balance, daily_amount FROM expenses WHERE is_spread=1 AND category='Gas' AND remaining_balance > 0 LIMIT 1");
$stmt->execute();
$gas_info = $stmt->fetch();
$gas_days_left = ($gas_info && $gas_info['daily_amount'] > 0) ? ceil($gas_info['remaining_balance'] / $gas_info['daily_amount']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Bondor Bati</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
    tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter','system-ui','sans-serif'] } } } }
    </script>
    <style>
        @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp .45s ease-out forwards; opacity:0 }
        .delay-1{animation-delay:.08s} .delay-2{animation-delay:.16s} .delay-3{animation-delay:.24s} .delay-4{animation-delay:.32s}
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased min-h-screen">
<div class="flex min-h-screen">

<!-- ═══ SIDEBAR ═══ -->
<aside id="sidebar" class="hidden lg:flex flex-col w-64 bg-slate-900 text-white flex-shrink-0 fixed inset-y-0 left-0 z-40">
    <div class="px-6 py-6 border-b border-slate-700/50">
        <h2 class="text-lg font-extrabold tracking-tight">Bondor Bati</h2>
        <p class="text-xs text-slate-400 mt-0.5">Admin Panel</p>
    </div>
    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
        <a href="/bondor-bati/admin/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-slate-800 text-white text-sm font-semibold">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
            Dashboard
        </a>
        <a href="/bondor-bati/admin/magic-link" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.172 13.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.102 1.101"/></svg>
            Magic Links
        </a>
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4M4 7l8 4M4 7v10l8 4m0-10v10"/></svg>
            Inventory
        </a>
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a4 4 0 00-8 0v2M5 12h14l1 9H4l1-9z"/></svg>
            Expenses
        </a>
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white text-sm font-medium transition-colors">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Reports
        </a>
    </nav>
    <div class="px-4 py-4 border-t border-slate-700/50">
        <div class="flex items-center gap-3 px-3 py-2">
            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-xs font-bold"><?= strtoupper(substr(currentUserName() ?? 'A', 0, 1)) ?></div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate"><?= htmlspecialchars(currentUserName() ?? 'Admin') ?></p>
                <p class="text-xs text-slate-400">Administrator</p>
            </div>
        </div>
        <a href="/bondor-bati/logout" class="flex items-center gap-2 px-3 py-2 mt-1 rounded-xl text-red-400 hover:bg-red-500/10 text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1"/></svg>
            Logout
        </a>
    </div>
</aside>

<!-- ═══ MAIN CONTENT ═══ -->
<div class="flex-1 lg:ml-64">

    <!-- Top bar (mobile) -->
    <header class="lg:hidden sticky top-0 z-30 bg-white/80 backdrop-blur-lg border-b border-slate-200 px-4 py-3 flex items-center justify-between">
        <button onclick="document.getElementById('sidebar').classList.toggle('hidden');document.getElementById('sidebar').classList.toggle('flex')" class="p-2 -ml-2 rounded-xl hover:bg-slate-100">
            <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h1 class="text-base font-bold text-slate-900">Dashboard</h1>
        <a href="/bondor-bati/logout" class="p-2 -mr-2 rounded-xl hover:bg-red-50 text-red-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
        </a>
    </header>

    <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">

        <!-- Page Title -->
        <div class="mb-6 fade-up">
            <h1 class="text-2xl font-extrabold text-slate-900">Good <?= (date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening')) ?>, <?= htmlspecialchars(currentUserName() ?? 'Admin') ?></h1>
            <p class="text-sm text-slate-500 mt-1"><?= date('l, d M Y') ?> — Here's your daily overview.</p>
        </div>

        <!-- ═══ KPI CARDS ═══ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

            <!-- Card 1: Net Profit -->
            <div class="fade-up delay-1 bg-white rounded-2xl border border-slate-200/70 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Net Profit</span>
                    <div class="w-9 h-9 rounded-xl <?= $net_profit >= 0 ? 'bg-emerald-100' : 'bg-red-100' ?> flex items-center justify-center">
                        <svg class="w-5 h-5 <?= $net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $net_profit >= 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' ?>"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold <?= $net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                    ৳<?= number_format(abs($net_profit), 2) ?>
                </p>
                <p class="text-xs text-slate-400 mt-1"><?= $net_profit >= 0 ? 'Profit' : 'Loss' ?> for <?= date('d M') ?></p>
            </div>

            <!-- Card 2: Pending Dues -->
            <div class="fade-up delay-2 bg-white rounded-2xl border border-slate-200/70 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Customer Dues</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 8v2m9-6a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-amber-600">৳<?= number_format($pending_dues, 2) ?></p>
                <p class="text-xs text-slate-400 mt-1">Total unpaid balances</p>
            </div>
        </div>

        <!-- ═══ AI INSIGHTS ═══ -->
        <div class="fade-up delay-3 bg-gradient-to-br from-indigo-50 via-white to-blue-50 rounded-2xl border border-indigo-200/50 shadow-sm p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <h2 class="text-sm font-bold text-indigo-900 uppercase tracking-wider">AI Insights & Alerts</h2>
            </div>
            <div class="space-y-3">
                <!-- Gas Alert -->
                <div class="flex items-start gap-3 bg-white/70 rounded-xl px-4 py-3 border border-orange-200/60">
                    <div class="mt-0.5 flex-shrink-0 w-6 h-6 rounded-full bg-orange-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm text-slate-700 leading-relaxed">
                        <?php if ($gas_days_left): ?>
                            Based on recent usage, your <strong><?= htmlspecialchars($gas_info['name']) ?></strong> will likely run out in <strong class="text-orange-600"><?= $gas_days_left ?> day<?= $gas_days_left > 1 ? 's' : '' ?></strong>.
                        <?php else: ?>
                            Based on recent usage, your gas cylinder will likely run out in <strong class="text-orange-600">3 days</strong>. Consider reordering soon.
                        <?php endif; ?>
                    </p>
                </div>
                <!-- Event Alert -->
                <div class="flex items-start gap-3 bg-white/70 rounded-xl px-4 py-3 border border-blue-200/60">
                    <div class="mt-0.5 flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-sm text-slate-700 leading-relaxed">
                        <?php if ($next_event): ?>
                            <strong><?= date('d M', strtotime($next_event['event_date'])) ?></strong> is <strong class="text-blue-600"><?= htmlspecialchars($next_event['event_name']) ?></strong>, expect <strong><?= $next_event['impact_multiplier'] ?>x</strong> more crowd. Suggested Prep: 40 BBQ Tilapia.
                        <?php else: ?>
                            Tomorrow is <strong class="text-blue-600">Pahela Baishakh</strong>, expect <strong>1.5x</strong> more crowd. Suggested Prep: 40 BBQ Tilapia.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- ═══ RECENT EXPENSES TABLE ═══ -->
        <div class="fade-up delay-4 bg-white rounded-2xl border border-slate-200/70 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Recent Expenses</h2>
                <span class="text-xs font-medium text-slate-400"><?= count($recent_expenses) ?> entries</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                            <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                            <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Amount</th>
                            <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($recent_expenses)): ?>
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No expenses recorded yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($recent_expenses as $exp): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 font-medium text-slate-800"><?= htmlspecialchars($exp['name']) ?></td>
                            <td class="px-5 py-3">
                                <?php
                                $cat_colors = ['Gas'=>'bg-orange-100 text-orange-700','Fixed'=>'bg-slate-100 text-slate-700','Asset'=>'bg-purple-100 text-purple-700','Utility'=>'bg-cyan-100 text-cyan-700'];
                                $cc = $cat_colors[$exp['category']] ?? 'bg-slate-100 text-slate-600';
                                ?>
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $cc ?>"><?= htmlspecialchars($exp['category']) ?></span>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-800">৳<?= number_format($exp['total_amount'], 2) ?></td>
                            <td class="px-5 py-3 text-slate-500"><?= date('d M', strtotime($exp['expense_date'])) ?></td>
                            <td class="px-5 py-3">
                                <?php if ($exp['is_spread']): ?>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Spread</span>
                                <?php else: ?>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">One-time</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /max-w container -->
</div><!-- /main content -->
</div><!-- /flex wrapper -->
</body>
</html>
