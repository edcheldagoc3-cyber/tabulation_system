<?php
$isEdit = !empty($event);
$title = $isEdit ? 'Edit Event' : 'Create Event';
$action = $action ?? ($isEdit ? '/admin/events/edit/' . (int)$event->id : '/admin/events/create');
$pageTitle = $title;
$pageSubtitle = 'Manage event metadata used by categories and leaderboard grouping.';
$activePage = 'events';
$bodyClass = 'admin-modal-page';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$eventName = $isEdit ? ($event->name ?? '') : '';
$eventDate = $isEdit ? ($event->event_date ?? '') : '';
$eventDescription = $isEdit ? ($event->description ?? '') : '';
$eventStatus = $isEdit ? ($event->status ?? 'draft') : 'draft';
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
                <label>Event Name</label>
                <input class="input" type="text" name="name" required value="<?= htmlspecialchars($eventName) ?>" placeholder="USTP Jasaan Foundation Day">
            </div>
            <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="field">
                    <label>Event Date</label>
                    <input class="input" type="date" name="event_date" required value="<?= htmlspecialchars($eventDate) ?>">
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status" class="input">
                        <option value="draft" <?= ($eventStatus === 'draft') ? 'selected' : '' ?>>Draft</option>
                        <option value="live" <?= ($eventStatus === 'live') ? 'selected' : '' ?>>Live</option>
                        <option value="archived" <?= ($eventStatus === 'archived') ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label>Description</label>
                <textarea class="input" name="description" placeholder="Optional notes about the event"><?= htmlspecialchars($eventDescription) ?></textarea>
            </div>
            <div class="actions" style="display:flex;gap:10px;margin-top:20px;">
                <a class="btn btn-light js-modal-close" href="<?= app_url('/admin/events') ?>">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
