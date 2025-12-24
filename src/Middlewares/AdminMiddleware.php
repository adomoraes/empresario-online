<?php

namespace App\Middlewares;

use App\Config\Database;
use App\Config\AppHelper; // <--- Importante
use PDO;

class AdminMiddleware
{
    public function handle()
    {
        // O user já foi injetado no $_REQUEST pelo AuthMiddleware
        if (!isset($_REQUEST['user'])) {
            AppHelper::sendResponse(401, ['error' => 'Utilizador não autenticado.']);
            return;
        }

        $user = $_REQUEST['user'];

        if ($user['role'] !== 'admin') {
            AppHelper::sendResponse(403, ['error' => 'Acesso restrito a Administradores.']);
            return; // O AppHelper em modo teste impede o exit, mas aqui fazemos return para parar o fluxo
        }
    }
}
