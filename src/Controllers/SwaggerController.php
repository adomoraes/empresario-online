<?php

namespace App\Controllers;

use OpenApi\Generator;

class SwaggerController
{
    public function index()
    {
        // 1. Desliga avisos do PHP para garantir que o JSON sai limpo
        error_reporting(0);
        ini_set('display_errors', 0);

        // 2. Define EXATAMENTE o que deve ser escaneado
        $pathsToScan = [
            __DIR__ . '/../Controllers',
            __DIR__ . '/../routes.php'
        ];

        // --- CORREÇÃO AQUI ---
        // Em vez de Generator::scan(), instanciamos a classe:
        $openapi = (new Generator())->generate($pathsToScan);
        // ---------------------

        header('Content-Type: application/json');
        echo $openapi->toJson();
    }
}
