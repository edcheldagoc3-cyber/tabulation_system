<?php
$pageTitle = 'Admin Dashboard';
$pageSubtitle = 'Executive overview of campus events, judging progress, and tabulation status.';
$activePage = 'dashboard';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
?>

<style>
/* Modern Dashboard Styles */
.dashboard-hero {
    background: linear-gradient(135deg, #181920 0%, #2a1622 100%);
    border-radius: 20px;
    padding: 26px 30px;
    color: #fff;
    margin-bottom: 26px;
    border: 1px solid rgba(255,255,255,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 16px 36px rgba(15,17,23,.1);
}
.hero-text h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -.02em;
    color: #fff;
}
.hero-text p {
    margin: 6px 0 0;
    color: #9ca3af;
    font-size: 14px;
}
.hero-actions {
    display: flex;
    gap: 12px;
}

/* Event Cards Grid */
.event-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}
.event-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all .2s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,.02);
}
.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0,0,0,.06);
    border-color: rgba(109,26,43,.25);
}
.event-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}
.event-card-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: var(--ink);
}
.event-card-desc {
    color: var(--muted);
    font-size: 13px;
    margin-top: 4px;
    line-height: 1.4;
}
.event-pill-row {
    display: flex;
    gap: 10px;
    margin: 16px 0;
    flex-wrap: wrap;
}
.metric-chip {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 12px;
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 80px;
}
.metric-chip .label {
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 600;
}
.metric-chip .val {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    margin-top: 2px;
}
.event-card-footer {
    padding-top: 16px;
    border-top: 1px solid var(--line);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}

/* Quick Shortcuts Strip */
.shortcuts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
    margin-top: 20px;
}
.shortcut-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all .15s ease;
}
.shortcut-card:hover {
    border-color: var(--maroon);
    background: #faf8f7;
    transform: translateY(-1px);
}
.shortcut-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 18px;
    flex-shrink: 0;
}
</style>

<!-- Welcome Hero Banner -->
<div class="dashboard-hero">
    <div class="hero-text">
        <h3><i class="fa-solid fa-gauge-high" style="color:var(--gold); margin-right:8px;"></i> Tabulation Control Center</h3>
        <p>Manage university events, sub-competitions, rubrics, and real-time tabulation progress.</p>
    </div>
    <div class="hero-actions">
        <a class="btn btn-light" href="<?= app_url('/viewer') ?>" target="_blank">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Public Leaderboard
        </a>
        <a class="btn btn-primary" href="<?= app_url('/admin/events/create') ?>" onclick="loadModal('<?= app_url('/admin/events/create') ?>', 'Create Event', 'Setup a new campus event'); return false;">
            <i class="fa-solid fa-plus"></i> New Event
        </a>
    </div>
</div>

<!-- Key System Metrics -->
<div class="grid-stats">
    <div class="stat">
        <div class="label"><i class="fa-solid fa-calendar-days" style="color:var(--maroon)"></i> Events</div>
        <div class="value"><?= count($events ?? []) ?></div>
        <div class="meta"><?= (int)($stats['live_events'] ?? 0) ?> currently live</div>
    </div>
    <div class="stat">
        <div class="label"><i class="fa-solid fa-tags" style="color:var(--gold)"></i> Competitions</div>
        <div class="value"><?= (int)($stats['categories_total'] ?? 0) ?></div>
        <div class="meta">Configured categories</div>
    </div>
    <div class="stat">
        <div class="label"><i class="fa-solid fa-list-check" style="color:#0f766e"></i> Criteria</div>
        <div class="value"><?= (int)($stats['criteria_total'] ?? 0) ?></div>
        <div class="meta">Scoring rubrics</div>
    </div>
    <div class="stat">
        <div class="label"><i class="fa-solid fa-user-group" style="color:#2563eb"></i> Contestants</div>
        <div class="value"><?= (int)($stats['contestants_total'] ?? 0) ?></div>
        <div class="meta">Registered teams & solos</div>
    </div>
    <div class="stat">
        <div class="label"><i class="fa-solid fa-user-tie" style="color:#7c3aed"></i> Judges</div>
        <div class="value"><?= (int)($stats['judges_total'] ?? 0) ?></div>
        <div class="meta">Active judge accounts</div>
    </div>
    <div class="stat">
        <div class="label"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i> Released</div>
        <div class="value"><?= (int)($stats['released_categories'] ?? 0) ?></div>
        <div class="meta">Published to public</div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SECTION: ACTIVE & SCHEDULED EVENTS (EVENT WORKSPACE HUBS)                 -->
