<?php
namespace App\Models;

use App\Core\Model;

class ContestantModel extends Model {

    public static function getAll() {
        return self::fetchAll(
            "SELECT c.*, cat.name AS category_name, e.name AS event_name
             FROM contestants c
             JOIN categories cat ON c.category_id = cat.id
             JOIN events e ON cat.event_id = e.id
             ORDER BY e.event_date DESC, cat.name, c.name"
        );
    }

    public static function getByCategory($categoryId) {
        return self::fetchAll("SELECT * FROM contestants WHERE category_id = ? ORDER BY name", [$categoryId]);
    }

    public static function getByEvent($eventId): array {
        return self::fetchAll(
            "SELECT c.*, cat.name AS category_name, cat.computation_type
             FROM contestants c
             JOIN categories cat ON c.category_id = cat.id
             WHERE cat.event_id = ?
             ORDER BY cat.name ASC, c.name ASC",
            [$eventId]
        );
    }

    public static function bulkCreate(int $categoryId, array $names, string $type = 'team', ?int $createdBy = null): int {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO contestants (category_id, name, type, created_by)
             VALUES (?, ?, ?, ?)"
        );

        $inserted = 0;
        foreach ($names as $name) {
            $name = trim((string)$name);
            if ($name === '') {
                continue;
            }
            if ($stmt->execute([$categoryId, $name, $type, $createdBy])) {
                $inserted++;
            }
        }

        return $inserted;
    }

    public static function getById($id) {
        return self::fetchOne("SELECT * FROM contestants WHERE id = ?", [$id]);
    }

    public static function countByCategory($categoryId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) AS total FROM contestants WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $row = $stmt->fetch();
        return $row ? (int)$row->total : 0;
    }

    public static function create($data) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO contestants (category_id, name, type, created_by)
             VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['category_id'],
            trim($data['name']),
            $data['type'] ?? 'solo',
            $data['created_by'] ?? null,
        ]);
    }

    public static function update($id, $data) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE contestants
             SET category_id = ?, name = ?, type = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['category_id'],
            trim($data['name']),
            $data['type'] ?? 'solo',
            $id,
        ]);
    }

    public static function delete($id) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM contestants WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
