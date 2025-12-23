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
        // Slug simples: transforma "Tech PHP" em "tech-php"
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
        return (int) $pdo->lastInsertId();
    }

    // Podes adicionar delete() e update() aqui depois
}
