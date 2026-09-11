<?php
$isEdit = !empty($contestant);
$title = $isEdit ? 'Edit Contestant' : 'Register Contestant';
$action = $action ?? ($isEdit ? '/admin/contestants/edit/' . (int)$contestant->id : '/admin/contestants/create');
$pageTitle = $title;
$pageSubtitle = 'Register a solo participant or a team under a category.';
$activePage = 'contestants';
$bodyClass = 'admin-modal-page';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$contestantCategoryId = $isEdit ? (int)($contestant->category_id ?? 0) : 0;
$contestantName = $isEdit ? ($contestant->name ?? '') : '';
$contestantType = $isEdit ? ($contestant->type ?? 'solo') : 'solo';
?>
<div class="section">
    <div class="section-head">
        <div>
            <h3><i class="fa-solid fa-user-group" style="color:#2563eb"></i> <?= htmlspecialchars($title) ?></h3>
            <p>Keep contestants organized under the category they will compete in.</p>
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
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category->id ?>" <?= ($contestantCategoryId === (int)$category->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($category->event_name ?? '') . ' - ' . $category->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="field">
                    <label>Contestant Name</label>
                    <input class="input" type="text" name="name" required value="<?= htmlspecialchars($contestantName) ?>" placeholder="Contestant or team name">
                </div>
                <div class="field">
                    <label>Type</label>
                    <select name="type">
                        <option value="solo" <?= $contestantType === 'solo' ? 'selected' : '' ?>>Solo</option>
                        <option value="team" <?= $contestantType === 'team' ? 'selected' : '' ?>>Team</option>
                    </select>
                </div>
            </div>
            <div class="split-actions" style="justify-content:flex-end; margin-top:24px;">
                <a class="btn btn-light js-modal-close" href="<?= app_url('/admin/contestants') ?>">
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
