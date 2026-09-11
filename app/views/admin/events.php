<?php
$pageTitle = 'Manage Events';
$pageSubtitle = 'Create, edit, or delete events.';
$activePage = 'events';
ob_start();
$csrf = function_exists('csrf_token') ? csrf_token() : '';
?>
<div class="section">
    <div class="section-head">
        <div>
            <h3><i class="fa-solid fa-calendar-days"></i> All Events</h3>
            <p>Here you can manage all events in the system.</p>
        </div>
        <a class="btn btn-primary" href="<?= app_url('/admin/events/create') ?>" onclick="loadModal('<?= app_url('/admin/events/create') ?>', 'Create Event', 'Add event details'); return false;"><i class="fa-solid fa-plus"></i> Create Event</a>
    </div>
    <div class="section-body">
        <?php if (empty($events)): ?>
            <div class="empty">No events created yet.</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Categories</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td>
                            <a href="<?= app_url('/admin/events/manage/' . (int)$event->id) ?>" style="font-weight:700; color:var(--ink); font-size:15px;">
                                <?= htmlspecialchars($event->name) ?>
                            </a><br>
                            <span class="muted"><?= htmlspecialchars($event->description ?? '') ?></span>
                        </td>
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($event->event_date))) ?></td>
                        <td><span class="tag <?= htmlspecialchars($event->status) ?>"><?= htmlspecialchars(ucfirst($event->status)) ?></span></td>
                        <td><?= (int)($event->categories_total ?? 0) ?></td>
                        <td>
                            <div class="split-actions">
                                <a class="btn btn-primary small" href="<?= app_url('/admin/events/manage/' . (int)$event->id) ?>"><i class="fa-solid fa-folder-open"></i> Workspace</a>
                                <a class="btn btn-light small" title="Duplicate event structure" href="<?= app_url('/admin/events/duplicate/' . (int)$event->id) ?>"><i class="fa-solid fa-copy"></i></a>
                                <a class="btn btn-light small" href="<?= app_url('/admin/events/edit/' . (int)$event->id) ?>" onclick="loadModal('<?= app_url('/admin/events/edit/' . (int)$event->id) ?>', 'Edit Event', 'Update event details'); return false;"><i class="fa-solid fa-pen"></i></a>
                                <button class="btn btn-danger small" type="button" onclick="confirmDelete('<?= app_url('/admin/events/delete/' . (int)$event->id) ?>', 'Delete Event', 'Delete this event and its related records?');"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
