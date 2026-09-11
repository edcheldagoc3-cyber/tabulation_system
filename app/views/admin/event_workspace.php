<?php
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$pageTitle = htmlspecialchars($event->name);
$pageSubtitle = 'Event Workspace · ' . date('F d, Y', strtotime($event->event_date));
$activePage = 'events';
ob_start();

$totalCategories = count($categories ?? []);
$totalContestants = count($contestants ?? []);
$totalAssignments = count($assignments ?? []);
$releasedCount = count(array_filter($categories ?? [], static function ($c) {
    return !empty($c->is_released);
}));
?>

<style>
/* Workspace Specific Styling */
.workspace-banner {
    background: linear-gradient(135deg, #1b1c24 0%, #291a22 50%, #15161d 100%);
    color: #fff;
    border-radius: 20px;
    padding: 24px 28px;
    margin-bottom: 24px;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: 0 16px 36px rgba(10,12,18,.12);
    position: relative;
    overflow: hidden;
}
.workspace-banner::after {
    content: "";
    position: absolute;
    top: -50%;
    right: -20%;
    width: 380px;
    height: 380px;
    background: radial-gradient(circle, rgba(212,167,44,.15), transparent 70%);
    pointer-events: none;
}
.workspace-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    flex-wrap: wrap;
    position: relative;
    z-index: 2;
}
.workspace-title-wrap h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -.03em;
    display: flex;
    align-items: center;
    gap: 12px;
}
.workspace-meta {
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 16px;
    color: #9ca3af;
    font-size: 13px;
    flex-wrap: wrap;
}
.workspace-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}
.workspace-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 12px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,.08);
}
.w-stat {
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 12px;
    padding: 10px 14px;
}
.w-stat .num {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
}
.w-stat .desc {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-top: 2px;
}

/* Tab Navigation */
.workspace-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid var(--line);
    margin-bottom: 24px;
    overflow-x: auto;
    padding-bottom: 2px;
}
.tab-btn {
    padding: 12px 18px;
    font-size: 14px;
    font-weight: 600;
    color: var(--muted);
    background: transparent;
    border: none;
    cursor: pointer;
    border-radius: 10px 10px 0 0;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s ease;
    white-space: nowrap;
    position: relative;
}
.tab-btn i {
    font-size: 15px;
}
.tab-btn:hover {
    color: var(--ink);
    background: rgba(255,255,255,.6);
}
.tab-btn.active {
    color: var(--maroon);
    background: #fff;
    border: 1px solid var(--line);
    border-bottom-color: #fff;
    margin-bottom: -2px;
    box-shadow: 0 -4px 12px rgba(0,0,0,.03);
}
.tab-badge {
    background: rgba(109,26,43,.1);
    color: var(--maroon);
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
}
.tab-btn.active .tab-badge {
    background: var(--maroon);
    color: #fff;
}

