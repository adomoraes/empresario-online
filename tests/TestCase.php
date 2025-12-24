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
        // 1. Limpeza de Memória (Matar o "User Fantasma")
        // Garante que nenhum dado de request anterior afeta o teste atual
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET'; // Reset ao padrão

        // 2. Conexão e Transação
        $this->pdo = Database::getConnection();

        // Iniciamos a transação AGORA. 
        // Tudo o que criares (users, categorias) será desfeito no tearDown.
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        // Desfaz tudo o que foi feito no banco durante o teste
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }

    protected function authenticateUser($role = 'user')
    {
        // Cria user e token dentro da transação atual
        $email = $role . '_' . uniqid() . '@teste.com'; // Email único para evitar colisão
        $password = password_hash('123', PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([ucfirst($role), $email, $password, $role]);
        $userId = $this->pdo->lastInsertId();

        $token = bin2hex(random_bytes(16));
        $this->pdo->prepare("INSERT INTO personal_access_tokens (user_id, token) VALUES (?, ?)")
            ->execute([$userId, $token]);

        return $token;
    }

    protected function call(string $method, string $uri, array $body = [], array $headers = [])
    {
        // 1. Configurar $_GET a partir da URL
        $urlComponents = parse_url($uri);
        $_GET = [];
        if (isset($urlComponents['query'])) {
            parse_str($urlComponents['query'], $_GET);
        }

        // 2. Configurar o ambiente
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['TEST_JSON_BODY'] = $body;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // Limpar headers antigos
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) unset($_SERVER[$key]);
        }

        // Adicionar novos headers
        foreach ($headers as $key => $value) {
            $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$headerName] = $value;
        }

        // 3. Capturar Output
        ob_start();

        try {
            $router = new Router();
            require __DIR__ . '/../src/routes.php';
            $router->dispatch();
        } catch (\App\Config\HttpResponseException $e) {
            // Captura a "saída forçada" do AppHelper
        } catch (\Throwable $e) {
            // Se houver um erro grave (Fatal Error), mostra no terminal para debug
            fwrite(STDERR, "\nFATAL ERROR: " . $e->getMessage() . "\n");
            throw $e;
        }

        $output = ob_get_clean();

        return [
            'status' => http_response_code(),
            'body' => json_decode($output, true) ?? []
        ];
    }
}
