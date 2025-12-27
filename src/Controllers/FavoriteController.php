<?php

namespace App\Controllers;

use App\Config\AppHelper;
use App\Models\UserFavorite;
use OpenApi\Attributes as OA;

class FavoriteController
{
    #[OA\Get(
        path: '/favorites',
        tags: ['Usuário'],
        summary: 'Lista os favoritos do usuário',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de favoritos',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['article', 'interview']),
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'date', type: 'string', format: 'date-time')
                ]))
            )
        ]
    )]
    public function index()
    {
        $user = $_REQUEST['user'];
        $favorites = UserFavorite::all($user['id']);
        AppHelper::sendResponse(200, ['data' => $favorites]);
    }

    #[OA\Post(
        path: '/favorites',
        tags: ['Usuário'],
        summary: 'Adiciona um item aos favoritos',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'id'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['article', 'interview']),
                    new OA\Property(property: 'id', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Adicionado aos favoritos'),
            new OA\Response(response: 400, description: 'Dados inválidos')
        ]
    )]
    public function store()
    {
        $user = $_REQUEST['user'];
        $data = AppHelper::getJsonInput();

        if (empty($data['type']) || empty($data['id']) || !in_array($data['type'], ['article', 'interview'])) {
            AppHelper::sendResponse(400, ['error' => 'Tipo (article/interview) e ID são obrigatórios']);
            return;
        }

        UserFavorite::add($user['id'], $data['type'], $data['id']);
        AppHelper::sendResponse(201, ['message' => 'Adicionado aos favoritos']);
    }

    #[OA\Delete(
        path: '/favorites',
        tags: ['Usuário'],
        summary: 'Remove um item dos favoritos',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'id'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['article', 'interview']),
                    new OA\Property(property: 'id', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Removido dos favoritos')
        ]
    )]
    public function destroy()
    {
        $user = $_REQUEST['user'];
        $data = AppHelper::getJsonInput();

        if (empty($data['type']) || empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'Tipo e ID obrigatórios']);
            return;
        }

        UserFavorite::remove($user['id'], $data['type'], $data['id']);
        AppHelper::sendResponse(200, ['message' => 'Removido dos favoritos']);
    }
}