/* Tab Panels */
.tab-panel {
    display: none;
}
.tab-panel.active {
    display: block;
    animation: tabFadeIn .2s ease;
}
@keyframes tabFadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Fast Action Bar */
.fast-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.btn-preset {
    background: linear-gradient(135deg, #0f766e, #0d5f59);
    color: #fff;
    box-shadow: 0 4px 14px rgba(15,118,110,.25);
}
.btn-preset:hover {
    background: #0d5f59;
    transform: translateY(-1px);
}
.category-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    transition: box-shadow .2s ease;
}
.category-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,.04);
}
.category-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 14px;
}
.cat-title {
    font-size: 17px;
    font-weight: 700;
    color: var(--ink);
    margin: 0;
}
.criteria-list-mini {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}
.criterion-chip {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.weight-badge {
    font-weight: 700;
    color: #0f766e;
    background: rgba(15,118,110,.08);
    padding: 2px 6px;
    border-radius: 4px;
}
</style>

<!-- Top Breadcrumb & Actions -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <div style="font-size:13px; color:var(--muted)">
        <a href="<?= app_url('/admin/events') ?>" style="color:var(--maroon); font-weight:600;"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>
        <span style="margin:0 8px">/</span>
        <span><?= htmlspecialchars($event->name) ?></span>
    </div>
    <div style="display:flex; gap:8px;">
        <a class="btn btn-light small" href="<?= app_url('/viewer?event_id=' . (int)$event->id) ?>" target="_blank">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Public Board
        </a>
        <a class="btn btn-light small" href="<?= app_url('/admin/events/edit/' . (int)$event->id) ?>" onclick="loadModal('<?= app_url('/admin/events/edit/' . (int)$event->id) ?>', 'Edit Event', 'Update event details'); return false;">
            <i class="fa-solid fa-pen"></i> Edit Event
        </a>
    </div>
</div>

<!-- Event Hero Banner -->
<div class="workspace-banner">
    <div class="workspace-header">
        <div class="workspace-title-wrap">
            <h2>
                <i class="fa-solid fa-trophy" style="color:var(--gold)"></i>
                <?= htmlspecialchars($event->name) ?>
            </h2>
            <div class="workspace-meta">
                <span><i class="fa-regular fa-calendar"></i> <?= date('F d, Y', strtotime($event->event_date)) ?></span>
                <span>
                    <i class="fa-solid fa-circle-dot" style="font-size:10px; color:<?= $event->status === 'live' ? '#22c55e' : '#9ca3af' ?>"></i>
                    Status: <strong style="color:#fff; text-transform:uppercase; letter-spacing:.05em"><?= htmlspecialchars($event->status) ?></strong>
                </span>
                <?php if (!empty($event->description)): ?>
                    <span><i class="fa-regular fa-message"></i> <?= htmlspecialchars($event->description) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <a class="btn btn-primary" href="<?= app_url('/admin/categories/create') ?>" onclick="openNewCategoryModal(); return false;">
                <i class="fa-solid fa-plus"></i> Add Competition
            </a>
        </div>
    </div>

    <!-- Quick Stats Strip -->
    <div class="workspace-stats">
        <div class="w-stat">
            <div class="num"><?= $totalCategories ?></div>
            <div class="desc">Competitions</div>
        </div>
        <div class="w-stat">
            <div class="num"><?= $totalContestants ?></div>
            <div class="desc">Contestants / Teams</div>
        </div>
        <div class="w-stat">
            <div class="num"><?= $totalAssignments ?></div>
            <div class="desc">Judges Assigned</div>
        </div>
        <div class="w-stat">
            <div class="num" style="color:var(--gold)"><?= $releasedCount ?> / <?= $totalCategories ?></div>
            <div class="desc">Released to Public</div>
        </div>
    </div>
</div>

<!-- Workspace Tabs -->
<div class="workspace-tabs" role="tablist">
    <button class="tab-btn active" onclick="switchTab('tab-competitions', this)" role="tab">
        <i class="fa-solid fa-tags"></i> Competitions / Categories
        <span class="tab-badge"><?= $totalCategories ?></span>
    </button>
    <button class="tab-btn" onclick="switchTab('tab-contestants', this)" role="tab">
        <i class="fa-solid fa-user-group"></i> Contestants & Departments
        <span class="tab-badge"><?= $totalContestants ?></span>
    </button>
    <button class="tab-btn" onclick="switchTab('tab-judges', this)" role="tab">
        <i class="fa-solid fa-user-tie"></i> Judge Assignments
        <span class="tab-badge"><?= $totalAssignments ?></span>
    </button>
    <button class="tab-btn" onclick="switchTab('tab-criteria', this)" role="tab">
        <i class="fa-solid fa-list-check"></i> Scoring Criteria Rubrics
        <span class="tab-badge"><?= count($criteria ?? []) ?></span>
    </button>
    <button class="tab-btn" onclick="switchTab('tab-tabulation', this)" role="tab">
        <i class="fa-solid fa-chart-line"></i> Live Tabulation Pulse
    </button>
</div>

<!-- ========================================================================= -->
<!-- TAB 1: COMPETITIONS / CATEGORIES                                          -->
<!-- ========================================================================= -->
<div id="tab-competitions" class="tab-panel active">
    <div class="section">
        <div class="section-head">
            <div>
                <h3><i class="fa-solid fa-tags" style="color:var(--gold)"></i> Competitions under <?= htmlspecialchars($event->name) ?></h3>
                <p>Manage sub-competitions, scoring engines, and tie-breaking rules.</p>
            </div>
            <a class="btn btn-primary small" href="<?= app_url('/admin/categories/create') ?>" onclick="openNewCategoryModal(); return false;">
                <i class="fa-solid fa-plus"></i> Add Competition
            </a>
        </div>
        <div class="section-body">
            <?php if (empty($categories)): ?>
                <div class="empty" style="padding:40px 20px; text-align:center;">
                    <i class="fa-solid fa-tags" style="font-size:36px; color:var(--muted); margin-bottom:12px; display:block;"></i>
                    <p style="font-weight:600; margin-bottom:4px;">No competitions created for this event yet.</p>
                    <p class="muted" style="margin-bottom:16px;">Add competitions like Cheer Dance, Vocal Solo, or Quiz Bee.</p>
                    <a class="btn btn-primary small" href="<?= app_url('/admin/categories/create') ?>" onclick="openNewCategoryModal(); return false;">
                        <i class="fa-solid fa-plus"></i> Add First Competition
                    </a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Competition</th>
                                <th>Scoring Engine</th>
                                <th>Contestants</th>
                                <th>Judging Progress</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($cat->name) ?></strong>
                                    <div class="muted" style="font-size:12px;">
                                        Tie-Break: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $cat->tiebreak_rule ?? 'manual'))) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="pill" style="text-transform:capitalize;">
                                        <?= htmlspecialchars(str_replace('_', ' ', $cat->computation_type)) ?>
                                    </span>
                                </td>
                                <td><?= (int)($cat->contestants_total ?? 0) ?></td>
                                <td style="min-width:180px">
                                    <div class="progress"><span style="width: <?= (int)($cat->progress_percent ?? 0) ?>%"></span></div>
                                    <div class="muted" style="font-size:11px; margin-top:3px;">
                                        <?= (int)($cat->judges_submitted ?? 0) ?> / <?= (int)($cat->judges_total ?? 0) ?> judges completed
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($cat->is_released)): ?>
                                        <span class="tag good"><i class="fa-solid fa-circle-check"></i> Released</span>
                                    <?php elseif ((int)($cat->progress_percent ?? 0) >= 100): ?>
                                        <span class="tag warn"><i class="fa-solid fa-clock"></i> Ready for Release</span>
                                    <?php else: ?>
                                        <span class="tag draft"><i class="fa-solid fa-hourglass-half"></i> In Progress</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="split-actions">
                                        <a class="btn btn-light small" title="Review & Tabulation" href="<?= app_url('/tabulator/review/' . (int)$cat->id) ?>">
                                            <i class="fa-solid fa-calculator"></i>
                                        </a>
                                        <a class="btn btn-light small" title="Edit" href="<?= app_url('/admin/categories/edit/' . (int)$cat->id . '?event_id=' . (int)$event->id) ?>" onclick="loadModal('<?= app_url('/admin/categories/edit/' . (int)$cat->id . '?event_id=' . (int)$event->id) ?>', 'Edit Competition', 'Update competition settings'); return false;">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <button class="btn btn-danger small" type="button" title="Delete" onclick="confirmDelete('<?= app_url('/admin/categories/delete/' . (int)$cat->id . '?event_id=' . (int)$event->id) ?>', 'Delete Competition', 'Delete this competition, its criteria, and contestant scores?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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
