<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Category
{
    public static function all(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(string $name): int
    {
        $pdo = Database::getConnection();

        // 1. Transliterar (Ê -> E, ç -> c)
        $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT', $name);

        // 2. Gerar Slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cleanName), '-'));

        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $name): bool
    {
        $pdo = Database::getConnection();

        // Mesmo processo para update
        $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cleanName), '-'));

        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
        return $stmt->execute([$name, $slug, $id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
