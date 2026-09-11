
<?php
$isEdit = !empty($user);
$title = $isEdit ? 'Edit User' : 'Create User';
$action = $action ?? ($isEdit ? '/admin/users/edit/' . (int)$user->id : '/admin/users/create');
$pageTitle = $title;
$pageSubtitle = 'Manage user accounts and roles.';
$activePage = 'users';
$bodyClass = 'admin-modal-page';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$fullName = $isEdit ? ($user->full_name ?? '') : '';
$email = $isEdit ? ($user->email ?? '') : '';
$role = $isEdit ? ($user->role ?? 'judge') : 'judge';
?>
<div class="section">
    <div class="section-head">
        <h3><?= htmlspecialchars($title) ?></h3>
    </div>
    <div class="section-body">
        <?php if (!empty($error)): ?>
            <div class="error" style="background:#fee2e2;color:#991b1b;padding:12px 14px;border-radius:14px;margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= htmlspecialchars(app_url($action)) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="field">
                <label>Full Name</label>
                <input class="input" type="text" name="full_name" required value="<?= htmlspecialchars($fullName) ?>" placeholder="John Doe">
            </div>
            <div class="field">
                <label>Email</label>
                <input class="input" type="email" name="email" required value="<?= htmlspecialchars($email) ?>" placeholder="user@ustp.edu.ph">
            </div>
            <div class="field">
                <label><?= $isEdit ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
                <input class="input" type="password" name="password" <?= $isEdit ? '' : 'required' ?>>
            </div>
            <div class="field">
                <label>Role</label>
                <select name="role" required>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="tabulator" <?= $role === 'tabulator' ? 'selected' : '' ?>>Tabulator</option>
                    <option value="judge" <?= $role === 'judge' ? 'selected' : '' ?>>Judge</option>
                    <option value="viewer" <?= $role === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                </select>
            </div>
            <div class="actions" style="display:flex;gap:10px;margin-top:20px;">
                <a class="btn btn-light js-modal-close" href="<?= app_url('/admin/users') ?>">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
