<?php
// Ensure a 404 status code is sent to the browser
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found | Bondor Bati</title>
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
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-up {
            animation: fadeUp 0.5s ease-out forwards;
        }
    </style>
</head>

<body class="bg-slate-50 font-sans antialiased min-h-screen flex items-center justify-center p-4">

    <div class="text-center max-w-md fade-up">

        <div class="relative inline-block mb-6">
            <h1 class="text-9xl font-extrabold text-slate-200 tracking-tighter drop-shadow-sm">404</h1>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="w-16 h-16 bg-red-100 rounded-2xl rotate-12 flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-red-500 -rotate-12" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-slate-900 mb-3">Page Not Found</h2>
        <p class="text-slate-500 mb-8 leading-relaxed">
            We couldn't find the page you are looking for. It might have been removed, renamed, or you typed the URL
            incorrectly.
        </p>

        <a href="/bondor-bati/"
            class="inline-flex items-center justify-center px-6 py-3.5 bg-slate-900 text-white font-semibold rounded-xl shadow-lg hover:bg-slate-800 hover:-translate-y-0.5 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Return to Dashboard
        </a>

    </div>

</body>

</html>