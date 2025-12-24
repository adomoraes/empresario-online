<?php

// 1. Autoload
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Router;
use App\Controllers\UserController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\AdminMiddleware;
use App\Controllers\CategoryController;
use App\Controllers\LogController;
use App\Middlewares\LogMiddleware;
use App\Controllers\InterestController;
use App\Controllers\DashboardController;
use App\Controllers\ImportController;
use App\Controllers\ArticleController;

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

// --- ROTAS PÚBLICAS ---
$router->post('/register', UserController::class, 'register');
$router->post('/login', UserController::class, 'login', [LogMiddleware::class]);

// --- ROTAS PROTEGIDAS ---
// Repara no array extra com AuthMiddleware::class
$router->get('/me', UserController::class, 'me', [AuthMiddleware::class]);

// --- ROTAS ADMIN ---
// Repara na ordem do array: Primeiro valida o Token, depois valida a Role.
$router->get('/users', UserController::class, 'index', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
// --- GESTÃO DE ARTIGOS (ADMIN) ---
$router->get('/articles', ArticleController::class, 'index', [
    App\Middlewares\AuthMiddleware::class,
    App\Middlewares\AdminMiddleware::class
]);

$router->post('/articles', ArticleController::class, 'store', [
    App\Middlewares\AuthMiddleware::class,
    App\Middlewares\AdminMiddleware::class,
    App\Middlewares\LogMiddleware::class
]);

// --- ROTAS USER (PROFILE) ---
$router->put('/profile', UserController::class, 'updateProfile', [
    App\Middlewares\AuthMiddleware::class,
    LogMiddleware::class
]);

// --- ROTAS ADMIN (CATEGORIAS) ---
$router->get('/categories', CategoryController::class, 'index'); // Público ou Protegido? Vamos deixar público para leitura
$router->post('/categories', CategoryController::class, 'store', [
    App\Middlewares\AuthMiddleware::class,
    App\Middlewares\AdminMiddleware::class, // Só admin cria categorias
    LogMiddleware::class
]);

// --- ROTAS ADMIN (LOGS) ---
$router->get('/admin/logs', LogController::class, 'index', [
    App\Middlewares\AuthMiddleware::class,
    App\Middlewares\AdminMiddleware::class
]);

// --- GESTÃO DE INTERESSES (USER) ---
$router->get('/interests', InterestController::class, 'index', [AuthMiddleware::class]);
$router->post('/interests', InterestController::class, 'store', [AuthMiddleware::class]);
$router->delete('/interests', InterestController::class, 'delete', [AuthMiddleware::class]);

// --- DASHBOARD PERSONALIZADO ---
$router->get('/dashboard', DashboardController::class, 'index', [AuthMiddleware::class]);

// --- IMPORTAÇÃO DE DADOS ---
// Rota para enviar o JSON complexo
$router->post('/admin/import/interview', ImportController::class, 'import', [
    App\Middlewares\AuthMiddleware::class,
    App\Middlewares\AdminMiddleware::class // Se já tiveres o teu user como admin
]);

// --- EXECUTAR ---
$router->dispatch();
