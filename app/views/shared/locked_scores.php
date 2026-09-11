<?php
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$unlockBasePath = $unlockBasePath ?? '/admin/scores/unlock';
$filterBasePath = $filterBasePath ?? '/admin/scores';
?>
<div class="section">
    <div class="section-head">
        <div>
            <h3><i class="fa-solid fa-lock"></i> Locked Score Submissions</h3>
            <p>Unlock a submission so the judge can edit and resubmit (FR9).</p>
        </div>
        <form method="GET" action="<?= app_url($filterBasePath) ?>" class="split-actions" style="align-items:flex-end;">
            <div class="field" style="margin:0;min-width:220px;">
                <label>Filter by category</label>
                <select name="category_id" class="input" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category->id ?>" <?= ((int)($selectedCategoryId ?? 0) === (int)$category->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($category->event_name ?? '') . ' - ' . $category->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="section-body">
        <?php if (empty($submissions)): ?>
            <div class="empty">No locked score submissions found.</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Category</th>
                        <th>Judge</th>
                        <th>Contestant</th>
                        <th>Scores</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?= htmlspecialchars($submission->event_name) ?></td>
                        <td><?= htmlspecialchars($submission->category_name) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($submission->judge_name) ?></strong><br>
                            <span class="muted"><?= htmlspecialchars($submission->judge_email) ?></span>
                        </td>
                        <td><?= htmlspecialchars($submission->contestant_name) ?></td>
                        <td><?= (int)$submission->score_count ?></td>
                        <td><?= !empty($submission->last_submitted_at) ? date('M d, Y g:i A', strtotime($submission->last_submitted_at)) : '—' ?></td>
                        <td>
                            <form class="inline-delete" method="POST"
                                  action="<?= app_url($unlockBasePath . '/' . (int)$submission->assignment_id . '/' . (int)$submission->contestant_id . (!empty($selectedCategoryId) ? '?category_id=' . (int)$selectedCategoryId : '')) ?>"
                                  onsubmit="return confirm('Unlock scores for this judge and contestant?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                <button class="btn btn-light small" type="submit"><i class="fa-solid fa-unlock"></i> Unlock</button>
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
