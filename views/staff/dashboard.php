<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Bondor Bati</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body class="bg-slate-100 font-[Inter] min-h-screen pb-20">

    <header class="bg-slate-900 text-white px-5 py-4 flex items-center justify-between sticky top-0 z-10 shadow-md">
        <div>
            <h1 class="text-lg font-bold">Bondor Bati Cart</h1>
            <p class="text-xs text-slate-400">Hi,
                <?= htmlspecialchars(currentUserName()) ?>
            </p>
        </div>
        <a href="/bondor-bati/logout"
            class="px-3 py-1.5 bg-slate-800 rounded-lg text-sm font-medium hover:bg-red-500 hover:text-white transition-colors">Logout</a>
    </header>

    <main class="p-4">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4">Today's Menu & Stock</h2>

        <div class="space-y-3">
            <?php if (empty($today_stocks)): ?>
                <div class="bg-white p-6 rounded-2xl text-center text-slate-500 border border-slate-200">
                    No menu items found. Admin needs to add items.
                </div>
            <?php else: ?>
                <?php foreach ($today_stocks as $item): ?>
                    <div
                        class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/60 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-900 text-lg">
                                <?= htmlspecialchars($item['item_name']) ?>
                            </h3>
                            <p class="text-sm text-slate-500">৳
                                <?= number_format($item['selling_price'], 2) ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="block text-2xl font-black text-blue-600">
                                <?= (int) $item['opening_qty'] ?>
                            </span>
                            <span class="text-[10px] uppercase font-bold text-slate-400">In Stock</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div
        class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-slate-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <a href="/bondor-bati/staff/closing"
            class="w-full flex items-center justify-center py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Submit Night Closing
        </a>
    </div>

</body>

</html>