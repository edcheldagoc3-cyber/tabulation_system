<?php
namespace App\Models;

use App\Core\Model;

class ReportModel extends Model {

    /**
     * Get all released results for a category with judge details
     */
    public static function getCategoryReport($categoryId) {
        $sql = "
            SELECT 
                r.*,
                c.name as contestant_name,
                c.type as contestant_type,
                cat.name as category_name,
                cat.computation_type,
                e.name as event_name,
                e.event_date,
                r.released_at,
                release_user.full_name as released_by_name,
                (SELECT GROUP_CONCAT(CONCAT(u.full_name, ':', s.score_value) SEPARATOR '|')
                 FROM scores s
                 JOIN judge_assignments ja ON s.judge_assignment_id = ja.id
                 JOIN users u ON ja.user_id = u.id
                 WHERE s.contestant_id = c.id AND s.is_locked = 1
                ) as judge_scores
            FROM results r
            JOIN contestants c ON r.contestant_id = c.id
            JOIN categories cat ON r.category_id = cat.id
            JOIN events e ON cat.event_id = e.id
            LEFT JOIN users release_user ON r.released_by = release_user.id
            WHERE r.category_id = ? AND r.is_released = 1
            ORDER BY r.final_rank ASC
        ";
        return self::fetchAll($sql, [$categoryId]);
    }

    /**
     * Get all released categories for an event
     */
    public static function getEventReport($eventId) {
        $sql = "
            SELECT 
                e.id as event_id,
                e.name as event_name,
                e.event_date,
                cat.id as category_id,
                cat.name as category_name,
                cat.computation_type,
                (SELECT COUNT(*) FROM results WHERE category_id = cat.id AND is_released = 1) as results_count,
                (SELECT CONCAT(c.name, ' (', r.final_score, ')') 
                 FROM results r 
                 JOIN contestants c ON r.contestant_id = c.id 
                 WHERE r.category_id = cat.id AND r.final_rank = 1 AND r.is_released = 1
                 LIMIT 1) as winner
            FROM events e
            JOIN categories cat ON e.id = cat.event_id
            WHERE e.id = ?
            ORDER BY cat.name ASC
        ";
        return self::fetchAll($sql, [$eventId]);
    }

    /**
     * Get judge scores for a specific contestant
     */
    public static function getContestantScoreSheet($contestantId, $categoryId) {
        $sql = "
            SELECT 
                c.name as contestant_name,
                cat.name as category_name,
                e.name as event_name,
                cr.name as criterion_name,
                cr.weight_percent,
                s.score_value,
                u.full_name as judge_name,
                s.submitted_at
            FROM scores s
            JOIN contestants c ON s.contestant_id = c.id
            JOIN categories cat ON c.category_id = cat.id
            JOIN events e ON cat.event_id = e.id
            JOIN criteria cr ON s.criteria_id = cr.id
            JOIN judge_assignments ja ON s.judge_assignment_id = ja.id
            JOIN users u ON ja.user_id = u.id
            WHERE s.contestant_id = ? AND cat.id = ? AND s.is_locked = 1
            ORDER BY u.full_name, cr.name
        ";
        return self::fetchAll($sql, [$contestantId, $categoryId]);
    }

    /**
     * Get every locked score for an official category export.
     */
    public static function getCategoryScoreBreakdown($categoryId) {
        return self::fetchAll(" 
            SELECT
                c.name as contestant_name,
                c.type as contestant_type,
                cat.name as category_name,
                e.name as event_name,
                e.event_date,
                cr.name as criterion_name,
                cr.weight_percent,
                s.score_value,
                s.submitted_at,
                u.full_name as judge_name,
                r.final_rank,
                r.final_score,
                r.released_at,
                release_user.full_name as released_by_name
            FROM scores s
            JOIN contestants c ON s.contestant_id = c.id
            JOIN categories cat ON c.category_id = cat.id
            JOIN events e ON cat.event_id = e.id
            JOIN criteria cr ON s.criteria_id = cr.id
            JOIN judge_assignments ja ON s.judge_assignment_id = ja.id
            JOIN users u ON ja.user_id = u.id
            JOIN results r ON r.category_id = cat.id AND r.contestant_id = c.id AND r.is_released = 1
            LEFT JOIN users release_user ON r.released_by = release_user.id
            WHERE cat.id = ? AND s.is_locked = 1
            ORDER BY r.final_rank ASC, c.name ASC, u.full_name ASC, cr.name ASC
        ", [$categoryId]);
    }

    /**
     * Get all released categories (for dropdowns)
     */
    public static function getReleasedCategories() {
        return self::fetchAll("
            SELECT DISTINCT cat.id, cat.name, e.name as event_name 
            FROM categories cat
            JOIN events e ON cat.event_id = e.id
            JOIN results r ON r.category_id = cat.id
            WHERE r.is_released = 1
            ORDER BY e.event_date DESC, cat.name
        ");
    }

    /**
     * Get all events with released results
     */
    public static function getEventsWithResults() {
        return self::fetchAll("
            SELECT e.*, 
                (SELECT COUNT(*) FROM categories cat 
                 JOIN results r ON r.category_id = cat.id 
                 WHERE cat.event_id = e.id AND r.is_released = 1) as released_count
            FROM events e
            WHERE EXISTS (
                SELECT 1 FROM categories cat 
                JOIN results r ON r.category_id = cat.id 
                WHERE cat.event_id = e.id AND r.is_released = 1
            )
            ORDER BY e.event_date DESC
        ");
    }
}
