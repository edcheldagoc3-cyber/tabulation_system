<?php
$isEdit = $isEdit ?? !empty($user);
$action = $action ?? ($isEdit ? '/admin/users/edit/' . (int)$user->id : '/admin/users/create');
$errors = $errors ?? [];
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$fieldError = static function ($key) use ($errors) {
    return !empty($errors[$key]) ? '<div class="field-error">' . htmlspecialchars($errors[$key]) . '</div>' : '';
};
$role = $user->role ?? 'judge';
?>
<form method="POST" action="<?= htmlspecialchars(app_url($action)) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="field">
        <label>Full Name</label>
        <input class="input" type="text" name="full_name" required value="<?= htmlspecialchars($user->full_name ?? '') ?>" placeholder="John Doe">
        <?= $fieldError('full_name') ?>
    </div>
    <div class="field">
        <label>Email</label>
        <input class="input" type="email" name="email" required value="<?= htmlspecialchars($user->email ?? '') ?>" placeholder="user@ustp.edu.ph">
        <?= $fieldError('email') ?>
    </div>
    <div class="field">
        <label><?= $isEdit ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
        <input class="input" type="password" name="password" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
        <?= $fieldError('password') ?>
    </div>
    <div class="field">
        <label>Role</label>
        <select name="role" class="input" required>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="tabulator" <?= $role === 'tabulator' ? 'selected' : '' ?>>Tabulator</option>
            <option value="judge" <?= $role === 'judge' ? 'selected' : '' ?>>Judge</option>
            <option value="viewer" <?= $role === 'viewer' ? 'selected' : '' ?>>Viewer</option>
        </select>
    </div>
    <div class="modal-actions">
        <button class="btn btn-light" type="button" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    </div>
</form>
