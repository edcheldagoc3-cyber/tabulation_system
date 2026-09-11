<?php
$pageTitle = 'Manage Categories';
$pageSubtitle = 'Create, edit, or delete categories.';
$activePage = 'categories';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
?>
<div class="section">
    <div class="section-head">
        <div><h3><i class="fa-solid fa-tags"></i> All Categories</h3></div>
        <a class="btn btn-primary" href="<?= app_url('/admin/categories/create') ?>" onclick="loadModal('<?= app_url('/admin/categories/create') ?>', 'Create Category', 'Configure a scoring category'); return false;"><i class="fa-solid fa-plus"></i> Create Category</a>
    </div>
    <div class="section-body">
        <?php if (empty($categories)): ?>
            <div class="empty">No categories created yet.</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Category</th><th>Event</th><th>Computation</th><th>Progress</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cat->name) ?></strong></td>
                        <td><?= htmlspecialchars($cat->event_name ?? '') ?></td>
                        <td><span class="tag"><?= htmlspecialchars($cat->computation_type) ?></span></td>
                        <td><?= (int)($cat->progress_percent ?? 0) ?>%</td>
                        <td>
                            <div class="split-actions">
                                <a class="btn btn-light small" href="<?= app_url('/admin/categories/edit/' . (int)$cat->id) ?>" onclick="loadModal('<?= app_url('/admin/categories/edit/' . (int)$cat->id) ?>', 'Edit Category', 'Update category settings'); return false;"><i class="fa-solid fa-pen"></i></a>
                                <button class="btn btn-danger small" type="button" onclick="confirmDelete('<?= app_url('/admin/categories/delete/' . (int)$cat->id) ?>', 'Delete Category', 'Delete this category and its related records?');"><i class="fa-solid fa-trash"></i></button>
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
