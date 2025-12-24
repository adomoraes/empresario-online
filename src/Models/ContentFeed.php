<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class ContentFeed
{

    /**
     * Busca o Feed Personalizado com Paginação.
     */
    public static function getPersonalizedFeed(int $userId, int $page = 1, int $limit = 10): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;

        // Query Union (User Interests)
        $sql = "
            SELECT * FROM (
                SELECT 
                    a.id, a.title, LEFT(a.content, 150) as excerpt, a.created_at as date, 
                    'article' as content_type, c.name as category_name, c.slug as category_slug
                FROM articles a
                JOIN categories c ON a.category_id = c.id
                JOIN user_interests ui ON ui.category_id = c.id
                WHERE ui.user_id = :uid1

                UNION ALL

                SELECT 
                    i.id, i.title, i.excerpt, i.published_at as date, 
                    'interview' as content_type, c.name as category_name, c.slug as category_slug
                FROM interviews i
                JOIN interview_categories ic ON i.id = ic.interview_id
                JOIN categories c ON ic.category_id = c.id
                JOIN user_interests ui ON ui.category_id = c.id
                WHERE ui.user_id = :uid2
            ) as feed
            ORDER BY date DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        // BindValue é necessário para LIMIT e OFFSET funcionarem como inteiros no PDO MySQL
        $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um Feed Geral (Últimas novidades) para quando não há personalização.
     */
    public static function getGeneralFeed(int $page = 1, int $limit = 10): array
    {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $limit;

        // Mesma lógica, mas SEM o JOIN com user_interests
        $sql = "
            SELECT * FROM (
                SELECT 
                    a.id, a.title, LEFT(a.content, 150) as excerpt, a.created_at as date, 
                    'article' as content_type, c.name as category_name, c.slug as category_slug
                FROM articles a
                LEFT JOIN categories c ON a.category_id = c.id

                UNION ALL

                SELECT 
                    i.id, i.title, i.excerpt, i.published_at as date, 
                    'interview' as content_type, 'Múltiplas' as category_name, '' as category_slug
                FROM interviews i
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
}
