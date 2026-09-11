<?php
$pageTitle = 'Live Leaderboard · ' . htmlspecialchars($selectedEventName ?? 'USTP TabulaSys');
$pageSubtitle = '';
ob_start();
?>
<!-- Event & Category Selectors -->
<div style="background:#fff;border-radius:16px;padding:16px 20px;border:1px solid var(--line);margin-bottom:24px;display:flex;align-items:center;gap:16px 24px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <label style="font-weight:600;font-size:14px;color:var(--muted);">Event</label>
        <form method="GET" action="<?= app_url('/viewer') ?>" style="display:inline;">
            <select name="event_id" onchange="this.form.submit()" style="padding:8px 14px;border:1px solid var(--line);border-radius:10px;font-size:14px;background:#fff;font-weight:500;min-width:160px;">
                <?php foreach ($events as $event): ?>
                    <option value="<?= (int)$event->id ?>" <?= ((int)$eventId === (int)$event->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($event->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <label style="font-weight:600;font-size:14px;color:var(--muted);">Category</label>
        <form method="GET" action="<?= app_url('/viewer') ?>" style="display:inline;">
            <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
            <select name="category_id" onchange="this.form.submit()" style="padding:8px 14px;border:1px solid var(--line);border-radius:10px;font-size:14px;background:#fff;font-weight:500;min-width:160px;">
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category->id ?>" <?= ((int)$categoryId === (int)$category->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<!-- Category Tabs -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
    <?php foreach ($categories as $category): ?>
        <a href="<?= app_url('/viewer?event_id=' . (int)$eventId . '&category_id=' . (int)$category->id) ?>"
           style="padding:8px 18px;border-radius:30px;background:<?= ((int)$categoryId === (int)$category->id) ? 'var(--maroon)' : '#fff' ?>;color:<?= ((int)$categoryId === (int)$category->id) ? '#fff' : 'var(--ink)' ?>;border:1px solid var(--line);font-weight:600;font-size:14px;text-decoration:none;">
            <?= htmlspecialchars($category->name) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Status Banner -->
<?php
$hasResults = $hasResults ?? !empty($results);
$isReleased = $isReleased ?? false;
?>
<div style="padding:14px 20px;border-radius:14px;margin-bottom:24px;display:flex;align-items:center;gap:12px;font-weight:500;font-size:15px;flex-wrap:wrap;
    <?= $isReleased ? 'background:#d1fae5;color:#065f46;border-left:6px solid #0b7e4b;' : 'background:#fef3c7;color:#92400e;border-left:6px solid #f59e0b;' ?>">
    <i class="fa-solid <?= $isReleased ? 'fa-check-circle' : 'fa-clock' ?>"></i>
    <span>
        <strong><?= $isReleased ? 'Official Results' : 'Provisional Results' ?></strong>
        <?= $isReleased ? '— Released by the Tabulator. These rankings are final.' : '— Waiting for Tabulator finalization. Scores are subject to change.' ?>
    </span>
</div>

<!-- Podium & Rankings -->
<?php if (!$hasResults): ?>
    <div style="padding:48px;border:1px dashed #ddd2c9;border-radius:18px;text-align:center;color:var(--muted);background:#fff;">
        <i class="fa-solid fa-hourglass-half" style="font-size:48px;color:#d0d8e3;display:block;margin-bottom:12px;"></i>
        <h3 style="font-weight:600;color:#1e293b;">No scores available yet</h3>
        <p style="color:var(--muted);">Results will appear here as judges submit scores.</p>
    </div>
<?php else: ?>
    <?php
    $top3 = array_slice($results, 0, 3);
    $medalColors = ['#f59e0b', '#94a3b8', '#c08457'];
    $rankLabels = ['🥇', '🥈', '🥉'];
    ?>
    <!-- Podium -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:28px;padding:20px 10px 10px;background:linear-gradient(180deg,#f8fafc,#fff);border-radius:20px;border:1px solid var(--line);">
        <?php foreach ([1, 0, 2] as $idx): // Order: 2nd, 1st, 3rd ?>
            <?php if (!isset($top3[$idx])) continue; $item = $top3[$idx]; $pos = $idx; ?>
            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;padding:12px 8px 8px;border-radius:16px;flex:1;
                <?= $pos === 0 ? 'background:#fef9e7;border:2px solid #f59e0b;transform:scale(1.05);' : ($pos === 1 ? 'background:#f1f4f9;border:2px solid #d0d8e3;' : 'background:#fef3e8;border:2px solid #e8a87c;') ?>">
                <div style="font-size:32px;margin-bottom:2px;"><?= $rankLabels[$pos] ?></div>
                <div style="font-weight:700;font-size:18px;color:#0b1a33;margin-top:2px;"><?= htmlspecialchars($item->contestant_name) ?></div>
                <div style="font-weight:700;font-size:22px;color:var(--maroon);"><?= number_format((float)$item->final_score, 2) ?></div>
                <div style="font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;"><?= $pos === 0 ? '1st' : ($pos === 1 ? '2nd' : '3rd') ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Full Rankings Table -->
    <div style="background:#fff;border-radius:16px;border:1px solid var(--line);overflow:hidden;">
        <div style="padding:16px 24px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <h3 style="font-weight:600;font-size:16px;color:#0b1a33;"><i class="fa-solid fa-list-ol"></i> Full Rankings</h3>
            <span style="font-size:14px;color:var(--muted);">Showing <?= count($results) ?> contestants</span>
        </div>
        <div style="padding:0 24px 24px;overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:14px 6px 10px 0;font-weight:600;color:#4b5b74;border-bottom:1px solid var(--line);font-size:12px;text-transform:uppercase;letter-spacing:0.4px;">Rank</th>
                        <th style="text-align:left;padding:14px 6px 10px 0;font-weight:600;color:#4b5b74;border-bottom:1px solid var(--line);font-size:12px;text-transform:uppercase;letter-spacing:0.4px;">Contestant</th>
                        <th style="text-align:right;padding:14px 6px 10px 0;font-weight:600;color:#4b5b74;border-bottom:1px solid var(--line);font-size:12px;text-transform:uppercase;letter-spacing:0.4px;">Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td style="padding:12px 6px 12px 0;border-bottom:1px solid #f1f4f9;vertical-align:middle;">
                                <span style="font-weight:700;color:var(--muted);min-width:28px;display:inline-block;">#<?= (int)$result->final_rank ?></span>
                                <?php if ((int)$result->final_rank <= 3): ?>
                                    <span style="font-size:18px;margin-left:4px;"><?= ['🥇', '🥈', '🥉'][(int)$result->final_rank - 1] ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px 6px 12px 0;border-bottom:1px solid #f1f4f9;vertical-align:middle;font-weight:600;"><?= htmlspecialchars($result->contestant_name) ?></td>
                            <td style="padding:12px 6px 12px 0;border-bottom:1px solid #f1f4f9;vertical-align:middle;text-align:right;font-weight:600;"><?= number_format((float)$result->final_score, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Auto-refresh info -->
<div style="text-align:center;font-size:13px;color:var(--muted);padding:16px 0 8px;border-top:1px solid var(--line);margin-top:16px;">
    <i class="fa-solid fa-sync-alt fa-fw"></i> Auto-refreshes every 15 seconds
    <span style="margin:0 8px;">·</span>
    <i class="fa-solid fa-clock"></i> Last updated: <span id="lastUpdate"><?= date('h:i:s A') ?></span>
</div>

<script>
    // Auto-refresh every 15 seconds
    setTimeout(() => {
        location.reload();
    }, 15000);
</script>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
