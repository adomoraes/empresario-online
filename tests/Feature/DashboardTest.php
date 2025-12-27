<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    // REMOVIDO: private $pdo; (Já é herdado do TestCase como protected)
    // REMOVIDO: setUp() (O TestCase já faz a conexão)

    public function test_dashboard_shows_latest_content_for_new_user()
    {
        // 1. Setup: Limpar base e criar dados genéricos
        $this->pdo->exec("DELETE FROM articles");
        $this->pdo->exec("DELETE FROM categories");
        $this->pdo->exec("DELETE FROM users WHERE id = 99");

        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (1, 'Geral', 'geral')");
        // User autor do artigo
        $this->pdo->exec("INSERT IGNORE INTO users (id, name, email, password) VALUES (99, 'Ed', 'e@e.com', '123')");
        $this->pdo->exec("INSERT INTO articles (title, content, category_id, user_id, created_at) VALUES ('Artigo Novo', '...', 1, 99, NOW())");

        // 2. Login como usuário NOVO (sem histórico)
        $token = $this->authenticateUser();

        $response = $this->call('GET', '/dashboard', [], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);

        // Ajuste: O controller retorna 'latest_fallback', não apenas 'latest'
        $this->assertEquals('latest_fallback', $response['body']['strategy']);

        $this->assertNotEmpty($response['body']['data']);
        $this->assertEquals('Artigo Novo', $response['body']['data'][0]['title']);
    }

    public function test_dashboard_recommends_based_on_history()
    {
        // 1. Setup: Limpeza e Criação de Cenário
        $this->pdo->exec("DELETE FROM articles");
        $this->pdo->exec("DELETE FROM categories");
        $this->pdo->exec("DELETE FROM user_history");
        $this->pdo->exec("DELETE FROM users WHERE id = 99");

        // Categorias
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (10, 'Tech', 'tech')");
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (20, 'Health', 'health')");

        // Autor
        $this->pdo->exec("INSERT IGNORE INTO users (id, name, email, password) VALUES (99, 'Ed', 'e@e.com', '123')");

        // Artigos
        $this->pdo->exec("INSERT INTO articles (title, content, category_id, user_id, created_at) VALUES ('PHP Rocks', '...', 10, 99, NOW())"); // Tech
        $this->pdo->exec("INSERT INTO articles (title, content, category_id, user_id, created_at) VALUES ('Comer Maçãs', '...', 20, 99, NOW())"); // Saúde

        // 2. Criar User e Simular Histórico (User adora Tech)
        $token = $this->authenticateUser();

        // Descobrir ID do user logado
        $stmt = $this->pdo->prepare("SELECT user_id FROM personal_access_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $userId = $stmt->fetchColumn();

        // Inserir 3 views em Tech (Cat 10)
        for ($i = 0; $i < 3; $i++) {
            $this->pdo->exec("INSERT INTO user_history (user_id, category_id, content_type, content_id, accessed_at) VALUES ($userId, 10, 'article', 1, NOW())");
        }

        // 3. Chamar Dashboard
        $response = $this->call('GET', '/dashboard', [], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);

        // Ajuste: O controller retorna 'hybrid_recommendation'
        $this->assertEquals('hybrid_recommendation', $response['body']['strategy']);

        // Deve recomendar o artigo de Tech
        $this->assertEquals('PHP Rocks', $response['body']['data'][0]['title']);

        // NÃO deve recomendar o de Saúde (pois não tem histórico nem interesse)
        $titulos = array_column($response['body']['data'], 'title');
        $this->assertNotContains('Comer Maçãs', $titulos);
    }
}
