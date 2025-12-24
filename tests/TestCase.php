<?php

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use App\Config\Database;
use App\Config\Router;

class TestCase extends PHPUnitTestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        // 1. Define constante para o AppHelper não dar exit
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }

        // 2. Conecta ao banco de teste e INICIA UMA TRANSAÇÃO
        // Isso garante que o teste não grave lixo permanente no banco
        $this->pdo = Database::getConnection();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        // 3. DESFAZ tudo o que o teste fez no banco (Rollback)
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Simula uma requisição HTTP completa (GET, POST, etc)
     */
    protected function call(string $method, string $uri, array $body = [], array $headers = [])
    {
        // A. Configurar o ambiente (Mock do $_SERVER)
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['TEST_JSON_BODY'] = $body; // O nosso AppHelper vai ler daqui
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; // Simula um IP local

        // Limpar headers antigos
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                unset($_SERVER[$key]);
            }
        }

        // Adicionar novos headers
        foreach ($headers as $key => $value) {
            $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$headerName] = $value;
        }

        // B. Capturar o Output (o echo do controller)
        ob_start();

        // C. Rodar o Router
        $router = new Router();

        // IMPORTANTE: Carrega as rotas que extraímos para src/routes.php
        require __DIR__ . '/../src/routes.php';

        $router->dispatch();

        $output = ob_get_clean();

        return [
            'status' => http_response_code(),
            'body' => json_decode($output, true) ?? []
        ];
    }

    /**
     * Helper para criar user e obter token de autenticação
     */
    protected function authenticateUser(string $role = 'user'): string
    {
        $email = 'test_' . uniqid() . '@user.com';
        $pass = password_hash('123', PASSWORD_DEFAULT);

        // Inserir User
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('Test User', ?, ?, ?)");
        $stmt->execute([$email, $pass, $role]);
        $uid = $this->pdo->lastInsertId();

        // Gerar e Inserir Token
        $token = bin2hex(random_bytes(16));
        $stmtToken = $this->pdo->prepare("INSERT INTO personal_access_tokens (user_id, token, name) VALUES (?, ?, 'TestToken')");
        $stmtToken->execute([$uid, $token]);

        return $token;
    }
}
