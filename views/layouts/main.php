<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bondor Bati POS | <?= htmlspecialchars($pageTitle ?? 'Dashboard'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Sidebar Navigation -->
    <?php $currentPage = $page ?? 'dashboard'; ?>
    <?php $user = currentUser(); ?>
    <aside class="sidebar glass-panel" id="sidebar">
        <div class="brand" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <span><i class="fa-solid fa-fire-burner"></i> Bondor Bati</span>
            <a href="?<?= http_build_query(array_merge($_GET, ['action' => 'toggle_lang'])) ?>" class="btn btn-glass" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; text-decoration: none; border-radius: 6px;">
                <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'বাংলা' : 'EN' ?>
            </a>
        </div>
        
        <ul class="nav-links">
            <?php
            // Main nav pages (shown in sidebar list)
            $mainNavPages = ['dashboard', 'morning', 'service', 'closing', 'forecast', 'staff'];
            foreach ($mainNavPages as $slug):
                if (!canAccess($slug)) continue;
                $meta = ALL_PAGES[$slug];
            ?>
            <li><a href="?page=<?= $slug; ?>" class="nav-item <?= $currentPage === $slug ? 'active' : ''; ?>">
                <i class="fa-solid <?= $meta['icon']; ?>"></i> <?= __($meta['label']); ?>
            </a></li>
            <?php endforeach; ?>
        </ul>

        <div style="margin-top: auto; display: flex; flex-direction: column; gap: 0.5rem;">
            <?php if (canAccess('items')): ?>
            <a href="?page=items" class="btn btn-glass <?= $currentPage === 'items' ? 'active' : ''; ?>" style="width: 100%; justify-content: center; text-decoration: none;">
                <i class="fa-solid fa-list"></i> <?= __('Menu Items'); ?>
            </a>
            <?php endif; ?>
            <?php if (canAccess('suppliers')): ?>
            <a href="?page=suppliers" class="btn btn-glass <?= $currentPage === 'suppliers' ? 'active' : ''; ?>" style="width: 100%; justify-content: center; text-decoration: none;">
                <i class="fa-solid fa-truck"></i> <?= __('Suppliers'); ?>
            </a>
            <?php endif; ?>

            <!-- User Info & Logout -->
            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--glass-border);">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div class="avatar" style="width: 36px; height: 36px; font-size: 0.8rem; flex-shrink: 0;">
                        <?= strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-weight: 600; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($user['name']); ?>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">
                            <span class="badge <?= $user['role'] === 'ADMIN' ? 'warning' : 'success'; ?>"><?= $user['role']; ?></span>
                        </div>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-danger" style="width: 100%; justify-content: center; text-decoration: none;">
                    <i class="fa-solid fa-right-from-bracket"></i> <?= __('Logout'); ?>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <?php include $contentView; ?>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>
