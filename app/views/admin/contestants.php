<?php
$pageTitle = 'Manage Contestants';
$pageSubtitle = 'Register solo or team contestants.';
$activePage = 'contestants';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
?>
<div class="section">
    <div class="section-head">
        <div><h3><i class="fa-solid fa-user-group"></i> All Contestants</h3></div>
        <a class="btn btn-primary" href="<?= app_url('/admin/contestants/create') ?>" onclick="loadModal('<?= app_url('/admin/contestants/create') ?>', 'Register Contestant', 'Add a participant or team'); return false;"><i class="fa-solid fa-plus"></i> Register Contestant</a>
    </div>
    <div class="section-body">
        <?php if (empty($contestants)): ?>
            <div class="empty">No contestants registered yet.</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Contestant</th><th>Category</th><th>Type</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($contestants as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c->name) ?></strong></td>
                        <td><?= htmlspecialchars($c->category_name ?? '') ?></td>
                        <td><span class="tag"><?= ucfirst($c->type) ?></span></td>
                        <td>
                            <div class="split-actions">
                                <a class="btn btn-light small" href="<?= app_url('/admin/contestants/edit/' . (int)$c->id) ?>" onclick="loadModal('<?= app_url('/admin/contestants/edit/' . (int)$c->id) ?>', 'Edit Contestant', 'Update participant details'); return false;"><i class="fa-solid fa-pen"></i></a>
                                <button class="btn btn-danger small" type="button" onclick="confirmDelete('<?= app_url('/admin/contestants/delete/' . (int)$c->id) ?>', 'Delete Contestant', 'Delete this contestant?');"><i class="fa-solid fa-trash"></i></button>
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
