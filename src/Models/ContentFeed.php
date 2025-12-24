<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class ContentFeed
{

    /**
     * Busca conteúdo baseado numa lista de Categorias (Array de IDs).
     * Ex: [1, 2, 5] (Vindos de interesses explícitos ou histórico)
     */
    public static function getFeedByCategories(array $categoryIds, int $page = 1, int $limit = 10): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;

        // Transformar array [1, 2] em string "1,2" para o SQL IN
        $idsStr = implode(',', array_map('intval', $categoryIds));

        $sql = "
            SELECT * FROM (
                SELECT 
                    a.id, a.title, LEFT(a.content, 150) as excerpt, a.created_at as date, 
                    'article' as content_type, c.name as category_name, c.slug as category_slug
                FROM articles a
                JOIN categories c ON a.category_id = c.id
                WHERE a.category_id IN ($idsStr)

                UNION ALL

                SELECT 
                    i.id, i.title, i.excerpt, i.published_at as date, 
                    'interview' as content_type, c.name as category_name, c.slug as category_slug
                FROM interviews i
                JOIN interview_categories ic ON i.id = ic.interview_id
                JOIN categories c ON ic.category_id = c.id
                WHERE ic.category_id IN ($idsStr)
            ) as feed
            ORDER BY date DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mantém o getGeneralFeed() igual ao passo anterior...
    public static function getGeneralFeed(int $page = 1, int $limit = 10): array
    {
        // ... (código igual ao anterior) ...
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM (
                SELECT a.id, a.title, LEFT(a.content, 150) as excerpt, a.created_at as date, 'article' as content_type, c.name as cat_name, c.slug as cat_slug 
                FROM articles a LEFT JOIN categories c ON a.category_id = c.id
                UNION ALL
                SELECT i.id, i.title, i.excerpt, i.published_at as date, 'interview' as content_type, 'Várias', '' 
                FROM interviews i
            ) as feed ORDER BY date DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
