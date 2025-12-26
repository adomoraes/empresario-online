<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Article
{
    /**
     * Lista todos os artigos (com nomes de autor e categoria).
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
     * Busca um artigo específico pelo ID (Método que estava em falta).
     */
    public static function find(int $id)
    {
        $pdo = Database::getConnection();
        $sql = "SELECT a.*, c.name as category_name, u.name as author_name 
                FROM articles a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo artigo.
     * Nota: Ajustei a ordem dos parâmetros para facilitar a chamada no Controller.
     */
    public static function create(string $title, string $content, int $categoryId, int $userId): int
    {
        $pdo = Database::getConnection();

        // Gerar slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

        $sql = "INSERT INTO articles (title, content, category_id, user_id) 
                VALUES (:title, :content, :category_id, :user_id)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title'       => $title,
            ':content'     => $content,
            ':category_id' => $categoryId,
            ':user_id'     => $userId
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Atualiza um artigo existente.
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();

        // Construção dinâmica simples ou fixa
        // Assumindo que $data traz os campos
        $title = $data['title'];
        $content = $data['content'];
        $categoryId = $data['category_id'] ?? null;

        $sql = "UPDATE articles 
                SET title = ?, content = ?, category_id = ? 
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$title, $content, $categoryId, $id]);
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
}
