<?php
$activePage = $activePage ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Tabulator';
$pageSubtitle = $pageSubtitle ?? '';
$csrf = function_exists('csrf_token') ? csrf_token() : '';
// Get current user role
$user = \App\Core\Auth::user();
$role = $user ? $user->role : null;
$sidebarCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
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
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 72px;
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
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            min-height: 100vh;
            transition: grid-template-columns .3s ease;
        }
        .app.sidebar-collapsed { grid-template-columns: var(--sidebar-collapsed-width) 1fr; }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #14151b 0%, #1e1f27 100%);
            color: #e5e7eb;
            position: sticky;
            top: 0;
            height: 100vh;
            border-right: 1px solid rgba(255,255,255,.06);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width .3s ease;
            width: var(--sidebar-width);
        }
        .app.sidebar-collapsed .sidebar { width: var(--sidebar-collapsed-width); }
        .brand {
            padding: 28px 24px 22px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            min-height: 88px;
            position: relative;
        }
        .app.sidebar-collapsed .brand { padding: 16px; justify-content: center; }
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
        .brand-copy { transition: opacity .2s ease, transform .2s ease; white-space: nowrap; }
        .app.sidebar-collapsed .brand-copy { opacity: 0; transform: scale(.8); pointer-events: none; width: 0; overflow: hidden; }
        .sidebar-toggle { position: absolute; top: 20px; right: 16px; width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.06); color: #d1d5db; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 2; }
        .sidebar-toggle:hover { background: rgba(255,255,255,.12); color: #fff; }
        .app.sidebar-collapsed .sidebar-toggle { right: 20px; }
        .sidebar-toggle i { font-size: 14px; transition: transform .3s ease; }
        @media (min-width: 1101px) { .app.sidebar-collapsed .brand-badge { display: none; } }

        .nav {
            padding: 16px;
            flex: 1;
            overflow-y: auto;
        }
        .app.sidebar-collapsed .nav { padding: 8px; }
        .nav .group {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .18em;
            color: #8a8f9c;
            padding: 16px 12px 8px;
            transition: opacity .2s ease;
            white-space: nowrap;
        }
        .app.sidebar-collapsed .nav .group { opacity: 0; height: 0; padding: 0; margin: 0; overflow: hidden; }
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
            white-space: nowrap;
            overflow: hidden;
        }
        .nav a span { transition: opacity .2s ease; }
        .app.sidebar-collapsed .nav a { padding: 10px; justify-content: center; }
        .app.sidebar-collapsed .nav a span { display: none; }
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
            min-height: 72px;
        }
        .app.sidebar-collapsed .sidebar-footer { padding: 12px; justify-content: center; }
        .sidebar-footer .footer-copy { white-space: nowrap; transition: opacity .2s ease; }
        .app.sidebar-collapsed .footer-copy { display: none; }
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
        }
        .headline h2 {
            margin: 0;
            font-size: 28px;
            letter-spacing: -.04em;
        }
        .headline p {
            margin: 6px 0 0;
            color: var(--muted);
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
        .btn-success {
            background: var(--success);
            color: #fff;
        }
        .btn-success:hover { background: #0d6e3d; }
        .btn-danger {
            background: rgba(185,28,28,.12);
            color: var(--danger);
        }
        .btn-danger:hover { background: rgba(185,28,28,.2); }

        .content {
            padding: 24px 28px 36px;
            flex: 1;
        }

        /* Common components */
        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px;
        }
        .muted { color: var(--muted); }
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
        }
        .progress > span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, var(--maroon), var(--gold));
        }
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .tag.good { background: #dcfce7; color: #166534; }
        .tag.warn { background: #fef3c7; color: #92400e; }
        .tag.draft { background: #e0ecff; color: #1e40af; }
        .table-wrap { overflow: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 760px; }
        th, td { padding: 14px 10px; border-bottom: 1px solid #f0ece8; text-align: left; vertical-align: middle; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; }
        td { font-size: 14px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--shadow);
        }
        .stat .label {
            font-size: 13px;
            color: var(--muted);
            font-weight: 700;
        }
        .stat .value {
            margin-top: 8px;
            font-size: 28px;
            font-weight: 800;
        }
        .stat .meta {
            margin-top: 4px;
            font-size: 12px;
            color: var(--muted);
        }

        .section {
            background: rgba(255,255,255,.84);
            border: 1px solid rgba(232,226,221,.95);
            border-radius: 24px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .section-head {
            padding: 18px 20px;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .section-head h3 {
            margin: 0;
            font-size: 18px;
        }
        .section-head p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }
        .section-body {
            padding: 20px;
        }
        .inline { display: inline; }

        @media (max-width: 1100px) {
            .app { grid-template-columns: 1fr; }
            .sidebar { position: relative; height: auto; }
            .topbar-inner, .content { padding: 18px; }
        }
        @media (max-width: 640px) {
            .headline h2 { font-size: 24px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<div class="app <?= $sidebarCollapsed ? 'sidebar-collapsed' : '' ?>" id="appContainer">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <button class="sidebar-toggle" type="button" onclick="toggleSidebar()" title="Toggle sidebar" aria-label="Toggle sidebar" aria-controls="sidebar">
                <i class="fa-solid <?= $sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left' ?>"></i>
            </button>
            <div class="mark">
                <div class="brand-badge"><i class="fa-solid fa-table"></i></div>
                <div class="brand-copy">
                    <h1>USTP TabulaSys</h1>
                    <p>Tabulator Control Center</p>
                </div>
            </div>
        </div>
        <nav class="nav">
            <div class="group">Oversight</div>
            <a href="<?= app_url('/tabulator') ?>" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
            </a>
            <a href="<?= app_url('/tabulator/scores') ?>" class="<?= $activePage === 'scores' ? 'active' : '' ?>">
                <i class="fa-solid fa-lock"></i><span>Locked Scores</span>
            </a>

            <div class="group">Jump to</div>
            <a href="<?= app_url('/viewer') ?>"><i class="fa-solid fa-eye"></i><span>Public Leaderboard</span></a>
            <a href="<?= app_url('/logout') ?>"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </nav>
        <div class="sidebar-footer">
            <div class="avatar">T</div>
            <div class="footer-copy">
                <div style="font-weight:800;color:#fff;">Tabulator</div>
                <div style="font-size:12px;color:#9ca3af;">USTP Jasaan</div>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="shell">
        <header class="topbar">
            <div class="topbar-inner">
                <div class="headline">
                    <h2><?= htmlspecialchars($pageTitle) ?></h2>
                    <p><?= htmlspecialchars($pageSubtitle) ?></p>
                </div>
                <div class="actions">
                    <a class="btn btn-light" href="<?= app_url('/viewer') ?>"><i class="fa-solid fa-eye"></i> Leaderboard</a>
                </div>
            </div>
        </header>

        <div class="content">
            <?= $content ?? '' ?>
        </div>
    </main>
</div>
<script>
    function toggleSidebar() {
        const app = document.getElementById('appContainer');
        const collapsed = app.classList.toggle('sidebar-collapsed');
        document.cookie = 'sidebar_collapsed=' + (collapsed ? 'true' : 'false') + '; path=/; max-age=31536000';
        const icon = document.querySelector('.sidebar-toggle i');
        if (icon) icon.className = collapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left';
    }
</script>
</body>
</html>
