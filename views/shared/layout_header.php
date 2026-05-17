<?php
/**
 * Shared Layout Header
 * 
 * Include this file at the top of every view to get:
 * - Consistent <head> with meta tags, Tailwind, Inter font
 * - PWA manifest + service worker registration
 * - Mobile-optimized viewport and theme color
 * 
 * Usage: 
 *   <?php $page_title = 'Dashboard'; include __DIR__ . '/../shared/layout_header.php'; ?>
 */

$page_title = $page_title ?? 'Bondor Bati POS';
$base_path  = '/bondor-bati';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Bondor Bati POS & Inventory Management System">

    <title><?= htmlspecialchars($page_title) ?> | Bondor Bati</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#f97316">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Bondor Bati">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= $base_path ?>/public/manifest.json">

    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="<?= $base_path ?>/public/icons/icon-192x192.png">
    <link rel="apple-touch-icon" href="<?= $base_path ?>/public/icons/icon-192x192.png">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind Config -->
    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
            }
        }
    }
    </script>

    <!-- Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?= $base_path ?>/public/service-worker.js', {
                scope: '<?= $base_path ?>/'
            })
            .then((reg) => console.log('[PWA] Service worker registered, scope:', reg.scope))
            .catch((err) => console.warn('[PWA] SW registration failed:', err));
        });
    }
    </script>

    <!-- Shared Styles -->
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: fadeIn 0.45s ease-out forwards; }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased min-h-screen">
