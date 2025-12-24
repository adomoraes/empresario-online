<?php

namespace App\Middlewares;

use App\Config\Database;
use App\Config\AppHelper;
use PDO;

class AuthMiddleware
{
    public function handle()
    {
        // 1. Tenta obter headers de forma segura
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        // 2. Busca o Header Authorization
        // Prioridade:
        // A. getallheaders() (Apache/Produção)
        // B. $_SERVER['HTTP_AUTHORIZATION'] (Nginx, CLI e PHPUnit)
        $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (!$authHeader) {
            AppHelper::sendResponse(401, ['error' => 'Token não fornecido.']);
            return;
        }

        // 3. Extrair o Token (Bearer <token>)
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            AppHelper::sendResponse(401, ['error' => 'Formato do token inválido.']);
            return;
        }

        $token = $matches[1];
        $pdo = Database::getConnection();

        // 4. Validar Token no Banco
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.role 
            FROM personal_access_tokens t
            JOIN users u ON t.user_id = u.id
            WHERE t.token = ?
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            AppHelper::sendResponse(401, ['error' => 'Token inválido ou expirado.']);
            return;
        }

        // 5. Injeta o user na requisição
        $_REQUEST['user'] = $user;
    }
}
