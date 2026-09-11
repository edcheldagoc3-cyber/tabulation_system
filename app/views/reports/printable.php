<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($category->name) ?> Results</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; max-width: 1000px; margin: auto; }
        h1 { font-size: 28px; color: #6d1a2b; text-align: center; }
        .subtitle { text-align: center; color: #667085; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #6d1a2b; color: #fff; padding: 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #e8e2dd; }
        .rank { font-weight: bold; font-size: 18px; }
        .medal { font-size: 24px; }
        .footer { text-align: center; margin-top: 40px; color: #667085; font-size: 12px; border-top: 1px solid #e8e2dd; padding-top: 20px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 20px; }
        }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($category->name) ?></h1>
    <p class="subtitle">Official Results</p>
    <p class="subtitle"><?= htmlspecialchars($category->event_name ?? '') ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Contestant</th>
                <th>Score</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td>
                        <?php if ((int)$row->final_rank === 1): ?>
                            <span class="medal">🥇</span>
                        <?php elseif ((int)$row->final_rank === 2): ?>
                            <span class="medal">🥈</span>
                        <?php elseif ((int)$row->final_rank === 3): ?>
                            <span class="medal">🥉</span>
                        <?php else: ?>
                            <span class="rank">#<?= (int)$row->final_rank ?></span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($row->contestant_name) ?></strong></td>
                    <td style="font-weight:bold;font-size:18px;"><?= number_format((float)$row->final_score, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        Generated on <?= date('F d, Y h:i A') ?> · USTP TabulaSys
    </div>
    
    <button onclick="window.print()" class="no-print" style="position:fixed;bottom:20px;right:20px;padding:12px 24px;background:#6d1a2b;color:#fff;border:0;border-radius:10px;cursor:pointer;font-weight:bold;">🖨️ Print</button>
</body>
</html>