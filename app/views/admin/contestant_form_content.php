<?php
$isEdit = $isEdit ?? !empty($contestant);
$action = $action ?? ($isEdit ? '/admin/contestants/edit/' . (int)$contestant->id : '/admin/contestants/create');
$errors = $errors ?? [];
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$fieldError = static function ($key) use ($errors) {
    return !empty($errors[$key]) ? '<div class="field-error">' . htmlspecialchars($errors[$key]) . '</div>' : '';
};
?>
<form method="POST" action="<?= htmlspecialchars(app_url($action)) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($returnEventId)): ?><input type="hidden" name="return_event_id" value="<?= (int)$returnEventId ?>"><?php endif; ?>
    <div class="field">
        <label>Category</label>
        <select name="category_id" class="input" required>
            <option value="">Select category</option>
            <?php foreach (($categories ?? []) as $category): ?>
                <option value="<?= (int)$category->id ?>" <?= (int)($contestant->category_id ?? 0) === (int)$category->id ? 'selected' : '' ?>><?= htmlspecialchars(($category->event_name ?? '') . ' - ' . $category->name) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $fieldError('category_id') ?>
    </div>
    <div class="form-row">
        <div class="field">
            <label>Contestant Name</label>
            <input class="input" type="text" name="name" required value="<?= htmlspecialchars($contestant->name ?? '') ?>" placeholder="Contestant or team name">
            <?= $fieldError('name') ?>
        </div>
        <div class="field">
            <label>Type</label>
            <?php $type = $contestant->type ?? 'solo'; ?>
            <select name="type" class="input">
                <option value="solo" <?= $type === 'solo' ? 'selected' : '' ?>>Solo</option>
                <option value="team" <?= $type === 'team' ? 'selected' : '' ?>>Team</option>
            </select>
        </div>
    </div>
    <div class="modal-actions">
        <button class="btn btn-light" type="button" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    </div>
</form>
