<?php
namespace App\Models;

use App\Core\Model;

class CategoryModel extends Model {

    public static function getAll() {
        return self::fetchAll(
            "SELECT c.*, e.name AS event_name, e.event_date
             FROM categories c
             JOIN events e ON c.event_id = e.id
             ORDER BY e.event_date DESC, c.name ASC"
        );
    }

    public static function getById($id) {
        return self::fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
    }

    public static function getByEvent($eventId) {
        return self::fetchAll(
            "SELECT * FROM categories WHERE event_id = ? ORDER BY name ASC",
            [$eventId]
        );
    }

    public static function getByEventWithProgress(int $eventId): array {
        $sql = "
            SELECT
                c.*,
                e.name AS event_name,
                e.status AS event_status,
                e.event_date,
                (SELECT COUNT(*) FROM judge_assignments ja WHERE ja.category_id = c.id) AS judges_total,
                (SELECT COUNT(*)
                 FROM judge_assignments ja
                 WHERE ja.category_id = c.id
                   AND (SELECT COUNT(*) FROM contestants co WHERE co.category_id = c.id) > 0
                   AND (SELECT COUNT(*) FROM criteria cr WHERE cr.category_id = c.id) > 0
                   AND (SELECT COUNT(*)
                        FROM scores s
                        JOIN contestants co ON co.id = s.contestant_id AND co.category_id = c.id
                        JOIN criteria cr ON cr.id = s.criteria_id AND cr.category_id = c.id
                        WHERE s.judge_assignment_id = ja.id AND s.is_locked = 1)
                       = (SELECT COUNT(*) FROM contestants co WHERE co.category_id = c.id)
                         * (SELECT COUNT(*) FROM criteria cr WHERE cr.category_id = c.id)) AS judges_submitted,
                (SELECT COUNT(*) FROM contestants WHERE category_id = c.id) AS contestants_total,
                (SELECT COUNT(*) FROM criteria WHERE category_id = c.id) AS criteria_total,
                (SELECT COUNT(*) FROM results WHERE category_id = c.id AND is_released = 1) AS results_released
            FROM categories c
            JOIN events e ON c.event_id = e.id
            WHERE c.event_id = ?
            ORDER BY c.name ASC
        ";

        $results = self::fetchAll($sql, [$eventId]);
        foreach ($results as $category) {
            $total = (int)($category->judges_total ?? 0);
            $submitted = (int)($category->judges_submitted ?? 0);
            $category->progress_percent = $total > 0 ? round(($submitted / $total) * 100) : 0;
            $category->status = $category->progress_percent >= 100 ? 'complete' : 'in progress';
            $category->is_released = ((int)($category->results_released ?? 0)) > 0;
        }

        return $results;
    }

    public static function countByEvent($eventId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM categories WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $row = $stmt->fetch();
        return $row ? (int)$row->total : 0;
    }

    public static function create($data) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO categories
             (event_id, name, computation_type, tiebreak_rule, tiebreak_criterion_id, created_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $created = $stmt->execute([
            $data['event_id'],
            trim($data['name']),
            $data['computation_type'] ?? 'raw_average',
            $data['tiebreak_rule'] ?? 'manual',
            $data['tiebreak_criterion_id'] ?? null,
            $data['created_by'] ?? null,
        ]);
        return $created ? (int)$db->lastInsertId() : false;
    }

    public static function update($id, $data) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE categories
             SET event_id = ?, name = ?, computation_type = ?, tiebreak_rule = ?, tiebreak_criterion_id = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['event_id'],
            trim($data['name']),
            $data['computation_type'] ?? 'raw_average',
            $data['tiebreak_rule'] ?? 'manual',
            $data['tiebreak_criterion_id'] ?? null,
            $id,
        ]);
    }

    public static function delete($id) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getAllWithProgress() {
        $sql = "
            SELECT
                c.*,
                e.name AS event_name,
                e.status AS event_status,
                e.event_date,
                (SELECT COUNT(*) FROM judge_assignments ja WHERE ja.category_id = c.id) AS judges_total,
                (SELECT COUNT(*)
                 FROM judge_assignments ja
                 WHERE ja.category_id = c.id
                   AND (SELECT COUNT(*) FROM contestants co WHERE co.category_id = c.id) > 0
                   AND (SELECT COUNT(*) FROM criteria cr WHERE cr.category_id = c.id) > 0
                   AND (SELECT COUNT(*)
                        FROM scores s
                        JOIN contestants co ON co.id = s.contestant_id AND co.category_id = c.id
                        JOIN criteria cr ON cr.id = s.criteria_id AND cr.category_id = c.id
                        WHERE s.judge_assignment_id = ja.id AND s.is_locked = 1)
                       = (SELECT COUNT(*) FROM contestants co WHERE co.category_id = c.id)
                         * (SELECT COUNT(*) FROM criteria cr WHERE cr.category_id = c.id)) AS judges_submitted,
                (SELECT COUNT(*) FROM contestants WHERE category_id = c.id) AS contestants_total,
                (SELECT COUNT(*) FROM results WHERE category_id = c.id AND is_released = 1) AS results_released
            FROM categories c
            JOIN events e ON c.event_id = e.id
            ORDER BY e.event_date DESC, c.name ASC
        ";

        $results = self::fetchAll($sql);
        foreach ($results as $category) {
            $total = (int)($category->judges_total ?? 0);
            $submitted = (int)($category->judges_submitted ?? 0);
            $category->progress_percent = $total > 0 ? round(($submitted / $total) * 100) : 0;
            $category->status = $category->progress_percent >= 100 ? 'complete' : 'in progress';
            $category->is_released = ((int)($category->results_released ?? 0)) > 0;
        }

        return $results;
    }

    public static function getWithProgress($categoryId) {
        $sql = "
            SELECT
                c.*,
                e.name AS event_name,
                e.status AS event_status,
                e.event_date,
                (SELECT COUNT(*) FROM judge_assignments WHERE category_id = c.id) AS judges_total,
                (SELECT COUNT(*)
                 FROM judge_assignments ja
                 WHERE ja.category_id = c.id
                   AND (SELECT COUNT(*) FROM contestants co WHERE co.category_id = c.id) > 0
                   AND (SELECT COUNT(*) FROM criteria cr WHERE cr.category_id = c.id) > 0
                   AND (SELECT COUNT(*)
                        FROM scores s
                        JOIN contestants co ON co.id = s.contestant_id AND co.category_id = c.id
                        JOIN criteria cr ON cr.id = s.criteria_id AND cr.category_id = c.id
                        WHERE s.judge_assignment_id = ja.id AND s.is_locked = 1)
                       = (SELECT COUNT(*) FROM contestants co WHERE co.category_id = c.id)
                         * (SELECT COUNT(*) FROM criteria cr WHERE cr.category_id = c.id)) AS judges_submitted,
                (SELECT COUNT(*) FROM contestants WHERE category_id = c.id) AS contestants_total,
                (SELECT COUNT(*) FROM results WHERE category_id = c.id AND is_released = 1) AS results_released
            FROM categories c
            JOIN events e ON c.event_id = e.id
            WHERE c.id = ?
        ";

        $category = self::fetchOne($sql, [$categoryId]);
        if ($category) {
            $total = (int)($category->judges_total ?? 0);
            $submitted = (int)($category->judges_submitted ?? 0);
            $category->progress_percent = $total > 0 ? round(($submitted / $total) * 100) : 0;
            $category->status = $category->progress_percent >= 100 ? 'complete' : 'in progress';
            $category->is_released = ((int)($category->results_released ?? 0)) > 0;
        }

        return $category;
    }
}
