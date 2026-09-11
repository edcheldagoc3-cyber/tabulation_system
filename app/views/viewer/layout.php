<?php
$pageTitle = $pageTitle ?? 'USTP TabulaSys · Live Results';
$pageSubtitle = $pageSubtitle ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
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
            --shadow: 0 24px 60px rgba(20, 18, 16, .08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Inter", sans-serif;
            background: radial-gradient(circle at top left, rgba(109,26,43,.08), transparent 35%),
                        radial-gradient(circle at top right, rgba(212,167,44,.12), transparent 28%),
                        var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }

        /* Topbar */
        .topbar {
            background: #fff;
            padding: 16px 28px;
            border-bottom: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
        }
        .topbar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .topbar .brand i {
            font-size: 28px;
            color: var(--maroon);
        }
        .topbar .brand h1 {
            font-size: 20px;
            font-weight: 800;
            color: #0b1a33;
        }
        .topbar .brand small {
            display: block;
            font-size: 12px;
            color: var(--muted);
            font-weight: 400;
            margin-top: -2px;
        }
        .topbar .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: 0.12s;
        }
        .btn-primary {
            background: var(--maroon);
            color: #fff;
        }
        .btn-primary:hover { background: #521422; }
        .btn-light {
            background: #fff;
            border: 1px solid var(--line);
            color: var(--ink);
        }
        .btn-light:hover { background: #f1f4f9; }

        .live-badge {
            background: #dc2626;
            color: #fff;
            padding: 4px 16px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .live-badge i { font-size: 10px; animation: pulse-dot 1.5s infinite; }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .content {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 28px 40px;
        }

        .footer {
            text-align: center;
            padding: 20px 0 12px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 13px;
            margin-top: 24px;
        }
        .footer i { margin: 0 4px; }

        @media (max-width: 640px) {
            .topbar { padding: 12px 16px; flex-direction: column; align-items: stretch; }
            .content { padding: 16px; }
            .topbar .brand h1 { font-size: 17px; }
        }
    </style>
</head>
<body>

    <!-- Topbar -->
    <header class="topbar">
        <div class="brand">
            <i class="fa-solid fa-trophy"></i>
            <div>
                <h1>USTP TabulaSys</h1>
                <small>Jasaan Campus · Live Results</small>
            </div>
        </div>
        <div class="actions">
            <span class="live-badge"><i class="fa-solid fa-circle"></i> LIVE</span>
            <a class="btn btn-light" href="<?= app_url('/login') ?>"><i class="fa-solid fa-right-to-bracket"></i> Admin</a>
            <a class="btn btn-primary" href="<?= app_url('/viewer') ?>"><i class="fa-solid fa-rotate"></i> Refresh</a>
        </div>
    </header>

    <!-- Content -->
    <div class="content">
        <?= $content ?? '' ?>
    </div>

    <!-- Footer -->
    <div class="footer">
        <i class="fa-solid fa-database"></i> Auto-refreshes every 15s ·
        <i class="fa-solid fa-shield-alt"></i> Official results are released by the Tabulator
    </div>

</body>
</html>