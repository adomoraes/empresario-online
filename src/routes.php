<?php

use App\Config\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\AdminMiddleware;

$router = new Router();

// =================================================================================
// 🌍 ROTAS PÚBLICAS (Acesso Livre)
// =================================================================================

// Documentação
$router->get('/', function () {
    include __DIR__ . '/../public/swagger.html';
});
$router->get('/api-docs', 'App\Controllers\SwaggerController@index');

// Autenticação
$router->post('/login', 'App\Controllers\UserController@login');
$router->post('/register', 'App\Controllers\UserController@register');

// Leitura de Conteúdo (Público conforme solicitado)
$router->get('/articles', 'App\Controllers\ArticleController@index');
$router->get('/article', 'App\Controllers\ArticleController@show');

$router->get('/interviews', 'App\Controllers\InterviewController@index');
$router->get('/interview', 'App\Controllers\InterviewController@show');

$router->get('/categories', 'App\Controllers\CategoryController@index');


// =================================================================================
// 🔒 ROTAS PROTEGIDAS (Requer Login)
// =================================================================================
// Montamos um grupo raiz para aplicar o AuthMiddleware
$router->mount('/', function () use ($router) {

    // Aplica validação de Token para tudo dentro deste bloco
    $router->use(new AuthMiddleware());

    // Área do Usuário
    $router->get('/me', 'App\Controllers\UserController@me');
    $router->put('/profile', 'App\Controllers\UserController@updateProfile');
    $router->get('/dashboard', 'App\Controllers\DashboardController@index');

    // Interesses
    $router->post('/interests', 'App\Controllers\InterestController@store');
    $router->get('/interests', 'App\Controllers\InterestController@index');

    // =============================================================================
    // 🛡️ ÁREA DE ADMINISTRAÇÃO (Requer Role 'admin')
    // =============================================================================
    // Montamos um sub-grupo para aplicar o AdminMiddleware
    $router->mount('/', function () use ($router) {

        // Aplica validação de Admin
        $router->use(new AdminMiddleware());

        // --- 1. GESTÃO DE CONTEÚDO (CRIAR / EDITAR / APAGAR) ---

        // Artigos
        $router->post('/articles', 'App\Controllers\ArticleController@store');
        $router->put('/articles', 'App\Controllers\ArticleController@update');
        $router->delete('/articles', 'App\Controllers\ArticleController@destroy');

        // Categorias
        $router->post('/categories', 'App\Controllers\CategoryController@store');
        $router->put('/categories', 'App\Controllers\CategoryController@update');
        $router->delete('/categories', 'App\Controllers\CategoryController@destroy');

        // Entrevistas
        $router->post('/interviews', 'App\Controllers\InterviewController@store');
        $router->put('/interviews', 'App\Controllers\InterviewController@update');
        $router->delete('/interviews', 'App\Controllers\InterviewController@destroy');

        // --- 2. PAINEL ADMINISTRATIVO (Rotas com prefixo /admin explicito) ---

        // Users
        $router->get('/admin/users', 'App\Controllers\UserController@index');
        $router->put('/admin/users', 'App\Controllers\UserController@adminUpdate');
        $router->delete('/admin/users', 'App\Controllers\UserController@destroy');

        // Logs
        $router->get('/admin/logs', 'App\Controllers\LogController@index');
        $router->delete('/admin/logs', 'App\Controllers\LogController@clear');

        // Dashboard & Import
        $router->get('/admin/dashboard', 'App\Controllers\AdminDashboardController@index');
        // Nota: Ajustei para importInterview pois é o método que existe no controller atual
        $router->post('/admin/import/interview', 'App\Controllers\ImportController@importInterview');
    });
});

// Executa o router
$router->dispatch();
