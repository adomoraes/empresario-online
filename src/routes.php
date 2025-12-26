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

        $router->post('/articles', 'App\Controllers\ArticleController@store');
        $router->post('/categories', 'App\Controllers\CategoryController@store');
        $router->post('/interviews', 'App\Controllers\InterviewController@store');
    });
});
