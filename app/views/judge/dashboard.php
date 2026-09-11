<?php
$pageTitle = 'Assigned Categories';
$pageSubtitle = 'Your assigned categories – open a category to start scoring.';
$activePage = 'dashboard';
$pageSubtitle = 'Choose a category to begin scoring.';
ob_start();
?>
<?php if (empty($assignments)): ?>
    <div class="empty">You do not have any category assignments yet.</div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-top:0;">
        <?php foreach ($assignments as $assignment): ?>
            <div class="card judge-assignment-card" style="display:flex;flex-direction:column;gap:10px;">
                <div class="pill" style="background:#f6ead0;color:#7a5600;width:fit-content;">
                    <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($assignment->event_name) ?>
                </div>
                <h3 class="section-title" style="margin:0;font-size:18px;"><?= htmlspecialchars($assignment->category_name) ?></h3>
                <div class="muted" style="font-size:13px;">Event date: <?= htmlspecialchars(date('M d, Y', strtotime($assignment->event_date))) ?></div>
                <div class="muted" style="font-size:13px;">Computation: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $assignment->computation_type))) ?></div>
                <div style="margin-top:4px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                        <span class="muted">My scoring progress</span>
                        <strong><?= (int)($assignment->contestants_submitted ?? 0) ?>/<?= (int)($assignment->contestants_total ?? 0) ?> contestants</strong>
                    </div>
                    <div class="progress"><span style="width: <?= (int)($assignment->progress_percent ?? 0) ?>%;"></span></div>
                    <div class="muted" style="font-size:12px;margin-top:5px;"><?= (int)($assignment->progress_percent ?? 0) ?>% submitted and locked</div>
                </div>
                <div style="margin-top:4px;">
                    <a class="btn btn-primary" href="<?= app_url('/judge/score/' . (int)$assignment->category_id) ?>">
                        <i class="fa-solid fa-pen-to-square"></i> Open Scoring
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
