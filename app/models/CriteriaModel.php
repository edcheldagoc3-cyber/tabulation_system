<?php
namespace App\Models;

use App\Core\Model;

class CriteriaModel extends Model {

    public static function getAllWithDetails() {
        return self::fetchAll(
            "SELECT ct.*, c.name AS category_name, e.name AS event_name
             FROM criteria ct
             JOIN categories c ON ct.category_id = c.id
             JOIN events e ON c.event_id = e.id
             ORDER BY e.event_date DESC, c.name ASC, ct.name ASC"
        );
    }

    public static function getById($id) {
        return self::fetchOne("SELECT * FROM criteria WHERE id = ?", [$id]);
    }

    public static function getByCategory($categoryId) {
        return self::fetchAll(
            "SELECT * FROM criteria WHERE category_id = ? ORDER BY name ASC",
            [$categoryId]
        );
    }

    public static function getByEvent($eventId): array {
        return self::fetchAll(
            "SELECT ct.*, c.name AS category_name
             FROM criteria ct
             JOIN categories c ON ct.category_id = c.id
             WHERE c.event_id = ?
             ORDER BY c.name ASC, ct.name ASC",
            [$eventId]
        );
    }

    public static function getTotalWeightPercent(int $categoryId, ?int $excludeCriterionId = null): float {
        $criteria = self::getByCategory($categoryId);
        $total = 0.0;
        foreach ($criteria as $criterion) {
            if ($excludeCriterionId !== null && (int)$criterion->id === $excludeCriterionId) {
                continue;
            }
            $total += (float)$criterion->weight_percent;
        }

        return $total;
    }

    public static function create($data) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO criteria
             (category_id, name, weight_percent, min_score, max_score, created_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['category_id'],
            trim($data['name']),
            $data['weight_percent'],
            $data['min_score'],
            $data['max_score'],
            $data['created_by'] ?? null,
        ]);
    }

    public static function update($id, $data) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE criteria
             SET category_id = ?, name = ?, weight_percent = ?, min_score = ?, max_score = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['category_id'],
            trim($data['name']),
            $data['weight_percent'],
            $data['min_score'],
            $data['max_score'],
            $id,
        ]);
    }

    public static function delete($id) {
        $db = self::getDB();
        $db->prepare("UPDATE categories SET tiebreak_criterion_id = NULL WHERE tiebreak_criterion_id = ?")
            ->execute([$id]);
        $stmt = $db->prepare("DELETE FROM criteria WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
