<?php

namespace App\Middlewares;

use App\Models\AccessLog;

class LogMiddleware
{
    public function handle(): void
    {
        // Tenta pegar o user, se já tiver passado pelo AuthMiddleware
        $userId = isset($_REQUEST['user']) ? $_REQUEST['user']['id'] : null;
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $ip = $_SERVER['REMOTE_ADDR'];

        AccessLog::create($userId, $method, $uri, $ip);

        // O Log não bloqueia nada, apenas grava e deixa seguir
    }
}
