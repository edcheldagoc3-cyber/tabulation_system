<?php
$pageTitle = 'Scoring Progress';
$pageSubtitle = 'Track completion per category and release the public results when ready.';
$activePage = 'dashboard';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
?>
<!-- Stats -->
<div class="stats-grid">
    <div class="stat">
        <div class="label">Categories</div>
        <div class="value"><?= (int)($summary['categories_total'] ?? 0) ?></div>
        <div class="meta">Total categories</div>
    </div>
    <div class="stat">
        <div class="label">Complete</div>
        <div class="value"><?= (int)($summary['complete'] ?? 0) ?></div>
        <div class="meta">Ready to release</div>
    </div>
    <div class="stat">
        <div class="label">Released</div>
        <div class="value"><?= (int)($summary['released'] ?? 0) ?></div>
        <div class="meta">Published to public</div>
    </div>
</div>

<!-- Category Progress Table -->
<section class="section">
    <div class="section-head">
        <div>
            <h3><i class="fa-solid fa-chart-column" style="color:#6d1a2b;"></i> Category Progress</h3>
            <p>Once all judges have submitted, use "Release Results" to publish the leaderboard.</p>
        </div>
    </div>
    <div class="section-body">
        <?php if (empty($categories)): ?>
            <div class="empty">No categories available for tabulation yet.</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Event</th>
                        <th>Judges</th>
                        <th>Contestants</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($category->name) ?></strong><br>
                            <span class="muted" style="font-size:12px;"><?= htmlspecialchars($category->computation_type) ?></span>
                        </td>
                        <td><?= htmlspecialchars($category->event_name) ?></td>
                        <td><?= (int)($category->judges_submitted ?? 0) ?>/<?= (int)($category->judges_total ?? 0) ?></td>
                        <td><?= (int)($category->contestants_total ?? 0) ?></td>
                        <td style="min-width:180px;">
                            <div class="progress"><span style="width: <?= (int)($category->progress_percent ?? 0) ?>%;"></span></div>
                            <div class="muted" style="font-size:12px;margin-top:6px;"><?= (int)($category->progress_percent ?? 0) ?>% complete</div>
                        </td>
                        <td>
                            <a class="btn btn-light" href="<?= app_url('/tabulator/review/' . (int)$category->id) ?>" style="padding:6px 10px;font-size:12px;border-radius:10px;">
                                <i class="fa-solid fa-magnifying-glass-chart"></i> Review
                            </a>
                            <?php if (!empty($category->is_released)): ?>
                                <span class="tag good"><i class="fa-solid fa-circle-check"></i> Released</span>
                            <?php elseif ((int)($category->progress_percent ?? 0) >= 100): ?>
                                <span class="tag warn"><i class="fa-solid fa-clock"></i> Ready to Release</span>
                            <?php else: ?>
                                <span class="tag draft"><i class="fa-solid fa-hourglass-half"></i> In Progress</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($category->is_released)): ?>
                                <form class="inline" method="POST" action="<?= app_url('/tabulator/hold/' . (int)$category->id) ?>" onsubmit="return confirm('Hold these results from official release?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-light" type="submit" style="padding:6px 14px;font-size:13px;border-radius:10px;">
                                        <i class="fa-solid fa-pause"></i> Hold
                                    </button>
                                </form>
                            <?php else: ?>
                                <?php if ((int)($category->progress_percent ?? 0) >= 100): ?>
                                    <form class="inline" method="POST" action="<?= app_url('/tabulator/release/' . (int)$category->id) ?>" onsubmit="return confirm('Release results for this category?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn btn-success" type="submit" style="padding:6px 14px;font-size:13px;border-radius:10px;">
                                            <i class="fa-solid fa-bullhorn"></i> Release
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="tag draft">Waiting for judges</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
