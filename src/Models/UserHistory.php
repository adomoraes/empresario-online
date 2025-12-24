<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class UserHistory
{

    /**
     * Regista que o user viu um conteúdo.
     */
    public static function record(int $userId, int $categoryId, string $type, int $contentId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO user_history (user_id, category_id, content_type, content_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $categoryId, $type, $contentId]);
    }

    /**
     * O ALGORITMO: Retorna IDs das categorias mais visitadas.
     * Regra: Top 5 categorias com mais visitas nos últimos 30 dias.
     */
    public static function getImplicitInterestCategories(int $userId): array
    {
        $pdo = Database::getConnection();

        $sql = "SELECT category_id
                FROM user_history
                WHERE user_id = ? 
                AND accessed_at > NOW() - INTERVAL 30 DAY
                GROUP BY category_id
                ORDER BY COUNT(*) DESC
                LIMIT 5";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Retorna array simples: [1, 5, 8]
    }
}
