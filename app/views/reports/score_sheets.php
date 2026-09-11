<?php
$pageTitle = 'Score Sheet';
$pageSubtitle = 'Detailed judge scores for ' . htmlspecialchars($scores[0]->contestant_name ?? 'Contestant');
$activePage = 'reports';
ob_start();
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($scores[0]->contestant_name ?? 'Contestant') ?></h2>
        <p class="muted" style="margin:4px 0 0;"><?= htmlspecialchars($scores[0]->category_name ?? '') ?> · <?= htmlspecialchars($scores[0]->event_name ?? '') ?></p>
    </div>
    <a class="btn btn-light small" href="<?= app_url('/reports') ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="section">
    <div class="section-body">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Judge</th>
                        <th>Criterion</th>
                        <th>Weight</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scores as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row->judge_name) ?></strong></td>
                            <td><?= htmlspecialchars($row->criterion_name) ?></td>
                            <td><?= number_format((float)$row->weight_percent, 2) ?>%</td>
                            <td style="font-weight:700;"><?= number_format((float)$row->score_value, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../admin/layout.php'; ?>