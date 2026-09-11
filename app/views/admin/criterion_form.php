<?php
$isEdit = !empty($criterion);
$title = $isEdit ? 'Edit Criterion' : 'Create Criterion';
$action = $action ?? ($isEdit ? '/admin/criteria/edit/' . (int)$criterion->id : '/admin/criteria/create');
$pageTitle = $title;
$pageSubtitle = 'Configure the scoring criterion used by judges in a category.';
$activePage = 'criteria';
$bodyClass = 'admin-modal-page';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$criterionCategoryId = $isEdit ? (int)($criterion->category_id ?? 0) : 0;
$criterionName = $isEdit ? ($criterion->name ?? '') : '';
$criterionWeight = $isEdit ? ($criterion->weight_percent ?? 0) : 0;
$criterionMin = $isEdit ? ($criterion->min_score ?? 0) : 0;
$criterionMax = $isEdit ? ($criterion->max_score ?? 100) : 100;
?>
<div class="section">
    <div class="section-head">
        <div>
            <h3><i class="fa-solid fa-list-check" style="color:#0f766e"></i> <?= htmlspecialchars($title) ?></h3>
            <p>Set the scoring range and weight used by judges in each category.</p>
        </div>
    </div>
    <div class="section-body">
        <?php if (!empty($error)): ?>
            <div class="error" style="background:#fee2e2;color:#991b1b;padding:12px 14px;border-radius:14px;margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= htmlspecialchars(app_url($action)) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="field">
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category->id ?>" <?= ($criterionCategoryId === (int)$category->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($category->event_name ?? '') . ' - ' . $category->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Criterion Name</label>
                <input class="input" type="text" name="name" required value="<?= htmlspecialchars($criterionName) ?>" placeholder="Example: Talent and Stage Presence">
            </div>
            <div class="form-row">
                <div class="field">
                    <label>Weight Percent</label>
                    <input class="input" type="number" name="weight_percent" min="0" max="100" step="0.01" required value="<?= htmlspecialchars((string)$criterionWeight) ?>">
                    <p class="criteria-note" style="font-size:12px;color:#9ca3af;margin-top:4px;">Total weight per category must not exceed 100%.</p>
                </div>
                <div class="field">
                    <label>Min Score</label>
                    <input class="input" type="number" name="min_score" step="0.01" value="<?= htmlspecialchars((string)$criterionMin) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label>Max Score</label>
                    <input class="input" type="number" name="max_score" step="0.01" value="<?= htmlspecialchars((string)$criterionMax) ?>">
                </div>
            </div>
            <div class="split-actions" style="justify-content:flex-end; margin-top:24px;">
                <a class="btn btn-light js-modal-close" href="<?= app_url('/admin/criteria') ?>">
                    <i class="fa-solid fa-arrow-left"></i> Cancel
                </a>
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
