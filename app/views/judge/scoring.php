<?php
$pageTitle = 'Scoring: ' . htmlspecialchars($category->name ?? 'Category');
$pageSubtitle = htmlspecialchars($category->event_name ?? '') . ' – Score each contestant carefully.';
$activePage = 'scoring';
ob_start();

$csrf = function_exists('csrf_token') ? csrf_token() : '';
$criteriaJson = json_encode($criteria ?? []);
$contestantsJson = json_encode($contestants ?? []);
$scoresJson = json_encode($scoresGrouped ?? []);
$statusJson = json_encode($contestantStatus ?? []);
$statusCounts = ['empty' => 0, 'draft' => 0, 'submitted' => 0];
foreach (($contestants ?? []) as $contestant) {
    $statusCounts[$contestantStatus[$contestant->id] ?? 'empty']++;
}
?>
<style>
    .judge-scoring-page {
        position: relative;
    }
    .judge-scoring-summary,
    .judge-scoring-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .judge-scoring-summary { margin-top: 16px; }
    .judge-summary-pill {
        padding: 8px 12px;
        border-radius: 999px;
        background: #f5f7fb;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }
    .judge-scoring-controls {
        justify-content: space-between;
        margin-top: 18px;
        padding: 12px;
        border: 1px solid var(--line);
        border-radius: 14px;
        background: #f8fafc;
    }
    .judge-scoring-controls input,
    .judge-scoring-controls select {
        min-height: 38px;
        padding: 8px 12px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: #fff;
        color: var(--ink);
    }
    .judge-scoring-controls input { min-width: 220px; }
    .contestant-card.is-hidden { display: none !important; }
    .judge-score-strip {
        position: sticky;
        top: 92px;
        z-index: 12;
        margin-bottom: 18px;
        background: linear-gradient(135deg, #6d1a2b, #3a0f19);
        color: #fff;
        border: none;
        box-shadow: 0 16px 36px rgba(58, 15, 25, .18);
    }
    .judge-score-strip .progress {
        background: rgba(255,255,255,.18);
        margin-top: 10px;
        margin-bottom: 4px;
    }
    .judge-score-strip .progress > span {
        background: #fff;
    }
    .judge-score-strip-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .judge-score-strip-actions {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .judge-score-strip-actions .btn {
        padding: 9px 12px;
        border-radius: 12px;
        white-space: nowrap;
    }
    .judge-score-card { box-shadow: 0 14px 34px rgba(20, 18, 16, .06); }
    .judge-scoring-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 18px;
        align-items: start;
    }
    .judge-scoring-help {
        position: sticky;
        top: 170px;
        align-self: start;
    }
    .scoring-rows { display: grid; gap: 8px; }
    .scoring-row {
        display: grid;
        grid-template-columns: minmax(180px, .45fr) minmax(280px, 1fr);
        gap: 18px;
        align-items: center;
        padding: 14px 16px;
        border: 1px solid #eee7e1;
        border-radius: 14px;
        background: #fffdfb;
    }
    .scoring-row.is-hidden { display: none; }
    .scoring-contestant { display: grid; gap: 3px; min-width: 0; }
    .scoring-contestant strong { overflow-wrap: anywhere; }
    .score-status { color: #1e40af; font-size: 12px; font-weight: 700; }
    .score-status.is-locked { color: #166534; }
    .scoring-inputs { display: grid; grid-template-columns: minmax(0, 1fr) 88px; gap: 14px; align-items: center; }
    .scoring-inputs input[type="range"] { width: 100%; accent-color: var(--maroon); }
    .score-number { min-height: 38px; padding: 8px 10px; border: 1px solid var(--line); border-radius: 10px; color: var(--ink); background: #fff; }
    .criterion-block { padding: 16px; border: 1px solid #eee7e1; border-radius: 16px; background: #fffdfb; }
    .criterion-block + .criterion-block { margin-top: 12px; }
    .score-modal-actions { border-top: 1px solid var(--line); padding-top: 16px !important; }
    @media (max-width: 850px) {
        .judge-scoring-layout { grid-template-columns: 1fr !important; }
        .judge-scoring-help { position: static; }
    }
    @media (max-width: 520px) {
        .judge-score-strip { top: 76px; }
        .judge-score-strip-actions { width: 100%; }
        .judge-score-strip-actions .btn { width: 100%; justify-content: center; }
        .judge-scoring-controls,
        .judge-scoring-controls > div { align-items: stretch; flex-direction: column; }
        .judge-scoring-controls input,
        .judge-scoring-controls select,
        .judge-scoring-controls button { width: 100%; }
        .scoring-row { grid-template-columns: 1fr; gap: 10px; }
        .scoring-inputs { grid-template-columns: minmax(0, 1fr) 76px; gap: 8px; }
    }
</style>
<div class="judge-scoring-page">
    <div class="card judge-score-strip">
        <div class="judge-score-strip-inner">
            <div style="min-width:0;">
                <div style="font-size:13px;opacity:0.8;">Category</div>
                <div style="font-size:22px;font-weight:800;margin-top:4px;"><?= htmlspecialchars($category->name ?? '') ?></div>
                <div style="font-size:14px;opacity:0.8;margin-top:4px;">Computation: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $category->computation_type ?? ''))) ?></div>
                <div class="progress">
                    <span style="width: <?= (int)($category->progress_percent ?? 0) ?>%;"></span>
                </div>
                <div style="font-size:13px;opacity:0.8;"><?= (int)($category->progress_percent ?? 0) ?>% complete</div>
            </div>
            <div class="judge-score-strip-actions">
                <a class="btn btn-light" href="<?= app_url('/judge') ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>

<!-- Main Scoring Area -->
<div class="judge-scoring-layout">
    <section class="card judge-score-card">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 class="section-title" style="margin:0 0 8px;font-size:18px;">Score All Contestants</h2>
                <div class="muted" style="font-size:14px;">Choose one criterion, then rate every contestant without opening separate forms.</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn btn-light" type="button" onclick="saveAll(true)" <?= empty($criteria) || empty($contestants) ? 'disabled' : '' ?>><i class="fa-solid fa-floppy-disk"></i> Save Drafts</button>
                <button class="btn btn-primary" type="button" onclick="submitAll()" <?= empty($criteria) || empty($contestants) ? 'disabled' : '' ?>><i class="fa-solid fa-paper-plane"></i> Submit All</button>
            </div>
        </div>

        <div class="judge-scoring-summary">
            <span class="judge-summary-pill"><i class="fa-solid fa-list-check"></i> <?= count($contestants ?? []) ?> contestants</span>
            <span class="judge-summary-pill" style="background:#f3f4f6;color:#4b5563;"><i class="fa-regular fa-circle"></i> <?= $statusCounts['empty'] ?> not started</span>
            <span class="judge-summary-pill" style="background:#e0ecff;color:#1e40af;"><i class="fa-solid fa-pen-ruler"></i> <?= $statusCounts['draft'] ?> drafts</span>
            <span class="judge-summary-pill" style="background:#dcfce7;color:#166534;"><i class="fa-solid fa-circle-check"></i> <?= $statusCounts['submitted'] ?> submitted</span>
        </div>

        <div class="judge-scoring-controls">
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <label for="criterionSelect" style="font-weight:800;">Criterion</label>
                <select id="criterionSelect" aria-label="Choose criterion">
                    <?php foreach (($criteria ?? []) as $criterion): ?>
                        <option value="<?= (int)$criterion->id ?>"><?= htmlspecialchars($criterion->name) ?> (<?= htmlspecialchars((string)$criterion->weight_percent) ?>%)</option>
                    <?php endforeach; ?>
                </select>
                <input id="contestantSearch" type="search" placeholder="Search contestants..." aria-label="Search contestants">
                <select id="statusFilter" aria-label="Filter contestants by status">
                    <option value="all">All statuses</option>
                    <option value="empty">Not started</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                </select>
            </div>
            <span id="criterionProgress" class="muted" style="font-size:13px;font-weight:700;"></span>
        </div>

        <?php if (empty($criteria)): ?>
            <div class="empty" style="margin-top:16px;">No criteria have been configured for this category yet.</div>
        <?php elseif (empty($contestants)): ?>
            <div class="empty" style="margin-top:16px;">No contestants are registered in this category yet.</div>
        <?php else: ?>
            <div id="scoringRows" class="scoring-rows" style="margin-top:16px;"></div>
            <div id="filteredEmpty" class="empty" style="display:none;margin-top:16px;">No contestants match the current search or filter.</div>
        <?php endif; ?>
    </section>

    <aside class="judge-scoring-help">
        <div class="card judge-score-card">
            <h2 style="margin:0 0 8px;font-size:18px;">Scoring Help</h2>
            <p class="muted" style="margin-top:0;font-size:14px;">Use the slider or enter an exact score. Move through each criterion using the selector above.</p>
            <div style="margin-top:14px;display:grid;gap:10px;">
                <div class="pill" style="background:#f6ead0;color:#7a5600;width:fit-content;"><i class="fa-solid fa-lock"></i> Final submit locks scores</div>
                <div class="pill" style="background:#e0ecff;color:#1e40af;width:fit-content;"><i class="fa-solid fa-floppy-disk"></i> Drafts stay private</div>
                <div class="pill" style="background:#dcfce7;color:#166534;width:fit-content;"><i class="fa-solid fa-arrows-left-right"></i> Rate side by side</div>
            </div>
        </div>
    </aside>
</div>
</div>

<!-- Toast -->
<div id="toast" style="position:fixed;right:18px;bottom:18px;background:#111827;color:#fff;padding:12px 14px;border-radius:14px;box-shadow:0 18px 40px rgba(0,0,0,.2);display:none;z-index:60;"></div>

<script>
const criteria = <?= $criteriaJson ?: '[]' ?>;
const contestants = <?= $contestantsJson ?: '[]' ?>;
const existingScores = <?= $scoresJson ?: '[]' ?>;
const contestantStatus = <?= $statusJson ?: '[]' ?>;
const categoryId = <?= (int)($categoryId ?? 0) ?>;
const csrfToken = <?= json_encode($csrf) ?>;
let selectedCriterionId = criteria.length ? Number(criteria[0].id) : null;

function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.display = 'block';
    setTimeout(() => toast.style.display = 'none', 2600);
}

function getCriterion() {
    return criteria.find(criterion => Number(criterion.id) === selectedCriterionId);
}

function defaultScore(criterion) {
    return ((Number(criterion.min_score ?? 0) + Number(criterion.max_score ?? 100)) / 2).toFixed(2);
}

function renderRows() {
    const container = document.getElementById('scoringRows');
    if (!container || !getCriterion()) return;
    const criterion = getCriterion();
    const min = Number(criterion.min_score ?? 0);
    const max = Number(criterion.max_score ?? 100);
    let complete = 0;
    container.innerHTML = contestants.map(contestant => {
        const id = Number(contestant.id);
        const saved = existingScores[id] || {};
        const value = saved[criterion.id] !== undefined ? saved[criterion.id] : defaultScore(criterion);
        const locked = contestantStatus[id] === 'submitted';
        complete += saved[criterion.id] !== undefined ? 1 : 0;
        return `<div class="scoring-row" data-name="${escapeHtml(String(contestant.name).toLowerCase())}" data-status="${escapeHtml(contestantStatus[id] || 'empty')}">
            <div class="scoring-contestant">
                <strong>${escapeHtml(contestant.name)}</strong>
                <small class="muted">${escapeHtml(String(contestant.type || 'solo'))}</small>
                <span class="score-status ${locked ? 'is-locked' : ''}">${locked ? '<i class="fa-solid fa-lock"></i> Locked' : '<i class="fa-solid fa-pen"></i> Editable'}</span>
            </div>
            <div class="scoring-inputs">
                <input class="score-range" type="range" min="${min}" max="${max}" step="0.01" value="${value}" data-contestant-id="${id}" ${locked ? 'disabled' : ''} aria-label="Score ${escapeHtml(contestant.name)}">
                <input class="score-number" type="number" min="${min}" max="${max}" step="0.01" value="${value}" data-contestant-id="${id}" ${locked ? 'disabled' : ''} aria-label="Exact score for ${escapeHtml(contestant.name)}">
            </div>
        </div>`;
    }).join('');
    const progress = document.getElementById('criterionProgress');
    if (progress) progress.textContent = `${complete}/${contestants.length} saved for this criterion`;
    filterContestants();
}

function updateScore(input) {
    const row = input.closest('.scoring-row');
    const value = input.value;
    row.querySelectorAll('input').forEach(control => {
        control.value = value;
    });
}

function filterContestants() {
    const query = (document.getElementById('contestantSearch')?.value || '').trim().toLowerCase();
    const status = document.getElementById('statusFilter')?.value || 'all';
    let visible = 0;
    document.querySelectorAll('.scoring-row').forEach(row => {
        const show = (!query || row.dataset.name.includes(query)) && (status === 'all' || row.dataset.status === status);
        row.classList.toggle('is-hidden', !show);
        if (show) visible++;
    });
    const emptyState = document.getElementById('filteredEmpty');
    if (emptyState) emptyState.style.display = visible === 0 ? 'block' : 'none';
}

function gatherScores(contestantId) {
    const scores = { ...(existingScores[contestantId] || {}) };
    const criterion = getCriterion();
    const row = [...document.querySelectorAll('.scoring-row')].find(item => item.querySelector(`[data-contestant-id="${contestantId}"]`));
    if (row && criterion) scores[criterion.id] = Number(parseFloat(row.querySelector('.score-number').value).toFixed(2));
    criteria.forEach(item => {
        if (scores[item.id] === undefined) scores[item.id] = Number(defaultScore(item));
    });
    return scores;
}

async function submitScores(contestantId, isDraft) {
    const response = await fetch('<?= app_url('/score/submit') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
            contestant_id: contestantId,
            category_id: categoryId,
            scores: gatherScores(contestantId),
            is_draft: isDraft
        })
    });

    const payload = await response.json();
    if (!response.ok || payload.error) {
        throw new Error(payload.error || 'Unable to save scores.');
    }
    return payload;
}

async function saveAll(isDraft) {
    const editable = contestants.filter(contestant => contestantStatus[contestant.id] !== 'submitted');
    if (!editable.length) {
        showToast('All contestant scores are already locked.');
        return;
    }
    try {
        for (const contestant of editable) await submitScores(contestant.id, isDraft);
        showToast(isDraft ? 'All drafts saved successfully.' : 'All scores submitted successfully.');
        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        alert(error.message);
    }
}

async function submitAll() {
    if (!confirm('Submit final scores for all editable contestants? This will lock their scores.')) {
        return;
    }
    await saveAll(false);
}

document.getElementById('criterionSelect')?.addEventListener('change', event => {
    selectedCriterionId = Number(event.target.value);
    renderRows();
});
document.getElementById('contestantSearch')?.addEventListener('input', filterContestants);
document.getElementById('statusFilter')?.addEventListener('change', filterContestants);
document.getElementById('scoringRows')?.addEventListener('input', event => {
    if (event.target.matches('.score-range, .score-number')) updateScore(event.target);
});

renderRows();

function formatScore(value) {
    const num = Number(value);
    return Number.isInteger(num) ? String(num) : num.toFixed(2);
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