</div>

<!-- ========================================================================= -->
<!-- TAB 2: CONTESTANTS & DEPARTMENTS (WITH BULK TOOLS)                         -->
<!-- ========================================================================= -->
<div id="tab-contestants" class="tab-panel">
    <div class="section">
        <div class="section-head">
            <div>
                <h3><i class="fa-solid fa-user-group" style="color:#2563eb"></i> Contestants & Department Teams</h3>
                <p>Register solo contestants or department teams across competitions.</p>
            </div>
            <div class="fast-actions">
                <button class="btn btn-preset small" type="button" onclick="openUstpQuickAddModal()">
                    <i class="fa-solid fa-bolt"></i> Quick-Add 5 USTP Departments
                </button>
                <button class="btn btn-light small" type="button" onclick="openBulkContestantsModal()">
                    <i class="fa-solid fa-list-ol"></i> Bulk Add Names
                </button>
                <a class="btn btn-primary small" href="<?= app_url('/admin/contestants/create') ?>" onclick="openNewContestantModal(); return false;">
                    <i class="fa-solid fa-plus"></i> Add Single
                </a>
            </div>
        </div>
        <div class="section-body">
            <?php if (empty($contestants)): ?>
                <div class="empty" style="padding:40px 20px; text-align:center;">
                    <i class="fa-solid fa-user-group" style="font-size:36px; color:var(--muted); margin-bottom:12px; display:block;"></i>
                    <p style="font-weight:600; margin-bottom:4px;">No contestants registered for this event yet.</p>
                    <p class="muted" style="margin-bottom:16px;">Click the green button above to auto-register all 5 USTP Jasaan departments in 1 click!</p>
                    <button class="btn btn-preset" type="button" onclick="openUstpQuickAddModal()">
                        <i class="fa-solid fa-bolt"></i> Quick-Add 5 USTP Departments Now
                    </button>
                </div>
            <?php else: ?>
                <!-- Category Filter -->
                <div style="margin-bottom:16px; display:flex; gap:12px; align-items:center;">
                    <label style="font-size:13px; font-weight:600; color:var(--muted);">Filter by Competition:</label>
                    <select id="contestantCategoryFilter" class="input" style="max-width:300px;" onchange="filterContestantTable(this.value)">
                        <option value="">All Competitions (<?= count($contestants) ?>)</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="table-wrap">
                    <table id="contestantsTable">
                        <thead>
                            <tr>
                                <th>Contestant / Team Name</th>
                                <th>Competition</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contestants as $c): ?>
                            <tr data-category-id="<?= (int)$c->category_id ?>">
                                <td>
                                    <strong><?= htmlspecialchars($c->name) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($c->category_name) ?></td>
                                <td>
                                    <span class="pill" style="<?= $c->type === 'team' ? 'background:#e0e7ff; color:#3730a3;' : '' ?>">
                                        <?= ucfirst($c->type) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="split-actions">
                                        <a class="btn btn-light small" href="<?= app_url('/admin/contestants/edit/' . (int)$c->id . '?event_id=' . (int)$event->id) ?>" onclick="loadModal('<?= app_url('/admin/contestants/edit/' . (int)$c->id . '?event_id=' . (int)$event->id) ?>', 'Edit Contestant', 'Update contestant details'); return false;">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <button class="btn btn-danger small" type="button" onclick="confirmDelete('<?= app_url('/admin/contestants/delete/' . (int)$c->id . '?event_id=' . (int)$event->id) ?>', 'Delete Contestant', 'Remove this contestant?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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
