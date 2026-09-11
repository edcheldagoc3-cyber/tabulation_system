<?php
$pageTitle = 'Assign Judge';
$pageSubtitle = 'Grant judges access to specific categories.';
$activePage = 'judges';
ob_start();
?>
<div class="section">
    <div class="section-head">
        <div><h3><i class="fa-solid fa-user-tie"></i> Assign Judge</h3></div>
    </div>
    <div class="section-body">
        <div style="max-width:620px;margin:0 auto;">
            <?php include __DIR__ . '/assign_judge_content.php'; ?>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
