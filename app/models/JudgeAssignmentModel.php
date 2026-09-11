<?php
namespace App\Models;

use App\Core\Model;

class JudgeAssignmentModel extends Model {

    public static function getAllWithDetails() {
        return self::fetchAll(
            "SELECT ja.*, u.full_name AS judge_name, u.email AS judge_email,
                    c.name AS category_name, e.name AS event_name
             FROM judge_assignments ja
             JOIN users u ON ja.user_id = u.id
             JOIN categories c ON ja.category_id = c.id
             JOIN events e ON c.event_id = e.id
             ORDER BY e.event_date DESC, c.name, u.full_name"
        );
    }

    public static function getByCategory($categoryId) {
        return self::fetchAll(
            "SELECT ja.*, u.full_name AS judge_name, u.email AS judge_email
             FROM judge_assignments ja
             JOIN users u ON ja.user_id = u.id
             WHERE ja.category_id = ?
             ORDER BY u.full_name",
            [$categoryId]
        );
    }

    public static function getByEvent($eventId): array {
        return self::fetchAll(
            "SELECT ja.*, u.full_name AS judge_name, u.email AS judge_email,
                    c.name AS category_name
             FROM judge_assignments ja
             JOIN users u ON ja.user_id = u.id
             JOIN categories c ON ja.category_id = c.id
             WHERE c.event_id = ?
             ORDER BY c.name ASC, u.full_name ASC",
            [$eventId]
        );
    }

    public static function getByUser($userId) {
        return self::fetchAll(
            "SELECT ja.*, c.name AS category_name, c.computation_type, e.name AS event_name, e.event_date
                    ,(SELECT COUNT(*)
                      FROM contestants contestant
                      WHERE contestant.category_id = ja.category_id
                        AND (SELECT COUNT(*)
                             FROM scores score
                             WHERE score.judge_assignment_id = ja.id
                               AND score.contestant_id = contestant.id
                               AND score.is_locked = 1)
                            = (SELECT COUNT(*) FROM criteria criterion WHERE criterion.category_id = ja.category_id)
                     ) AS contestants_submitted
                    ,(SELECT COUNT(*) FROM contestants contestant WHERE contestant.category_id = ja.category_id) AS contestants_total
             FROM judge_assignments ja
             JOIN categories c ON ja.category_id = c.id
             JOIN events e ON c.event_id = e.id
             WHERE ja.user_id = ?
             ORDER BY e.event_date DESC, c.name",
            [$userId]
        );
    }

    public static function getById($id) {
        return self::fetchOne(
            "SELECT ja.*, u.full_name AS judge_name, c.name AS category_name, e.name AS event_name
             FROM judge_assignments ja
             JOIN users u ON ja.user_id = u.id
             JOIN categories c ON ja.category_id = c.id
             JOIN events e ON c.event_id = e.id
             WHERE ja.id = ?",
            [$id]
        );
    }

    public static function countByCategory($categoryId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM judge_assignments WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $row = $stmt->fetch();
        return $row ? (int)$row->total : 0;
    }

    public static function assign($userId, $categoryId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO judge_assignments (user_id, category_id)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE assigned_at = CURRENT_TIMESTAMP"
        );
        return $stmt->execute([$userId, $categoryId]);
    }

    public static function remove($userId, $categoryId) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM judge_assignments WHERE user_id = ? AND category_id = ?");
        return $stmt->execute([$userId, $categoryId]);
    }

    public static function removeById($id) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM judge_assignments WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getCategoryIdsForJudge($userId) {
        $rows = self::fetchAll("SELECT category_id FROM judge_assignments WHERE user_id = ?", [$userId]);
        return array_map(static function ($row) {
            return (int)$row->category_id;
        }, $rows);
    }
}
