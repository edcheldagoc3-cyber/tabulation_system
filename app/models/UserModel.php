<?php
namespace App\Models;

use App\Core\Model;

class UserModel extends Model {

    /**
     * Find a user by their email address (for login).
     */
    public static function findByEmail($email) {
        return self::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    }

    /**
     * Get a user by ID.
     */
    public static function getById($id) {
        return self::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    /**
     * Count users by role (e.g., 'admin', 'tabulator', 'judge').
     */
    public static function countByRole($role) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = ?");
        $stmt->execute([$role]);
        $result = $stmt->fetch();
        return $result ? (int)$result->total : 0;
    }

    /**
     * Get all users with a specific role.
     */
    public static function getByRole($role) {
        return self::fetchAll("SELECT * FROM users WHERE role = ? ORDER BY full_name", [$role]);
    }

    /**
     * Get all judges (alias for getByRole('judge')).
     */
    public static function getAllJudges() {
        return self::getByRole('judge');
    }

    /**
     * Get all tabulators.
     */
    public static function getAllTabulators() {
        return self::getByRole('tabulator');
    }

    /**
     * Get all admins.
     */
    public static function getAllAdmins() {
        return self::getByRole('admin');
    }
    public static function getAll() {
    return self::fetchAll("SELECT * FROM users ORDER BY full_name");
}

    /**
     * Create a new user (for registration / admin creation).
     */
    public static function create($data) {
        $db = self::getDB();
        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, password_hash, role) 
            VALUES (?, ?, ?, ?)
        ");
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        return $stmt->execute([
            $data['full_name'],
            $data['email'],
            $passwordHash,
            $data['role'] ?? 'judge'
        ]);
    }

    /**
     * Update a user's details.
     */
    public static function update($id, $data) {
        $db = self::getDB();
        $fields = [];
        $params = [];
        if (isset($data['full_name'])) {
            $fields[] = "full_name = ?";
            $params[] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $params[] = $data['role'];
        }
        if (isset($data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (empty($fields)) return true;
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a user (cascades to judge_assignments).
     */
    public static function delete($id) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Check if a user is assigned as a judge to a specific category.
     */
    public static function isAssignedToCategory($userId, $categoryId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT id FROM judge_assignments WHERE user_id = ? AND category_id = ?");
        $stmt->execute([$userId, $categoryId]);
        return $stmt->fetch() !== false;
    }
}