<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_user_can_register()
    {
        // 1. Tenta registar
        $response = $this->call('POST', '/register', [
            'name' => 'Tester',
            'email' => 'newuser@example.com',
            'password' => 'secret123'
        ]);

        // 2. Verifica se a API respondeu 201 (Created)
        $this->assertEquals(201, $response['status']);
        $this->assertEquals('Usuário registado com sucesso!', $response['body']['message']);

        // 3. Verifica se gravou no banco de teste
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['newuser@example.com']);
        $user = $stmt->fetch();

        $this->assertNotEmpty($user);
        $this->assertEquals('Tester', $user['name']);
    }

    public function test_user_can_login()
    {
        // 1. Cria um user manualmente no banco (Setup)
        $passwordHash = password_hash('senha123', PASSWORD_DEFAULT);
        $this->pdo->exec("INSERT INTO users (name, email, password) VALUES ('LoginUser', 'login@teste.com', '$passwordHash')");

        // 2. Tenta fazer login
        $response = $this->call('POST', '/login', [
            'email' => 'login@teste.com',
            'password' => 'senha123'
        ]);

        // 3. Verifica sucesso
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('token', $response['body']);
    }

    public function test_login_fails_with_wrong_password()
    {
        // 1. Setup
        $passwordHash = password_hash('senha123', PASSWORD_DEFAULT);
        $this->pdo->exec("INSERT INTO users (name, email, password) VALUES ('WrongUser', 'wrong@teste.com', '$passwordHash')");

        // 2. Tenta login com senha errada
        $response = $this->call('POST', '/login', [
            'email' => 'wrong@teste.com',
            'password' => 'senhaErrada'
        ]);

        // 3. Verifica falha (401 Unauthorized)
        $this->assertEquals(401, $response['status']);
    }
}
