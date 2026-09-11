<?php
$pageTitle = 'Category Report: ' . htmlspecialchars($category->name);
$pageSubtitle = 'Full rankings for ' . htmlspecialchars($category->name);
$activePage = 'reports';
ob_start();
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($category->name) ?></h2>
        <p class="muted" style="margin:4px 0 0;"><?= count($results) ?> contestants ranked</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn btn-light small" href="<?= app_url('/reports') ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <a class="btn btn-light small" href="<?= app_url('/reports/export-csv/' . (int)$category->id) ?>"><i class="fa-solid fa-file-csv"></i> CSV</a>
        <a class="btn btn-light small" href="<?= app_url('/reports/export-pdf/' . (int)$category->id) ?>"><i class="fa-solid fa-file-pdf"></i> PDF</a>
        <a class="btn btn-light small" href="<?= app_url('/reports/export-breakdown-pdf/' . (int)$category->id) ?>"><i class="fa-solid fa-table-list"></i> Breakdown PDF</a>
        <a class="btn btn-primary small" href="<?= app_url('/reports/printable/' . (int)$category->id) ?>" target="_blank"><i class="fa-solid fa-print"></i> Print</a>
    </div>
</div>

<div class="section">
    <div class="section-body">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Contestant</th>
                        <th>Type</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td>
                                <span style="font-weight:700;font-size:18px;">
                                    <?php if ((int)$row->final_rank === 1): ?>🥇
                                    <?php elseif ((int)$row->final_rank === 2): ?>🥈
                                    <?php elseif ((int)$row->final_rank === 3): ?>🥉
                                    <?php else: ?>#<?= (int)$row->final_rank ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td><strong><?= htmlspecialchars($row->contestant_name) ?></strong></td>
                            <td><span class="pill"><?= htmlspecialchars(ucfirst($row->contestant_type)) ?></span></td>
                            <td style="font-weight:700;font-size:18px;color:var(--maroon);"><?= number_format((float)$row->final_score, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../admin/layout.php'; ?>
