<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\PersonalAccessToken;
use OpenApi\Attributes as OA;
use App\Config\Database;
use App\Config\AppHelper;
use PDO;

class UserController
{
    /**
     * Lista todos os usuários (Apenas Admin)
     */
    #[OA\Get(
        path: '/admin/users',
        tags: ['Admin'],
        summary: 'Listar todos os usuários',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de usuários recuperada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'role', type: 'string')
                            ]
                        ))
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Acesso negado')
        ]
    )]
    public function index()
    {
        $users = User::all();
        AppHelper::sendResponse(200, ['data' => $users]);
    }

    /**
     * Login do usuário.
     */
    #[OA\Post(
        path: '/login',
        tags: ['Autenticação'],
        summary: 'Autenticação de usuário',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'user@teste.com'),
                    new OA\Property(property: 'password', type: 'string', example: '123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'user', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Credenciais inválidas')
        ]
    )]
    public function login()
    {
        $data = AppHelper::getJsonInput();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            AppHelper::sendResponse(400, ['error' => 'Email e senha são obrigatórios.']);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            AppHelper::sendResponse(401, ['error' => 'Credenciais inválidas.']);
            return;
        }

        // Gerar Token
        $token = bin2hex(random_bytes(32));

        // Limpar tokens antigos e inserir novo
        $pdo->prepare("DELETE FROM personal_access_tokens WHERE user_id = ?")->execute([$user['id']]);
        $stmtToken = $pdo->prepare("INSERT INTO personal_access_tokens (user_id, token, name) VALUES (?, ?, ?)");
        $stmtToken->execute([$user['id'], $token, 'AuthToken']);

        AppHelper::sendResponse(200, [
            'message' => 'Login efetuado com sucesso!',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ]);
    }

    /**
     * Regista um novo usuário.
     */
    #[OA\Post(
        path: '/register',
        tags: ['Autenticação'],
        summary: 'Regista um novo usuário',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Nome do Usuário'),
                    new OA\Property(property: 'email', type: 'string', example: 'user@exemplo.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'senha123')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuário registado com sucesso'),
            new OA\Response(response: 400, description: 'Dados inválidos'),
            new OA\Response(response: 409, description: 'Email já registado')
        ]
    )]
    public function register()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            AppHelper::sendResponse(400, ['error' => 'Por favor preencha nome, email e senha.']);
            return;
        }

        try {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Usa o método create do Model User
            $userId = User::create(
                $data['name'],
                $data['email'],
                $passwordHash
            );

            AppHelper::sendResponse(201, [
                'message' => 'Usuário registado com sucesso!',
                'user_id' => $userId
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                AppHelper::sendResponse(409, ['error' => 'Este email já está registado.']);
            } else {
                AppHelper::sendResponse(500, ['error' => 'Erro interno ao criar usuário.']);
            }
        }
    }

    /**
     * Retorna os dados do usuário autenticado.
     */
    #[OA\Get(
        path: '/me',
        tags: ['Usuário'],
        summary: 'Retorna os dados do usuário autenticado',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sucesso'),
            new OA\Response(response: 401, description: 'Não autorizado')
        ]
    )]
    public function me()
    {
        // O usuário é injetado pelo AuthMiddleware
        $user = $_REQUEST['user'];

        AppHelper::sendResponse(200, [
            'message' => 'Você está autenticado!',
            'data' => $user
        ]);
    }

    /**
     * PUT /profile
     * Atualiza os dados do próprio usuário logado
     */
    public function updateProfile()
    {
        $user = $_REQUEST['user'];
        $data = AppHelper::getJsonInput();

        $newName = $data['name'] ?? $user['name'];
        $newEmail = $data['email'] ?? $user['email'];

        // Aqui podes usar User::update se adaptares para aceitar só nome/email
        // Ou fazer direto para simplificar:
        $pdo = Database::getConnection();
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
     * Admin atualiza dados de outro usuário (ex: mudar role)
     */
    #[OA\Put(
        path: '/admin/users',
        tags: ['Admin'],
        summary: 'Atualizar usuário (Promover/Alterar)',
        description: 'Permite ao admin alterar dados de qualquer user, inclusive mudar a ROLE para admin.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 2),
                    new OA\Property(property: 'role', type: 'string', example: 'admin'),
                    new OA\Property(property: 'name', type: 'string', example: 'Novo Nome')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuário atualizado'),
            new OA\Response(response: 400, description: 'Erro de validação')
        ]
    )]
    public function adminUpdate()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID do usuário alvo é obrigatório.']);
            return;
        }

        $currentUser = $_REQUEST['user'];
        // Proteção: Admin não pode tirar o próprio admin para não se trancar fora
        if ($data['id'] == $currentUser['id'] && isset($data['role']) && $data['role'] !== 'admin') {
            AppHelper::sendResponse(400, ['error' => 'Você não pode remover seu próprio acesso de admin.']);
            return;
        }

        User::update($data['id'], $data);
        AppHelper::sendResponse(200, ['message' => 'Usuário atualizado com sucesso.']);
    }

    /**
     * DELETE /admin/users
     * Admin remove um usuário do sistema.
     */
    #[OA\Delete(
        path: '/admin/users',
        tags: ['Admin'],
        summary: 'Remover usuário (Banir)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 5)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuário removido'),
            new OA\Response(response: 400, description: 'Erro ao remover')
        ]
    )]
    public function destroy()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID do usuário alvo é obrigatório.']);
            return;
        }

        $currentUser = $_REQUEST['user'];
        if ($data['id'] == $currentUser['id']) {
            AppHelper::sendResponse(400, ['error' => 'Você não pode apagar a sua própria conta aqui.']);
            return;
        }

        if (User::delete($data['id'])) {
            AppHelper::sendResponse(200, ['message' => 'Usuário removido do sistema.']);
        } else {
            AppHelper::sendResponse(500, ['error' => 'Erro ao remover usuário.']);
        }
    }
}
