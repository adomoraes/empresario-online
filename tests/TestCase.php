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
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->pdo = Database::getConnection();

        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        parent::tearDown();
    }

    protected function authenticateUser($role = 'user')
    {
        $email = $role . '_' . uniqid() . '@teste.com';
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
        // 1. Configurar Globals
        $urlComponents = parse_url($uri);
        $_GET = [];
        if (isset($urlComponents['query'])) {
            parse_str($urlComponents['query'], $_GET);
        }

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['TEST_JSON_BODY'] = $body;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) unset($_SERVER[$key]);
        }
        foreach ($headers as $key => $value) {
            $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$headerName] = $value;
        }

        // 2. Capturar Output
        ob_start();

        try {
            $router = new Router();
            require __DIR__ . '/../src/routes.php';
            $router->dispatch();
        } catch (\App\Config\HttpResponseException $e) {
            // Fluxo normal (resposta enviada)
        } catch (\Throwable $e) {
            // Erro Fatal
            fwrite(STDERR, "\nFATAL ERROR: " . $e->getMessage() . "\n");
            throw $e;
        }

        $output = ob_get_clean();
        $json = json_decode($output, true);

        // --- DEBUG CRÍTICO ---
        // Se a resposta não for JSON válido (ex: tem Warnings misturados), mostra tudo!
        if ($json === null && !empty($output)) {
            fwrite(STDERR, "\n\n!!! RESPOSTA CORROMPIDA !!!\n");
            fwrite(STDERR, "--------------------------------------------------\n");
            fwrite(STDERR, $output);
            fwrite(STDERR, "\n--------------------------------------------------\n\n");
        }

        return [
            'status' => http_response_code(),
            'body' => $json ?? []
        ];
    }
}
