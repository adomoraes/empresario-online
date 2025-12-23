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

// --- EXECUTAR ---
$router->dispatch();
