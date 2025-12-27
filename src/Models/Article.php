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

        // Nota: O slug é gerado mas não estava a ser inserido na query original.
        // Se a tabela tiver coluna 'slug', deve ser adicionada aqui.
        // Assumindo a estrutura atual sem slug na tabela articles:
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
     * Atualiza um artigo existente.
     * CORREÇÃO: Aceita array $data para maior flexibilidade e compatibilidade.
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();

        $fields = [];
        $values = [];

        if (!empty($data['title'])) {
            $fields[] = "title = ?";
            $values[] = $data['title'];
        }
        if (!empty($data['content'])) {
            $fields[] = "content = ?";
            $values[] = $data['content'];
        }
        if (array_key_exists('category_id', $data)) {
            $fields[] = "category_id = ?";
            $values[] = $data['category_id'];
        }

        if (empty($fields)) {
            return true;
        }

        $values[] = $id;
        $sql = "UPDATE articles SET " . implode(', ', $fields) . " WHERE id = ?";

        return $pdo->prepare($sql)->execute($values);
    }

    /**
     * Remove um artigo.
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Lista todos os artigos.
     */
    public static function all(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT a.*, c.name as category_name, u.name as author_name 
                FROM articles a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC";

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um artigo por ID.
     */
    public static function find(int $id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
