<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper;
use App\Config\HttpResponseException;
use App\Models\Article;
use PDO;

use OpenApi\Attributes as OA;

class ArticleController
{
    public function index()
    {
        $articles = Article::all();
        AppHelper::sendResponse(200, ['data' => $articles]);
    }

    #[OA\Get(
        path: '/article',
        tags: ['Artigos'],
        summary: 'Busca um artigo por ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Artigo encontrado'),
            new OA\Response(response: 400, description: 'ID necessário'),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            AppHelper::sendResponse(400, ['error' => 'ID necessário']);
            return;
        }

        $article = Article::find($id);
        if (!$article) {
            AppHelper::sendResponse(404, ['error' => 'Artigo não encontrado']);
            return;
        }

        // Auth Opcional (Silent Auth)
        if (!isset($_REQUEST['user'])) {
            $this->tryIdentifyUser();
        }

        // Gravar Histórico
        if (isset($_REQUEST['user'])) {
            \App\Models\UserHistory::record(
                $_REQUEST['user']['id'],
                $article['category_id'] ?? 0,
                'article',
                $id
            );
        }

        AppHelper::sendResponse(200, ['data' => $article]);
    }

    private function tryIdentifyUser()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        // Verifica Authorization, authorization, e HTTP_AUTHORIZATION
        $authHeader = $headers['Authorization']
            ?? $headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? null;

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.role 
                FROM personal_access_tokens t
                JOIN users u ON t.user_id = u.id
                WHERE t.token = ?
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_REQUEST['user'] = $user;
            }
        }
    }

    #[OA\Post(
        path: '/articles',
        tags: ['Admin'],
        summary: 'Cria um novo artigo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Criado'),
            new OA\Response(response: 400, description: 'Erro')
        ]
    )]
    public function store()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['title']) || empty($data['content'])) {
            AppHelper::sendResponse(400, ['error' => 'Dados incompletos']);
            return;
        }

        $userId = $_REQUEST['user']['id'] ?? 1;

        try {
            $id = Article::create(
                $userId,
                $data['category_id'] ?? 0,
                $data['title'],
                $data['content']
            );
            AppHelper::sendResponse(201, ['message' => 'Artigo criado', 'id' => $id]);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    #[OA\Put(
        path: '/articles',
        tags: ['Admin'],
        summary: 'Atualiza um artigo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'integer')])
        ),
        responses: [new OA\Response(response: 200, description: 'Atualizado')]
    )]
    public function update()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID obrigatório']);
            return;
        }

        // CORREÇÃO: Passamos o array $data inteiro, compatível com o Model
        if (Article::update($data['id'], $data)) {
            AppHelper::sendResponse(200, ['message' => 'Artigo atualizado com sucesso']);
        } else {
            AppHelper::sendResponse(500, ['error' => 'Erro ao atualizar artigo']);
        }
    }

    #[OA\Delete(
        path: '/articles',
        tags: ['Admin'],
        summary: 'Remove um artigo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'integer')])),
        responses: [new OA\Response(response: 200, description: 'Removido')]
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
