<?php
$isEdit = $isEdit ?? !empty($event);
$title = $isEdit ? 'Edit Event' : 'Create Event';
$action = $action ?? ($isEdit ? '/admin/events/edit/' . (int)$event->id : '/admin/events/create');
$errors = $errors ?? [];
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$fieldError = static function ($key) use ($errors) {
    return !empty($errors[$key]) ? '<div class="field-error">' . htmlspecialchars($errors[$key]) . '</div>' : '';
};
?>
<form method="POST" action="<?= htmlspecialchars(app_url($action)) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="field">
        <label>Event Name</label>
        <input class="input" type="text" name="name" required value="<?= htmlspecialchars($event->name ?? '') ?>" placeholder="USTP Jasaan Foundation Day">
        <?= $fieldError('name') ?>
    </div>
    <div class="form-row">
        <div class="field">
            <label>Event Date</label>
            <input class="input" type="date" name="event_date" required value="<?= htmlspecialchars($event->event_date ?? '') ?>">
            <?= $fieldError('event_date') ?>
        </div>
        <div class="field">
            <label>Status</label>
            <select name="status" class="input">
                <?php $status = $event->status ?? 'draft'; ?>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="live" <?= $status === 'live' ? 'selected' : '' ?>>Live</option>
                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>
    </div>
    <div class="field">
        <label>Description</label>
        <textarea class="input" name="description" placeholder="Optional notes about the event"><?= htmlspecialchars($event->description ?? '') ?></textarea>
    </div>
    <div class="modal-actions">
        <button class="btn btn-light" type="button" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    </div>
</form>
