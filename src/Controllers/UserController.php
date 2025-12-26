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
    #[OA\Get(
        path: '/admin/users',
        tags: ['Admin'],
        summary: 'Listar todos os utilizadores',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de utilizadores recuperada',
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

    // ... (Mantém os métodos login, register, me, updateProfile iguais) ...

    // ... (Podes colar este bloco abaixo sobre os métodos adminUpdate e destroy existentes) ...

    /**
     * PUT /admin/users
     * Admin atualiza dados de outro utilizador (ex: mudar role)
     */
    #[OA\Put(
        path: '/admin/users',
        tags: ['Admin'],
        summary: 'Atualizar utilizador (Promover/Alterar)',
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
            new OA\Response(response: 200, description: 'Utilizador atualizado'),
            new OA\Response(response: 400, description: 'Erro de validação')
        ]
    )]
    public function adminUpdate()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID do utilizador alvo é obrigatório.']);
        }

        $currentUser = $_REQUEST['user'];
        // Proteção: Admin não pode tirar o próprio admin para não se trancar fora
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
    #[OA\Delete(
        path: '/admin/users',
        tags: ['Admin'],
        summary: 'Remover utilizador (Banir)',
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
            new OA\Response(response: 200, description: 'Utilizador removido'),
            new OA\Response(response: 400, description: 'Erro ao remover')
        ]
    )]
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
