<?php
$pageTitle = 'Manage Judges';
$pageSubtitle = 'Assign judges to categories.';
$activePage = 'judges';
ob_start();
?>
<div class="section">
    <div class="section-head">
        <div><h3><i class="fa-solid fa-user-plus"></i> Judge Assignments</h3></div>
        <a class="btn btn-primary" href="<?= app_url('/admin/judges/assign') ?>" onclick="loadModal('<?= app_url('/admin/judges/assign') ?>', 'Assign Judge', 'Grant access to a scoring category'); return false;"><i class="fa-solid fa-user-check"></i> Assign Judge</a>
    </div>
    <div class="section-body">
        <?php if (empty($assignments)): ?>
            <div class="empty">No judge assignments yet.</div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Judge</th><th>Category</th><th>Event</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($assignment->judge_name) ?></strong><br><span class="muted"><?= htmlspecialchars($assignment->judge_email) ?></span></td>
                        <td><?= htmlspecialchars($assignment->category_name) ?></td>
                        <td><?= htmlspecialchars($assignment->event_name) ?></td>
                        <td><button class="btn btn-danger small" type="button" onclick="confirmDelete('<?= app_url('/admin/judges/remove/' . (int)$assignment->id) ?>', 'Remove Assignment', 'Remove this judge assignment?');"><i class="fa-solid fa-trash"></i> Remove</button></td>
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
