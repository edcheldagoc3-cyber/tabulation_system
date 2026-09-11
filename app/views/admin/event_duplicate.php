<?php
$pageTitle = 'Duplicate Event Structure';
$pageSubtitle = 'Copy competitions, rubrics, weights, and tabulation rules into a fresh event.';
$activePage = 'events'; $csrf = csrf_token(); ob_start();
?>
<div class="section"><div class="section-head"><div><h3><i class="fa-solid fa-copy"></i> Duplicate <?= htmlspecialchars($source->name) ?></h3><p>Contestants, judges, scores, results, and releases will not be copied.</p></div></div>
<div class="section-body"><form method="POST" action="<?= app_url('/admin/events/duplicate/' . (int)$source->id) ?>" style="max-width:640px"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
<div class="field"><label>New Event Name</label><input class="input" name="name" required value="<?= htmlspecialchars($source->name . ' Copy') ?>"></div>
<div class="field"><label>Event Date</label><input class="input" type="date" name="event_date" required value="<?= htmlspecialchars($source->event_date) ?>"></div>
<div class="field"><label>Description</label><textarea class="input" name="description" rows="3"><?= htmlspecialchars($source->description ?? '') ?></textarea></div>
<button class="btn btn-primary"><i class="fa-solid fa-copy"></i> Duplicate Event Structure</button> <a class="btn btn-light" href="<?= app_url('/admin/events') ?>">Cancel</a>
</form></div></div>
<?php $content = ob_get_clean(); include __DIR__ . '/layout.php'; ?>
