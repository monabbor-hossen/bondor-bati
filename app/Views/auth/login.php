<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1a1a2e">
    <title>Login — Bondor Bati POS</title>
    <style>
        :root {
            --bg-base:    #0f0f1a;
            --bg-surface: #1a1a2e;
            --bg-card:    #16213e;
            --accent:     #e94560;
            --text:       #eaeaea;
            --text-muted: #8899aa;
            --border:     #2a2a45;
            --radius:     14px;
            --font:       -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg-base);
            color: var(--text);
            font-family: var(--font);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem 1.75rem;
        }

        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-icon {
            font-size: 2.5rem;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .brand h1 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .brand h1 span { color: var(--accent); }

        .brand p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .form-group {
            margin-bottom: 1.1rem;
        }

        label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.4rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1rem;
            outline: none;
            transition: border-color 0.15s ease;
            min-height: 52px;
        }

        input:focus { border-color: var(--accent); }

        .btn-login {
            width: 100%;
            min-height: 52px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: opacity 0.15s ease, transform 0.1s ease;
            letter-spacing: 0.02em;
        }

        .btn-login:active { transform: scale(0.97); opacity: 0.85; }

        .alert-error {
            background: rgba(233, 69, 96, 0.15);
            border: 1px solid rgba(233, 69, 96, 0.4);
            color: #ff8fa3;
            border-radius: 10px;
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            text-align: center;
        }

        .footer-note {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.72rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand">
            <div class="brand-icon">🍗</div>
            <h1>Bondor<span>.</span>Bati</h1>
            <p>Point of Sale System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="?url=auth/login">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    autocomplete="username"
                    autofocus
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn-login">Sign In →</button>
        </form>

        <p class="footer-note">Bondor Bati POS &copy; <?= date('Y') ?></p>
    </div>

</body>
</html>
