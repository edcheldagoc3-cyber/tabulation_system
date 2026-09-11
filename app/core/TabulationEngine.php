<?php
namespace App\Core;

class TabulationEngine {

    public static function compute(array $scores, array $criteria, string $type, array $options = []): array {
        $tiebreakRule = $options['tiebreak_rule'] ?? 'manual';
        $tiebreakCriterionId = isset($options['tiebreak_criterion_id'])
            ? (int)$options['tiebreak_criterion_id']
            : null;

        if ($type === 'rank_based') {
            $finalScores = self::computeRankBased($scores, $criteria);
            $lowerIsBetter = true;
        } else {
            $finalScores = self::computeScoreBased($scores, $criteria, $type);
            $lowerIsBetter = false;
        }

        if (empty($finalScores)) {
            return [];
        }

        return self::rankWithTiebreak(
            $finalScores,
            $scores,
            $criteria,
            $type,
            $tiebreakRule,
            $tiebreakCriterionId,
            $lowerIsBetter
        );
    }

    private static function computeScoreBased(array $scores, array $criteria, string $type): array {
        $judgeTotals = [];

        foreach ($scores as $judgeId => $contestants) {
            foreach ($contestants as $contestantId => $criteriaScores) {
                if (!isset($judgeTotals[$contestantId])) {
                    $judgeTotals[$contestantId] = [];
                }
                $judgeTotals[$contestantId][$judgeId] = self::sumCriteriaScores(
                    $criteriaScores,
                    $criteria,
                    $type
                );
            }
        }

        $finalScores = [];
        foreach ($judgeTotals as $contestantId => $perJudge) {
            $count = count($perJudge);
            if ($count === 0) {
                continue;
            }
            $finalScores[$contestantId] = array_sum($perJudge) / $count;
        }

        return $finalScores;
    }

    private static function computeRankBased(array $scores, array $criteria): array {
        $contestantJudgeRanks = [];

        foreach ($scores as $judgeId => $contestants) {
            $judgeTotals = [];
            foreach ($contestants as $contestantId => $criteriaScores) {
                $judgeTotals[$contestantId] = self::sumCriteriaScores($criteriaScores, $criteria, 'raw_average');
            }

            arsort($judgeTotals, SORT_NUMERIC);
            $rank = 1;
            $position = 0;
            $previousTotal = null;

            foreach ($judgeTotals as $contestantId => $total) {
                $position++;
                if ($previousTotal === null || !self::floatsEqual($total, $previousTotal)) {
                    $rank = $position;
                }
                $contestantJudgeRanks[$contestantId][$judgeId] = $rank;
                $previousTotal = $total;
            }
        }

        $finalScores = [];
        foreach (self::allContestantIds($scores) as $contestantId) {
            $ranks = $contestantJudgeRanks[$contestantId] ?? [];
            if (empty($ranks)) {
                continue;
            }
            $finalScores[$contestantId] = array_sum($ranks) / count($ranks);
        }

        return $finalScores;
    }

    private static function rankWithTiebreak(
        array $finalScores,
        array $scores,
        array $criteria,
        string $type,
        string $tiebreakRule,
        ?int $tiebreakCriterionId,
        bool $lowerIsBetter
    ): array {
        $sortedContestants = array_keys($finalScores);
        usort($sortedContestants, static function ($left, $right) use ($finalScores, $lowerIsBetter) {
            $leftScore = $finalScores[$left];
            $rightScore = $finalScores[$right];
            if (self::floatsEqual($leftScore, $rightScore)) {
                return $left <=> $right;
            }
            return $lowerIsBetter ? ($leftScore <=> $rightScore) : ($rightScore <=> $leftScore);
        });

        $groups = [];
        $currentGroup = [];
        $currentScore = null;

        foreach ($sortedContestants as $contestantId) {
            $score = $finalScores[$contestantId];
            if ($currentScore === null || self::floatsEqual($score, $currentScore)) {
                $currentGroup[] = $contestantId;
                $currentScore = $score;
                continue;
            }

            $groups[] = ['score' => $currentScore, 'contestants' => $currentGroup];
            $currentGroup = [$contestantId];
            $currentScore = $score;
        }

        if (!empty($currentGroup)) {
            $groups[] = ['score' => $currentScore, 'contestants' => $currentGroup];
        }

        $ranked = [];
        $nextRank = 1;

        foreach ($groups as $group) {
            $contestants = $group['contestants'];
            if (count($contestants) > 1 && $tiebreakRule !== 'manual') {
                $contestants = self::resolveTieGroup(
                    $contestants,
                    $scores,
                    $criteria,
                    $type,
                    $tiebreakRule,
                    $tiebreakCriterionId,
                    $lowerIsBetter
                );
            }

            foreach ($contestants as $contestantId) {
                $ranked[$contestantId] = [
                    'score' => $group['score'],
                    'rank' => $nextRank,
                ];
            }

            $nextRank += count($contestants);
        }

        return $ranked;
    }

