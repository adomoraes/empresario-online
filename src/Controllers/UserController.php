<?php

namespace App\Controllers;

use App\Models\User;

class UserController
{
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
