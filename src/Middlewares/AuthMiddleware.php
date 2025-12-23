<?php

namespace App\Middlewares;

use App\Models\PersonalAccessToken;

class AuthMiddleware
{
    /**
     * O método handle é executado antes do controlador.
     * Se a autenticação falhar, ele encerra o script (exit).
     */
    public function handle(): void
    {
        // 1. Tentar obter o cabeçalho Authorization
        $headers = $this->getAuthorizationHeader();

        // 2. Verificar se tem o formato "Bearer <token>"
        if (!$headers || !preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            $this->unauthorized('Token não fornecido ou formato inválido.');
        }

        $token = $matches[1];

        // 3. Validar no Banco de Dados
        $user = PersonalAccessToken::findUserByToken($token);

        if (!$user) {
            $this->unauthorized('Token inválido ou expirado.');
        }

        // 4. (Opcional) Guardar o utilizador numa variável global ou estática
        // para que o Controller saiba quem está logado.
        // Aqui usamos uma superglobal personalizada para simplicidade.
        $_REQUEST['user'] = $user;
    }

    /**
     * Função auxiliar para devolver erro 401 e parar tudo.
     */
    private function unauthorized(string $message): void
    {
        http_response_code(401);
        echo json_encode(['error' => $message]);
        exit; // Mata o processo aqui. O Controller nunca será chamado.
    }

    /**
     * Função robusta para apanhar o Header (funciona em Apache/Nginx/Docker)
     */
    private function getAuthorizationHeader(): ?string
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx ou FastCGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Corrige problemas de maiúsculas/minúsculas
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
}
