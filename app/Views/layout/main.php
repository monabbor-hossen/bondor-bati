<!DOCTYPE html>
<html lang="<?= currentLang() ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <meta name="description" content="<?= __('app_name') ?> — Premium Street Food POS">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= htmlspecialchars($pageTitle ?? __('app_name')) ?> — <?= __('app_name') ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        surface: '#0a0a0f',
                        card: '#12121a',
                        'card-hover': '#1a1a26',
                        border: '#1e1e2e',
                        accent: '#f43f5e',
                        'accent-light': '#fb7185',
                        'accent-dark': '#be123c',
                        success: '#10b981',
                        warning: '#f59e0b',
                        info: '#6366f1',
                        'text-primary': '#e2e8f0',
                        'text-muted': '#64748b',
                    },
                    fontFamily: {
                        sans: ['Inter', 'Noto Sans Bengali', 'system-ui', 'sans-serif'],
                        bengali: ['Noto Sans Bengali', 'Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Google Fonts: Inter + Noto Sans Bengali -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">

    <style>
        /* Critical overrides for ultra-dark mobile UI */
        * { -webkit-tap-highlight-color: transparent; }
        body {
            background: #0a0a0f;
            color: #e2e8f0;
            font-family: 'Inter', 'Noto Sans Bengali', system-ui, sans-serif;
            min-height: 100dvh;
            overscroll-behavior: none;
        }
        /* Hide scrollbar but allow scrolling */
        ::-webkit-scrollbar { width: 0; height: 0; }

        /* Smooth input focus ring */
        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(244, 63, 94, 0.4);
        }

        /* Bottom nav safe area */
        .app-main { padding-bottom: 5.5rem; }

        /* Glass card effect */
        .glass {
            background: rgba(18, 18, 26, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(30, 30, 46, 0.6);
        }

        /* Stat chip pulse animation */
        @keyframes pulse-accent {
            0%, 100% { box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.3); }
            50% { box-shadow: 0 0 0 8px rgba(244, 63, 94, 0); }
        }
        .pulse-accent { animation: pulse-accent 2s infinite; }

        /* Slide up animation for cards */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slideUp { animation: slideUp 0.3s ease-out forwards; }

        /* Stagger children */
        .stagger > * { opacity: 0; animation: slideUp 0.3s ease-out forwards; }
        .stagger > *:nth-child(1) { animation-delay: 0.05s; }
        .stagger > *:nth-child(2) { animation-delay: 0.1s; }
        .stagger > *:nth-child(3) { animation-delay: 0.15s; }
        .stagger > *:nth-child(4) { animation-delay: 0.2s; }
        .stagger > *:nth-child(5) { animation-delay: 0.25s; }
        .stagger > *:nth-child(6) { animation-delay: 0.3s; }

        /* Toast notification */
        .toast {
            position: fixed; top: 1rem; left: 50%; transform: translateX(-50%);
            z-index: 9999; padding: 0.75rem 1.25rem;
            border-radius: 12px; font-weight: 600; font-size: 0.85rem;
            animation: slideDown 0.3s ease-out;
            max-width: 90vw; text-align: center;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translate(-50%, -20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }

        /* Offline indicator */
        #offline-banner {
            display: none;
            position: fixed; bottom: 4.5rem; left: 50%; transform: translateX(-50%);
            background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fbbf24; padding: 0.5rem 1rem; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600; z-index: 100;
            backdrop-filter: blur(8px);
        }

        /* Bengali font tweaks */
        .font-bn { font-family: 'Noto Sans Bengali', sans-serif; }

        /* Number inputs remove spinners */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type="number"] { -moz-appearance: textfield; }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col">

        <!-- ═══════════════════════════════════════════════════════
             TOP HEADER
        ════════════════════════════════════════════════════════ -->
        <header class="sticky top-0 z-50 glass px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-black tracking-tight">
                    <?= __('app_name') !== 'বন্দর বাটি' ? 'Bondor' : 'বন্দর' ?><span class="text-accent">.</span><?= __('app_name') !== 'বন্দর বাটি' ? 'Bati' : 'বাটি' ?>
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <!-- Language Toggle -->
                <?php $oppLang = currentLang() === 'en' ? 'bn' : 'en'; ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['lang' => $oppLang])) ?>"
                   id="lang-toggle"
                   class="text-xs font-bold px-2.5 py-1 rounded-full border border-border text-text-muted hover:text-accent hover:border-accent transition-all duration-200">
                    <?= $oppLang === 'bn' ? 'বাংলা' : 'EN' ?>
                </a>

                <!-- Date Badge -->
                <span class="text-[0.65rem] font-semibold text-text-muted bg-card px-2 py-1 rounded-full border border-border hidden sm:inline">
                    <?= date('D, d M') ?>
                </span>

                <!-- Offline indicator dot -->
                <span id="connection-dot" class="w-2 h-2 rounded-full bg-success transition-colors duration-300" title="Online"></span>
            </div>
        </header>

        <!-- ═══════════════════════════════════════════════════════
             MAIN CONTENT AREA
        ════════════════════════════════════════════════════════ -->
        <main class="app-main flex-1 px-4 pt-4">
            <?php require ROOT_PATH . '/app/Views/' . ($contentView ?? 'dashboard/index') . '.php'; ?>
        </main>

        <!-- ═══════════════════════════════════════════════════════
             OFFLINE BANNER
        ════════════════════════════════════════════════════════ -->
        <div id="offline-banner">
            <i class="fas fa-wifi-slash mr-1"></i> <?= __('offline_mode') ?>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             BOTTOM NAVIGATION BAR
        ════════════════════════════════════════════════════════ -->
        <nav class="fixed bottom-0 inset-x-0 z-50 glass border-t border-border">
            <div class="flex items-stretch justify-around max-w-lg mx-auto">
                <?php
                $role = $_SESSION['role'] ?? 'staff';
                $nav = [];
                if ($role === 'admin') {
                    $nav[] = ['url' => 'dashboard', 'key' => 'dashboard', 'icon' => 'fas fa-home',          'label' => 'nav_dashboard'];
                }
                $nav[] = ['url' => 'bazaar',    'key' => 'bazaar',    'icon' => 'fas fa-cart-shopping',  'label' => 'nav_prep'];
                $nav[] = ['url' => 'inventory/closeDayView', 'key' => 'close', 'icon' => 'fas fa-moon',     'label' => 'nav_close'];
                if ($role === 'admin') {
                    $nav[] = ['url' => 'settings',  'key' => 'settings',  'icon' => 'fas fa-cog',           'label' => 'nav_settings'];
                }

                foreach ($nav as $item):
                    $isActive = ($activeNav ?? '') === $item['key'];
                ?>
                <a href="?url=<?= $item['url'] ?>"
                   class="flex flex-col items-center justify-center py-2 px-3 min-h-[3.5rem] text-[0.6rem] font-semibold uppercase tracking-wider transition-colors duration-200
                          <?= $isActive ? 'text-accent' : 'text-text-muted hover:text-text-primary' ?>">
                    <i class="<?= $item['icon'] ?> text-lg mb-0.5 <?= $isActive ? 'text-accent' : '' ?>"></i>
                    <?= __($item['label']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </nav>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         TOAST CONTAINER
    ════════════════════════════════════════════════════════ -->
    <div id="toast-container"></div>

    <!-- ═══════════════════════════════════════════════════════
         GLOBAL JS UTILITIES & INDEXEDDB SYNC
    ════════════════════════════════════════════════════════ -->
    <script>
        // ── Toast Notifications ───────────────────────────────
        function showToast(message, type = 'success', duration = 3000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-emerald-500/20 border border-emerald-500/40 text-emerald-400',
                error:   'bg-red-500/20 border border-red-500/40 text-red-400',
                warning: 'bg-amber-500/20 border border-amber-500/40 text-amber-400',
                info:    'bg-indigo-500/20 border border-indigo-500/40 text-indigo-400',
            };
            toast.className = `toast ${colors[type] || colors.info}`;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; }, duration - 300);
            setTimeout(() => toast.remove(), duration);
        }

        // ── IndexedDB Offline Queue ───────────────────────────
        const DB_NAME = 'bondor_sync_db';
        const STORE_NAME = 'sync_queue';
        
        function initDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, 1);
                request.onupgradeneeded = e => {
                    const db = e.target.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                    }
                };
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }
        
        async function saveToQueue(url, data) {
            const db = await initDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                tx.objectStore(STORE_NAME).add({ url, data, timestamp: Date.now() });
                tx.oncomplete = () => resolve();
                tx.onerror = () => reject(tx.error);
            });
        }
        
        async function processQueue() {
            if (!navigator.onLine) return;
            const db = await initDB();
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.getAll();
            
            request.onsuccess = async () => {
                const items = request.result;
                if (items.length === 0) return;
                
                showToast(`Syncing ${items.length} offline records...`, 'info');
                let successCount = 0;
                
                for (let item of items) {
                    try {
                        const res = await fetch(item.url, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(item.data)
                        });
                        if (res.ok) {
                            await new Promise((resolve) => {
                                const delTx = db.transaction(STORE_NAME, 'readwrite');
                                delTx.objectStore(STORE_NAME).delete(item.id);
                                delTx.oncomplete = resolve;
                            });
                            successCount++;
                        }
                    } catch (e) {
                        console.error('Sync failed for item', item);
                    }
                }
                
                if (successCount > 0) {
                    showToast(`Successfully synced ${successCount} records.`, 'success');
                }
            };
        }

        // ── Offline/Online Detection ──────────────────────────
        const offlineBanner = document.getElementById('offline-banner');
        const connDot       = document.getElementById('connection-dot');

        function updateOnlineStatus() {
            if (navigator.onLine) {
                offlineBanner.style.display = 'none';
                connDot.classList.remove('bg-warning');
                connDot.classList.add('bg-success');
                connDot.title = 'Online';
                processQueue();
            } else {
                offlineBanner.style.display = 'block';
                connDot.classList.remove('bg-success');
                connDot.classList.add('bg-warning');
                connDot.title = 'Offline';
            }
        }

        window.addEventListener('online', () => { updateOnlineStatus(); showToast('Back online! Syncing...', 'success'); });
        window.addEventListener('offline', () => { updateOnlineStatus(); showToast('You are offline. Data saved locally.', 'warning'); });
        updateOnlineStatus();

        // ── Currency Formatter ────────────────────────────────
        function formatTK(amount) {
            return '৳' + parseFloat(amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        }

        // ── AJAX Helper with Queueing ─────────────────────────
        async function apiPost(url, data) {
            if (!navigator.onLine) {
                await saveToQueue(url, data);
                showToast('Offline: Data saved locally. Will sync when online.', 'warning');
                return { success: true, offline: true, message: 'Saved to local queue.' };
            }

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                return await res.json();
            } catch (err) {
                await saveToQueue(url, data);
                showToast('Network error: Data saved locally.', 'warning');
                return { success: true, offline: true, message: 'Saved to local queue.' };
            }
        }
    </script>

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/bondor-bati/sw.js')
                .then(reg => console.log('SW registered:', reg.scope))
                .catch(err => console.log('SW failed:', err));
        }

        <?php if (!empty($_SESSION['user_id'])): ?>
        // ── Real-Time Kill Switch ─────────────────────────────
        setInterval(async () => {
            try {
                const response = await fetch('?url=auth/checkStatus');
                const result = await response.json();
                if (result && result.active === false) {
                    window.location.href = '?url=auth/login';
                }
            } catch (err) {
                // Ignore network errors, only act on explicit false status
            }
        }, 10000);
        <?php endif; ?>
    </script>
</body>
</html>
