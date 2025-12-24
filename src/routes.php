<?php

use App\Controllers\UserController;
use App\Controllers\InterviewController;
use App\Controllers\ArticleController;
use App\Controllers\CategoryController;
use App\Controllers\InterestController;
use App\Controllers\DashboardController;
use App\Controllers\LogController;
use App\Controllers\ImportController;

use App\Middlewares\AuthMiddleware;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\LogMiddleware;

/** @var \App\Config\Router $router */

// --- 1. AUTENTICAÇÃO ---
$router->post('/register', UserController::class, 'register');
$router->post('/login', UserController::class, 'login', [LogMiddleware::class]);

// --- 2. UTILIZADOR (PROFILE) ---
$router->get('/me', UserController::class, 'me', [AuthMiddleware::class]);
$router->put('/profile', UserController::class, 'updateProfile', [
    AuthMiddleware::class,
    LogMiddleware::class
]);

// --- 3. DASHBOARD E INTERESSES ---
$router->get('/dashboard', DashboardController::class, 'index', [AuthMiddleware::class]);
$router->get('/interests', InterestController::class, 'index', [AuthMiddleware::class]);
$router->post('/interests', InterestController::class, 'store', [AuthMiddleware::class]);
$router->delete('/interests', InterestController::class, 'delete', [AuthMiddleware::class]);

// --- 4. CONTEÚDOS ---
$router->get('/interviews', InterviewController::class, 'index', [AuthMiddleware::class]);
$router->post('/interviews', InterviewController::class, 'store', [AuthMiddleware::class]);

// --- 5. ÁREA ADMINISTRATIVA ---
// Categorias
$router->get('/categories', CategoryController::class, 'index');
$router->post('/categories', CategoryController::class, 'store', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);

// Artigos
$router->get('/articles', ArticleController::class, 'index', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
$router->post('/articles', ArticleController::class, 'store', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);

// Importação
$router->post('/admin/import/interview', ImportController::class, 'import', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);

// Users CRUD
$router->get('/admin/users', UserController::class, 'index', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
$router->put('/admin/users', UserController::class, 'adminUpdate', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);
$router->delete('/admin/users', UserController::class, 'destroy', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);

// Logs
$router->get('/admin/logs', LogController::class, 'index', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
$router->delete('/admin/logs', LogController::class, 'clear', [
    AuthMiddleware::class,
    AdminMiddleware::class,
    LogMiddleware::class
]);

// --- 6. LEITURA E HISTÓRICO ---
$router->get('/article', ArticleController::class, 'show', [
    AuthMiddleware::class,
    LogMiddleware::class
]);
$router->get('/interview', InterviewController::class, 'show', [
    AuthMiddleware::class,
    LogMiddleware::class
]);
