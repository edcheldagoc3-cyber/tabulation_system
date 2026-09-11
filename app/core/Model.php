<?php
namespace App\Core;

use Config\Database;
use PDO;

class Model {
    protected static function getDB(): PDO {
        return Database::getConnection();
    }

    protected static function fetchOne($sql, $params = []) {
        $stmt = self::getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    protected static function fetchAll($sql, $params = []) {
        $stmt = self::getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}