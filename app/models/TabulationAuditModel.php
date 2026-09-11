<?php
namespace App\Models;

use App\Core\Model;

class TabulationAuditModel extends Model {

    public static function recordRun($categoryId, $computationType, array $scores, $triggeredBy = null): int {
        $db = self::getDB();
        $db->beginTransaction();

        try {
            $runStatement = $db->prepare(
                "INSERT INTO tabulation_runs
                 (category_id, computation_type, score_count, triggered_by)
                 VALUES (?, ?, ?, ?)"
            );
            $runStatement->execute([
                $categoryId,
                $computationType,
                count($scores),
                $triggeredBy,
            ]);
            $runId = (int)$db->lastInsertId();

            $scoreStatement = $db->prepare(
                "INSERT INTO tabulation_run_scores
                 (run_id, score_id, judge_assignment_id, contestant_id, criteria_id, score_value, submitted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($scores as $score) {
                $scoreStatement->execute([
                    $runId,
                    $score->id ?? null,
                    $score->judge_assignment_id,
                    $score->contestant_id,
                    $score->criteria_id,
                    $score->score_value,
                    $score->submitted_at,
                ]);
            }

            $db->commit();
            return $runId;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }
}