<!-- ========================================================================= -->
<div class="section">
    <div class="section-head">
        <div>
            <h3><i class="fa-solid fa-calendar-days" style="color:var(--maroon)"></i> Campus Events & Workspaces</h3>
            <p>Select an event to access its dedicated workspace, manage competitions, and bulk-add contestants.</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-light small" href="<?= app_url('/admin/events') ?>">View All Events</a>
            <a class="btn btn-primary small" href="<?= app_url('/admin/events/create') ?>" onclick="loadModal('<?= app_url('/admin/events/create') ?>', 'Create Event', 'Add event details'); return false;">
                <i class="fa-solid fa-plus"></i> Create Event
            </a>
        </div>
    </div>
    <div class="section-body">
        <?php if (empty($events)): ?>
            <div class="empty" style="padding:40px 20px; text-align:center;">
                <i class="fa-solid fa-calendar-plus" style="font-size:40px; color:var(--muted); margin-bottom:14px; display:block;"></i>
                <p style="font-size:16px; font-weight:700; margin-bottom:4px;">No campus events found</p>
                <p class="muted" style="margin-bottom:18px;">Create your first event (e.g., USTP Jasaan Paugnat 2026) to start tabulating.</p>
                <a class="btn btn-primary" href="<?= app_url('/admin/events/create') ?>" onclick="loadModal('<?= app_url('/admin/events/create') ?>', 'Create Event', 'Add event details'); return false;">
                    <i class="fa-solid fa-plus"></i> Create First Event
                </a>
            </div>
        <?php else: ?>
            <div class="event-cards-grid">
                <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div>
                        <div class="event-card-head">
                            <div>
                                <h4 class="event-card-title"><?= htmlspecialchars($event->name) ?></h4>
                                <div class="muted" style="font-size:12px; margin-top:2px;">
                                    <i class="fa-regular fa-calendar"></i> <?= date('M d, Y', strtotime($event->event_date)) ?>
                                </div>
                            </div>
                            <span class="tag <?= htmlspecialchars($event->status) ?>">
                                <?= ucfirst($event->status) ?>
                            </span>
                        </div>

                        <?php if (!empty($event->description)): ?>
                            <div class="event-card-desc"><?= htmlspecialchars($event->description) ?></div>
                        <?php endif; ?>

                        <div class="event-pill-row">
                            <div class="metric-chip">
                                <span class="label">Competitions</span>
                                <span class="val"><?= (int)($event->categories_total ?? 0) ?></span>
                            </div>
                            <div class="metric-chip">
                                <span class="label">Released</span>
                                <span class="val" style="color:var(--success)"><?= (int)($event->released_categories ?? 0) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="event-card-footer">
                        <a class="btn btn-primary" style="flex:1; justify-content:center;" href="<?= app_url('/admin/events/manage/' . (int)$event->id) ?>">
                            <i class="fa-solid fa-folder-open"></i> Open Workspace
                        </a>
                        <a class="btn btn-light" title="Public View" href="<?= app_url('/viewer?event_id=' . (int)$event->id) ?>" target="_blank">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a class="btn btn-light" title="Edit Event Details" href="<?= app_url('/admin/events/edit/' . (int)$event->id) ?>" onclick="loadModal('<?= app_url('/admin/events/edit/' . (int)$event->id) ?>', 'Edit Event', 'Update details'); return false;">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SECTION: RECENT COMPETITIONS & TABULATION STATUS                          -->
