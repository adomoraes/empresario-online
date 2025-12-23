<?php

namespace App\Middlewares;

class AdminMiddleware
{
    public function handle(): void
    {
        // 1. Verificação de Segurança
        // Este middleware DEVE correr sempre DEPOIS do AuthMiddleware.
        // Se não houver user no request, algo correu mal na ordem.
        if (!isset($_REQUEST['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Acesso negado. Autenticação necessária primeiro.']);
            exit;
        }

        $user = $_REQUEST['user'];

        // 2. Verificar a Role
        // No schema.sql definimos o default como 'user'. O admin tem de ser 'admin'.
        if ($user['role'] !== 'admin') {
            http_response_code(403); // 403 Forbidden (Proibido)
            echo json_encode(['error' => 'Acesso restrito a Administradores.']);
            exit;
        }

        // Se passar aqui, o utilizador é Admin e o código segue para o Controller.
    }
}
