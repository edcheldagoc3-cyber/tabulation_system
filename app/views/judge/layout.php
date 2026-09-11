<?php
$activePage = $activePage ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Judge';
$pageSubtitle = $pageSubtitle ?? '';
$csrf = function_exists('csrf_token') ? csrf_token() : '';
// Get current user role
$user = \App\Core\Auth::user();
$role = $user ? $user->role : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> · USTP TabulaSys</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #f6f4f2;
            --panel: #fff;
            --ink: #15161a;
            --muted: #667085;
            --line: #e8e2dd;
            --maroon: #6d1a2b;
            --gold: #d4a72c;
            --success: #15803d;
            --danger: #b91c1c;
            --shadow: 0 24px 60px rgba(20, 18, 16, .08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Inter", sans-serif;
            background: radial-gradient(circle at top left, rgba(109,26,43,.08), transparent 35%),
                        radial-gradient(circle at top right, rgba(212,167,44,.12), transparent 28%),
                        var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }

        .app {
            display: block;
            min-height: 100vh;
            line-height: 1.5;
        }

        /* Judge portal uses a compact top navigation instead of a sidebar. */
        .sidebar {
            display: none;
        }
        .brand {
            padding: 28px 24px 22px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .brand .mark {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-badge {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--maroon), #3b0f17);
            display: grid;
            place-items: center;
            color: var(--gold);
            font-size: 20px;
            box-shadow: 0 12px 28px rgba(109,26,43,.35);
        }
        .brand h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -.02em;
        }
        .brand p {
            margin: 4px 0 0;
            color: #9ca3af;
            font-size: 12px;
        }

        .nav {
            padding: 16px;
            flex: 1;
            overflow-y: auto;
        }
        .nav .group {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .18em;
            color: #8a8f9c;
            padding: 16px 12px 8px;
        }
        .nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            margin-bottom: 6px;
            border-radius: 14px;
            color: #d1d5db;
            font-weight: 600;
            transition: 0.12s;
        }
        .nav a:hover,
        .nav a.active {
            background: rgba(109,26,43,.35);
            color: #fff;
        }
        .nav i {
            width: 20px;
            text-align: center;
            color: var(--gold);
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 18px 20px;
            border-top: 1px solid rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), #f4d77b);
            color: #24140a;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        /* Main content */
        .shell {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Topbar */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(246,244,242,.86);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(232,226,221,.8);
        }
        .topbar-inner {
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            max-width: 1440px;
            margin: 0 auto;
        }
        .judge-brand { display:flex; align-items:center; gap:10px; margin-right:12px; flex-shrink:0; }
        .judge-brand .brand-badge { width:36px; height:36px; border-radius:11px; font-size:16px; }
        .judge-brand strong { display:block; font-size:15px; letter-spacing:-.02em; }
        .judge-brand span { display:block; margin-top:2px; color:var(--muted); font-size:11px; }
        .headline h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -.04em;
            color: #182338;
        }
        .headline p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
        }
        .actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: 0;
            border-radius: 14px;
            padding: 11px 16px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--maroon), #4b1020);
            color: #fff;
            box-shadow: 0 10px 24px rgba(109,26,43,.18);
        }
        .btn-primary:hover { background: #4b1020; }
        .btn-light {
            background: #fff;
            border: 1px solid var(--line);
            color: var(--ink);
        }
        .btn-light:hover { background: #f1f4f9; }
        .btn-gold {
            background: #d4a72c;
            color: #2f1a00;
        }
        .btn-gold:hover { background: #c49a26; }
        .btn-danger {
            background: rgba(185,28,28,.12);
            color: var(--danger);
        }
        .btn-danger:hover { background: rgba(185,28,28,.2); }

        .content {
            padding: 30px 32px 44px;
            flex: 1;
            max-width: 1440px;
            width: 100%;
            margin: 0 auto;
        }

        /* Common card styles for judge scoring */
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 14px 34px rgba(20, 18, 16, .06);
        }
        .judge-assignment-card { transition: transform .2s ease, box-shadow .2s ease; }
        .judge-assignment-card:hover { transform: translateY(-2px); box-shadow: 0 18px 40px rgba(20, 18, 16, .1); }
        .section-title { color:#182338; font-weight:800; letter-spacing:-.02em; }
        .eyebrow { color:var(--muted); font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        .muted { color: var(--muted); }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
        }
        .empty {
            padding: 24px;
            border: 1px dashed #ddd2c9;
            border-radius: 18px;
            color: var(--muted);
            background: #fff;
        }
        .progress {
            height: 10px;
            background: #efe8e1;
            border-radius: 999px;
            overflow: hidden;
            margin: 14px 0 6px;
        }
        .progress > span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, var(--maroon), var(--gold));
        }

        @media (max-width: 1100px) {
            .topbar-inner, .content { padding: 18px; }
        }
        @media (max-width: 640px) {
            .topbar-inner { align-items:flex-start; gap:12px; flex-wrap:wrap; }
            .judge-brand { margin-right:auto; }
            .headline { width:100%; order:3; }
            .headline h2 { font-size: 24px; }
            .headline p { font-size:13px; line-height:1.45; }
            .actions { gap:6px; }
            .actions .btn { padding:9px 11px; font-size:12px; }
        }
    </style>
</head>
<body>
<div class="app">
    <!-- Main -->
    <main class="shell">
        <header class="topbar">
            <div class="topbar-inner">
                <a class="judge-brand" href="<?= app_url('/judge') ?>" aria-label="Judge dashboard">
                    <div class="brand-badge"><i class="fa-solid fa-pen-to-square"></i></div>
                    <div><strong>USTP TabulaSys</strong><span>Judge Portal</span></div>
                </a>
                <div class="headline">
                    <h2><?= htmlspecialchars($pageTitle) ?></h2>
                    <p><?= htmlspecialchars($pageSubtitle) ?></p>
                </div>
                <div class="actions">
                    <a class="btn btn-light" href="<?= app_url('/viewer') ?>"><i class="fa-solid fa-eye"></i> Public</a>
                    <a class="btn btn-gold" href="<?= app_url('/logout') ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="content">
            <?= $content ?? '' ?>
        </div>
    </main>
</div>
</body>
</html>
