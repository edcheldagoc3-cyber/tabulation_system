<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CategoryResults;
use App\Models\CategoryModel;
use App\Models\ResultModel;
use App\Models\ScoreModel;

class TabulatorController extends Controller {

    public function index() {
        Auth::requireRole('tabulator');

        $categories = CategoryModel::getAllWithProgress();
        $summary = [
            'categories_total' => count($categories),
            'complete' => count(array_filter($categories, static function ($category) {
                return (int)($category->progress_percent ?? 0) >= 100;
            })),
            'released' => count(array_filter($categories, static function ($category) {
                return !empty($category->is_released);
            })),
        ];

        $this->view('tabulator/dashboard', [
            'categories' => $categories,
            'summary' => $summary,
        ]);
    }

    public function review($categoryId) {
        Auth::requireRole('tabulator');

        $category = CategoryModel::getWithProgress($categoryId);
        if (!$category) {
            http_response_code(404);
            die('Category not found.');
        }

        $criteria = ScoreModel::getCriteriaByCategory($categoryId);
        $contestants = ScoreModel::getContestantsByCategory($categoryId);
        $scores = ScoreModel::getLockedScoresWithDetails($categoryId);
        $results = ResultModel::getByCategory($categoryId);
        $weightsTotal = 0.0;
        foreach ($criteria as $criterion) {
            $weightsTotal += (float)$criterion->weight_percent;
        }

        $rankCounts = [];
        foreach ($results as $result) {
            $rank = (int)$result->final_rank;
            $rankCounts[$rank] = ($rankCounts[$rank] ?? 0) + 1;
        }
        $tiedRanks = array_keys(array_filter($rankCounts, static function ($count) {
            return $count > 1;
        }));

        $warnings = [];
        if (empty($criteria)) {
            $warnings[] = 'No scoring criteria are configured.';
        }
        if (empty($contestants)) {
            $warnings[] = 'No contestants are registered.';
        }
        if ((int)($category->progress_percent ?? 0) < 100) {
            $warnings[] = 'Not all assigned judges have completed every contestant and criterion.';
        }
        if ($category->computation_type === 'weighted_criteria' && abs($weightsTotal - 100.0) > 0.0001) {
            $warnings[] = 'Weighted criteria must total exactly 100%.';
        }
        if (empty($results) && !empty($scores)) {
            $warnings[] = 'Scores exist, but no computed results are available yet.';
        }
        if (!empty($tiedRanks)) {
            $warnings[] = 'Unresolved tie detected at rank ' . implode(', ', $tiedRanks) . '.';
        }

        foreach ($scores as $score) {
            $score->weighted_contribution = $category->computation_type === 'weighted_criteria'
                ? (float)$score->score_value * ((float)$score->weight_percent / 100)
                : null;
        }

        $this->view('tabulator/review', [
            'category' => $category,
            'criteria' => $criteria,
            'contestants' => $contestants,
            'scores' => $scores,
            'results' => $results,
            'weightsTotal' => $weightsTotal,
            'warnings' => $warnings,
            'canRelease' => empty($warnings) && !empty($results),
        ]);
    }

    public function recompute($categoryId) {
        $user = Auth::requireRole('tabulator');
        $this->assertCsrf();

        if (!CategoryModel::getById($categoryId)) {
            http_response_code(404);
            die('Category not found.');
        }

        CategoryResults::recompute((int)$categoryId, (int)$user->id);
        $this->redirect('/tabulator/review/' . (int)$categoryId);
    }

    public function release($categoryId) {
        Auth::requireRole('tabulator');
        $this->assertCsrf();

        $category = CategoryModel::getWithProgress($categoryId);
        if (!$category) {
            http_response_code(404);
            die('Category not found.');
        }

        $blockers = $this->releaseBlockers($categoryId, $category);
        if (!empty($blockers)) {
            http_response_code(422);
            die('Results cannot be released: ' . implode(' ', $blockers));
        }

        CategoryResults::recompute((int)$categoryId, (int)Auth::user()->id);
        ResultModel::releaseCategory($categoryId, Auth::user()->id);
        $this->redirect('/tabulator');
    }

    public function hold($categoryId) {
        Auth::requireRole('tabulator');
        $this->assertCsrf();

        $category = CategoryModel::getById($categoryId);
        if (!$category) {
            http_response_code(404);
            die('Category not found.');
        }

        ResultModel::unreleaseCategory((int)$categoryId);
        $this->redirect('/tabulator');
    }

    public function scores() {
        Auth::requireRole('tabulator');
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        if ($categoryId !== null && $categoryId <= 0) {
            $categoryId = null;
        }

        $this->view('tabulator/scores', [
            'submissions' => ScoreModel::getLockedSubmissions($categoryId),
            'categories' => CategoryModel::getAll(),
            'selectedCategoryId' => $categoryId,
        ]);
    }

    private function assertCsrf() {
        if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
            http_response_code(419);
            die('Invalid security token. Please refresh and try again.');
        }
    }

    private function releaseBlockers($categoryId, $category): array {
        $blockers = [];
        if ((int)($category->progress_percent ?? 0) < 100) {
            $blockers[] = 'not all judges have completed scoring.';
        }

        $criteria = ScoreModel::getCriteriaByCategory($categoryId);
        if (empty($criteria)) {
            $blockers[] = 'criteria are missing.';
        }

        $contestants = ScoreModel::getContestantsByCategory($categoryId);
        if (empty($contestants)) {
            $blockers[] = 'contestants are missing.';
        }

        if ($category->computation_type === 'weighted_criteria') {
            $weightTotal = 0.0;
            foreach ($criteria as $criterion) {
                $weightTotal += (float)$criterion->weight_percent;
            }
            if (abs($weightTotal - 100.0) > 0.0001) {
                $blockers[] = 'weighted criteria must total 100%.';
            }
        }

        $results = ResultModel::getByCategory($categoryId);
        if (empty($results)) {
            $blockers[] = 'results have not been computed.';
        }

        $rankCounts = [];
        foreach ($results as $result) {
            $rank = (int)$result->final_rank;
            $rankCounts[$rank] = ($rankCounts[$rank] ?? 0) + 1;
        }
        if (!empty(array_filter($rankCounts, static function ($count) {
            return $count > 1;
        }))) {
            $blockers[] = 'unresolved ties must be handled.';
        }

        return $blockers;
    }
}
