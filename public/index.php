<?php

// 1. Autoload do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// --- IMPORTAÇÃO DE CLASSES ---

// Configuração
use App\Config\Router;

// Controladores
use App\Controllers\UserController;
use App\Controllers\InterviewController;
use App\Controllers\ArticleController;
use App\Controllers\CategoryController;
use App\Controllers\InterestController;
use App\Controllers\DashboardController;
use App\Controllers\LogController;
use App\Controllers\ImportController;

// Middlewares
use App\Middlewares\AuthMiddleware;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\LogMiddleware;

// 2. Configurações Globais (Headers & CORS)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Em produção, define o domínio específico do frontend
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratamento de pre-flight request do CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 3. Instanciar o Router
$router = new Router();


// ==================================================================
// DEFINIÇÃO DE ROTAS
// ==================================================================

// --- 1. AUTENTICAÇÃO ---
$router->post('/register', UserController::class, 'register');
// O LogMiddleware grava quem fez login
$router->post('/login', UserController::class, 'login', [LogMiddleware::class]);


// --- 2. UTILIZADOR (PROFILE) ---
// Ver dados do utilizador logado
$router->get('/me', UserController::class, 'me', [AuthMiddleware::class]);
// Atualizar perfil (Nome/Email)
$router->put('/profile', UserController::class, 'updateProfile', [
    AuthMiddleware::class,
    LogMiddleware::class
]);


// --- 3. DASHBOARD E INTERESSES (USER EXPERIENCE) ---
// O Feed personalizado misturando Artigos e Entrevistas
$router->get('/dashboard', DashboardController::class, 'index', [AuthMiddleware::class]);

// Gestão de Interesses (Seguir Categorias)
$router->get('/interests', InterestController::class, 'index', [AuthMiddleware::class]);
$router->post('/interests', InterestController::class, 'store', [AuthMiddleware::class]);
$router->delete('/interests', InterestController::class, 'delete', [AuthMiddleware::class]);


// --- 4. CONTEÚDOS (ENTREVISTAS) ---
// Listar entrevistas do utilizador (ou geral, dependendo da lógica do controller)
$router->get('/interviews', InterviewController::class, 'index', [AuthMiddleware::class]);
// Criar entrevista manualmente (Simples)
$router->post('/interviews', InterviewController::class, 'store', [AuthMiddleware::class]);


// --- 5. ÁREA ADMINISTRATIVA ---

// A. Categorias (Base para tudo)
$router->get('/categories', CategoryController::class, 'index'); // Público para facilitar selects no frontend
$router->post('/categories', CategoryController::class, 'store', [
    AuthMiddleware::class,
    AdminMiddleware::class, // Só Admin cria
    LogMiddleware::class
]);

// B. Artigos (Conteúdo exclusivo Admin)
$router->get('/articles', ArticleController::class, 'index', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
$router->post('/articles', ArticleController::class, 'store', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);

// C. Importação de Dados Legados (JSON Complexo)
$router->post('/admin/import/interview', ImportController::class, 'import', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);

// D. Gestão de Utilizadores (CRUD Admin)
// Listar todos os users
$router->get('/admin/users', UserController::class, 'index', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
// Promover/Alterar user (PUT body: {id: 1, role: 'admin'})
$router->put('/admin/users', UserController::class, 'adminUpdate', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);
// Apagar/Banir user (DELETE body: {id: 1})
$router->delete('/admin/users', UserController::class, 'destroy', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);

// E. Gestão de Logs do Sistema
// Ver histórico
$router->get('/admin/logs', LogController::class, 'index', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
// Limpar histórico
$router->delete('/admin/logs', LogController::class, 'clear', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);


// ==================================================================
// EXECUÇÃO
// ==================================================================
$router->dispatch();
