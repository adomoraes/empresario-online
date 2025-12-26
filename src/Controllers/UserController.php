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
     * Lista todos os utilizadores (Apenas Admin)
     */
    public function index()
    {
        $users = User::all();
        AppHelper::sendResponse(200, ['data' => $users]);
    }

    // ...

    #[OA\Post(
        path: '/login',
        tags: ['Autenticação'],
        summary: 'Autenticação de utilizador',
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
    /**
     * Login do utilizador.
     * Recebe email/password -> Retorna Token.
     */
    public function login()
    {
        // 1. Receber dados (Usa o Helper para suportar Testes Unitários)
        $data = AppHelper::getJsonInput();

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            AppHelper::sendResponse(400, ['error' => 'Email e senha são obrigatórios.']);
            return;
        }

        $pdo = Database::getConnection();

        // 2. Buscar utilizador pelo Email
        $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. VERIFICAÇÃO DE SEGURANÇA (Onde o erro estava acontecendo)
        // Se o user não existe OU a senha não bate com o hash -> Erro 401
        if (!$user || !password_verify($password, $user['password'])) {
            AppHelper::sendResponse(401, ['error' => 'Credenciais inválidas.']);
            return;
        }

        // 4. Gerar Token (Simples)
        // Nota: Em produção usarias JWT, aqui usamos um token aleatório no banco
        $token = bin2hex(random_bytes(32)); // 64 caracteres

        // Guardar token no banco
        // Remove tokens antigos deste user para manter a tabela limpa (opcional, mas recomendado)
        $pdo->prepare("DELETE FROM personal_access_tokens WHERE user_id = ?")->execute([$user['id']]);

        // Insere o novo
        $stmtToken = $pdo->prepare("INSERT INTO personal_access_tokens (user_id, token, name) VALUES (?, ?, ?)");
        $stmtToken->execute([$user['id'], $token, 'AuthToken']);

        // 5. Retornar Sucesso
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
     * Regista um novo utilizador.
     */
    #[OA\Post(
        path: '/register',
        tags: ['Autenticação'],
        summary: 'Regista um novo utilizador',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Nome do Utilizador'),
                    new OA\Property(property: 'email', type: 'string', example: 'user@exemplo.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'senha123')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Utilizador registado com sucesso'),
            new OA\Response(response: 400, description: 'Dados inválidos'),
            new OA\Response(response: 409, description: 'Email já registado')
        ]
    )]
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
    #[OA\Get(
        path: '/me',
        tags: ['Utilizador'],
        summary: 'Retorna os dados do utilizador autenticado',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autorizado')
        ]
    )]
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
