<?php
$pageTitle = 'Review: ' . htmlspecialchars($category->name ?? 'Category');
$pageSubtitle = 'Inspect submissions and verify the calculation before release.';
$activePage = 'dashboard';
$csrf = function_exists('csrf_token') ? csrf_token() : '';
ob_start();
?>
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($category->name ?? '') ?></h2>
        <p class="muted" style="margin:4px 0 0;"><?= htmlspecialchars($category->event_name ?? '') ?> | <?= htmlspecialchars($category->computation_type ?? '') ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <form method="POST" action="<?= app_url('/tabulator/recompute/' . (int)$category->id) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-light" type="submit"><i class="fa-solid fa-rotate"></i> Recompute</button>
        </form>
        <a class="btn btn-light" href="<?= app_url('/reports/export-pdf/' . (int)$category->id) ?>"><i class="fa-solid fa-file-pdf"></i> Results PDF</a>
        <a class="btn btn-light" href="<?= app_url('/reports/export-breakdown-pdf/' . (int)$category->id) ?>"><i class="fa-solid fa-table-list"></i> Breakdown PDF</a>
        <a class="btn btn-light" href="<?= app_url('/tabulator') ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat"><div class="label">Judge Completion</div><div class="value"><?= (int)($category->progress_percent ?? 0) ?>%</div><div class="meta"><?= (int)($category->judges_submitted ?? 0) ?>/<?= (int)($category->judges_total ?? 0) ?> judges complete</div></div>
    <div class="stat"><div class="label">Contestants</div><div class="value"><?= count($contestants) ?></div><div class="meta">Registered in category</div></div>
    <div class="stat"><div class="label">Locked Scores</div><div class="value"><?= count($scores) ?></div><div class="meta">Inputs used by tabulation</div></div>
    <div class="stat"><div class="label">Weight Total</div><div class="value"><?= number_format((float)$weightsTotal, 2) ?>%</div><div class="meta">Criteria configuration</div></div>
</div>

<?php if (!empty($warnings)): ?>
    <section class="section" style="border-left:5px solid #f59e0b;">
        <div class="section-head"><div><h3><i class="fa-solid fa-triangle-exclamation" style="color:#b45309;"></i> Review Warnings</h3><p>Resolve these items before releasing official results.</p></div></div>
        <div class="section-body">
            <?php foreach ($warnings as $warning): ?>
                <div style="padding:9px 0;border-bottom:1px solid var(--line);color:#92400e;"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($warning) ?></div>
            <?php endforeach; ?>
        </div>
    </section>
<?php else: ?>
    <div style="padding:14px 18px;border-radius:14px;background:#dcfce7;color:#166534;margin-bottom:20px;"><i class="fa-solid fa-circle-check"></i> All review checks passed. Results are ready for release.</div>
<?php endif; ?>

<section class="section">
    <div class="section-head"><div><h3><i class="fa-solid fa-ranking-star" style="color:#6d1a2b;"></i> Computed Results</h3><p>Final scores are based on locked submissions only.</p></div></div>
    <div class="section-body">
        <?php if (empty($results)): ?>
            <div class="empty">No computed results are available.</div>
        <?php else: ?>
            <div class="table-wrap"><table><thead><tr><th>Rank</th><th>Contestant</th><th>Final Score</th><th>Release Status</th></tr></thead><tbody>
            <?php foreach ($results as $result): ?>
                <tr><td><strong>#<?= (int)$result->final_rank ?></strong></td><td><?= htmlspecialchars($result->contestant_name) ?></td><td><strong><?= number_format((float)$result->final_score, 2) ?></strong></td><td><?= !empty($result->is_released) ? '<span class="tag good">Official</span>' : '<span class="tag warn">Provisional</span>' ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="section-head"><div><h3><i class="fa-solid fa-calculator" style="color:#6d1a2b;"></i> Score Breakdown</h3><p>Inspect every locked score used in the current computation.</p></div></div>
    <div class="section-body">
        <?php if (empty($scores)): ?>
            <div class="empty">No locked score submissions are available.</div>
        <?php else: ?>
            <div class="table-wrap"><table><thead><tr><th>Contestant</th><th>Judge</th><th>Criterion</th><th>Score</th><th>Weight</th><th>Weighted Contribution</th></tr></thead><tbody>
            <?php foreach ($scores as $score): ?>
                <tr><td><?= htmlspecialchars($score->contestant_name) ?></td><td><?= htmlspecialchars($score->judge_name) ?></td><td><?= htmlspecialchars($score->criterion_name) ?></td><td><?= number_format((float)$score->score_value, 2) ?></td><td><?= number_format((float)$score->weight_percent, 2) ?>%</td><td><?= $score->weighted_contribution === null ? '-' : number_format((float)$score->weighted_contribution, 2) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</section>

<div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
    <?php if (!empty($category->is_released)): ?>
        <form method="POST" action="<?= app_url('/tabulator/hold/' . (int)$category->id) ?>" onsubmit="return confirm('Hold these official results?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-light" type="submit"><i class="fa-solid fa-pause"></i> Hold Results</button>
        </form>
    <?php elseif ($canRelease): ?>
        <form method="POST" action="<?= app_url('/tabulator/release/' . (int)$category->id) ?>" onsubmit="return confirm('Release these results as official?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-success" type="submit"><i class="fa-solid fa-bullhorn"></i> Release Official Results</button>
        </form>
    <?php else: ?>
        <span class="tag draft"><i class="fa-solid fa-lock"></i> Release blocked until warnings are resolved</span>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
