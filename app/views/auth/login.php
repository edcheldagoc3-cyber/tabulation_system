<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · USTP Tabulation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e1114 0%, #111827 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #fff;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            background: rgba(30, 41, 59, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: #6d1a2b;
            color: #eab308;
            font-size: 28px;
            border-radius: 16px;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(109, 26, 43, 0.4);
            border: 1.5px solid #eab308;
        }
        .logo-section h1 {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.5px;
        }
        .logo-section p {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
        }
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        .input-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 16px;
        }
        .input-wrapper input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            transition: all 0.2s;
        }
        .input-wrapper input:focus {
            outline: none;
            border-color: #eab308;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.15);
        }
        .error-alert {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6d1a2b 0%, #521422 100%);
            border: 1px solid #eab308;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(109, 26, 43, 0.2);
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(109, 26, 43, 0.4);
            border-color: #fff;
        }
        .btn-submit:active {
            transform: translateY(1px);
        }
        .demo-badge {
            margin-top: 24px;
            padding: 12px;
            background: rgba(234, 179, 8, 0.08);
            border: 1px solid rgba(234, 179, 8, 0.15);
            border-radius: 12px;
            font-size: 12px;
            color: #eab308;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .demo-badge span {
            font-weight: 600;
        }
        .leaderboard-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
        }
        .leaderboard-link a {
            color: #eab308;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .leaderboard-link a:hover {
            color: #fff;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo-badge">
                <i class="fas fa-trophy"></i>
            </div>
            <h1>USTP TabulaSys</h1>
            <p>Jasaan Campus · Event Tabulation System</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(function_exists('csrf_token') ? csrf_token() : '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="input-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email"  required autocomplete="email">
                </div>
            </div>
            <div class="input-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password"  required autocomplete="current-password">
                </div>
            </div>
            <button type="submit" class="btn-submit">
                <span>Sign In</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="leaderboard-link">
            <a href="<?= app_url('/viewer') ?>"><i class="fas fa-eye"></i> View Public Leaderboard</a>
        </div>

        <div class="demo-badge">
            <span>💡 Demo Credentials Available:</span>
            admin@ustp.edu.ph / password
        </div>
    </div>
</body>
</html>
