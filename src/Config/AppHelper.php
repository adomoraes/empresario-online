<?php

namespace App\Config;

class AppHelper
{
    /**
     * Lê o input JSON. Nos testes, podemos injetar dados aqui.
     */
    public static function getJsonInput(): array
    {
        if (isset($_SERVER['TEST_JSON_BODY'])) {
            return $_SERVER['TEST_JSON_BODY'];
        }
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    /**
     * Envia a resposta. Em teste, apenas retorna o output.
     */
    public static function sendResponse(int $code, array $data): void
    {
        http_response_code($code);
        echo json_encode($data);
        // Em vez de exit, usamos return para o teste não parar
        if (!defined('PHPUNIT_RUNNING')) {
            exit;
        }
    }
}
