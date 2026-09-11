<?php
$pageTitle = 'Event Report: ' . htmlspecialchars($event->name);
$pageSubtitle = 'Summary of all categories for ' . htmlspecialchars($event->name);
$activePage = 'reports';
ob_start();
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($event->name) ?></h2>
        <p class="muted" style="margin:4px 0 0;"><?= date('F d, Y', strtotime($event->event_date)) ?></p>
    </div>
    <a class="btn btn-light small" href="<?= app_url('/reports') ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="section">
    <div class="section-body">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Computation</th>
                        <th>Participants</th>
                        <th>Winner</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row->category_name) ?></strong></td>
                            <td><span class="tag draft"><?= htmlspecialchars($row->computation_type) ?></span></td>
                            <td><?= (int)$row->results_count ?></td>
                            <td><?= htmlspecialchars($row->winner ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../admin/layout.php'; ?>