</div>

<!-- ========================================================================= -->
<!-- TAB 3: JUDGE ASSIGNMENTS                                                  -->
<!-- ========================================================================= -->
<div id="tab-judges" class="tab-panel">
    <div class="section">
        <div class="section-head">
            <div>
                <h3><i class="fa-solid fa-user-tie" style="color:#7c3aed"></i> Judge Assignments for <?= htmlspecialchars($event->name) ?></h3>
                <p>Assign judges specifically to competitions under this event.</p>
            </div>
            <button class="btn btn-primary small" type="button" onclick="openAssignJudgeModal()">
                <i class="fa-solid fa-user-plus"></i> Assign Judge
            </button>
        </div>
        <div class="section-body panel-grid">
            <div class="card">
                <h4>Quick Assign Judge</h4>
                <form method="POST" action="<?= app_url('/admin/judges/assign') ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_event_id" value="<?= (int)$event->id ?>">
                    <div class="field">
                        <label>Select Judge</label>
                        <select name="user_id" class="input" required>
                            <option value="">-- Choose Judge --</option>
                            <?php foreach ($judges as $judge): ?>
                                <option value="<?= (int)$judge->id ?>"><?= htmlspecialchars($judge->full_name . ' (' . $judge->email . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Assign to Competition</label>
                        <select name="category_id" class="input" required>
                            <option value="">-- Choose Competition --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-check"></i> Assign Judge to Competition</button>
                </form>
            </div>

            <div class="card">
                <h4>Assigned Judges (<?= count($assignments) ?>)</h4>
                <?php if (empty($assignments)): ?>
                    <div class="empty">No judges assigned to this event's competitions yet.</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Judge Name</th>
                                    <th>Competition</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($a->judge_name) ?></strong><br>
                                        <span class="muted" style="font-size:12px;"><?= htmlspecialchars($a->judge_email) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($a->category_name) ?></td>
                                    <td>
                                        <form class="inline-delete" method="POST" action="<?= app_url('/admin/judges/remove/' . (int)$a->id . '?event_id=' . (int)$event->id) ?>" onsubmit="return confirm('Remove this judge assignment?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                            <button class="btn btn-danger small" type="submit" title="Remove Assignment"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- TAB 4: SCORING CRITERIA RUBRICS                                           -->
<!-- ========================================================================= -->
<div id="tab-criteria" class="tab-panel">
    <div class="section">
        <div class="section-head">
            <div>
                <h3><i class="fa-solid fa-list-check" style="color:#0f766e"></i> Scoring Criteria & Rubrics</h3>
                <p>Define criteria and verify that category weights equal exactly 100%.</p>
            </div>
            <a class="btn btn-primary small" href="<?= app_url('/admin/criteria/create') ?>" onclick="openNewCriterionModal(); return false;">
                <i class="fa-solid fa-plus"></i> Add Criterion
            </a>
        </div>
        <div class="section-body">
            <?php if (empty($categories)): ?>
                <div class="empty">Please create at least one competition first.</div>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $catCriteria = array_filter($criteria ?? [], static function ($c) use ($cat) {
                        return (int)$c->category_id === (int)$cat->id;
                    });
                    $weightSum = 0.0;
                    foreach ($catCriteria as $cr) {
                        $weightSum += (float)$cr->weight_percent;
                    }
                    $isHundred = abs($weightSum - 100.0) < 0.001;
                    ?>
                    <div class="category-card">
                        <div class="category-header">
                            <div>
                                <h4 class="cat-title">
                                    <i class="fa-solid fa-tag" style="color:var(--gold)"></i>
                                    <?= htmlspecialchars($cat->name) ?>
                                    <span class="muted" style="font-size:12px; font-weight:normal; margin-left:8px;">
                                        (Engine: <?= htmlspecialchars(str_replace('_', ' ', $cat->computation_type)) ?>)
                                    </span>
                                </h4>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <?php if ($cat->computation_type === 'weighted_criteria'): ?>
                                    <span class="pill" style="<?= $isHundred ? 'background:#dcfce7; color:#166534;' : 'background:#fee2e2; color:#991b1b;' ?>">
                                        Weight Total: <?= number_format($weightSum, 1) ?>% <?= $isHundred ? '✓' : '(Must equal 100%)' ?>
                                    </span>
                                <?php endif; ?>
                                <button class="btn btn-light small" type="button" onclick="openAddCriterionForCategory(<?= (int)$cat->id ?>)">
                                    <i class="fa-solid fa-plus"></i> Add Criterion
                                </button>
                            </div>
                        </div>

                        <?php if (empty($catCriteria)): ?>
                            <div class="muted" style="font-size:13px; font-style:italic; padding:8px 0;">
                                No scoring criteria defined yet for this competition.
                            </div>
                        <?php else: ?>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Criterion Name</th>
                                            <th>Weight</th>
                                            <th>Score Range</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($catCriteria as $cr): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($cr->name) ?></strong></td>
                                            <td><span class="weight-badge"><?= number_format((float)$cr->weight_percent, 2) ?>%</span></td>
                                            <td><?= number_format((float)$cr->min_score, 0) ?> – <?= number_format((float)$cr->max_score, 0) ?> pts</td>
                                            <td>
                                                <div class="split-actions">
                                                    <a class="btn btn-light small" href="<?= app_url('/admin/criteria/edit/' . (int)$cr->id . '?event_id=' . (int)$event->id) ?>" onclick="loadModal('<?= app_url('/admin/criteria/edit/' . (int)$cr->id . '?event_id=' . (int)$event->id) ?>', 'Edit Criterion', 'Update criterion settings'); return false;">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                    <button class="btn btn-danger small" type="button" onclick="confirmDelete('<?= app_url('/admin/criteria/delete/' . (int)$cr->id . '?event_id=' . (int)$event->id) ?>', 'Delete Criterion', 'Delete this criterion?');">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- TAB 5: LIVE TABULATION PULSE                                              -->
<!-- ========================================================================= -->
<div id="tab-tabulation" class="tab-panel">
    <div class="section">
        <div class="section-head">
            <div>
                <h3><i class="fa-solid fa-chart-line" style="color:var(--maroon)"></i> Live Tabulation & Results Status</h3>
                <p>Monitor real-time judging progress across all competitions.</p>
            </div>
            <a class="btn btn-light small" href="<?= app_url('/viewer?event_id=' . (int)$event->id) ?>" target="_blank">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Public Leaderboard
            </a>
        </div>
        <div class="section-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px;">
                <?php foreach ($categories as $cat): ?>
                    <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                                <h4 style="margin:0; font-size:16px;"><?= htmlspecialchars($cat->name) ?></h4>
                                <?php if (!empty($cat->is_released)): ?>
                                    <span class="tag good"><i class="fa-solid fa-circle-check"></i> Released</span>
                                <?php elseif ((int)($cat->progress_percent ?? 0) >= 100): ?>
                                    <span class="tag warn"><i class="fa-solid fa-clock"></i> Ready to Release</span>
                                <?php else: ?>
                                    <span class="tag draft"><i class="fa-solid fa-hourglass-half"></i> Scoring</span>
                                <?php endif; ?>
                            </div>

                            <div style="margin:12px 0;">
                                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
                                    <span>Judging Progress</span>
                                    <strong><?= (int)($cat->progress_percent ?? 0) ?>%</strong>
                                </div>
                                <div class="progress" style="height:8px;"><span style="width:<?= (int)($cat->progress_percent ?? 0) ?>%"></span></div>
                                <div class="muted" style="font-size:11px; margin-top:4px;">
                                    <?= (int)($cat->judges_submitted ?? 0) ?> of <?= (int)($cat->judges_total ?? 0) ?> judges submitted
                                </div>
                            </div>

                            <div style="display:flex; gap:16px; font-size:12px; color:var(--muted); margin-top:10px;">
                                <div><i class="fa-solid fa-user-group"></i> <?= (int)($cat->contestants_total ?? 0) ?> Contestants</div>
                                <div><i class="fa-solid fa-list-check"></i> <?= (int)($cat->criteria_total ?? 0) ?> Criteria</div>
                            </div>
                        </div>

                        <div style="margin-top:18px; padding-top:14px; border-top:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                            <a class="btn btn-primary small" href="<?= app_url('/tabulator/review/' . (int)$cat->id) ?>">
                                <i class="fa-solid fa-calculator"></i> Tabulation Review
                            </a>
                            <a class="btn btn-light small" href="<?= app_url('/reports/category/' . (int)$cat->id) ?>" title="Category Report">
                                <i class="fa-solid fa-file-lines"></i> Report
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: QUICK-ADD 5 USTP DEPARTMENTS                                       -->
<!-- ========================================================================= -->
<div id="ustpModal" class="global-modal" aria-hidden="true">
    <div class="global-modal__card" role="dialog" aria-modal="true">
        <div class="global-modal__head">
            <div>
                <h3 class="global-modal__title"><i class="fa-solid fa-bolt" style="color:var(--gold)"></i> Quick-Add USTP Jasaan Departments</h3>
                <p class="global-modal__subtitle">Register all 5 official campus courses as team contestants in one click.</p>
            </div>
            <button type="button" class="global-modal__close" onclick="closeUstpModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="global-modal__body">
            <form method="POST" action="<?= app_url('/admin/contestants/bulk-create') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="event_id" value="<?= (int)$event->id ?>">
                <input type="hidden" name="preset" value="ustp_departments">
                <input type="hidden" name="type" value="team">

                <div class="field">
                    <label>Target Competition</label>
                    <select name="category_id" class="input" required id="ustpTargetCategory">
                        <option value="">-- Choose Competition --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:16px;">
                    <div style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:8px;">
                        The following 5 departments will be registered:
                    </div>
                    <ul style="margin:0; padding-left:20px; font-size:13px; color:#1e293b; line-height:1.8;">
                        <li><strong>BSIT</strong> — Department of Information Technology</li>
                        <li><strong>BSED</strong> — Department of Education</li>
                        <li><strong>BSHM</strong> — Department of Hospitality Management</li>
                        <li><strong>BSBA</strong> — Department of Business Administration</li>
                        <li><strong>BSECE</strong> — Department of Computer Engineering</li>
                    </ul>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button class="btn btn-light" type="button" onclick="closeUstpModal()">Cancel</button>
                    <button class="btn btn-preset" type="submit"><i class="fa-solid fa-bolt"></i> Register 5 Departments</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BULK ADD CUSTOM CONTESTANTS                                        -->
<!-- ========================================================================= -->
<div id="bulkModal" class="global-modal" aria-hidden="true">
    <div class="global-modal__card" role="dialog" aria-modal="true">
        <div class="global-modal__head">
            <div>
                <h3 class="global-modal__title"><i class="fa-solid fa-list-ol"></i> Bulk Add Contestants</h3>
                <p class="global-modal__subtitle">Paste multiple contestant or team names (one per line).</p>
            </div>
            <button type="button" class="global-modal__close" onclick="closeBulkModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="global-modal__body">
            <form method="POST" action="<?= app_url('/admin/contestants/bulk-create') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="event_id" value="<?= (int)$event->id ?>">

                <div class="form-row">
                    <div class="field">
                        <label>Target Competition</label>
                        <select name="category_id" class="input" required>
                            <option value="">-- Choose Competition --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Entry Type</label>
                        <select name="type" class="input">
                            <option value="team">Team</option>
                            <option value="solo">Solo</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label>Contestant Names (one per line)</label>
                    <textarea class="input" name="names" rows="6" required placeholder="Team Alpha&#10;Team Beta&#10;Team Gamma"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button class="btn btn-light" type="button" onclick="closeBulkModal()">Cancel</button>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus"></i> Add All Contestants</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tabId, triggerBtn) {
    document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    const target = document.getElementById(tabId);
    if (target) {
        target.classList.add('active');
    }
    if (triggerBtn) {
        triggerBtn.classList.add('active');
    }
}

