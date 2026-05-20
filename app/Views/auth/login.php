<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <title><?= __('login') ?> — <?= __('app_name') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { surface: '#0a0a0f', card: '#12121a', border: '#1e1e2e', accent: '#f43f5e', 'accent-light': '#fb7185', 'text-primary': '#e2e8f0', 'text-muted': '#64748b' },
                fontFamily: { sans: ['Inter', 'Noto Sans Bengali', 'system-ui', 'sans-serif'] }
            }}
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Noto+Sans+Bengali:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #0a0a0f; font-family: 'Inter', 'Noto Sans Bengali', sans-serif; }
        * { -webkit-tap-highlight-color: transparent; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes glow { 0%, 100% { box-shadow: 0 0 20px rgba(244,63,94,0.15); } 50% { box-shadow: 0 0 40px rgba(244,63,94,0.25); } }
        .glow { animation: glow 3s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-6 antialiased">

    <div class="w-full max-w-sm animate-fadeIn">

        <!-- Brand -->
        <div class="text-center mb-10">
            <h1 class="text-4xl font-black text-text-primary tracking-tight mb-2">
                <?= currentLang() === 'bn' ? 'বন্দর' : 'Bondor' ?><span class="text-accent">.</span><?= currentLang() === 'bn' ? 'বাটি' : 'Bati' ?>
            </h1>
            <p class="text-text-muted text-sm font-medium">Premium Street Food POS</p>
        </div>

        <!-- Login Card -->
        <div class="bg-card border border-border rounded-2xl p-6 glow">

            <!-- Error Message -->
            <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl text-sm font-medium mb-5 flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Admin Login Form -->
            <div class="mb-6">
                <h2 class="text-sm font-bold text-text-muted uppercase tracking-widest mb-4">
                    <i class="fas fa-shield-halved mr-1 text-accent"></i> <?= __('admin') ?> <?= __('login') ?>
                </h2>

                <form method="POST" action="?url=auth/login" id="login-form">
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-text-muted uppercase tracking-wider mb-1.5"><?= __('username') ?></label>
                        <input type="text" name="username" id="login-username" required autocomplete="username"
                               class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-text-primary text-base
                                      focus:border-accent transition-colors duration-200 placeholder-text-muted/40"
                               placeholder="admin">
                    </div>
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-text-muted uppercase tracking-wider mb-1.5"><?= __('password') ?></label>
                        <input type="password" name="password" id="login-password" required autocomplete="current-password"
                               class="w-full bg-surface border border-border rounded-xl px-4 py-3 text-text-primary text-base
                                      focus:border-accent transition-colors duration-200 placeholder-text-muted/40"
                               placeholder="••••••••">
                    </div>
                    <button type="submit" id="login-submit"
                            class="w-full bg-accent hover:bg-accent-light text-white font-bold py-3.5 rounded-xl
                                   transition-all duration-200 active:scale-[0.97] text-base tracking-wide">
                        <i class="fas fa-arrow-right-to-bracket mr-2"></i> <?= __('login_btn') ?>
                    </button>
                </form>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-3 my-5">
                <div class="flex-1 h-px bg-border"></div>
                <span class="text-text-muted text-[0.65rem] font-bold uppercase tracking-widest">OR</span>
                <div class="flex-1 h-px bg-border"></div>
            </div>

            <!-- Staff Magic Link Info -->
            <div class="text-center">
                <p class="text-text-muted text-xs font-medium mb-1">
                    <i class="fas fa-link mr-1 text-accent/60"></i> <?= __('magic_link_title') ?>
                </p>
                <p class="text-text-muted/60 text-[0.7rem]"><?= __('magic_link_desc') ?></p>
            </div>
        </div>

        <!-- Language Toggle -->
        <div class="text-center mt-6">
            <?php $oppLang = currentLang() === 'en' ? 'bn' : 'en'; ?>
            <a href="?url=auth/login&lang=<?= $oppLang ?>"
               class="text-xs font-bold text-text-muted hover:text-accent transition-colors px-3 py-1.5 rounded-full border border-border hover:border-accent">
                <?= $oppLang === 'bn' ? 'বাংলা' : 'English' ?>
            </a>
        </div>
    </div>
</body>
</html>
