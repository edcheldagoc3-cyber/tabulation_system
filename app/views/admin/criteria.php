<?php
$pageTitle = 'Manage Criteria';
$pageSubtitle = 'Define scoring criteria for categories.';
$activePage = 'criteria';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
?>
<div class="section">
    <div class="section-head">
        <div><h3><i class="fa-solid fa-list-check"></i> All Criteria</h3></div>
        <a class="btn btn-primary" href="<?= app_url('/admin/criteria/create') ?>" onclick="loadModal('<?= app_url('/admin/criteria/create') ?>', 'Create Criterion', 'Define a scoring criterion'); return false;"><i class="fa-solid fa-plus"></i> Create Criterion</a>
    </div>
    <div class="section-body">
        <?php if (empty($criteria)): ?>
            <div class="empty">No criteria defined yet.</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Criterion</th><th>Category</th><th>Weight</th><th>Range</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($criteria as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c->name) ?></strong></td>
                        <td><?= htmlspecialchars($c->category_name ?? '') ?></td>
                        <td><?= number_format((float)$c->weight_percent, 2) ?>%</td>
                        <td><?= number_format((float)$c->min_score, 2) ?> – <?= number_format((float)$c->max_score, 2) ?></td>
                        <td>
                            <div class="split-actions">
                                <a class="btn btn-light small" href="<?= app_url('/admin/criteria/edit/' . (int)$c->id) ?>" onclick="loadModal('<?= app_url('/admin/criteria/edit/' . (int)$c->id) ?>', 'Edit Criterion', 'Update scoring details'); return false;"><i class="fa-solid fa-pen"></i></a>
                                <button class="btn btn-danger small" type="button" onclick="confirmDelete('<?= app_url('/admin/criteria/delete/' . (int)$c->id) ?>', 'Delete Criterion', 'Delete this criterion?');"><i class="fa-solid fa-trash"></i></button>
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
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
