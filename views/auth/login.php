<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bondor Bati POS | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .login-container {
            width: 100%;
            max-width: 440px;
        }
        .login-card {
            padding: 2.5rem;
            text-align: center;
        }
        .login-brand {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a78bfa, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }
        .login-card .form-group {
            text-align: left;
        }
        .login-card .btn-primary {
            width: 100%;
            padding: 0.85rem;
            font-size: 1rem;
            margin-top: 0.5rem;
        }
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.75rem 0;
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--glass-border);
        }
        .staff-link-section {
            text-align: center;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .staff-link-section p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        .token-input-row {
            display: flex;
            gap: 0.5rem;
        }
        .token-input-row .form-control {
            flex: 1;
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Admin Login -->
        <div class="login-card glass-panel">
            <div class="login-brand">
                <i class="fa-solid fa-fire-burner"></i> Bondor Bati
            </div>
            <p class="login-subtitle">Admin Panel — Sign in to manage operations</p>

            <?php if (!empty($loginError)): ?>
                <div class="error-msg"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($loginError); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="login_type" value="admin">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter admin username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket"></i> Sign In</button>
            </form>

            <div class="divider">or</div>

            <!-- Staff Magic Link Access -->
            <div class="staff-link-section">
                <p><i class="fa-solid fa-wand-magic-sparkles"></i> Staff? Paste your access URL or key below.</p>
                <form id="staff-token-form" method="GET" action="index.php" onsubmit="extractToken(event)">
                    <div class="token-input-row">
                        <input type="text" id="token-input-raw" class="form-control" placeholder="Paste access URL or token key" required>
                        <input type="hidden" name="token" id="token-hidden">
                        <button type="submit" class="btn btn-glass"><i class="fa-solid fa-key"></i> Go</button>
                    </div>
                </form>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">
                    <i class="fa-solid fa-circle-info"></i> You can paste the full access link or just the token key.
                </p>
            </div>
        </div>
    </div>
    <script>
    function extractToken(e) {
        e.preventDefault();
        const raw = document.getElementById('token-input-raw').value.trim();
        let token = raw;
        // If it's a URL, extract the ?token= param
        try {
            const url = new URL(raw);
            const t = url.searchParams.get('token');
            if (t) token = t;
        } catch(err) {
            // Not a URL, use as-is (bare token)
        }
        document.getElementById('token-hidden').value = token;
        document.getElementById('staff-token-form').submit();
    }
    </script>
</body>
</html>
