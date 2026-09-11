<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\CategoryModel;
use App\Models\JudgeAssignmentModel;
use App\Models\ScoreModel;

class JudgeController extends Controller {

    public function dashboard() {
        $user = Auth::requireRole('judge');
        $assignments = JudgeAssignmentModel::getByUser($user->id);
        foreach ($assignments as $assignment) {
            $total = (int)($assignment->contestants_total ?? 0);
            $submitted = (int)($assignment->contestants_submitted ?? 0);
            $assignment->progress_percent = $total > 0 ? round(($submitted / $total) * 100) : 0;
        }

        $this->view('judge/dashboard', [
            'assignments' => $assignments,
        ]);
    }

    public function score($categoryId) {
        $user = Auth::requireRole('judge');
        if (!Auth::judgeOwnsCategoryAssignment($categoryId)) {
            http_response_code(403);
            die('You are not assigned to this category.');
        }

        $category = CategoryModel::getWithProgress($categoryId);
        $criteria = ScoreModel::getCriteriaByCategory($categoryId);
        $contestants = ScoreModel::getContestantsByCategory($categoryId);
        $assignment = ScoreModel::getJudgeAssignment($user->id, $categoryId);
        $existingScores = $assignment ? ScoreModel::getScoresByAssignment($assignment->id) : [];

        $scoresGrouped = [];
        $scoreState = [];
        foreach ($existingScores as $score) {
            $scoresGrouped[$score->contestant_id][$score->criteria_id] = (float)$score->score_value;
            if (!isset($scoreState[$score->contestant_id])) {
                $scoreState[$score->contestant_id] = ['count' => 0, 'all_locked' => true];
            }
            $scoreState[$score->contestant_id]['count']++;
            if (!$score->is_locked) {
                $scoreState[$score->contestant_id]['all_locked'] = false;
            }
        }

        $contestantStatus = [];
        $criteriaCount = count($criteria);
        foreach ($contestants as $contestant) {
            $state = $scoreState[$contestant->id] ?? null;
            if (!$state || $state['count'] === 0) {
                $contestantStatus[$contestant->id] = 'empty';
            } elseif ($state['all_locked'] && $state['count'] >= $criteriaCount) {
                $contestantStatus[$contestant->id] = 'submitted';
            } else {
                $contestantStatus[$contestant->id] = 'draft';
            }
        }

        $this->view('judge/scoring', [
            'categoryId' => (int)$categoryId,
            'category' => $category,
            'criteria' => $criteria,
            'contestants' => $contestants,
            'scoresGrouped' => $scoresGrouped,
            'contestantStatus' => $contestantStatus,
            'assignmentId' => $assignment->id ?? null,
            'hasCriteria' => !empty($criteria),
        ]);
    }
}
