<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Interview
{
    /**
     * Cria uma entrevista a partir dos dados do JSON.
     * Utiliza Transações para garantir integridade.
     */
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();

        try {
            // 1. Iniciar Transação
            $pdo->beginTransaction();

            // Tratamento de campos JSON e Defaults
            $imageData = json_encode($data['image'] ?? []);
            $teamData = json_encode($data['team'] ?? []);

            // Gerar slug se não vier. Se o título também não vier, gera um uniqid para não quebrar.
            $rawTitle = $data['title'] ?? 'Sem Título';
            $slug = $data['slug'] ?? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawTitle)));

            // Garantir que o slug é único (adicionando timestamp se necessário seria uma melhoria futura)
            // Aqui confiamos que o banco vai dar erro se for duplicado, e o catch apanha.

            $sql = "INSERT INTO interviews 
                    (title, slug, interviewee, excerpt, content, published_at, image_data, team_data) 
                    VALUES (:title, :slug, :interviewee, :excerpt, :content, :published_at, :img, :team)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title'       => $data['title'] ?? 'Sem Título',
                ':slug'        => $slug,
                ':interviewee' => $data['interviewee'] ?? 'Desconhecido',
                ':excerpt'     => $data['excerpt'] ?? '',
                ':content'     => $data['content'] ?? '',
                ':published_at' => $data['published_at'] ?? date('Y-m-d'), // Data de hoje se falhar
                ':img'         => $imageData,
                ':team'        => $teamData
            ]);

            $interviewId = (int) $pdo->lastInsertId();

            // Processar Categorias
            if (!empty($data['categories'])) {
                self::syncCategories($pdo, $interviewId, $data['categories']);
            }

            // 2. Confirmar Transação
            $pdo->commit();

            return $interviewId;
        } catch (PDOException $e) {
            // Se algo der errado, desfaz tudo
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e; // Relança o erro para o Controller tratar
        }
    }

    /**
     * Associa as categorias.
     * Recebe a conexão PDO já aberta na transação.
     */
    private static function syncCategories(PDO $pdo, int $interviewId, array $categoriesJson)
    {
        foreach ($categoriesJson as $catData) {
            // Validação mínima dos dados da categoria
            $slug = $catData['slug'] ?? null;
            $name = $catData['name'] ?? 'Sem Categoria';

            if (!$slug) continue; // Pula se não tiver slug

            // 1. Verificar se categoria existe
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $stmt->execute([$slug]);
            $catId = $stmt->fetchColumn();

            // 2. Se não existe, cria
            if (!$catId) {
                $stmtInsert = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                $stmtInsert->execute([$name, $slug]);
                $catId = $pdo->lastInsertId();
            }

            // 3. Vincular na tabela pivot
            $stmtPivot = $pdo->prepare("INSERT IGNORE INTO interview_categories (interview_id, category_id) VALUES (?, ?)");
            $stmtPivot->execute([$interviewId, $catId]);
        }
    }

    /**
     * Lista todas as entrevistas (Para o index do Controller).
     * Opcional: Filtra por user_id se passarmos o argumento.
     */
    public static function all(?int $userId = null): array
    {
        $pdo = Database::getConnection();

        // Query básica. 
        // Nota: Como a entrevista não tem "user_id" direto (é feita pelo admin), 
        // o filtro por user_id aqui só faria sentido se filtrássemos por INTERESSES.
        // Por enquanto, vamos retornar tudo ordenado por data.

        $sql = "SELECT id, title, slug, interviewee, published_at, created_at 
                FROM interviews 
                ORDER BY published_at DESC";

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca uma entrevista específica pelo ID (Útil para detalhes).
     */
    public static function find(int $id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM interviews WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza uma entrevista e sincroniza as categorias.
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // 1. Gerar slug a partir do novo título
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title'])));

            // 2. Query de Update
            $sql = "UPDATE interviews 
                    SET title = ?, slug = ?, interviewee = ?, content = ? 
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['title'],
                $slug,
                $data['interviewee'],
                $data['content'] ?? '',
                $id
            ]);

            // 3. Atualizar Categorias (Sync: Apagar antigas -> Inserir novas)
            if (isset($data['category_ids']) && is_array($data['category_ids'])) {
                // Remove ligações antigas
                $stmtDel = $pdo->prepare("DELETE FROM interview_categories WHERE interview_id = ?");
                $stmtDel->execute([$id]);

                // Insere as novas, se houver
                if (!empty($data['category_ids'])) {
                    $stmtIns = $pdo->prepare("INSERT INTO interview_categories (interview_id, category_id) VALUES (?, ?)");
                    foreach ($data['category_ids'] as $catId) {
                        $stmtIns->execute([$id, $catId]);
                    }
                }
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Remove uma entrevista pelo ID.
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM interviews WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
