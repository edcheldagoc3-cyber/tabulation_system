<?php
$isEdit = !empty($category);
$title = $isEdit ? 'Edit Category' : 'Create Category';
$pageTitle = $title;
$pageSubtitle = 'Set the scoring method and event linkage for this category.';
$activePage = 'categories';
$bodyClass = 'admin-modal-page';
$action = $action ?? ($isEdit ? '/admin/categories/edit/' . (int)$category->id : '/admin/categories/create');
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$categoryEventId   = $isEdit ? (int)($category->event_id ?? 0) : 0;
$categoryName      = $isEdit ? ($category->name ?? '') : '';
$categoryType      = $isEdit ? ($category->computation_type ?? 'raw_average') : 'raw_average';
$categoryTiebreak  = $isEdit ? ($category->tiebreak_rule ?? 'manual') : 'manual';
$categoryCriterion = $isEdit ? (int)($category->tiebreak_criterion_id ?? 0) : 0;

// $criteria is all criteria (passed from controller); filter to selected category for display
$allCriteria = $criteria ?? [];

ob_start();
?>
<style>
    .form-card {
        max-width: 760px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #e8e2dd;
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .form-card-head {
        padding: 22px 28px 20px;
        border-bottom: 1px solid #ece6df;
        background: linear-gradient(135deg, rgba(109,26,43,.07), rgba(212,167,44,.10));
    }
    .form-card-head h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        color: #0b1a33;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-card-head p {
        margin: 6px 0 0;
        color: #6b7280;
        font-size: 14px;
    }
    .form-card-body { padding: 28px; }

    .tiebreak-criterion-field { display: none; }
    .tiebreak-criterion-field.visible { display: block; }

    .criteria-note {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 4px;
    }
</style>

<div class="form-card">
    <div class="form-card-head">
        <h2><i class="fas fa-tags" style="color:#d4a72c"></i> <?= htmlspecialchars($title) ?></h2>
        <p>Configure the scoring method, tiebreak rules, and event linkage for this category.</p>
    </div>
    <div class="form-card-body">
        <?php if (!empty($error)): ?>
            <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:12px;margin-bottom:20px;font-weight:600;">
                <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars(app_url($action)) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <!-- Event -->
            <div class="field">
                <label>Event</label>
                <select name="event_id" required id="select-event">
                    <option value="">Select an event</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?= (int)$event->id ?>" <?= ($categoryEventId === (int)$event->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Category Name -->
            <div class="field">
                <label>Category Name</label>
                <input class="input" type="text" name="name" required
                       value="<?= htmlspecialchars($categoryName) ?>"
                       placeholder="e.g. Evening Gown, Best in Talent">
            </div>

            <div class="form-row">
                <!-- Computation Type -->
                <div class="field">
                    <label>Computation Type</label>
                    <select name="computation_type" required>
                        <option value="raw_average"      <?= $categoryType === 'raw_average'      ? 'selected' : '' ?>>Raw Average</option>
                        <option value="weighted_criteria" <?= $categoryType === 'weighted_criteria' ? 'selected' : '' ?>>Weighted Criteria</option>
                        <option value="rank_based"       <?= $categoryType === 'rank_based'       ? 'selected' : '' ?>>Rank Based</option>
                    </select>
                </div>

                <!-- Tiebreak Rule -->
                <div class="field">
                    <label>Tiebreak Rule</label>
                    <select name="tiebreak_rule" id="select-tiebreak">
                        <option value="manual"            <?= $categoryTiebreak === 'manual'            ? 'selected' : '' ?>>Manual</option>
                        <option value="highest_criterion" <?= $categoryTiebreak === 'highest_criterion' ? 'selected' : '' ?>>Highest Criterion</option>
                        <option value="sum_criteria"      <?= $categoryTiebreak === 'sum_criteria'      ? 'selected' : '' ?>>Sum Criteria</option>
                        <option value="more_top_ranks"    <?= $categoryTiebreak === 'more_top_ranks'    ? 'selected' : '' ?>>More Top Ranks</option>
                        <option value="lowest_variance"   <?= $categoryTiebreak === 'lowest_variance'   ? 'selected' : '' ?>>Lowest Variance</option>
                    </select>
                </div>
            </div>

            <!-- Tiebreak Criterion (shown only when tiebreak = highest_criterion) -->
            <div class="field tiebreak-criterion-field <?= $categoryTiebreak === 'highest_criterion' ? 'visible' : '' ?>"
                 id="tiebreak-criterion-wrap">
                <label>Tiebreak Criterion</label>
                <select name="tiebreak_criterion_id" id="select-tiebreak-criterion">
                    <option value="">— None / Select a criterion —</option>
                    <?php foreach ($allCriteria as $cr): ?>
                        <option value="<?= (int)$cr->id ?>"
                            <?= ($categoryCriterion === (int)$cr->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($cr->event_name ?? '') . ' › ' . ($cr->category_name ?? '') . ' › ' . $cr->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($allCriteria)): ?>
                    <p class="criteria-note"><i class="fas fa-info-circle"></i> No criteria defined yet. Create criteria first, then assign a tiebreak criterion.</p>
                <?php endif; ?>
            </div>

            <div class="split-actions" style="justify-content:flex-end; margin-top:24px;">
                <a class="btn btn-light js-modal-close" href="<?= app_url('/admin/categories') ?>">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-floppy-disk"></i> Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const tiebreakSelect = document.getElementById('select-tiebreak');
    const criterionWrap  = document.getElementById('tiebreak-criterion-wrap');

    function toggleCriterion() {
        if (tiebreakSelect.value === 'highest_criterion') {
            criterionWrap.classList.add('visible');
        } else {
            criterionWrap.classList.remove('visible');
            // Clear selection when hidden so no stale ID is submitted
            document.getElementById('select-tiebreak-criterion').value = '';
        }
    }

    tiebreakSelect.addEventListener('change', toggleCriterion);
    // Run on load in case of validation re-display
    toggleCriterion();
})();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
