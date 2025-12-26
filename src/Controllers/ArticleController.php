<?php

namespace App\Controllers;

use App\Models\Article;
use App\Config\AppHelper;
use OpenApi\Attributes as OA;

class ArticleController
{
    #[OA\Get(
        path: '/articles',
        tags: ['Conteúdo (Premium)'],
        summary: 'Lista todos os artigos',
        description: 'Retorna a lista de artigos. Requer login.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de artigos',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'excerpt', type: 'string')
                ]))
            ),
            new OA\Response(response: 401, description: 'Não autorizado')
        ]
    )]
    public function index()
    {
        $articles = Article::all();
        AppHelper::sendResponse(200, ['data' => $articles]);
    }

    #[OA\Get(
        path: '/article',
        tags: ['Conteúdo (Premium)'],
        summary: 'Lê um artigo completo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalhes do artigo'),
            new OA\Response(response: 404, description: 'Artigo não encontrado')
        ]
    )]
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            AppHelper::sendResponse(400, ['error' => 'ID obrigatório']);
            return;
        }

        $article = Article::find($id);
        if ($article) {
            AppHelper::sendResponse(200, ['data' => $article]);
        } else {
            AppHelper::sendResponse(404, ['error' => 'Artigo não encontrado']);
        }
    }

    // --- MÉTODOS DE ADMIN ---

    #[OA\Post(
        path: '/articles',
        tags: ['Admin'],
        summary: 'Criar Artigo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['title', 'content', 'category_id'],
            properties: [
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'content', type: 'string'),
                new OA\Property(property: 'category_id', type: 'integer')
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Criado')]
    )]
    public function store()
    {
        $data = AppHelper::getJsonInput();
        if (empty($data['title']) || empty($data['content']) || empty($data['category_id'])) {
            AppHelper::sendResponse(400, ['error' => 'Dados incompletos']);
            return;
        }

        $user = $_REQUEST['user'] ?? ['id' => 1];

        try {
            $id = Article::create($data['title'], $data['content'], $data['category_id'], $user['id']);
            AppHelper::sendResponse(201, ['message' => 'Artigo criado', 'id' => $id]);
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    #[OA\Put(
        path: '/articles',
        tags: ['Admin'],
        summary: 'Atualizar Artigo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['id', 'title'],
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'content', type: 'string'),
                new OA\Property(property: 'category_id', type: 'integer')
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Atualizado')]
    )]
    public function update()
    {
        $data = AppHelper::getJsonInput();
        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID obrigatório']);
            return;
        }

        if (Article::update($data['id'], $data)) {
            AppHelper::sendResponse(200, ['message' => 'Artigo atualizado']);
        } else {
            AppHelper::sendResponse(500, ['error' => 'Erro ao atualizar']);
        }
    }

    #[OA\Delete(
        path: '/articles',
        tags: ['Admin'],
        summary: 'Apagar Artigo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'integer')])),
        responses: [new OA\Response(response: 200, description: 'Apagado')]
    )]
    public function destroy()
    {
        $data = AppHelper::getJsonInput();
        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID obrigatório']);
            return;
        }

        if (Article::delete($data['id'])) {
            AppHelper::sendResponse(200, ['message' => 'Artigo removido']);
        } else {
            AppHelper::sendResponse(500, ['error' => 'Erro ao remover']);
        }
    }
}
