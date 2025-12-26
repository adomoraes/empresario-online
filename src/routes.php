<?php

use OpenApi\Attributes as OA;
use App\Config\Router;

$router = new Router();

// --- ROTAS PÚBLICAS ---
$router->get('/api-docs', 'App\Controllers\SwaggerController@index');
$router->post('/login', 'App\Controllers\UserController@login');
$router->post('/register', 'App\Controllers\UserController@register');

// Artigos (Leitura Pública)
$router->get('/articles', 'App\Controllers\ArticleController@index'); // Adicionei esta para listar artigos
$router->get('/article', 'App\Controllers\ArticleController@show');

// Entrevistas (Leitura Pública)
$router->get('/interviews', 'App\Controllers\InterviewController@index');
$router->get('/interview', 'App\Controllers\InterviewController@show');

// Categorias (Leitura Pública - Opcional, se tiveres o index no controller)
$router->get('/categories', 'App\Controllers\CategoryController@index');

// --- ROTAS PROTEGIDAS (Requer Login) ---
$router->group(['before' => 'App\Middlewares\AuthMiddleware'], function ($router) {

    $router->get('/me', 'App\Controllers\UserController@me');
    $router->get('/dashboard', 'App\Controllers\DashboardController@index');

    // --- ROTAS DE ADMIN (Requer Role 'admin') ---
    $router->group(['before' => 'App\Middlewares\AdminMiddleware'], function ($router) {

        // 1. Gestão de Conteúdo (CRIAR) - Já existiam
        $router->post('/articles', 'App\Controllers\ArticleController@store');
        $router->post('/categories', 'App\Controllers\CategoryController@store');
        $router->post('/interviews', 'App\Controllers\InterviewController@store');

        // === NOVAS ROTAS (ATUALIZAR e APAGAR) ===

        // Artigos
        $router->put('/articles', 'App\Controllers\ArticleController@update');
        $router->delete('/articles', 'App\Controllers\ArticleController@destroy');

        // Categorias
        $router->put('/categories', 'App\Controllers\CategoryController@update');
        $router->delete('/categories', 'App\Controllers\CategoryController@destroy');

        // Entrevistas
        $router->put('/interviews', 'App\Controllers\InterviewController@update');
        $router->delete('/interviews', 'App\Controllers\InterviewController@destroy');

        // ========================================

        // 2. Gestão de Utilizadores
        $router->get('/admin/users', 'App\Controllers\UserController@index');
        $router->put('/admin/users', 'App\Controllers\UserController@adminUpdate');
        $router->delete('/admin/users', 'App\Controllers\UserController@destroy');

        // 3. Gestão de Logs
        $router->get('/admin/logs', 'App\Controllers\LogController@index');
        $router->delete('/admin/logs', 'App\Controllers\LogController@clear');

        // 4. Importação e Dashboard
        $router->post('/admin/import/interview', 'App\Controllers\ImportController@import');
        $router->get('/admin/dashboard', 'App\Controllers\AdminDashboardController@index');
    });
});
