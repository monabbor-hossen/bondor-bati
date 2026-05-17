<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Bondor Bati</title>
    <meta name="description" content="Log in to the Bondor Bati POS system.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>

<body class="bg-slate-100 font-sans antialiased min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm animate-in">
        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            <div
                class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center shadow-lg shadow-blue-500/25">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Bondor Bati</h1>
            <p class="text-sm text-slate-500 mt-1">Sign in to your account</p>
        </div>

        <!-- Login Form Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 p-6">
            <?php if (isset($login_error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm font-medium rounded-xl px-4 py-3 mb-4">
                    <?= htmlspecialchars($login_error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="/bondor-bati/login" class="space-y-4">
                <div>
                    <label for="username"
                        class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username"
                        class="w-full h-12 px-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-slate-800 font-medium
                                  focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                </div>
                <div>
                    <label for="password"
                        class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        class="w-full h-12 px-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-slate-800 font-medium
                                  focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                </div>
                <button type="submit"
                    class="w-full h-12 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold text-sm uppercase tracking-wide
                               shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 active:scale-[0.98] transition-all duration-200">
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">&copy; <?= date('Y') ?> Bondor Bati POS</p>
    </div>

</body>

</html>