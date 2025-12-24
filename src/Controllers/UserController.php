<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\PersonalAccessToken;
use App\Config\AppHelper;

class UserController
{
    /**
     * Lista todos os utilizadores (Apenas Admin)
     */
    public function index()
    {
        $users = User::all();
        AppHelper::sendResponse(200, ['data' => $users]);
    }

    /**
     * Login do utilizador.
     * Recebe email/password -> Retorna Token.
     */
    public function login()
    {
        $data = AppHelper::getJsonInput();

        // 1. Validação simples
        if (empty($data['email']) || empty($data['password'])) {
            AppHelper::sendResponse(400, ['error' => 'Email e senha são obrigatórios.']);
        }

        // 2. Buscar utilizador pelo email
        $user = User::findByEmail($data['email']);

        // 3. Verificar se o utilizador existe E se a senha bate
        if (!$user || !password_verify($data['password'], $user->password)) {
            AppHelper::sendResponse(401, ['error' => 'Credenciais inválidas.']);
        }

        // 4. Gerar o Token
        try {
            $token = PersonalAccessToken::create($user->id, 'Dispositivo Postman');

            AppHelper::sendResponse(200, [
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
            AppHelper::sendResponse(500, ['error' => 'Erro ao gerar token: ' . $e->getMessage()]);
        }
    }

    /**
     * Regista um novo utilizador.
     */
    public function register()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            AppHelper::sendResponse(400, ['error' => 'Por favor preencha nome, email e senha.']);
        }

        try {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

            $userId = User::create(
                $data['name'],
                $data['email'],
                $passwordHash
            );

            AppHelper::sendResponse(201, [
                'message' => 'Utilizador registado com sucesso!',
                'user_id' => $userId
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                AppHelper::sendResponse(409, ['error' => 'Este email já está registado.']);
            } else {
                AppHelper::sendResponse(500, ['error' => 'Erro interno ao criar utilizador.']);
            }
        }
    }

    /**
     * Retorna os dados do utilizador autenticado.
     */
    public function me()
    {
        $user = $_REQUEST['user'];

        AppHelper::sendResponse(200, [
            'message' => 'Você está autenticado!',
            'data' => $user
        ]);
    }

    /**
     * PUT /profile
     * Atualiza os dados do próprio utilizador logado
     */
    public function updateProfile()
    {
        $user = $_REQUEST['user'];
        $data = AppHelper::getJsonInput();

        $newName = $data['name'] ?? $user['name'];
        $newEmail = $data['email'] ?? $user['email'];

        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");

        try {
            $stmt->execute([$newName, $newEmail, $user['id']]);
            AppHelper::sendResponse(200, ['message' => 'Perfil atualizado com sucesso!']);
        } catch (\PDOException $e) {
            AppHelper::sendResponse(500, ['error' => 'Erro ao atualizar. Email pode já existir.']);
        }
    }

    /**
     * PUT /admin/users
     * Admin atualiza dados de outro utilizador (ex: mudar role)
     */
    public function adminUpdate()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID do utilizador alvo é obrigatório.']);
        }

        $currentUser = $_REQUEST['user'];
        if ($data['id'] == $currentUser['id'] && isset($data['role']) && $data['role'] !== 'admin') {
            AppHelper::sendResponse(400, ['error' => 'Você não pode remover seu próprio acesso de admin.']);
        }

        User::update($data['id'], $data);
        AppHelper::sendResponse(200, ['message' => 'Utilizador atualizado com sucesso.']);
    }

    /**
     * DELETE /admin/users
     * Admin remove um utilizador do sistema.
     */
    public function destroy()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID do utilizador alvo é obrigatório.']);
        }

        $currentUser = $_REQUEST['user'];
        if ($data['id'] == $currentUser['id']) {
            AppHelper::sendResponse(400, ['error' => 'Você não pode apagar a sua própria conta aqui.']);
        }

        if (User::delete($data['id'])) {
            AppHelper::sendResponse(200, ['message' => 'Utilizador removido do sistema.']);
        } else {
            AppHelper::sendResponse(500, ['error' => 'Erro ao remover utilizador.']);
        }
    }
}
