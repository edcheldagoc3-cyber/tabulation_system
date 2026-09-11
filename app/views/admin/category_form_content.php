<?php
$isEdit = $isEdit ?? !empty($category);
$action = $action ?? ($isEdit ? '/admin/categories/edit/' . (int)$category->id : '/admin/categories/create');
$errors = $errors ?? [];
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$fieldError = static function ($key) use ($errors) {
    return !empty($errors[$key]) ? '<div class="field-error">' . htmlspecialchars($errors[$key]) . '</div>' : '';
};
$selectedEvent = (int)($category->event_id ?? 0);
$selectedType = $category->computation_type ?? 'raw_average';
$selectedRule = $category->tiebreak_rule ?? 'manual';
$selectedCriterion = (int)($category->tiebreak_criterion_id ?? 0);
?>
<form method="POST" action="<?= htmlspecialchars(app_url($action)) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($returnEventId)): ?><input type="hidden" name="return_event_id" value="<?= (int)$returnEventId ?>"><?php endif; ?>
    <?php if (!$isEdit): ?>
        <?php $rubricTemplates = \App\Models\RubricTemplateModel::getAll(); ?>
        <div class="field">
            <label>Load from Template <span class="muted">(optional)</span></label>
            <select name="rubric_template_id" class="input">
                <option value="">Start with a blank rubric</option>
                <?php foreach ($rubricTemplates as $template): ?>
                    <option value="<?= (int)$template->id ?>"><?= htmlspecialchars($template->name) ?> (<?= (int)$template->criteria_total ?> criteria)</option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="field">
        <label>Event</label>
        <select name="event_id" class="input" required>
            <option value="">Select an event</option>
            <?php foreach (($events ?? []) as $event): ?>
                <option value="<?= (int)$event->id ?>" <?= $selectedEvent === (int)$event->id ? 'selected' : '' ?>><?= htmlspecialchars($event->name) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $fieldError('event_id') ?>
    </div>
    <div class="field">
        <label>Category Name</label>
        <input class="input" type="text" name="name" required value="<?= htmlspecialchars($category->name ?? '') ?>" placeholder="e.g. Evening Gown, Best in Talent">
        <?= $fieldError('name') ?>
    </div>
    <div class="form-row">
        <div class="field">
            <label>Computation Type</label>
            <select name="computation_type" class="input" required>
                <option value="raw_average" <?= $selectedType === 'raw_average' ? 'selected' : '' ?>>Raw Average</option>
                <option value="weighted_criteria" <?= $selectedType === 'weighted_criteria' ? 'selected' : '' ?>>Weighted Criteria</option>
                <option value="rank_based" <?= $selectedType === 'rank_based' ? 'selected' : '' ?>>Rank Based</option>
            </select>
        </div>
        <div class="field">
            <label>Tiebreak Rule</label>
            <select name="tiebreak_rule" class="input" onchange="toggleTiebreakField(this)">
                <option value="manual" <?= $selectedRule === 'manual' ? 'selected' : '' ?>>Manual</option>
                <option value="highest_criterion" <?= $selectedRule === 'highest_criterion' ? 'selected' : '' ?>>Highest Criterion</option>
                <option value="sum_criteria" <?= $selectedRule === 'sum_criteria' ? 'selected' : '' ?>>Sum Criteria</option>
                <option value="more_top_ranks" <?= $selectedRule === 'more_top_ranks' ? 'selected' : '' ?>>More Top Ranks</option>
                <option value="lowest_variance" <?= $selectedRule === 'lowest_variance' ? 'selected' : '' ?>>Lowest Variance</option>
            </select>
        </div>
    </div>
    <div class="field tiebreak-criterion-wrap" style="<?= $selectedRule === 'highest_criterion' ? '' : 'display:none;' ?>">
        <label>Tiebreak Criterion</label>
        <select name="tiebreak_criterion_id" class="input">
            <option value="">None / Select a criterion</option>
            <?php foreach (($criteria ?? []) as $criterion): ?>
                <option value="<?= (int)$criterion->id ?>" <?= $selectedCriterion === (int)$criterion->id ? 'selected' : '' ?>><?= htmlspecialchars(($criterion->event_name ?? '') . ' > ' . ($criterion->category_name ?? '') . ' > ' . $criterion->name) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $fieldError('tiebreak_criterion_id') ?>
    </div>
    <div class="modal-actions">
        <button class="btn btn-light" type="button" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Category</button>
    </div>
</form>
