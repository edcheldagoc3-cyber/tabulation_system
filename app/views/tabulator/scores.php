<?php
$pageTitle = 'Locked Scores';
$pageSubtitle = 'Unlock submissions when a judge needs to correct their scores.';
$activePage = 'scores';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$submissions = $submissions ?? [];
$categories = $categories ?? [];
$selectedCategoryId = $selectedCategoryId ?? null;
?>

<!-- Filter -->
<div style="background:#fff;border-radius:16px;padding:16px 20px;border:1px solid var(--line);margin-bottom:24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
    <form method="GET" action="<?= app_url('/tabulator/scores') ?>" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
            <label style="font-weight:600;font-size:13px;color:var(--muted);">Filter by Category</label>
            <select name="category_id" onchange="this.form.submit()" style="padding:8px 14px;border:1px solid var(--line);border-radius:10px;font-size:14px;background:#fff;min-width:180px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat->id ?>" <?= ((int)$selectedCategoryId === (int)$cat->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat->event_name . ' - ' . $cat->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-light" style="padding:8px 16px;">Apply</button>
    </form>
</div>

<!-- Locked Scores Table -->
<div class="section">
    <div class="section-head">
        <div>
            <h3><i class="fa-solid fa-lock" style="color:#6d1a2b;"></i> Locked Submissions</h3>
            <p>These scores are locked. Use the "Unlock" button to allow a judge to make corrections.</p>
        </div>
        <span class="muted" style="font-size:13px;"><?= count($submissions) ?> submissions</span>
    </div>
    <div class="section-body">
        <?php if (empty($submissions)): ?>
            <div class="empty">No locked submissions found.</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Contestant</th>
                        <th>Category</th>
                        <th>Judge</th>
                        <th>Criteria Scored</th>
                        <th>Locked At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sub->contestant_name ?? 'N/A') ?></strong></td>
                            <td><?= htmlspecialchars($sub->category_name ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($sub->judge_name ?? 'N/A') ?></td>
                            <td><?= (int)($sub->score_count ?? 0) ?> criteria</td>
                            <td><?= !empty($sub->last_submitted_at) ? date('M d, Y h:i A', strtotime($sub->last_submitted_at)) : 'N/A' ?></td>
                            <td>
                                <?php if (isset($sub->assignment_id) && isset($sub->contestant_id)): ?>
                                <form class="inline" method="POST" action="<?= app_url('/admin/scores/unlock/' . (int)$sub->assignment_id . '/' . (int)$sub->contestant_id) ?>" onsubmit="return confirm('Unlock this submission? This allows the judge to re-submit.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-warning" type="submit" style="padding:6px 14px;font-size:12px;border-radius:8px;background:#fef3c7;color:#92400e;border:none;cursor:pointer;font-weight:700;">
                                        <i class="fa-solid fa-unlock"></i> Unlock
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="muted">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
