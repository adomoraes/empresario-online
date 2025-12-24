<?php

namespace App\Config;

use Exception;

// Uma exceção personalizada para controlarmos o fluxo nos testes
class HttpResponseException extends Exception {}

class AppHelper
{

    public static function getJsonInput(): array
    {
        if (isset($_SERVER['TEST_JSON_BODY'])) {
            return $_SERVER['TEST_JSON_BODY'];
        }
        $input = json_decode(file_get_contents('php://input'), true);
        return is_array($input) ? $input : [];
    }

    public static function sendResponse(int $code, array $data): void
    {
        http_response_code($code);
        echo json_encode($data);

        // Se estivermos em teste, LANÇAMOS EXCEÇÃO para parar a execução
        if (defined('PHPUNIT_RUNNING')) {
            throw new HttpResponseException("Response Sent", $code);
        }

        // Se for produção, mata o script
        exit;
    }
}
