<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Router;

// Configurações Globais (Headers & CORS)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Instanciar
$router = new Router();

// 2. Carregar Rotas (Aqui está a magia!)
// Como estamos a incluir o ficheiro, a variável $router daqui estará visível lá dentro.
require __DIR__ . '/../src/routes.php';

// 3. Executar
$router->dispatch();
