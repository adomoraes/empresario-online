<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Article
{

    /**
     * Cria um novo artigo.
     */
    public static function create(int $userId, int $categoryId, string $title, string $content): int
    {
        $pdo = Database::getConnection();

        // Gerar slug a partir do título
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

        $sql = "INSERT INTO articles (user_id, category_id, title, content) 
                VALUES (:user_id, :category_id, :title, :content)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id'     => $userId,
            ':category_id' => $categoryId,
            ':title'       => $title,
            ':content'     => $content
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Lista todos os artigos (Para o Admin gerir)
     */
    public static function all(): array
    {
        $pdo = Database::getConnection();
        // Trazemos também o nome da categoria e do autor
        $sql = "SELECT a.*, c.name as category_name, u.name as author_name 
                FROM articles a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC";

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Podes adicionar update() e delete() aqui seguindo a mesma lógica
}
