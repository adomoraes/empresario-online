<?php

// 1. Autoload
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Router;
use App\Controllers\UserController;

// 2. Configurações Globais (CORS e JSON)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 3. Instanciar o Router
$router = new Router();

// --- ROTAS ---

// Auth
$router->post('/register', UserController::class, 'register');
$router->post('/login', UserController::class, 'login'); // <--- Vamos criar isto agora

// --- EXECUTAR ---
$router->dispatch();
