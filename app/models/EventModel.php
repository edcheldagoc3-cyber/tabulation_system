<?php
namespace App\Models;

use App\Core\Model;

class EventModel extends Model {

    public static function getAll() {
        return self::fetchAll("SELECT * FROM events ORDER BY event_date DESC");
    }

    public static function getById($id) {
        return self::fetchOne("SELECT * FROM events WHERE id = ?", [$id]);
    }

    public static function countByStatus($status) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM events WHERE status = ?");
        $stmt->execute([$status]);
        $row = $stmt->fetch();
        return $row ? (int)$row->total : 0;
    }

    public static function getLive() {
        return self::fetchAll("SELECT * FROM events WHERE status = 'live' ORDER BY event_date DESC");
    }

    public static function create($data) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO events (name, event_date, description, status, created_by)
             VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            trim($data['name']),
            $data['event_date'],
            trim($data['description'] ?? ''),
            $data['status'] ?? 'draft',
            $data['created_by'] ?? null,
        ]);
    }

    public static function update($id, $data) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE events
             SET name = ?, event_date = ?, description = ?, status = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            trim($data['name']),
            $data['event_date'],
            trim($data['description'] ?? ''),
            $data['status'] ?? 'draft',
            $id,
        ]);
    }

    public static function delete($id) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function duplicateStructure(int $sourceEventId, array $data, int $userId): int {
        $source = self::getById($sourceEventId);
        if (!$source) {
            throw new \InvalidArgumentException('Source event not found.');
        }

        $db = self::getDB();
        $db->beginTransaction();
        try {
            $eventStmt = $db->prepare("INSERT INTO events (name, event_date, description, status, created_by) VALUES (?, ?, ?, 'draft', ?)");
            $eventStmt->execute([$data['name'], $data['event_date'], $data['description'], $userId]);
            $newEventId = (int)$db->lastInsertId();
            $categories = self::fetchAll("SELECT * FROM categories WHERE event_id = ? ORDER BY id", [$sourceEventId]);
            $categoryStmt = $db->prepare("INSERT INTO categories (event_id, name, computation_type, tiebreak_rule, tiebreak_criterion_id, created_by) VALUES (?, ?, ?, ?, NULL, ?)");
            $criteriaStmt = $db->prepare("INSERT INTO criteria (category_id, name, weight_percent, min_score, max_score, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $tieStmt = $db->prepare("UPDATE categories SET tiebreak_criterion_id = ? WHERE id = ?");

            foreach ($categories as $category) {
                $categoryStmt->execute([$newEventId, $category->name, $category->computation_type, $category->tiebreak_rule, $userId]);
                $newCategoryId = (int)$db->lastInsertId();
                $criteriaMap = [];
                foreach (self::fetchAll("SELECT * FROM criteria WHERE category_id = ? ORDER BY id", [$category->id]) as $criterion) {
                    $criteriaStmt->execute([$newCategoryId, $criterion->name, $criterion->weight_percent, $criterion->min_score, $criterion->max_score, $userId]);
                    $criteriaMap[(int)$criterion->id] = (int)$db->lastInsertId();
                }
                if ($category->tiebreak_criterion_id && isset($criteriaMap[(int)$category->tiebreak_criterion_id])) {
                    $tieStmt->execute([$criteriaMap[(int)$category->tiebreak_criterion_id], $newCategoryId]);
                }
            }
            $db->commit();
            return $newEventId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function getWithCounts() {
        $sql = "
            SELECT
                e.*,
                (SELECT COUNT(*) FROM categories c WHERE c.event_id = e.id) AS categories_total,
                (SELECT COUNT(*) FROM categories c
                    JOIN results r ON r.category_id = c.id
                    WHERE c.event_id = e.id AND r.is_released = 1
                 ) AS released_categories
            FROM events e
            ORDER BY e.event_date DESC, e.id DESC
        ";
        return self::fetchAll($sql);
    }
}
