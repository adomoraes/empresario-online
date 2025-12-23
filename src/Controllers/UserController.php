<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\PersonalAccessToken;

class UserController
{
    /**
     * Login do utilizador.
     * Recebe email/password -> Retorna Token.
     */
    public function login()
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        // 1. Validação simples
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email e senha são obrigatórios.']);
            return;
        }

        // 2. Buscar utilizador pelo email
        $user = User::findByEmail($data['email']);

        // 3. Verificar se o utilizador existe E se a senha bate
        // password_verify compara a senha em texto com o hash do banco
        if (!$user || !password_verify($data['password'], $user->password)) {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Credenciais inválidas.']);
            return;
        }

        // 4. Gerar o Token (O nosso "Sanctum" manual)
        try {
            $token = PersonalAccessToken::create($user->id, 'Dispositivo Postman');

            echo json_encode([
                'message' => 'Login efetuado com sucesso!',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao gerar token: ' . $e->getMessage()]);
        }
    }

    /**
     * Regista um novo utilizador.
     * Equivalente ao store() no Laravel.
     */
    public function register()
    {
        // 1. Ler o JSON enviado pelo Postman/Frontend
        // 'php://input' é um fluxo de leitura que permite ler o corpo bruto da requisição
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        // 2. Validação Básica (Substituto do $request->validate)
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Por favor preencha nome, email e senha.']);
            return;
        }

        try {
            // 3. Encriptar a senha (Segurança obrigatória)
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

            // 4. Chamar o Model para criar no banco
            $userId = User::create(
                $data['name'],
                $data['email'],
                $passwordHash
            );

            // 5. Devolver resposta de sucesso
            http_response_code(201); // Created
            echo json_encode([
                'message' => 'Utilizador registado com sucesso!',
                'user_id' => $userId
            ]);
        } catch (\PDOException $e) {
            // Capturar erro de email duplicado (código 23000 no MySQL)
            if ($e->getCode() == 23000) {
                http_response_code(409); // Conflict
                echo json_encode(['error' => 'Este email já está registado.']);
            } else {
                http_response_code(500); // Server Error
                echo json_encode(['error' => 'Erro interno ao criar utilizador.']);
            }
        }
    }
}
