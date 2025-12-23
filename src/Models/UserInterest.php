<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class UserInterest
{

    /**
     * Adiciona uma categoria aos interesses do utilizador.
     * Usa INSERT IGNORE para evitar erros se já existir.
     */
    public static function add(int $userId, int $categoryId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_interests (user_id, category_id) VALUES (?, ?)");
        return $stmt->execute([$userId, $categoryId]);
    }

    /**
     * Remove uma categoria dos interesses.
     */
    public static function remove(int $userId, int $categoryId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM user_interests WHERE user_id = ? AND category_id = ?");
        return $stmt->execute([$userId, $categoryId]);
    }

    /**
     * Lista todas as categorias que o utilizador segue.
     */
    public static function listByUserId(int $userId): array
    {
        $pdo = Database::getConnection();
        // JOIN para trazer o nome da categoria, não só o ID
        $sql = "SELECT c.id, c.name, c.slug 
                FROM categories c 
                JOIN user_interests ui ON c.id = ui.category_id 
                WHERE ui.user_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
