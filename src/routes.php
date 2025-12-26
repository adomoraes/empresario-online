<?php

use OpenApi\Attributes as OA;
use App\Config\Router;

$router = new Router();

// --- ROTAS PÚBLICAS ---
$router->get('/api-docs', 'App\Controllers\SwaggerController@index');
$router->post('/login', 'App\Controllers\UserController@login');
$router->post('/register', 'App\Controllers\UserController@register');

$router->get('/article', 'App\Controllers\ArticleController@show');
$router->get('/interviews', 'App\Controllers\InterviewController@index');
$router->get('/interview', 'App\Controllers\InterviewController@show');

// --- ROTAS PROTEGIDAS ---
$router->group(['before' => 'App\Middlewares\AuthMiddleware'], function ($router) {

    $router->get('/me', 'App\Controllers\UserController@me');
    $router->get('/dashboard', 'App\Controllers\DashboardController@index');

    // --- ROTAS DE ADMIN ---
    $router->group(['before' => 'App\Middlewares\AdminMiddleware'], function ($router) {

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

        // 1. Gestão de Utilizadores (UserController)
        $router->get('/admin/users', 'App\Controllers\UserController@index');       // Listar todos
        $router->put('/admin/users', 'App\Controllers\UserController@adminUpdate'); // Promover/Alterar Role
        $router->delete('/admin/users', 'App\Controllers\UserController@destroy');  // Banir/Remover

        // 2. Gestão de Logs (LogController)
        $router->get('/admin/logs', 'App\Controllers\LogController@index');         // Ver histórico
        $router->delete('/admin/logs', 'App\Controllers\LogController@clear');      // Limpar histórico

        // 3. Importação (ImportController)
        $router->post('/admin/import/interview', 'App\Controllers\ImportController@import'); // Importar JSON

        // 4. Dashboard Admin (Passo 2 deste plano)
        $router->get('/admin/dashboard', 'App\Controllers\AdminDashboardController@index');
    });
});
