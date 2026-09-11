<?php
namespace App\Models;

use App\Core\Model;

class RubricTemplateModel extends Model {
    public static function getAll(): array {
        return self::fetchAll("SELECT t.*, (SELECT COUNT(*) FROM rubric_template_criteria c WHERE c.template_id = t.id) AS criteria_total FROM rubric_templates t ORDER BY t.template_category, t.name");
    }

    public static function getById(int $id) {
        $template = self::fetchOne("SELECT * FROM rubric_templates WHERE id = ?", [$id]);
        if ($template) $template->criteria = self::fetchAll("SELECT * FROM rubric_template_criteria WHERE template_id = ? ORDER BY id", [$id]);
        return $template;
    }

    public static function create(array $data, int $userId): int {
        $db = self::getDB();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO rubric_templates (name, template_category, description, computation_type, tiebreak_rule, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data['name'], $data['template_category'], $data['description'], $data['computation_type'], $data['tiebreak_rule'], $userId]);
            $id = (int)$db->lastInsertId();
            self::replaceCriteria($db, $id, $data['criteria']);
            $db->commit();
            return $id;
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
    }

    public static function delete(int $id): bool {
        $stmt = self::getDB()->prepare("DELETE FROM rubric_templates WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function applyToCategory(int $templateId, int $categoryId, int $userId): int {
        $template = self::getById($templateId);
        if (!$template) throw new \InvalidArgumentException('Template not found.');
        $db = self::getDB();
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE categories SET computation_type = ?, tiebreak_rule = ?, tiebreak_criterion_id = NULL WHERE id = ?")
                ->execute([$template->computation_type, $template->tiebreak_rule, $categoryId]);
            $stmt = $db->prepare("INSERT INTO criteria (category_id, name, weight_percent, min_score, max_score, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($template->criteria as $criterion) $stmt->execute([$categoryId, $criterion->name, $criterion->weight_percent, $criterion->min_score, $criterion->max_score, $userId]);
            $db->commit();
            return count($template->criteria);
        } catch (\Throwable $e) { $db->rollBack(); throw $e; }
    }

    public static function export(int $id): ?array {
        $template = self::getById($id);
        if (!$template) return null;
        return ['format' => 'ustp-tabulation-rubric/v1', 'name' => $template->name, 'category' => $template->template_category, 'description' => $template->description, 'computation_type' => $template->computation_type, 'tiebreak_rule' => $template->tiebreak_rule, 'criteria' => array_map(static fn($c) => ['name'=>$c->name, 'weight_percent'=>(float)$c->weight_percent, 'min_score'=>(float)$c->min_score, 'max_score'=>(float)$c->max_score], $template->criteria)];
    }

    private static function replaceCriteria($db, int $templateId, array $criteria): void {
        $stmt = $db->prepare("INSERT INTO rubric_template_criteria (template_id, name, weight_percent, min_score, max_score) VALUES (?, ?, ?, ?, ?)");
        foreach ($criteria as $criterion) $stmt->execute([$templateId, $criterion['name'], $criterion['weight_percent'], $criterion['min_score'], $criterion['max_score']]);
    }
}
