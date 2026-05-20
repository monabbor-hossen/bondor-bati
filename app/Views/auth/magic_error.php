<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0f">
    <title>Link Error — <?= __('app_name') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>body { background: #0a0a0f; font-family: 'Inter', 'Noto Sans Bengali', sans-serif; }</style>
</head>
<body class="min-h-screen flex items-center justify-center px-6 text-slate-200">
    <div class="text-center max-w-sm">
        <div class="w-16 h-16 mx-auto mb-5 rounded-full bg-red-500/10 border border-red-500/30 flex items-center justify-center">
            <i class="fas fa-link-slash text-2xl text-red-400"></i>
        </div>
        <h2 class="text-xl font-bold mb-2">Link Invalid</h2>
        <p class="text-slate-400 text-sm mb-6"><?= htmlspecialchars($message ?? 'This link has expired or was already used.') ?></p>
        <p class="text-slate-500 text-xs">Please ask your admin for a new access link.</p>
    </div>
</body>
</html>
