<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ScoreModel extends Model {

    public static function getCategoryDetails($categoryId) {
        $sql = "SELECT c.*, e.id as event_id FROM categories c 
                JOIN events e ON c.event_id = e.id 
                WHERE c.id = ?";
        return self::fetchOne($sql, [$categoryId]);
    }

    public static function getCriteriaByCategory($categoryId) {
        return self::fetchAll("SELECT * FROM criteria WHERE category_id = ?", [$categoryId]);
    }

    public static function getContestantsByCategory($categoryId) {
        return self::fetchAll("SELECT * FROM contestants WHERE category_id = ?", [$categoryId]);
    }

    public static function getContestantByCategory($contestantId, $categoryId) {
        return self::fetchOne(
            "SELECT * FROM contestants WHERE id = ? AND category_id = ?",
            [$contestantId, $categoryId]
        );
    }

    public static function getJudgeAssignment($userId, $categoryId) {
        return self::fetchOne(
            "SELECT * FROM judge_assignments WHERE user_id = ? AND category_id = ?",
            [$userId, $categoryId]
        );
    }

    public static function saveScore($judgeAssignmentId, $contestantId, $criteriaId, $score) {
        $db = self::getDB();

        // Keep the assignment, contestant, and criterion inside one category.
        $contextStatement = $db->prepare(
            "SELECT ja.category_id AS assignment_category_id,
                    c.category_id AS contestant_category_id,
                    cr.category_id AS criteria_category_id
             FROM judge_assignments ja
             JOIN contestants c ON c.id = ?
             JOIN criteria cr ON cr.id = ?
             WHERE ja.id = ?"
        );
        $contextStatement->execute([$contestantId, $criteriaId, $judgeAssignmentId]);
        $context = $contextStatement->fetch();

        if (!$context
            || (int)$context->assignment_category_id !== (int)$context->contestant_category_id
            || (int)$context->assignment_category_id !== (int)$context->criteria_category_id
        ) {
            throw new \InvalidArgumentException('Assignment, contestant, and criterion must belong to the same category.');
        }

        $stmt = $db->prepare("SELECT id, is_locked FROM scores 
                              WHERE judge_assignment_id = ? AND contestant_id = ? AND criteria_id = ?");
        $stmt->execute([$judgeAssignmentId, $contestantId, $criteriaId]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing->is_locked) {
                throw new \Exception("Score is locked and cannot be updated.");
            }
            $stmt = $db->prepare("UPDATE scores SET score_value = ?, submitted_at = CURRENT_TIMESTAMP 
                                  WHERE id = ?");
            return $stmt->execute([$score, $existing->id]);
        } else {
            $stmt = $db->prepare("INSERT INTO scores (judge_assignment_id, contestant_id, criteria_id, score_value, is_locked) 
                                  VALUES (?, ?, ?, ?, FALSE)");
            return $stmt->execute([$judgeAssignmentId, $contestantId, $criteriaId, $score]);
        }
    }

    public static function lockScoresForContestant($judgeAssignmentId, $contestantId) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE scores SET is_locked = TRUE 
                              WHERE judge_assignment_id = ? AND contestant_id = ?");
        return $stmt->execute([$judgeAssignmentId, $contestantId]);
    }

    public static function unlockScoresForContestant($judgeAssignmentId, $contestantId, $unlockedBy) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE scores
             SET is_locked = FALSE, unlocked_by = ?
             WHERE judge_assignment_id = ? AND contestant_id = ? AND is_locked = TRUE"
        );
        return $stmt->execute([$unlockedBy, $judgeAssignmentId, $contestantId]);
    }

    public static function getLockedSubmissions(?int $categoryId = null) {
        $sql = "
            SELECT
                ja.id AS assignment_id,
                ja.category_id,
                s.contestant_id,
                u.full_name AS judge_name,
                u.email AS judge_email,
                ct.name AS contestant_name,
                cat.name AS category_name,
                e.name AS event_name,
                COUNT(s.id) AS score_count,
                MAX(s.submitted_at) AS last_submitted_at
            FROM scores s
            JOIN judge_assignments ja ON s.judge_assignment_id = ja.id
            JOIN users u ON ja.user_id = u.id
            JOIN contestants ct ON s.contestant_id = ct.id
            JOIN categories cat ON ja.category_id = cat.id
            JOIN events e ON cat.event_id = e.id
            WHERE s.is_locked = TRUE
        ";

        $params = [];
        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND ja.category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= "
            GROUP BY ja.id, s.contestant_id, ja.category_id, u.full_name, u.email,
                     ct.name, cat.name, e.name
            ORDER BY e.event_date DESC, cat.name ASC, u.full_name ASC, ct.name ASC
        ";

        return self::fetchAll($sql, $params);
    }

    public static function getScoresByAssignment($judgeAssignmentId) {
        return self::fetchAll("SELECT * FROM scores WHERE judge_assignment_id = ?", [$judgeAssignmentId]);
    }

    public static function getScoresForCategory($categoryId) {
        $sql = "SELECT s.*, ja.user_id as judge_id, c.id as contestant_id 
                FROM scores s
                JOIN judge_assignments ja ON s.judge_assignment_id = ja.id
                JOIN contestants c ON s.contestant_id = c.id
                WHERE c.category_id = ? AND s.is_locked = TRUE";
        return self::fetchAll($sql, [$categoryId]);
    }

    public static function getLockedScoresWithDetails($categoryId) {
        return self::fetchAll(
            "SELECT s.*, ja.category_id, ja.user_id AS judge_id,
                    u.full_name AS judge_name,
                    c.name AS contestant_name,
                    cr.name AS criterion_name,
                    cr.weight_percent
             FROM scores s
             JOIN judge_assignments ja ON s.judge_assignment_id = ja.id
             JOIN users u ON ja.user_id = u.id
             JOIN contestants c ON s.contestant_id = c.id
             JOIN criteria cr ON s.criteria_id = cr.id
             WHERE ja.category_id = ? AND s.is_locked = TRUE
             ORDER BY c.name ASC, u.full_name ASC, cr.name ASC",
            [$categoryId]
        );
    }

    public static function saveResult($categoryId, $contestantId, $score, $rank) {
        $db = self::getDB();
        $stmt = $db->prepare("INSERT INTO results (category_id, contestant_id, final_score, final_rank) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE final_score = ?, final_rank = ?");
        return $stmt->execute([$categoryId, $contestantId, $score, $rank, $score, $rank]);
    }
}
