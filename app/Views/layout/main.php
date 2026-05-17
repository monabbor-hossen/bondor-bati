<?php
/**
 * Master Layout Template
 * Wrap all views inside this template by including it at the top and bottom.
 * Variables available: $pageTitle, $activeNav (home|bazaar|stock|close)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Prevents zooming on mobile for a native-app feel -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1a1a2e">
    <title><?= htmlspecialchars($pageTitle ?? 'Bondor Bati POS') ?></title>
    <style>
        /* =============================================
           DESIGN TOKENS & RESET
        ============================================= */
        :root {
            --bg-base:       #0f0f1a;
            --bg-surface:    #1a1a2e;
            --bg-card:       #16213e;
            --accent:        #e94560;
            --accent-light:  #ff6b6b;
            --text-primary:  #eaeaea;
            --text-muted:    #8899aa;
            --border:        #2a2a45;
            --success:       #2ecc71;
            --warning:       #f39c12;
            --radius:        12px;
            --tap-target:    52px;
            --font:          -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            background-color: var(--bg-base);
            color: var(--text-primary);
            font-family: var(--font);
            font-size: 16px;
            -webkit-font-smoothing: antialiased;
            overscroll-behavior: none;
        }

        /* =============================================
           APP SHELL LAYOUT
        ============================================= */
        .app-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            max-width: 540px;
            margin: 0 auto;
        }

        /* Top Header */
        .app-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            background-color: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .app-header h1 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: 0.02em;
        }

        .app-header .brand-dot {
            color: var(--accent);
        }

        .app-header .date-badge {
            font-size: 0.75rem;
            color: var(--text-muted);
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
        }

        /* Scrollable Main Content */
        .app-main {
            flex: 1;
            padding: 1.25rem;
            padding-bottom: 90px; /* Offset for bottom nav */
            overflow-y: auto;
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 540px;
            display: flex;
            background-color: var(--bg-surface);
            border-top: 1px solid var(--border);
            z-index: 200;
        }

        .bottom-nav a {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 0;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            min-height: var(--tap-target);
            transition: color 0.15s ease;
        }

        .bottom-nav a.active {
            color: var(--accent);
        }

        .bottom-nav a .nav-icon {
            font-size: 1.4rem;
            margin-bottom: 0.2rem;
            line-height: 1;
        }

        /* =============================================
           REUSABLE COMPONENTS
        ============================================= */

        /* Card */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        /* Stat Chips */
        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .stat-chip {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            text-align: center;
        }

        .stat-chip .value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-chip .label {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
        }

        .stat-chip.accent .value { color: var(--accent); }
        .stat-chip.success .value { color: var(--success); }
        .stat-chip.warning .value { color: var(--warning); }

        /* Buttons */
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            min-height: var(--tap-target);
            padding: 0.85rem 1.25rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.15s ease, transform 0.1s ease;
            letter-spacing: 0.02em;
            margin-bottom: 0.75rem;
        }

        .btn:active { transform: scale(0.97); opacity: 0.85; }

        .btn-primary   { background: var(--accent);   color: #fff; }
        .btn-secondary { background: var(--bg-card);  color: var(--text-primary); border: 1px solid var(--border); }
        .btn-success   { background: var(--success);  color: #fff; }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .form-input {
            width: 100%;
            min-height: var(--tap-target);
            padding: 0.75rem 1rem;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text-primary);
            font-size: 1rem;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .form-input:focus {
            border-color: var(--accent);
        }

        /* Item Row (for forms with multiple items) */
        .item-row {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 0.75rem;
        }

        .item-row .item-name {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .item-row .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        /* Result / Read-only Display */
        .result-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.85rem 0;
            border-bottom: 1px solid var(--border);
        }

        .result-row:last-child { border-bottom: none; }

        .result-row .result-label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .result-row .result-value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .result-value.positive { color: var(--success); }
        .result-value.negative { color: var(--accent); }

        /* Section Title */
        .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        /* Alert Banner */
        .alert {
            padding: 0.85rem 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-info    { background: rgba(79, 70, 229, 0.2);  border: 1px solid rgba(79,70,229,0.4);  color: #a5b4fc; }
        .alert-success { background: rgba(46, 204, 113, 0.2); border: 1px solid rgba(46,204,113,0.4); color: var(--success); }
        .alert-warning { background: rgba(243, 156, 18, 0.2); border: 1px solid rgba(243,156,18,0.4); color: var(--warning); }
        .alert-danger  { background: rgba(233, 69, 96, 0.2);  border: 1px solid rgba(233,69,96,0.4);  color: var(--accent-light); }
    </style>
</head>
<body>
    <div class="app-shell">

        <!-- Top Header -->
        <header class="app-header">
            <h1>Bondor<span class="brand-dot">.</span>Bati</h1>
            <span class="date-badge"><?= date('D, d M Y') ?></span>
        </header>

        <!-- Page Content is injected here -->
        <main class="app-main">
            <?php require_once ROOT_PATH . '/app/Views/' . ($contentView ?? 'home') . '.php'; ?>
        </main>

        <!-- Bottom Navigation Bar -->
        <nav class="bottom-nav">
            <a href="?url=pos" class="<?= ($activeNav ?? '') === 'pos' ? 'active' : '' ?>">
                <span class="nav-icon">💰</span>POS
            </a>
            <a href="?url=home" class="<?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span>Home
            </a>
            <a href="?url=inventory/dailyPrep" class="<?= ($activeNav ?? '') === 'stock' ? 'active' : '' ?>">
                <span class="nav-icon">📦</span>Stock
            </a>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="?url=analytics/analytics" class="<?= ($activeNav ?? '') === 'analytics' ? 'active' : '' ?>">
                <span class="nav-icon">📈</span>Analytics
            </a>
            <a href="?url=hr/hrPayroll" class="<?= ($activeNav ?? '') === 'hr' ? 'active' : '' ?>">
                <span class="nav-icon">💼</span>Payroll
            </a>
            <?php endif; ?>
            <a href="?url=inventory/closeDayView" class="<?= ($activeNav ?? '') === 'close' ? 'active' : '' ?>">
                <span class="nav-icon">🌙</span>Close
            </a>
        </nav>

    </div>
</body>
</html>
