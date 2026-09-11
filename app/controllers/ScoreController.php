<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CategoryResults;
use App\Models\ScoreModel;

class ScoreController extends Controller {

    /**
     * GET /score/form?contestant_id=X&category_id=Y
     * Displays the scoring form for a judge (used by the view).
     */
    public function form() {
        $user = Auth::requireRole('judge');
        $contestantId = $_GET['contestant_id'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;

        if (!$contestantId || !$categoryId) {
            die("Missing contestant or category ID.");
        }

        // FR7 & FR10: Judge must be assigned to this category
        if (!Auth::judgeOwnsCategoryAssignment($categoryId)) {
            die("You are not assigned to this category.");
        }

        $category = ScoreModel::getCategoryDetails($categoryId);
        $criteria = ScoreModel::getCriteriaByCategory($categoryId);
        $contestants = ScoreModel::getContestantsByCategory($categoryId);
        $assignment = ScoreModel::getJudgeAssignment($user->id, $categoryId);

        if (empty($criteria)) {
            $this->json(['error' => 'No criteria have been configured for this category yet.'], 422);
        }

        // Return as JSON (your frontend JS can consume this)
        $this->json([
            'message' => 'Scoring form loaded',
            'category' => $category,
            'criteria' => $criteria,
            'contestants' => $contestants,
            'assignment_id' => $assignment->id ?? null
        ]);
    }

    /**
     * POST /score/submit
     * Handles score submission, validation, locking, and auto-tabulation.
     */
    public function submit() {
        // 1. Authenticate – only judges can submit
        $user = Auth::requireRole('judge');

        if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
            $this->json(['error' => 'Invalid security token. Please refresh the page and try again.'], 419);
        }

        // 2. Get JSON payload
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->json(['error' => 'Invalid JSON payload'], 400);
        }
        $contestantId = $data['contestant_id'] ?? null;
        $categoryId = $data['category_id'] ?? null;
        $scores = $data['scores'] ?? []; // [criteria_id => score_value]
        $isDraft = (bool)($data['is_draft'] ?? false);

        // 3. Validate required fields
        if (!$contestantId || !$categoryId || empty($scores)) {
            $this->json(['error' => 'Missing required fields (contestant_id, category_id, scores)'], 400);
        }

        // 4. Authorization – judge must be assigned to this category (FR7, FR10)
        if (!Auth::judgeOwnsCategoryAssignment($categoryId)) {
            $this->json(['error' => 'Unauthorized – you are not assigned to this category'], 403);
        }

        // 5. Get judge assignment record
        $assignment = ScoreModel::getJudgeAssignment($user->id, $categoryId);
        if (!$assignment) {
            $this->json(['error' => 'Assignment not found for this judge and category'], 404);
        }

        $contestant = ScoreModel::getContestantByCategory($contestantId, $categoryId);
        if (!$contestant) {
            $this->json(['error' => 'Contestant does not belong to this category.'], 400);
        }

        // 6. Validate scores against criteria min/max (FR8 validation)
        $criteria = ScoreModel::getCriteriaByCategory($categoryId);
        if (empty($criteria)) {
            $this->json(['error' => 'No criteria have been configured for this category yet.'], 422);
        }
        $allowedCriteria = [];
        foreach ($criteria as $criterion) {
            $allowedCriteria[(int)$criterion->id] = $criterion;
        }

        $normalizedScores = [];
        foreach ($scores as $criteriaId => $score) {
            if (!ctype_digit((string)$criteriaId)
                || !isset($allowedCriteria[(int)$criteriaId])
                || !is_numeric($score)
            ) {
                $this->json(['error' => 'One or more submitted criteria are invalid for this category.'], 400);
            }
            if (!is_finite((float)$score)) {
                $this->json(['error' => 'Scores must be finite numeric values.'], 400);
            }
            $normalizedScores[(int)$criteriaId] = (float)$score;
        }

        foreach ($criteria as $c) {
            $criterionId = (int)$c->id;
            if (!array_key_exists($criterionId, $normalizedScores)) {
                $this->json(['error' => "Missing score for criterion: {$c->name}"], 400);
            }
            if ($normalizedScores[$criterionId] < (float)$c->min_score
                || $normalizedScores[$criterionId] > (float)$c->max_score
            ) {
                $this->json([
                    'error' => "Score for '{$c->name}' must be between {$c->min_score} and {$c->max_score}"
                ], 400);
            }
        }

        // 7. Save scores and lock them (FR9 – scores locked after submission if not draft)
        try {
            foreach ($normalizedScores as $critId => $score) {
                ScoreModel::saveScore($assignment->id, $contestantId, $critId, $score);
            }

            if (!$isDraft) {
                // Lock all scores for this judge+contestant combination
                ScoreModel::lockScoresForContestant($assignment->id, $contestantId);

                CategoryResults::recompute($categoryId, (int)$user->id);

                $this->json(['message' => 'Scores submitted, locked, and tabulation updated successfully']);
            } else {
                $this->json(['message' => 'Draft scores saved successfully']);
            }

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
