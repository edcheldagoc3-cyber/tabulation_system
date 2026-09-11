<?php
namespace App\Core;

use App\Models\UserModel;
use Config\Database;

class Auth {
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login($email, $password) {
        self::startSession();
        $user = UserModel::findByEmail($email);
        if ($user && password_verify($password, $user->password_hash)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['role'] = $user->role;
            $_SESSION['full_name'] = $user->full_name;
            return true;
        }
        return false;
    }

    public static function logout() {
        self::startSession();
        session_destroy();
    }

    public static function user() {
        self::startSession();
        if (isset($_SESSION['user_id'])) {
            return (object) [
                'id' => $_SESSION['user_id'],
                'role' => $_SESSION['role'],
                'full_name' => $_SESSION['full_name']
            ];
        }
        return null;
    }

    public static function requireRole($role) {
        self::startSession();
        $user = self::user();
        if (!$user || $user->role !== $role) {
            http_response_code(403);
            die("Unauthorized access. Required role: $role");
        }
        return $user;
    }

    public static function requireAnyRole(array $roles) {
        self::startSession();
        $user = self::user();
        if (!$user || !in_array($user->role, $roles, true)) {
            http_response_code(403);
            die('Unauthorized access.');
        }
        return $user;
    }

    public static function judgeOwnsCategoryAssignment($categoryId) {
        self::startSession();
        $user = self::user();
        if (!$user || $user->role !== 'judge') {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM judge_assignments WHERE user_id = ? AND category_id = ?");
        $stmt->execute([$user->id, $categoryId]);
        return $stmt->fetch() !== false;
    }
}