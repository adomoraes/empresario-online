<?php

namespace Tests\Feature;

use Tests\TestCase;

class InterviewTest extends TestCase
{
    public function test_admin_can_create_interview_with_multiple_categories()
    {
        // 1. Setup: Criar 2 categorias
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (10, 'Business', 'biz')");
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (11, 'Tech', 'tech-2')");

        $token = $this->authenticateUser('admin');

        // 2. Criar Entrevista
        $response = $this->call('POST', '/interviews', [
            'title' => 'Entrevista com CEO',
            'interviewee' => 'Elon Musk',
            'content' => 'Conteúdo...',
            'category_ids' => [10, 11]
        ], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(
            201,
            $response['status'],
            "Erro retornado pela API: " . json_encode($response['body'] ?? [], JSON_UNESCAPED_UNICODE)
        );

        if ($response['status'] === 201) {
            $interviewId = $response['body']['id'];
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM interview_categories WHERE interview_id = ?");
            $stmt->execute([$interviewId]);
            $count = $stmt->fetchColumn();
            $this->assertEquals(2, $count, 'A entrevista devia ter 2 categorias associadas.');
        }
    }

    public function test_show_interview_returns_categories()
    {
        // 1. Setup
        $this->pdo->exec("INSERT INTO categories (id, name, slug) VALUES (20, 'Design', 'design')");
        $this->pdo->exec("INSERT INTO interviews (id, title, slug, interviewee) VALUES (50, 'Design Talk', 'design-talk', 'Jony Ive')");
        $this->pdo->exec("INSERT INTO interview_categories (interview_id, category_id) VALUES (50, 20)");

        // 2. CORREÇÃO: Autenticar Usuário (Rota agora é protegida)
        $token = $this->authenticateUser();

        // 3. Call GET com Token
        $response = $this->call('GET', '/interview?id=50', [], ['Authorization' => "Bearer $token"]);

        // 4. Assert
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Design', $response['body']['data']['categories'][0]['name']);
    }

    public function test_admin_can_update_interview()
    {
        $this->pdo->exec("INSERT INTO interviews (id, title, slug, interviewee) VALUES (10, 'Old Interview', 'old', 'Old Guy')");
        $token = $this->authenticateUser('admin');

        $response = $this->call('PUT', '/interviews', [
            'id' => 10,
            'title' => 'New Title',
            'interviewee' => 'New Guy',
            'category_ids' => []
        ], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);

        $stmt = $this->pdo->prepare("SELECT title, interviewee FROM interviews WHERE id = 10");
        $stmt->execute();
        $row = $stmt->fetch();
        $this->assertEquals('New Title', $row['title']);
        $this->assertEquals('New Guy', $row['interviewee']);
    }

    public function test_admin_can_delete_interview()
    {
        $this->pdo->exec("INSERT INTO interviews (id, title, slug, interviewee) VALUES (20, 'To Delete', 'del', 'Guy')");
        $token = $this->authenticateUser('admin');

        $response = $this->call('DELETE', '/interviews', [
            'id' => 20
        ], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM interviews WHERE id = 20");
        $stmt->execute();
        $this->assertEquals(0, $stmt->fetchColumn());
    }
}
