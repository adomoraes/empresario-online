<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class UserFavorite
{
    // Adiciona um favorito (ignora se já existir)
    public static function add(int $userId, string $type, int $contentId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_favorites (user_id, content_type, content_id) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $type, $contentId]);
    }

    // Remove um favorito
    public static function remove(int $userId, string $type, int $contentId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND content_type = ? AND content_id = ?");
        return $stmt->execute([$userId, $type, $contentId]);
    }

    // Lista todos os favoritos do utilizador com detalhes (Título, Data, etc.)
    public static function all(int $userId): array
    {
        $pdo = Database::getConnection();

        // Query com UNION para buscar Artigos e Entrevistas numa lista única
        $sql = "
            SELECT 
                'article' as type,
                a.id, 
                a.title, 
                LEFT(a.content, 200) as excerpt, 
                a.created_at as date,
                c.name as category_name
            FROM user_favorites f
            JOIN articles a ON f.content_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE f.user_id = ? AND f.content_type = 'article'

            UNION

            SELECT 
                'interview' as type,
                i.id, 
                i.title, 
                LEFT(i.content, 200) as excerpt, 
                i.published_at as date,
                'Entrevista' as category_name
            FROM user_favorites f
            JOIN interviews i ON f.content_id = i.id
            WHERE f.user_id = ? AND f.content_type = 'interview'

            ORDER BY date DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
