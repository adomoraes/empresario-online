<?php

namespace Tests\Feature;

use Tests\TestCase;

class ArticleTest extends TestCase
{
    /**
     * Tenta criar artigo sendo User Comum (Deve falhar 403)
     */
    public function test_normal_user_cannot_create_article()
    {
        $token = $this->authenticateUser('user'); // User normal

        $response = $this->call('POST', '/articles', [
            'title' => 'Hacker News',
            'content' => 'Tentativa de invasão',
            'category_id' => 1
        ], ['Authorization' => "Bearer $token"]);

        // O AdminMiddleware deve retornar 403 Forbidden
        $this->assertEquals(403, $response['status']);
    }

    /**
     * Tenta criar artigo sendo Admin (Deve passar 201)
     */
    public function test_admin_can_create_article()
    {
        // Criar uma categoria para não dar erro de chave estrangeira
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (1, 'Admin Cat', 'admin-cat')");

        $token = $this->authenticateUser('admin');

        $response = $this->call('POST', '/articles', [
            'title' => 'PHP 8.2 News',
            'content' => 'Novidades incríveis...',
            'category_id' => 1 // <--- ID válido agora
        ], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('id', $response['body']);
    }

    /**
     * Testa a leitura e a geração automática de histórico
     */
    public function test_viewing_article_records_history()
    {
        // 1. Setup: Criar Categoria e Artigo no banco
        // Usamos IDs altos (99, 100) para evitar colisão com outros testes
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (99, 'Tech', 'tech')");
        $this->pdo->exec("INSERT INTO users (id, name, email, password) VALUES (99, 'Editor', 'ed@t.com', '123')");
        $this->pdo->exec("INSERT INTO articles (id, title, content, category_id, user_id) VALUES (100, 'Artigo Teste', 'Conteudo', 99, 99)");

        // 2. Autenticar um leitor
        $token = $this->authenticateUser('user');

        // --- CORREÇÃO AQUI ---
        // Recuperar o ID real do utilizador associado ao token gerado
        $stmtUser = $this->pdo->prepare("SELECT user_id FROM personal_access_tokens WHERE token = ?");
        $stmtUser->execute([$token]);
        $userId = $stmtUser->fetchColumn();
        // ---------------------

        // 3. Acessar a rota GET /article
        $response = $this->call('GET', '/article?id=100', [], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Artigo Teste', $response['body']['data']['title']);

        // 4. Verificar se gravou no user_history com o ID correto
        $stmt = $this->pdo->prepare("SELECT * FROM user_history WHERE user_id = ? AND content_id = ? AND content_type = 'article'");
        $stmt->execute([$userId, 100]);
        $history = $stmt->fetch();

        // Debug: Se falhar, mostra o que aconteceu
        if (!$history) {
            fwrite(STDERR, "\nFalha no Histórico. User ID esperado: $userId. Token usado: $token\n");
        }

        $this->assertNotEmpty($history, 'O histórico não foi gravado!');
        $this->assertEquals(99, $history['category_id']);
    }

    public function test_admin_can_update_article()
    {
        // 1. Setup: Criar Categoria e Artigo
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (1, 'Tech', 'tech')");
        $this->pdo->exec("INSERT INTO users (id, name, email, password) VALUES (10, 'Author', 'a@a.com', '123')");
        $this->pdo->exec("INSERT INTO articles (id, title, content, category_id, user_id) VALUES (1, 'Velho Titulo', 'Conteudo...', 1, 10)");

        $token = $this->authenticateUser('admin');

        // 2. Ação: PUT /articles
        $response = $this->call('PUT', '/articles', [
            'id' => 1,
            'title' => 'Novo Titulo Editado',
            'content' => 'Conteúdo atualizado',
            'category_id' => 1
        ], ['Authorization' => "Bearer $token"]);

        // 3. Asserts
        $this->assertEquals(200, $response['status']);

        // Verificar no banco
        $stmt = $this->pdo->prepare("SELECT title FROM articles WHERE id = 1");
        $stmt->execute();
        $this->assertEquals('Novo Titulo Editado', $stmt->fetchColumn());
    }

    public function test_admin_can_delete_article()
    {
        // 1. Setup
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (1, 'Tech', 'tech')");
        $this->pdo->exec("INSERT INTO users (id, name, email, password) VALUES (10, 'Author', 'a@a.com', '123')");
        $this->pdo->exec("INSERT INTO articles (id, title, content, category_id, user_id) VALUES (50, 'Artigo Lixo', '...', 1, 10)");

        $token = $this->authenticateUser('admin');

        // 2. Ação: DELETE /articles
        $response = $this->call('DELETE', '/articles', [
            'id' => 50
        ], ['Authorization' => "Bearer $token"]);

        // 3. Asserts
        $this->assertEquals(200, $response['status']);

        // Verificar que sumiu do banco
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM articles WHERE id = 50");
        $stmt->execute();
        $this->assertEquals(0, $stmt->fetchColumn());
    }
}
