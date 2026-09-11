<?php
$pageTitle = 'User Management';
$pageSubtitle = 'Create, edit, or delete system users.';
$activePage = 'users';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
?>
<div class="section">
    <div class="section-head">
        <div><h3><i class="fa-solid fa-users"></i> All Users</h3></div>
        <a class="btn btn-primary" href="<?= app_url('/admin/users/create') ?>" onclick="loadModal('<?= app_url('/admin/users/create') ?>', 'Create User', 'Add a system account'); return false;"><i class="fa-solid fa-plus"></i> Create User</a>
    </div>
    <div class="section-body">
        <?php if (empty($users)): ?>
            <div class="empty">No users registered yet.</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u->full_name) ?></td>
                        <td><?= htmlspecialchars($u->email) ?></td>
                        <td><span class="tag"><?= ucfirst($u->role) ?></span></td>
                        <td>
                            <div class="split-actions">
                                <a class="btn btn-light small" href="<?= app_url('/admin/users/edit/' . (int)$u->id) ?>" onclick="loadModal('<?= app_url('/admin/users/edit/' . (int)$u->id) ?>', 'Edit User', 'Update account details'); return false;"><i class="fa-solid fa-pen"></i></a>
                                <?php if ((int)$u->id !== (int)\App\Core\Auth::user()->id): ?>
                                <button class="btn btn-danger small" type="button" onclick="confirmDelete('<?= app_url('/admin/users/delete/' . (int)$u->id) ?>', 'Delete User', 'Delete this user account?');"><i class="fa-solid fa-trash"></i></button>
                                <?php endif; ?>
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
