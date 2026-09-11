<?php
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$activePage = $activePage ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Admin';
$pageSubtitle = $pageSubtitle ?? '';
$user = \App\Core\Auth::user();
$role = $user ? $user->role : null;

// Check if sidebar is collapsed from cookie/localStorage
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
        :root{--bg:#f6f4f2;--panel:#fff;--ink:#15161a;--muted:#667085;--line:#e8e2dd;--maroon:#6d1a2b;--gold:#d4a72c;--success:#15803d;--danger:#b91c1c;--shadow:0 24px 60px rgba(20,18,16,.08);--sidebar-width:280px;--sidebar-collapsed-width:72px}
        *{box-sizing:border-box}
        body{margin:0;font-family:"Inter",sans-serif;background:radial-gradient(circle at top left, rgba(109,26,43,.08), transparent 35%),radial-gradient(circle at top right, rgba(212,167,44,.12), transparent 28%),var(--bg);color:var(--ink);min-height:100vh}
        a{text-decoration:none;color:inherit}
        .app{display:grid;grid-template-columns:var(--sidebar-width) 1fr;min-height:100vh;transition:grid-template-columns 0.3s ease}
        .app.sidebar-collapsed{grid-template-columns:var(--sidebar-collapsed-width) 1fr}

        /* Sidebar */
        .sidebar{background:linear-gradient(180deg,#14151b 0%,#1e1f27 100%);color:#e5e7eb;position:sticky;top:0;height:100vh;border-right:1px solid rgba(255,255,255,.06);display:flex;flex-direction:column;overflow:hidden;transition:width 0.3s ease;width:var(--sidebar-width)}
        .app.sidebar-collapsed .sidebar{width:var(--sidebar-collapsed-width)}
        .sidebar .brand{padding:20px 16px 16px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:12px;min-height:72px;position:relative}
        .app.sidebar-collapsed .sidebar .brand{padding:16px;justify-content:center}
        .brand-badge{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--maroon),#3b0f17);display:grid;place-items:center;color:var(--gold);font-size:18px;flex-shrink:0;box-shadow:0 8px 20px rgba(109,26,43,.35)}
        .brand-text{transition:opacity 0.2s ease, transform 0.2s ease;white-space:nowrap}
        .app.sidebar-collapsed .brand-text{opacity:0;transform:scale(0.8);pointer-events:none;width:0;overflow:hidden}
        .brand h1{margin:0;font-size:16px;font-weight:800;letter-spacing:-.02em;color:#fff}
        .brand p{margin:2px 0 0;color:#9ca3af;font-size:11px}

        /* Sidebar Toggle Button */
        .sidebar-toggle{position:absolute;top:20px;right:16px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);color:#d1d5db;width:32px;height:32px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s ease,color .2s ease;flex-shrink:0;margin:0;padding:0}
        .sidebar-toggle:hover{background:rgba(255,255,255,.12);color:#fff}
        .app.sidebar-collapsed .sidebar-toggle{right:20px}
        .sidebar-toggle i{font-size:14px;transition:transform 0.3s ease}
        @media (min-width:1101px){.app.sidebar-collapsed .brand-badge{display:none}}

        .nav{padding:12px 12px;flex:1;overflow-y:auto}
        .app.sidebar-collapsed .nav{padding:8px 8px}
        .nav .group{font-size:10px;text-transform:uppercase;letter-spacing:.15em;color:#5a647b;padding:12px 10px 4px;transition:opacity 0.2s ease;white-space:nowrap}
        .app.sidebar-collapsed .nav .group{opacity:0;height:0;padding:0;margin:0;overflow:hidden}
        .nav a{display:flex;align-items:center;gap:12px;padding:10px 12px;margin-bottom:4px;border-radius:10px;color:#d1d5db;font-weight:500;font-size:14px;transition:0.15s;white-space:nowrap;overflow:hidden}
        .nav a i{width:20px;text-align:center;font-size:16px;flex-shrink:0;color:var(--gold)}
        .app.sidebar-collapsed .nav a{padding:10px;justify-content:center}
        .app.sidebar-collapsed .nav a span{display:none}
        .nav a:hover,.nav a.active{background:rgba(109,26,43,.35);color:#fff}
        .nav a.active i{color:#fff}
        .sidebar-footer{margin-top:auto;padding:14px 16px;border-top:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:12px;min-height:64px}
        .app.sidebar-collapsed .sidebar-footer{padding:12px;justify-content:center}
        .app.sidebar-collapsed .sidebar-footer .footer-text{display:none}
        .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--gold),#f4d77b);color:#24140a;display:grid;place-items:center;font-weight:700;font-size:14px;flex-shrink:0}
        .sidebar-footer .footer-text{transition:opacity 0.2s ease;white-space:nowrap}
        .sidebar-footer .footer-text div:first-child{font-weight:700;color:#fff;font-size:13px}
        .sidebar-footer .footer-text div:last-child{font-size:11px;color:#9ca3af}

        /* Main content */
        .shell{display:flex;flex-direction:column;min-width:0}
        .topbar{position:sticky;top:0;z-index:20;background:rgba(246,244,242,.86);backdrop-filter:blur(14px);border-bottom:1px solid rgba(232,226,221,.8)}
        .topbar-inner{padding:14px 28px;display:flex;align-items:center;justify-content:space-between;gap:18px}
        .headline h2{margin:0;font-size:24px;letter-spacing:-.04em}
        .headline p{margin:4px 0 0;color:var(--muted);font-size:14px}
        .actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .btn{border:0;border-radius:12px;padding:9px 16px;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;text-decoration:none;transition:0.12s}
        .btn-primary{background:linear-gradient(135deg,var(--maroon),#4b1020);color:#fff;box-shadow:0 8px 20px rgba(109,26,43,.18)}
        .btn-primary:hover{background:#4b1020;transform:translateY(-1px)}
        .btn-light{background:#fff;border:1px solid var(--line);color:var(--ink)}
        .btn-light:hover{background:#f1f4f9}
        .btn-danger{background:rgba(185,28,28,.12);color:var(--danger)}
        .btn-danger:hover{background:rgba(185,28,28,.2)}
        .content{padding:24px 28px 36px;flex:1}

        /* ===== DASHBOARD-SPECIFIC STYLES ===== */
        .grid-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;margin-bottom:28px}
        .stat{background:#fff;border-radius:16px;padding:18px 20px;border:1px solid var(--line);box-shadow:0 2px 8px rgba(0,0,0,.02)}
        .stat .label{font-size:13px;color:var(--muted);font-weight:500;display:flex;align-items:center;gap:6px}
        .stat .value{font-size:28px;font-weight:800;color:#0b1a33;margin-top:4px;letter-spacing:-1px}
        .stat .meta{font-size:12px;color:#9ca3af;margin-top:2px}
        .section{background:rgba(255,255,255,.84);border:1px solid rgba(232,226,221,.95);border-radius:24px;box-shadow:var(--shadow);overflow:hidden;margin-bottom:22px}
        .section-head{padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}
        .section-head h3{margin:0;font-size:17px}
        .section-head p{margin:4px 0 0;color:var(--muted);font-size:13px}
        .section-body{padding:20px}
        .panel-grid{display:grid;grid-template-columns:1fr 1.2fr;gap:24px}
        .card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px}
        .card h4{margin:0 0 14px;font-size:15px}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .field{margin-bottom:14px}
        .field label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;color:#4b5563;letter-spacing:.4px;margin-bottom:4px}
        .input, select, textarea{width:100%;padding:10px 14px;border:1px solid var(--line);border-radius:10px;font-size:14px;font-family:inherit;background:#fff;color:#111827;transition:.12s}
        .input:focus, select:focus, textarea:focus{outline:none;border-color:var(--maroon);box-shadow:0 0 0 3px rgba(109,26,43,.08)}
        textarea{min-height:80px;resize:vertical}
        .table-wrap{overflow:auto}
        table{width:100%;border-collapse:collapse;min-width:760px}
        th,td{padding:12px 10px;border-bottom:1px solid #f0ece8;text-align:left;vertical-align:middle}
        th{font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#6b7280}
        td{font-size:14px}
        .tag{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700}
        .tag.live{background:#fee2e2;color:#991b1b}
        .tag.draft{background:#e0ecff;color:#1e40af}
        .tag.archived{background:#e5e7eb;color:#4b5563}
        .tag.good{background:#dcfce7;color:#166534}
        .tag.warn{background:#fef3c7;color:#92400e}
        .progress{height:8px;background:#efe8e1;border-radius:999px;overflow:hidden}
        .progress > span{display:block;height:100%;background:linear-gradient(90deg,var(--maroon),var(--gold))}
        .split-actions{display:flex;gap:8px;flex-wrap:wrap}
        .small{padding:6px 12px;border-radius:10px;font-size:12px}
        .muted{color:var(--muted)}
        .pill{display:inline-block;background:#f6ead0;color:#7a5600;padding:2px 12px;border-radius:30px;font-size:12px;font-weight:700}
        .empty{padding:24px;border:1px dashed #ddd2c9;border-radius:18px;background:#fff;color:var(--muted);text-align:center}
        .inline-delete{display:inline}
        .inline-delete button{border:0;background:transparent;cursor:pointer;color:#b91c1c;font-size:16px;padding:4px}

        /* Global Modal */
        body.modal-open{overflow:hidden}
        .global-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(14,16,20,.58);backdrop-filter:blur(8px);z-index:9999}
        .global-modal.is-open{display:flex}
        .global-modal__card{width:min(820px,100%);max-height:92vh;overflow:hidden;background:#fff;border:1px solid rgba(232,226,221,.95);border-radius:28px;box-shadow:0 40px 100px rgba(0,0,0,.24);animation:modalSlide .22s ease}
        .global-modal__head{padding:18px 22px;border-bottom:1px solid var(--line);display:flex;align-items:flex-start;justify-content:space-between;gap:18px}
        .global-modal__title{margin:0;font-size:20px;letter-spacing:-.03em}
        .global-modal__subtitle{margin:4px 0 0;color:var(--muted);font-size:13px;line-height:1.45}
        .global-modal__close{border:1px solid var(--line);background:#fff;color:var(--muted);width:38px;height:38px;border-radius:12px;display:grid;place-items:center;cursor:pointer;flex-shrink:0}
        .global-modal__close:hover{background:#f7f4f1;color:var(--ink)}
        .global-modal__body{padding:22px;overflow:auto;max-height:calc(92vh - 82px)}
        .modal-loading{display:grid;place-items:center;min-height:180px;color:var(--muted);gap:12px;text-align:center}
        .modal-spinner{width:34px;height:34px;border-radius:50%;border:3px solid rgba(109,26,43,.16);border-top-color:var(--maroon);animation:spin .8s linear infinite}
        .modal-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:22px}
        .modal-danger{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.14);color:#991b1b;padding:14px 16px;border-radius:16px}
        .field-error{margin-top:6px;font-size:12px;color:#b91c1c}
        @keyframes spin{to{transform:rotate(360deg)}}
        @keyframes modalSlide{from{opacity:0;transform:translateY(14px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
        @media (max-width:1100px){.app{grid-template-columns:1fr}.sidebar{position:relative;height:auto;width:100%!important}.app.sidebar-collapsed .sidebar{width:100%!important}.topbar-inner,.content{padding:18px}.panel-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}}
        @media (max-width:640px){.headline h2{font-size:20px}.grid-stats{grid-template-columns:1fr 1fr}}
    </style>
</head>
<body>
<div class="app <?= $sidebarCollapsed ? 'sidebar-collapsed' : '' ?>" id="appContainer">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-badge"><i class="fa-solid fa-trophy"></i></div>
            <div class="brand-text">
                <h1>USTP TabulaSys</h1>
                <p>Admin Control Center</p>
            </div>
            <button class="sidebar-toggle" type="button" onclick="toggleSidebar()" title="Toggle sidebar" aria-label="Toggle sidebar" aria-controls="sidebar">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        </div>
        <nav class="nav">
            <div class="group">Overview</div>
            <a href="<?= app_url('/admin') ?>" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
            </a>
            <a href="<?= app_url('/admin/events') ?>" class="<?= $activePage === 'events' ? 'active' : '' ?>">
                <i class="fa-solid fa-calendar-days"></i><span>Events</span>
            </a>
            <a href="<?= app_url('/admin/categories') ?>" class="<?= $activePage === 'categories' ? 'active' : '' ?>">
                <i class="fa-solid fa-tags"></i><span>Categories</span>
            </a>
            <a href="<?= app_url('/admin/criteria') ?>" class="<?= $activePage === 'criteria' ? 'active' : '' ?>">
                <i class="fa-solid fa-list-check"></i><span>Criteria</span>
            </a>
            <a href="<?= app_url('/admin/templates') ?>" class="<?= $activePage === 'templates' ? 'active' : '' ?>">
                <i class="fa-solid fa-layer-group"></i><span>Templates</span>
            </a>
            <a href="<?= app_url('/admin/contestants') ?>" class="<?= $activePage === 'contestants' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-group"></i><span>Contestants</span>
            </a>
            <a href="<?= app_url('/admin/judges') ?>" class="<?= $activePage === 'judges' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-tie"></i><span>Judges</span>
            </a>
            <a href="<?= app_url('/admin/users') ?>" class="<?= $activePage === 'users' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i><span>Users</span>
            </a>
            <a href="<?= app_url('/reports') ?>" class="<?= $activePage === 'reports' ? 'active' : '' ?>">
                 <i class="fa-solid fa-file-lines"></i><span>Reports</span>
            </a>

            <div class="group">Jump to</div>
            <a href="<?= app_url('/viewer') ?>"><i class="fa-solid fa-eye"></i><span>Public Leaderboard</span></a>
            <a href="<?= app_url('/logout') ?>"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </nav>
        <div class="sidebar-footer">
            <div class="avatar">A</div>
            <div class="footer-text">
                <div>Admin</div>
                <div>USTP Jasaan</div>
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
                    <a class="btn btn-light" href="<?= app_url('/viewer') ?>"><i class="fa-solid fa-eye"></i> Public View</a>
                    <a class="btn btn-primary" href="<?= app_url('/admin/events/create') ?>" onclick="loadModal('<?= htmlspecialchars(app_url('/admin/events/create'), ENT_QUOTES, 'UTF-8') ?>', 'Create Event', 'Add a new event'); return false;"><i class="fa-solid fa-plus"></i> New Event</a>
                </div>
            </div>
        </header>

        <div class="content">
            <?= $content ?? '' ?>
        </div>
    </main>
</div>

<div id="globalModal" class="global-modal" aria-hidden="true">
    <div class="global-modal__card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="global-modal__head">
            <div>
                <h3 id="modalTitle" class="global-modal__title">Modal</h3>
                <p id="modalSubtitle" class="global-modal__subtitle"></p>
            </div>
            <button type="button" class="global-modal__close" onclick="closeModal()" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="modalBody" class="global-modal__body"></div>
    </div>
</div>

<script>
    window.csrfToken = <?= json_encode($csrf) ?>;
    const globalModal = document.getElementById('globalModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const modalBody = document.getElementById('modalBody');

    function toggleSidebar() {
        const app = document.getElementById('appContainer');
        const isCollapsed = app.classList.contains('sidebar-collapsed');

        // Toggle class
        if (isCollapsed) {
            app.classList.remove('sidebar-collapsed');
            document.cookie = 'sidebar_collapsed=false; path=/; max-age=31536000';
        } else {
            app.classList.add('sidebar-collapsed');
            document.cookie = 'sidebar_collapsed=true; path=/; max-age=31536000';
        }

        // Adjust the chevron icon
        const icon = document.querySelector('.sidebar-toggle i');
        if (icon) {
            if (app.classList.contains('sidebar-collapsed')) {
                icon.className = 'fa-solid fa-chevron-right';
            } else {
                icon.className = 'fa-solid fa-chevron-left';
            }
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openModal(title, subtitle, content) {
        modalTitle.textContent = title || '';
        modalSubtitle.textContent = subtitle || '';
        modalBody.innerHTML = content || '';
        globalModal.classList.add('is-open');
        globalModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        globalModal.classList.remove('is-open');
        globalModal.setAttribute('aria-hidden', 'true');
        modalBody.innerHTML = '';
        modalTitle.textContent = '';
        modalSubtitle.textContent = '';
        document.body.classList.remove('modal-open');
        window.__pendingDeleteUrl = null;
    }

    function modalLoadingContent(message) {
        return '<div class="modal-loading"><div class="modal-spinner"></div><div>' + escapeHtml(message || 'Loading...') + '</div></div>';
    }

    function toggleTiebreakField(select) {
        const wrap = select.closest('form').querySelector('.tiebreak-criterion-wrap');
        if (!wrap) {
            return;
        }
        wrap.style.display = select.value === 'highest_criterion' ? '' : 'none';
        if (select.value !== 'highest_criterion') {
            const criterion = wrap.querySelector('select');
            if (criterion) {
                criterion.value = '';
            }
        }
    }

    async function loadModal(url, title, subtitle) {
        openModal(title, subtitle, modalLoadingContent('Loading form...'));
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                },
                credentials: 'same-origin'
            });

            const html = await response.text();
            modalBody.innerHTML = html;
        } catch (error) {
            modalBody.innerHTML = '<div class="modal-danger">Unable to load the form. Please try again.</div>';
        }
    }

    function confirmDelete(url, title, message) {
        window.__pendingDeleteUrl = url;
        openModal(
            title || 'Delete item',
            'Confirm this action',
            `
                <div class="modal-danger">${escapeHtml(message || 'This action cannot be undone.')}</div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-light" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="performDelete(window.__pendingDeleteUrl)">Delete</button>
                </div>
            `
        );
    }

    async function performDelete(url) {
        if (!url) {
            return;
        }

        modalBody.innerHTML = modalLoadingContent('Deleting...');
        try {
            const formData = new FormData();
            formData.append('csrf_token', window.csrfToken || '');

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData,
                credentials: 'same-origin'
            });

            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload || payload.success === false) {
                const message = payload && payload.message ? payload.message : 'Delete failed.';
                modalBody.innerHTML = '<div class="modal-danger">' + escapeHtml(message) + '</div><div class="modal-actions"><button type="button" class="btn btn-light" onclick="closeModal()">Close</button></div>';
                return;
            }

            closeModal();
            window.location.reload();
        } catch (error) {
            modalBody.innerHTML = '<div class="modal-danger">Unable to delete the record. Please try again.</div><div class="modal-actions"><button type="button" class="btn btn-light" onclick="closeModal()">Close</button></div>';
        }
    }

    async function submitModalForm(form) {
        const formData = new FormData(form);
        if (!formData.has('csrf_token') && window.csrfToken) {
            formData.append('csrf_token', window.csrfToken);
        }

        modalBody.innerHTML = modalLoadingContent('Saving...');

        try {
            const response = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/html;q=0.9'
                },
                body: formData,
                credentials: 'same-origin'
            });

            const contentType = response.headers.get('content-type') || '';
            const text = await response.text();

            if (contentType.includes('application/json')) {
                const payload = JSON.parse(text || '{}');
                if (response.ok && payload.success) {
                    closeModal();
                    window.location.reload();
                    return;
                }

                modalBody.innerHTML = '<div class="modal-danger">' + escapeHtml(payload.message || 'Unable to save changes.') + '</div>' + modalBody.innerHTML;
                return;
            }

            if (response.ok) {
                closeModal();
                window.location.reload();
                return;
            }

            modalBody.innerHTML = text;
        } catch (error) {
            modalBody.innerHTML = '<div class="modal-danger">Unable to save the form. Please try again.</div>';
        }
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (!form.closest('#globalModal')) {
            return;
        }

        event.preventDefault();
        submitModalForm(form);
    });

    globalModal.addEventListener('click', function (event) {
        if (event.target === globalModal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && globalModal.classList.contains('is-open')) {
            closeModal();
        }
    });

    // On load, ensure the chevron icon matches the current state
    document.addEventListener('DOMContentLoaded', function() {
        const app = document.getElementById('appContainer');
        const icon = document.querySelector('.sidebar-toggle i');
        if (icon) {
            if (app.classList.contains('sidebar-collapsed')) {
                icon.className = 'fa-solid fa-chevron-right';
            } else {
                icon.className = 'fa-solid fa-chevron-left';
            }
        }
    });
</script>
</body>
</html>