function filterContestantTable(categoryId) {
    const rows = document.querySelectorAll('#contestantsTable tbody tr');
    rows.forEach(row => {
        if (!categoryId || row.getAttribute('data-category-id') === categoryId) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function openUstpQuickAddModal(preselectedCatId) {
    if (preselectedCatId) {
        document.getElementById('ustpTargetCategory').value = preselectedCatId;
    }
    document.getElementById('ustpModal').setAttribute('aria-hidden', 'false');
}

function closeUstpModal() {
    document.getElementById('ustpModal').setAttribute('aria-hidden', 'true');
}

function openBulkContestantsModal() {
    document.getElementById('bulkModal').setAttribute('aria-hidden', 'false');
}

function closeBulkModal() {
    document.getElementById('bulkModal').setAttribute('aria-hidden', 'true');
}

function openNewCategoryModal() {
    loadModal('<?= app_url('/admin/categories/create?event_id=' . (int)$event->id) ?>', 'Create Competition', 'Add a sub-competition under <?= addslashes(htmlspecialchars($event->name)) ?>');
}

function openNewContestantModal() {
    loadModal('<?= app_url('/admin/contestants/create?event_id=' . (int)$event->id) ?>', 'Add Contestant', 'Register a contestant or team');
}

function openNewCriterionModal() {
    loadModal('<?= app_url('/admin/criteria/create?event_id=' . (int)$event->id) ?>', 'Add Scoring Criterion', 'Define a rubric criterion and percentage weight');
}

function openAssignJudgeModal() {
    loadModal('<?= app_url('/admin/judges/assign?event_id=' . (int)$event->id) ?>', 'Assign Judge', 'Assign a judge to a competition');
}

function openAddCriterionForCategory(categoryId) {
    loadModal('<?= app_url('/admin/criteria/create?event_id=' . (int)$event->id) ?>&category_id=' + encodeURIComponent(categoryId), 'Add Scoring Criterion', 'Define a rubric criterion and percentage weight');
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
