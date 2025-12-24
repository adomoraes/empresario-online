<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_shows_latest_content_for_new_user()
    {
        // 1. Setup: Criar 2 artigos de categorias diferentes
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (1, 'Geral', 'geral')");
        $this->pdo->exec("INSERT INTO users (id, name, email, password) VALUES (99, 'Ed', 'e@e.com', '123')");

        $this->pdo->exec("INSERT INTO articles (title, content, category_id, user_id) VALUES ('Artigo Novo', '...', 1, 99)");

        // 2. Login como utilizador NOVO (sem histórico)
        $token = $this->authenticateUser('user');

        $response = $this->call('GET', '/dashboard', [], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('latest', $response['body']['strategy']); // Vamos retornar qual estratégia foi usada
        $this->assertNotEmpty($response['body']['data']);
    }

    public function test_dashboard_recommends_based_on_history()
    {
        // 1. Setup: Criar Categorias "Tech" (10) e "Saúde" (20)
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (10, 'Tech', 'tech')");
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (20, 'Health', 'health')");
        $this->pdo->exec("INSERT INTO users (id, name, email, password) VALUES (99, 'Ed', 'e@e.com', '123')");

        // Artigos
        $this->pdo->exec("INSERT INTO articles (title, content, category_id, user_id) VALUES ('PHP Rocks', '...', 10, 99)"); // Tech
        $this->pdo->exec("INSERT INTO articles (title, content, category_id, user_id) VALUES ('Comer Maçãs', '...', 20, 99)"); // Saúde

        // 2. Criar User e Simular Histórico (User adora Tech)
        $token = $this->authenticateUser('user');

        // Descobrir ID do user logado
        $stmt = $this->pdo->prepare("SELECT user_id FROM personal_access_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $userId = $stmt->fetchColumn();

        // Inserir 3 views em Tech (Cat 10)
        for ($i = 0; $i < 3; $i++) {
            $this->pdo->exec("INSERT INTO user_history (user_id, category_id, content_type, content_id) VALUES ($userId, 10, 'article', 1)");
        }

        // 3. Chamar Dashboard
        $response = $this->call('GET', '/dashboard', [], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('recommendation', $response['body']['strategy']);

        // Deve recomendar o artigo de Tech
        $this->assertEquals('PHP Rocks', $response['body']['data'][0]['title']);

        // NÃO deve recomendar o de Saúde
        $titulos = array_column($response['body']['data'], 'title');
        $this->assertNotContains('Comer Maçãs', $titulos);
    }
}
