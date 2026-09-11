<?php
$errors = $errors ?? [];
$assignment = $assignment ?? null;
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$fieldError = static function ($key) use ($errors) {
    return !empty($errors[$key]) ? '<div class="field-error">' . htmlspecialchars($errors[$key]) . '</div>' : '';
};
?>
<form method="POST" action="<?= htmlspecialchars(app_url('/admin/judges/assign')) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($returnEventId)): ?><input type="hidden" name="return_event_id" value="<?= (int)$returnEventId ?>"><?php endif; ?>
    <div class="field">
        <label>Judge</label>
        <select name="user_id" class="input" required>
            <option value="">Select judge</option>
            <?php foreach (($judges ?? []) as $judge): ?>
                <option value="<?= (int)$judge->id ?>" <?= (int)($assignment->user_id ?? 0) === (int)$judge->id ? 'selected' : '' ?>><?= htmlspecialchars($judge->full_name . ' (' . $judge->email . ')') ?></option>
            <?php endforeach; ?>
        </select>
        <?= $fieldError('user_id') ?>
    </div>
    <div class="field">
        <label>Category</label>
        <select name="category_id" class="input" required>
            <option value="">Select category</option>
            <?php foreach (($categories ?? []) as $category): ?>
                <option value="<?= (int)$category->id ?>" <?= (int)($assignment->category_id ?? 0) === (int)$category->id ? 'selected' : '' ?>><?= htmlspecialchars(($category->event_name ?? '') . ' - ' . $category->name) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $fieldError('category_id') ?>
    </div>
    <div class="modal-actions">
        <button class="btn btn-light" type="button" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-check"></i> Assign Judge</button>
    </div>
</form>
