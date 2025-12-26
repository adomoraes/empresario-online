<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    public function test_admin_dashboard_returns_correct_stats()
    {
        // 1. Setup: Criar User ID 1 (Autor dos artigos) para evitar erro de Foreign Key
        // INSERT IGNORE garante que não falha se por acaso já existir
        $this->pdo->exec("INSERT IGNORE INTO users (id, name, email, password) VALUES (1, 'Admin', 'admin@test.com', '123')");

        // Criar dados fictícios
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (1, 'Cat', 'cat')");

        // Criar 3 Artigos associados ao User 1
        $this->pdo->exec("INSERT INTO articles (title, content, user_id) VALUES ('A1', 'C', 1), ('A2', 'C', 1), ('A3', 'C', 1)");

        // Criar 2 Entrevistas
        $this->pdo->exec("INSERT INTO interviews (title, slug, interviewee) VALUES ('I1', 's1', 'P1'), ('I2', 's2', 'P2')");

        $token = $this->authenticateUser('admin');

        // 2. Call
        $response = $this->call('GET', '/admin/dashboard', [], ['Authorization' => "Bearer $token"]);

        // 3. Assert
        $this->assertEquals(200, $response['status']);

        $stats = $response['body']['stats'];

        $this->assertGreaterThanOrEqual(1, $stats['total_users']);
        $this->assertGreaterThanOrEqual(3, $stats['total_articles']);
        $this->assertGreaterThanOrEqual(2, $stats['total_interviews']);
    }
}