    private static function resolveTieGroup(
        array $contestants,
        array $scores,
        array $criteria,
        string $type,
        string $tiebreakRule,
        ?int $tiebreakCriterionId,
        bool $lowerIsBetter
    ): array {
        $metrics = [];
        foreach ($contestants as $contestantId) {
            $metrics[$contestantId] = self::tiebreakMetric(
                $contestantId,
                $scores,
                $criteria,
                $type,
                $tiebreakRule,
                $tiebreakCriterionId
            );
        }

        usort($contestants, static function ($left, $right) use ($metrics, $lowerIsBetter, $tiebreakRule) {
            $leftMetric = $metrics[$left];
            $rightMetric = $metrics[$right];

            if (self::floatsEqual($leftMetric, $rightMetric)) {
                return $left <=> $right;
            }

            if ($tiebreakRule === 'lowest_variance') {
                return $leftMetric <=> $rightMetric;
            }

            if ($lowerIsBetter) {
                return $leftMetric <=> $rightMetric;
            }

            return $rightMetric <=> $leftMetric;
        });

        return $contestants;
    }

    private static function tiebreakMetric(
        int $contestantId,
        array $scores,
        array $criteria,
        string $type,
        string $tiebreakRule,
        ?int $tiebreakCriterionId
    ): float {
        switch ($tiebreakRule) {
            case 'highest_criterion':
                if (!$tiebreakCriterionId) {
                    return 0.0;
                }
                return self::averageCriterionScore($contestantId, $tiebreakCriterionId, $scores);

            case 'sum_criteria':
                return self::averageJudgeTotal($contestantId, $scores, $criteria, 'raw_average');

            case 'more_top_ranks':
                return (float)self::countTopJudgeRanks($contestantId, $scores, $criteria);

            case 'lowest_variance':
                return self::judgeScoreVariance($contestantId, $scores, $criteria, $type);

            default:
                return 0.0;
        }
    }

    private static function sumCriteriaScores(array $criteriaScores, array $criteria, string $type): float {
        $total = 0.0;
        foreach ($criteriaScores as $criteriaId => $score) {
            $weight = (float)($criteria[$criteriaId]['weight'] ?? 1);
            if ($type === 'weighted_criteria') {
                $total += ((float)$score) * $weight;
            } else {
                $total += (float)$score;
            }
        }

        return $total;
    }

    private static function averageCriterionScore(int $contestantId, int $criteriaId, array $scores): float {
        $values = [];
        foreach ($scores as $judgeScores) {
            if (!isset($judgeScores[$contestantId][$criteriaId])) {
                continue;
            }
            $values[] = (float)$judgeScores[$contestantId][$criteriaId];
        }

        if (empty($values)) {
            return 0.0;
        }

        return array_sum($values) / count($values);
    }

    private static function averageJudgeTotal(
        int $contestantId,
        array $scores,
        array $criteria,
        string $type
    ): float {
        $totals = [];
        foreach ($scores as $judgeId => $judgeScores) {
            if (!isset($judgeScores[$contestantId])) {
                continue;
            }
            $totals[] = self::sumCriteriaScores($judgeScores[$contestantId], $criteria, $type);
        }

        if (empty($totals)) {
            return 0.0;
        }

        return array_sum($totals) / count($totals);
    }

    private static function countTopJudgeRanks(int $contestantId, array $scores, array $criteria): int {
        $topRankCount = 0;

        foreach ($scores as $judgeScores) {
            $judgeTotals = [];
            foreach ($judgeScores as $otherContestantId => $criteriaScores) {
                $judgeTotals[$otherContestantId] = self::sumCriteriaScores($criteriaScores, $criteria, 'raw_average');
            }

            if (empty($judgeTotals) || !isset($judgeTotals[$contestantId])) {
                continue;
            }

            arsort($judgeTotals, SORT_NUMERIC);
            $bestTotal = reset($judgeTotals);
            if (self::floatsEqual($judgeTotals[$contestantId], $bestTotal)) {
                $topRankCount++;
            }
        }

        return $topRankCount;
    }

    private static function judgeScoreVariance(
        int $contestantId,
        array $scores,
        array $criteria,
        string $type
    ): float {
        $totals = [];
        foreach ($scores as $judgeScores) {
            if (!isset($judgeScores[$contestantId])) {
                continue;
            }
            $totals[] = self::sumCriteriaScores($judgeScores[$contestantId], $criteria, $type);
        }

        $count = count($totals);
        if ($count <= 1) {
            return 0.0;
        }

        $mean = array_sum($totals) / $count;
        $variance = 0.0;
        foreach ($totals as $total) {
            $variance += ($total - $mean) ** 2;
        }

        return $variance / $count;
    }

    private static function allContestantIds(array $scores): array {
        $ids = [];
        foreach ($scores as $contestants) {
            foreach ($contestants as $contestantId => $_) {
                $ids[(int)$contestantId] = true;
            }
        }

        return array_keys($ids);
    }

    private static function floatsEqual(float $left, float $right): bool {
        return abs($left - $right) < 0.0001;
    }
}
