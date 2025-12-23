<?php

// 1. Carregar o Autoload do Composer
// O __DIR__ garante que o caminho é relativo à pasta atual
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Teste; // Importar a classe

// 2. Teste rápido
// Vamos tentar usar uma classe que ainda não existe para ver o erro (ou criar uma de teste)
// Mas por enquanto, apenas confirmamos que o ficheiro carregou.

header('Content-Type: application/json');

echo json_encode([
    'message' => Teste::hello()
]);
