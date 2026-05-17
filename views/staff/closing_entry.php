<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Night Closing | Bondor Bati</title>
    <meta name="description" content="Staff nightly closing entry for Bondor Bati POS">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50:  '#f0f7ff',
                            100: '#e0effe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        surface: {
                            50:  '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Smooth entrance animations */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .animate-slide-up {
            animation: slideUp 0.4s ease-out forwards;
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        .item-card {
            animation: slideUp 0.4s ease-out forwards;
            opacity: 0;
        }

        /* Remove number input spinners for cleaner look */
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] { -moz-appearance: textfield; }

        /* Tap highlight for mobile */
        .tap-highlight:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }

        /* Pulse animation for submit */
        @keyframes subtlePulse {
            0%, 100% { box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.35); }
            50%      { box-shadow: 0 4px 20px 0 rgba(37, 99, 235, 0.55); }
        }
        .btn-pulse { animation: subtlePulse 2.5s ease-in-out infinite; }

        /* Toast notification */
        .toast {
            animation: slideUp 0.3s ease-out forwards;
        }
        .toast-exit {
            animation: fadeOut 0.3s ease-out forwards;
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-surface-100 font-sans antialiased min-h-screen">

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- HEADER                                                        -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-lg border-b border-surface-200 animate-fade-in">
        <div class="max-w-lg mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-800 text-surface-900 tracking-tight leading-none">
                    Bondor Bati
                </h1>
                <p class="text-xs font-medium text-surface-400 mt-0.5 tracking-wide uppercase">
                    Night Closing
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span id="currentDate" class="text-xs font-semibold text-surface-500 bg-surface-100 px-3 py-1.5 rounded-full"></span>
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-md">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                </div>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- MAIN CONTENT                                                  -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <main class="max-w-lg mx-auto px-4 pt-5 pb-32">

        <!-- Instruction Banner -->
        <div class="bg-brand-50 border border-brand-100 rounded-2xl px-4 py-3.5 mb-5 flex items-start gap-3 animate-slide-up">
            <div class="mt-0.5 flex-shrink-0">
                <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm text-brand-700 leading-relaxed">
                Count the <strong>remaining items</strong> in each tray and enter the numbers below. Mark any <strong>spoiled</strong> items separately.
            </p>
        </div>

        <!-- Closing Entry Form -->
        <form id="closingForm" method="POST" action="/bondor-bati/api/routes.php?action=submit_closing" class="space-y-3">
            
            <?php
            /**
             * $menu_items is expected to be passed to this view as an array of associative arrays.
             * Example structure:
             * $menu_items = [
             *     ['id' => 1, 'item_name' => 'BBQ Telapia',    'opening_qty' => 25],
             *     ['id' => 2, 'item_name' => 'Grilled Chicken', 'opening_qty' => 18],
             *     ['id' => 3, 'item_name' => 'Fish Curry',      'opening_qty' => 12],
             * ];
             */

            // Fallback demo data if $menu_items is not set (for preview purposes)
            if (!isset($menu_items) || empty($menu_items)) {
                $menu_items = [
                    ['id' => 1, 'item_name' => 'BBQ Telapia',      'opening_qty' => 25],
                    ['id' => 2, 'item_name' => 'Grilled Chicken',   'opening_qty' => 18],
                    ['id' => 3, 'item_name' => 'Fish Curry',        'opening_qty' => 12],
                    ['id' => 4, 'item_name' => 'Beef Bhuna',        'opening_qty' => 10],
                    ['id' => 5, 'item_name' => 'Mixed Vegetables',  'opening_qty' => 15],
                ];
            }

            foreach ($menu_items as $index => $item):
                $delay = ($index * 60) + 100; // stagger animation
            ?>

            <!-- Item Card: <?= htmlspecialchars($item['item_name']) ?> -->
            <div class="item-card bg-white rounded-2xl shadow-sm border border-surface-200/70 overflow-hidden hover:shadow-md transition-shadow duration-200" 
                 style="animation-delay: <?= $delay ?>ms">
                
                <!-- Item Header -->
                <div class="px-4 pt-4 pb-2 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-surface-100 to-surface-200 flex items-center justify-center flex-shrink-0">
                            <span class="text-base font-bold text-surface-600"><?= $index + 1 ?></span>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-surface-800 leading-tight">
                                <?= htmlspecialchars($item['item_name']) ?>
                            </h3>
                            <p class="text-xs text-surface-400 mt-0.5">
                                Opened: <span class="font-semibold text-surface-500"><?= (int) $item['opening_qty'] ?> pcs</span>
                            </p>
                        </div>
                    </div>
                    <!-- Live status indicator -->
                    <div class="status-dot w-2.5 h-2.5 rounded-full bg-surface-300 flex-shrink-0" 
                         id="status-<?= (int) $item['id'] ?>"></div>
                </div>

                <!-- Input Fields -->
                <div class="px-4 pb-4 pt-1 grid grid-cols-2 gap-3">
                    <!-- Closing / Leftover Qty -->
                    <div>
                        <label for="closing_<?= (int) $item['id'] ?>" 
                               class="block text-xs font-semibold text-surface-500 mb-1.5 uppercase tracking-wider">
                            Leftover
                        </label>
                        <input type="number"
                               id="closing_<?= (int) $item['id'] ?>"
                               name="items[<?= (int) $item['id'] ?>][closing_qty]"
                               min="0"
                               max="<?= (int) $item['opening_qty'] ?>"
                               placeholder="0"
                               required
                               class="w-full h-14 text-center text-xl font-bold text-surface-800 
                                      bg-surface-50 border-2 border-surface-200 rounded-xl
                                      focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 
                                      transition-all duration-200 tap-highlight"
                               oninput="handleInput(<?= (int) $item['id'] ?>)">
                    </div>

                    <!-- Wastage / Spoiled Qty -->
                    <div>
                        <label for="wastage_<?= (int) $item['id'] ?>" 
                               class="block text-xs font-semibold text-surface-500 mb-1.5 uppercase tracking-wider">
                            Spoiled
                        </label>
                        <input type="number"
                               id="wastage_<?= (int) $item['id'] ?>"
                               name="items[<?= (int) $item['id'] ?>][wastage_qty]"
                               min="0"
                               max="<?= (int) $item['opening_qty'] ?>"
                               placeholder="0"
                               class="w-full h-14 text-center text-xl font-bold text-red-600 
                                      bg-red-50/50 border-2 border-surface-200 rounded-xl
                                      focus:outline-none focus:border-red-400 focus:ring-4 focus:ring-red-400/10 
                                      transition-all duration-200 tap-highlight"
                               oninput="handleInput(<?= (int) $item['id'] ?>)">
                    </div>
                </div>
            </div>

            <?php endforeach; ?>

        </form>
    </main>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- STICKY SUBMIT BUTTON                                          -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="fixed bottom-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-t border-surface-200 animate-fade-in">
        <div class="max-w-lg mx-auto px-4 py-4">
            <!-- Summary bar -->
            <div class="flex items-center justify-between mb-3 text-xs font-semibold text-surface-500">
                <span>Items filled: <span id="filledCount" class="text-surface-800">0</span> / <?= count($menu_items) ?></span>
                <span id="totalSpoiled" class="text-red-500 opacity-0 transition-opacity duration-200">0 spoiled</span>
            </div>
            <button type="submit" 
                    form="closingForm"
                    id="submitBtn"
                    disabled
                    class="w-full h-14 rounded-2xl text-base font-bold tracking-wide uppercase
                           bg-surface-300 text-surface-500 cursor-not-allowed
                           transition-all duration-300 ease-out
                           flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Submit Closing
            </button>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toastContainer" class="fixed top-20 left-1/2 -translate-x-1/2 z-50"></div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- JAVASCRIPT                                                    -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <script>
        // Set current date in header
        const dateEl = document.getElementById('currentDate');
        const now = new Date();
        dateEl.textContent = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

        const totalItems = <?= count($menu_items) ?>;
        const submitBtn  = document.getElementById('submitBtn');
        const filledEl   = document.getElementById('filledCount');
        const spoiledEl  = document.getElementById('totalSpoiled');

        function handleInput(itemId) {
            const statusDot = document.getElementById('status-' + itemId);
            const closingInput = document.getElementById('closing_' + itemId);

            // Update status dot
            if (closingInput.value !== '' && closingInput.value !== undefined) {
                statusDot.classList.remove('bg-surface-300');
                statusDot.classList.add('bg-emerald-400');
            } else {
                statusDot.classList.remove('bg-emerald-400');
                statusDot.classList.add('bg-surface-300');
            }

            // Count filled items
            let filled = 0;
            let totalSpoiled = 0;

            <?php foreach ($menu_items as $item): ?>
            (function() {
                const c = document.getElementById('closing_<?= (int) $item['id'] ?>');
                const w = document.getElementById('wastage_<?= (int) $item['id'] ?>');
                if (c && c.value !== '') filled++;
                if (w && w.value !== '' && parseInt(w.value) > 0) totalSpoiled += parseInt(w.value);
            })();
            <?php endforeach; ?>

            filledEl.textContent = filled;

            // Spoiled counter
            if (totalSpoiled > 0) {
                spoiledEl.textContent = totalSpoiled + ' spoiled';
                spoiledEl.classList.remove('opacity-0');
                spoiledEl.classList.add('opacity-100');
            } else {
                spoiledEl.classList.remove('opacity-100');
                spoiledEl.classList.add('opacity-0');
            }

            // Toggle submit button state
            if (filled === totalItems) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-surface-300', 'text-surface-500', 'cursor-not-allowed');
                submitBtn.classList.add('bg-gradient-to-r', 'from-brand-600', 'to-brand-700', 'text-white', 'shadow-lg', 'btn-pulse', 'active:scale-[0.98]');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('bg-surface-300', 'text-surface-500', 'cursor-not-allowed');
                submitBtn.classList.remove('bg-gradient-to-r', 'from-brand-600', 'to-brand-700', 'text-white', 'shadow-lg', 'btn-pulse', 'active:scale-[0.98]');
            }
        }

        // Form submission handler
        document.getElementById('closingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Visual feedback
            submitBtn.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Submitting...
            `;
            submitBtn.disabled = true;
            submitBtn.classList.remove('btn-pulse');

            // Gather inputs
            const payload = { items: {} };
            const formElements = this.elements;
            
            for (let i = 0; i < formElements.length; i++) {
                const el = formElements[i];
                if (el.name && el.name.startsWith('items[')) {
                    // Extract item ID and field type from name attribute, e.g., items[1][closing_qty]
                    const match = el.name.match(/items\[(\d+)\]\[(closing_qty|wastage_qty)\]/);
                    if (match) {
                        const id = match[1];
                        const field = match[2];
                        if (!payload.items[id]) {
                            payload.items[id] = {};
                        }
                        payload.items[id][field] = el.value ? parseInt(el.value, 10) : 0;
                    }
                }
            }

            try {
                const response = await fetch('/bondor-bati/api/routes.php?action=submit_closing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success !== false) {
                    showToast('Stock updated successfully!', 'success');
                    submitBtn.innerHTML = `
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Submitted
                    `;
                    submitBtn.classList.remove('from-brand-600', 'to-brand-700');
                    submitBtn.classList.add('from-emerald-500', 'to-emerald-600');
                    
                    // Optional: reset form or disable inputs
                    // setTimeout(() => window.location.reload(), 2000);
                } else {
                    throw new Error(data.message || 'Submission failed');
                }
            } catch (error) {
                console.error('Error submitting closing data:', error);
                showToast(error.message || 'Network error, please try again.', 'error');
                
                // Re-enable button on error
                submitBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Submit Closing
                `;
                submitBtn.disabled = false;
                submitBtn.classList.add('btn-pulse');
            }
        });

        function showToast(message, type) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-emerald-600' : 'bg-red-500';
            toast.className = `toast ${bgColor} text-white text-sm font-semibold px-5 py-3 rounded-2xl shadow-xl`;
            toast.textContent = message;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('toast-exit');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>

</body>
</html>
