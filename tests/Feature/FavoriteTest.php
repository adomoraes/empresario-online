<?php

namespace Tests\Feature;

use Tests\TestCase;

class FavoriteTest extends TestCase
{
    public function test_user_can_add_and_list_favorites()
    {
        // 1. Setup: Limpar base de dados
        $this->pdo->exec("DELETE FROM user_favorites");
        $this->pdo->exec("DELETE FROM articles");
        $this->pdo->exec("DELETE FROM interviews");
        $this->pdo->exec("DELETE FROM categories");

        // 2. Criar Dados Dummy (CORREÇÃO: Adicionado 'slug' aos INSERTS)

        // Categoria (Obrigatório slug)
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (99, 'Test', 'test-category')");

        // User
        $this->pdo->exec("INSERT IGNORE INTO users (id, name, email, password) VALUES (1, 'Admin', 'a@a.com', '123')");

        // Artigo (Artigos na estrutura atual parecem não exigir slug no SQL direto, ou o teste ArticleTest teria falhado)
        $this->pdo->exec("INSERT INTO articles (id, user_id, category_id, title, content, created_at) VALUES (10, 1, 99, 'Artigo Top', 'Conteudo...', NOW())");

        // Entrevista (Geralmente exige slug também, adicionado por segurança)
        // Se a tabela interviews não tiver slug, remova a coluna 'slug' e o valor 'entrevista-top' deste comando.
        $this->pdo->exec("INSERT INTO interviews (id, title, slug, interviewee, published_at) VALUES (20, 'Entrevista Top', 'entrevista-top', 'Joao', NOW())");

        // 3. Login
        $token = $this->authenticateUser();

        // 4. Adicionar Favorito (Artigo)
        $response = $this->call(
            'POST',
            '/favorites',
            ['type' => 'article', 'id' => 10],
            ['Authorization' => "Bearer $token"]
        );
        $this->assertEquals(201, $response['status']);

        // 5. Adicionar Favorito (Entrevista)
        $this->call(
            'POST',
            '/favorites',
            ['type' => 'interview', 'id' => 20],
            ['Authorization' => "Bearer $token"]
        );

        // 6. Listar e Verificar
        $listResponse = $this->call('GET', '/favorites', [], ['Authorization' => "Bearer $token"]);
        $data = $listResponse['body']['data'];

        $this->assertCount(2, $data);
        $titles = array_column($data, 'title');
        $this->assertContains('Artigo Top', $titles);
        $this->assertContains('Entrevista Top', $titles);
    }

    public function test_user_can_remove_favorite()
    {
        // 1. Setup e Login
        $this->pdo->exec("DELETE FROM user_favorites");
        $token = $this->authenticateUser();

        // Recuperar ID do user logado
        $stmt = $this->pdo->prepare("SELECT user_id FROM personal_access_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $userId = $stmt->fetchColumn();

        // Inserir favorito diretamente no banco para testar a remoção
        $this->pdo->exec("INSERT IGNORE INTO user_favorites (user_id, content_type, content_id) VALUES ($userId, 'article', 10)");

        // 2. Remover
        $response = $this->call(
            'DELETE',
            '/favorites',
            ['type' => 'article', 'id' => 10],
            ['Authorization' => "Bearer $token"]
        );
        $this->assertEquals(200, $response['status']);

        // 3. Verificar se sumiu
        $listResponse = $this->call('GET', '/favorites', [], ['Authorization' => "Bearer $token"]);

        // Verifica se o array 'data' está vazio ou não contém o artigo 10
        $data = $listResponse['body']['data'];
        $ids = array_column($data, 'id');
        $this->assertNotContains(10, $ids);
    }
}
