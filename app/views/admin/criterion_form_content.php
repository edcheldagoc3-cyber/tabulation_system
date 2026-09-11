<?php
$isEdit = $isEdit ?? !empty($criterion);
$action = $action ?? ($isEdit ? '/admin/criteria/edit/' . (int)$criterion->id : '/admin/criteria/create');
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
            <option value="">Select a category</option>
            <?php foreach (($categories ?? []) as $category): ?>
                <option value="<?= (int)$category->id ?>" <?= (int)($criterion->category_id ?? 0) === (int)$category->id ? 'selected' : '' ?>><?= htmlspecialchars(($category->event_name ?? '') . ' - ' . $category->name) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $fieldError('category_id') ?>
    </div>
    <div class="field">
        <label>Criterion Name</label>
        <input class="input" type="text" name="name" required value="<?= htmlspecialchars($criterion->name ?? '') ?>" placeholder="Example: Talent and Stage Presence">
        <?= $fieldError('name') ?>
    </div>
    <div class="form-row">
        <div class="field">
            <label>Weight Percent</label>
            <input class="input" type="number" name="weight_percent" min="0" max="100" step="0.01" required value="<?= htmlspecialchars((string)($criterion->weight_percent ?? 0)) ?>">
            <?= $fieldError('weight_percent') ?>
        </div>
        <div class="field">
            <label>Min Score</label>
            <input class="input" type="number" name="min_score" step="0.01" value="<?= htmlspecialchars((string)($criterion->min_score ?? 0)) ?>">
            <?= $fieldError('min_score') ?>
        </div>
    </div>
    <div class="field">
        <label>Max Score</label>
        <input class="input" type="number" name="max_score" step="0.01" value="<?= htmlspecialchars((string)($criterion->max_score ?? 100)) ?>">
        <?= $fieldError('max_score') ?>
    </div>
    <div class="modal-actions">
        <button class="btn btn-light" type="button" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    </div>
</form>
