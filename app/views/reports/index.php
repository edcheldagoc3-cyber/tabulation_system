<?php
$pageTitle = 'Reports';
$pageSubtitle = 'Generate and export official results reports.';
$activePage = 'reports';
ob_start();
?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    <!-- Event Reports -->
    <div class="section">
        <div class="section-head">
            <h3><i class="fa-solid fa-calendar-days" style="color:var(--maroon)"></i> Event Reports</h3>
        </div>
        <div class="section-body">
            <?php if (empty($events)): ?>
                <div class="empty">No events with released results.</div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--line);">
                        <div>
                            <strong><?= htmlspecialchars($event->name) ?></strong>
                            <span class="muted" style="font-size:12px;margin-left:8px;"><?= date('M d, Y', strtotime($event->event_date)) ?></span>
                            <br><span class="muted" style="font-size:12px;"><?= (int)$event->released_count ?> categories released</span>
                        </div>
                        <a class="btn btn-light small" href="<?= app_url('/reports/event/' . (int)$event->id) ?>">
                            <i class="fa-solid fa-file-lines"></i> View
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Category Reports -->
    <div class="section">
        <div class="section-head">
            <h3><i class="fa-solid fa-tags" style="color:var(--gold)"></i> Category Reports</h3>
        </div>
        <div class="section-body">
            <?php if (empty($categories)): ?>
                <div class="empty">No released categories.</div>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--line);">
                        <div>
                            <strong><?= htmlspecialchars($cat->name) ?></strong>
                            <span class="muted" style="font-size:12px;margin-left:8px;"><?= htmlspecialchars($cat->event_name) ?></span>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <a class="btn btn-light small" href="<?= app_url('/reports/category/' . (int)$cat->id) ?>">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a class="btn btn-light small" href="<?= app_url('/reports/export-csv/' . (int)$cat->id) ?>">
                                <i class="fa-solid fa-file-csv"></i>
                            </a>
                            <a class="btn btn-light small" href="<?= app_url('/reports/export-pdf/' . (int)$cat->id) ?>">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                            <a class="btn btn-light small" href="<?= app_url('/reports/export-breakdown-pdf/' . (int)$cat->id) ?>">
                                <i class="fa-solid fa-table-list"></i>
                            </a>
                            <a class="btn btn-light small" href="<?= app_url('/reports/printable/' . (int)$cat->id) ?>" target="_blank">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../admin/layout.php'; ?>
