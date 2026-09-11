<?php
namespace App\Core;

use App\Models\CategoryModel;
use App\Models\ResultModel;
use App\Models\ScoreModel;
use App\Models\TabulationAuditModel;

class CategoryResults {

    public static function recompute(int $categoryId, ?int $triggeredBy = null): void {
        $category = CategoryModel::getById($categoryId);
        if (!$category) {
            throw new \RuntimeException('Category not found.');
        }

        $scoresRaw = ScoreModel::getScoresForCategory($categoryId);
        if (empty($scoresRaw)) {
            return;
        }

        $criteria = ScoreModel::getCriteriaByCategory($categoryId);
        $criteriaWeights = [];
        foreach ($criteria as $criterion) {
            $criteriaWeights[$criterion->id] = ['weight' => ((float)$criterion->weight_percent) / 100];
        }

        $scoresFormatted = [];
        foreach ($scoresRaw as $scoreRow) {
            $scoresFormatted[$scoreRow->judge_id][$scoreRow->contestant_id][$scoreRow->criteria_id] = $scoreRow->score_value;
        }

        $computed = TabulationEngine::compute(
            $scoresFormatted,
            $criteriaWeights,
            $category->computation_type,
            [
                'tiebreak_rule' => $category->tiebreak_rule ?? 'manual',
                'tiebreak_criterion_id' => $category->tiebreak_criterion_id ?? null,
            ]
        );

        TabulationAuditModel::recordRun(
            $categoryId,
            $category->computation_type,
            $scoresRaw,
            $triggeredBy
        );

        foreach ($computed as $contestantId => $result) {
            ResultModel::saveResult($categoryId, $contestantId, $result['score'], $result['rank']);
        }
    }
}
