<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class ContentFeed
{

    /**
     * Busca Artigos e Entrevistas misturados, ordenados por data.
     * Filtra pelos interesses do utilizador.
     */
    public static function getPersonalizedFeed(int $userId): array
    {
        $pdo = Database::getConnection();

        // Esta Query é complexa, mas poderosa.
        // 1. Selecionamos Artigos e dizemos que o tipo é 'article'
        // 2. Selecionamos Entrevistas e dizemos que o tipo é 'interview'
        // 3. Juntamos tudo com UNION ALL
        // 4. Filtramos onde a categoria do conteúdo bate com os interesses do user

        $sql = "
            SELECT * FROM (
                -- Buscar Artigos
                SELECT 
                    a.id, 
                    a.title, 
                    LEFT(a.content, 150) as excerpt, -- Simula excerpt para artigos
                    a.created_at as date, 
                    'article' as content_type,
                    c.name as category_name,
                    c.id as category_id
                FROM articles a
                JOIN categories c ON a.category_id = c.id
                JOIN user_interests ui ON ui.category_id = c.id
                WHERE ui.user_id = :uid1

                UNION ALL

                -- Buscar Entrevistas
                SELECT 
                    i.id, 
                    i.title, 
                    i.excerpt, 
                    i.published_at as date, 
                    'interview' as content_type,
                    c.name as category_name,
                    c.id as category_id
                FROM interviews i
                JOIN interview_categories ic ON i.id = ic.interview_id
                JOIN categories c ON ic.category_id = c.id
                JOIN user_interests ui ON ui.category_id = c.id
                WHERE ui.user_id = :uid2
            ) as feed
            ORDER BY date DESC
            LIMIT 20
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid1' => $userId, ':uid2' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
