<?php
$pageTitle = 'Locked Scores';
$pageSubtitle = 'Review and unlock judge submissions when corrections are needed.';
$activePage = 'scores';
ob_start();
$unlockBasePath = '/admin/scores/unlock';
$filterBasePath = '/admin/scores';
include __DIR__ . '/../shared/locked_scores.php';
$content = ob_get_clean();
include __DIR__ . '/layout.php';
