<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminUserTest extends TestCase
{
    public function test_admin_can_promote_user()
    {
        // 1. Criar utilizador comum
        $this->pdo->exec("INSERT INTO users (id, name, email, password, role) VALUES (50, 'Common', 'c@c.com', '123', 'user')");

        $token = $this->authenticateUser('admin');

        // 2. Promover
        $response = $this->call('PUT', '/admin/users', [
            'id' => 50,
            'role' => 'admin'
        ], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);

        // 3. Validar
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = 50");
        $stmt->execute();
        $this->assertEquals('admin', $stmt->fetchColumn());
    }

    public function test_admin_cannot_demote_himself()
    {
        // Este teste garante a regra de segurança que criámos
        $token = $this->authenticateUser('admin');

        // Descobrir meu ID
        $stmt = $this->pdo->prepare("SELECT user_id FROM personal_access_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $myId = $stmt->fetchColumn();

        // Tentar mudar minha role para 'user'
        $response = $this->call('PUT', '/admin/users', [
            'id' => $myId,
            'role' => 'user'
        ], ['Authorization' => "Bearer $token"]);

        // Esperamos erro 400
        $this->assertEquals(400, $response['status']);
        $this->assertStringContainsString('não pode remover seu próprio acesso', $response['body']['error']);
    }

    public function test_admin_can_delete_user()
    {
        $this->pdo->exec("INSERT INTO users (id, name, email, password) VALUES (60, 'Ban Me', 'b@b.com', '123')");
        $token = $this->authenticateUser('admin');

        $response = $this->call('DELETE', '/admin/users', [
            'id' => 60
        ], ['Authorization' => "Bearer $token"]);

        $this->assertEquals(200, $response['status']);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE id = 60");
        $stmt->execute();
        $this->assertEquals(0, $stmt->fetchColumn());
    }
}