<!-- ========================================================================= -->
<div class="section">
    <div class="section-head">
        <div>
            <h3><i class="fa-solid fa-chart-pie" style="color:#0f766e"></i> Active Competitions & Judging Status</h3>
            <p>Live overview of category progress across all events.</p>
        </div>
        <a class="btn btn-light small" href="<?= app_url('/admin/categories') ?>">View All Competitions</a>
    </div>
    <div class="section-body">
        <?php if (empty($categories)): ?>
            <div class="empty">No competitions registered yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Competition</th>
                            <th>Parent Event</th>
                            <th>Scoring Engine</th>
                            <th>Judging Progress</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cat->name) ?></strong></td>
                            <td><?= htmlspecialchars($cat->event_name ?? '') ?></td>
                            <td>
                                <span class="pill" style="text-transform:capitalize;">
                                    <?= htmlspecialchars(str_replace('_', ' ', $cat->computation_type)) ?>
                                </span>
                            </td>
                            <td style="min-width:180px">
                                <div class="progress"><span style="width: <?= (int)($cat->progress_percent ?? 0) ?>%"></span></div>
                                <div class="muted" style="font-size:11px; margin-top:3px;">
                                    <?= (int)($cat->judges_submitted ?? 0) ?>/<?= (int)($cat->judges_total ?? 0) ?> judges
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($cat->is_released)): ?>
                                    <span class="tag good"><i class="fa-solid fa-circle-check"></i> Released</span>
                                <?php elseif ((int)($cat->progress_percent ?? 0) >= 100): ?>
                                    <span class="tag warn"><i class="fa-solid fa-clock"></i> Ready</span>
                                <?php else: ?>
                                    <span class="tag draft"><i class="fa-solid fa-hourglass-half"></i> In Progress</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="split-actions">
                                    <a class="btn btn-primary small" title="Tabulator Review & Release" href="<?= app_url('/tabulator/review/' . (int)$cat->id) ?>">
                                        <i class="fa-solid fa-calculator"></i> Review
                                    </a>
                                    <a class="btn btn-light small" title="Event Workspace" href="<?= app_url('/admin/events/manage/' . (int)$cat->event_id) ?>">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- SECTION: QUICK SHORTCUTS & MANAGEMENT MODULES                             -->
<!-- ========================================================================= -->
<div class="section">
    <div class="section-head">
        <div>
            <h3><i class="fa-solid fa-compass" style="color:var(--maroon)"></i> Management Shortcuts</h3>
            <p>Direct navigation to system modules and user management.</p>
        </div>
    </div>
    <div class="section-body">
        <div class="shortcuts-grid">
            <a class="shortcut-card" href="<?= app_url('/admin/events') ?>">
                <div class="shortcut-icon" style="background:rgba(109,26,43,.1); color:var(--maroon);">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:14px; color:var(--ink);">All Events</div>
                    <div class="muted" style="font-size:12px;">Create & manage events</div>
                </div>
            </a>
            <a class="shortcut-card" href="<?= app_url('/admin/categories') ?>">
                <div class="shortcut-icon" style="background:rgba(212,167,44,.15); color:#92400e;">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:14px; color:var(--ink);">Competitions</div>
                    <div class="muted" style="font-size:12px;">View all categories</div>
                </div>
            </a>
            <a class="shortcut-card" href="<?= app_url('/admin/contestants') ?>">
                <div class="shortcut-icon" style="background:rgba(37,99,235,.1); color:#2563eb;">
                    <i class="fa-solid fa-user-group"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:14px; color:var(--ink);">Contestants</div>
                    <div class="muted" style="font-size:12px;">Manage team rosters</div>
                </div>
            </a>
            <a class="shortcut-card" href="<?= app_url('/admin/judges') ?>">
                <div class="shortcut-icon" style="background:rgba(124,58,237,.1); color:#7c3aed;">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:14px; color:var(--ink);">Judges</div>
                    <div class="muted" style="font-size:12px;">Assign judges to contests</div>
                </div>
            </a>
            <a class="shortcut-card" href="<?= app_url('/admin/users') ?>">
                <div class="shortcut-icon" style="background:rgba(15,118,110,.1); color:#0f766e;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:14px; color:var(--ink);">User Accounts</div>
                    <div class="muted" style="font-size:12px;">Admin, tabulator, judge logins</div>
                </div>
            </a>
            <a class="shortcut-card" href="<?= app_url('/reports') ?>">
                <div class="shortcut-icon" style="background:rgba(21,128,61,.1); color:var(--success);">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:14px; color:var(--ink);">Reports & Exports</div>
                    <div class="muted" style="font-size:12px;">PDF & CSV signed sheets</div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>