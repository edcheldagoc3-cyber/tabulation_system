<?php
namespace App\Models;

use App\Core\Model;

class ResultModel extends Model {

    public static function getByCategory($categoryId) {
        return self::fetchAll(
            "SELECT r.*, c.name AS contestant_name
             FROM results r
             JOIN contestants c ON r.contestant_id = c.id
             WHERE r.category_id = ?
             ORDER BY r.final_rank ASC, c.name ASC",
            [$categoryId]
        );
    }

    public static function getReleasedByCategory($categoryId) {
        return self::fetchAll(
            "SELECT r.*, c.name AS contestant_name
             FROM results r
             JOIN contestants c ON r.contestant_id = c.id
             WHERE r.category_id = ? AND r.is_released = 1
             ORDER BY r.final_rank ASC, c.name ASC",
            [$categoryId]
        );
    }

    public static function getReleasedByEvent($eventId) {
        return self::fetchAll(
            "SELECT r.*, c.name AS contestant_name, cat.name AS category_name, e.name AS event_name
             FROM results r
             JOIN contestants c ON r.contestant_id = c.id
             JOIN categories cat ON r.category_id = cat.id
             JOIN events e ON cat.event_id = e.id
             WHERE cat.event_id = ? AND r.is_released = 1
             ORDER BY cat.name, r.final_rank ASC",
            [$eventId]
        );
    }

    public static function getReleasedCategoriesByEvent($eventId) {
        return self::fetchAll(
            "SELECT DISTINCT cat.*
             FROM categories cat
             JOIN results r ON r.category_id = cat.id
             WHERE cat.event_id = ? AND r.is_released = 1
             ORDER BY cat.name",
            [$eventId]
        );
    }

    public static function saveResult($categoryId, $contestantId, $score, $rank) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO results (category_id, contestant_id, final_score, final_rank)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE final_score = VALUES(final_score), final_rank = VALUES(final_rank)"
        );
        return $stmt->execute([$categoryId, $contestantId, $score, $rank]);
    }

    public static function releaseCategory($categoryId, $releasedBy) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE results
             SET is_released = 1, released_by = ?, released_at = NOW()
             WHERE category_id = ?"
        );
        return $stmt->execute([$releasedBy, $categoryId]);
    }

    public static function unreleaseCategory($categoryId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE results
             SET is_released = 0, released_by = NULL, released_at = NULL
             WHERE category_id = ?"
        );
        return $stmt->execute([$categoryId]);
    }

    public static function getReleaseStateByCategory($categoryId) {
        return self::fetchOne(
            "SELECT
                COUNT(*) AS total_results,
                SUM(CASE WHEN is_released = 1 THEN 1 ELSE 0 END) AS released_results
             FROM results
             WHERE category_id = ?",
            [$categoryId]
        );
    }
}
