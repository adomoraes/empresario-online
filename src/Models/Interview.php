<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Interview
{

    /**
     * Cria uma entrevista a partir dos dados do JSON.
     */
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();

        // Tratamento de campos JSON
        $imageData = json_encode($data['image'] ?? []);
        $teamData = json_encode($data['team'] ?? []);

        // Gerar slug se não vier (simples)
        $slug = $data['slug'] ?? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title'])));

        $sql = "INSERT INTO interviews 
                (title, slug, interviewee, excerpt, content, published_at, image_data, team_data) 
                VALUES (:title, :slug, :interviewee, :excerpt, :content, :published_at, :img, :team)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title'       => $data['title'],
            ':slug'        => $slug,
            ':interviewee' => $data['interviewee'], // Extrair só o nome se necessário
            ':excerpt'     => $data['excerpt'],
            ':content'     => $data['content'],
            ':published_at' => $data['published_at'],
            ':img'         => $imageData,
            ':team'        => $teamData
        ]);

        $interviewId = (int) $pdo->lastInsertId();

        // Processar Categorias (vêm no JSON)
        if (!empty($data['categories'])) {
            self::syncCategories($interviewId, $data['categories']);
        }

        return $interviewId;
    }

    /**
     * Associa as categorias.
     * Verifica se a categoria já existe pelo nome, se não, cria.
     */
    private static function syncCategories(int $interviewId, array $categoriesJson)
    {
        $pdo = Database::getConnection();

        foreach ($categoriesJson as $catData) {
            // 1. Verificar se categoria existe (pelo slug ou nome)
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $stmt->execute([$catData['slug']]);
            $catId = $stmt->fetchColumn();

            // 2. Se não existe, cria
            if (!$catId) {
                $stmtInsert = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                $stmtInsert->execute([$catData['name'], $catData['slug']]);
                $catId = $pdo->lastInsertId();
            }

            // 3. Vincular na tabela pivot
            $stmtPivot = $pdo->prepare("INSERT IGNORE INTO interview_categories (interview_id, category_id) VALUES (?, ?)");
            $stmtPivot->execute([$interviewId, $catId]);
        }
    }
}
