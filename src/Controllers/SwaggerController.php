<?php

namespace App\Controllers;

use OpenApi\Generator;

class SwaggerController
{
    public function index()
    {
        // 1. Limpar qualquer output anterior (espaços, warnings, lixo do buffer)
        // Isso previne que caracteres antes do JSON quebrem o parser
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 2. Desliga avisos do PHP para garantir que o JSON sai limpo
        error_reporting(0);
        ini_set('display_errors', 0);

        // 3. Define EXATAMENTE o que deve ser escaneado
        $pathsToScan = [
            __DIR__ . '/../Controllers',
            // __DIR__ . '/../routes.php' // DICA: Geralmente não é necessário escanear rotas se usas Attributes nos Controllers
        ];

        try {
            // 4. Gerar o objeto OpenAPI
            $openapi = (new Generator())->generate($pathsToScan);

            // 5. Enviar Cabeçalhos
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *'); // Ajuda se estiveres a rodar o Swagger UI separado

            // 6. Enviar JSON e MATAR o processo imediatamente
            echo $openapi->toJson();
            exit; // <--- CRUCIAL: Garante que nada mais é enviado (nem logs, nem html, nem newlines)

        } catch (\Throwable $e) {
            // Se der erro na geração, retorna um JSON de erro válido em vez de estourar a tela
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Erro ao gerar Swagger: ' . $e->getMessage()]);
            exit;
        }
    }
}
