<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper; // <--- Importante
use PDO;

use OpenApi\Attributes as OA;

class ArticleController
{
    public function index()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, title, content FROM articles ORDER BY created_at DESC");
        AppHelper::sendResponse(200, ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
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
            new OA\Response(response: 400, description: 'ID do artigo é necessário'),
            new OA\Response(response: 404, description: 'Artigo não encontrado')
        ]
    )]
    public function show()
    {
        // 1. Validação básica do ID
        $id = $_GET['id'] ?? null;
        if (!$id) {
            AppHelper::sendResponse(400, ['error' => 'ID necessário']);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$article) {
            AppHelper::sendResponse(404, ['error' => 'Artigo não encontrado']);
            return;
        }

        // 2. Auth Opcional (Silent Auth)
        // Se o utilizador não veio de um middleware, tentamos identificá-lo agora
        if (!isset($_REQUEST['user'])) {
            $this->tryIdentifyUser();
        }

        // 3. Gravar Histórico (Agora o UserHistory existe!)
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

    // Método auxiliar privado para tentar ler o token sem bloquear
    private function tryIdentifyUser()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }
        $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;

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
                    new OA\Property(property: 'title', type: 'string', example: 'Título do Artigo'),
                    new OA\Property(property: 'content', type: 'string', example: 'Conteúdo do artigo...'),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Artigo criado'),
            new OA\Response(response: 400, description: 'Dados incompletos'),
            new OA\Response(response: 401, description: 'Não autorizado')
        ]
    )]
    public function store()
    {
        $data = AppHelper::getJsonInput(); // <--- Para funcionar no PHPUnit

        if (empty($data['title']) || empty($data['content'])) {
            AppHelper::sendResponse(400, ['error' => 'Dados incompletos']);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO articles (title, content, category_id, user_id) VALUES (?, ?, ?, ?)");

        // Assume que o middleware de Admin já validou o user e ele está em $_REQUEST['user']
        // Se for um teste sem middleware, podemos precisar de um fallback, mas o teste cuidará disso.
        $userId = $_REQUEST['user']['id'] ?? 1;

        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['category_id'] ?? null,
            $userId
        ]);

        AppHelper::sendResponse(201, ['message' => 'Artigo criado', 'id' => $pdo->lastInsertId()]);
    }

    #[OA\Put(
        path: '/articles',
        tags: ['Admin'],
        summary: 'Atualiza um artigo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id', 'title', 'content'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Atualizado'),
            new OA\Response(response: 400, description: 'Erro')
        ]
    )]
    public function update()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['id']) || empty($data['title']) || empty($data['content'])) {
            AppHelper::sendResponse(400, ['error' => 'ID, Título e Conteúdo são obrigatórios']);
            return;
        }

        if (\App\Models\Article::update($data['id'], $data['title'], $data['content'], $data['category_id'] ?? null)) {
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
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'id', type: 'integer')])
        ),
        responses: [new OA\Response(response: 200, description: 'Removido')]
    )]
    public function destroy()
    {
        $data = AppHelper::getJsonInput();
        if (empty($data['id'])) {
            AppHelper::sendResponse(400, ['error' => 'ID obrigatório']);
            return;
        }

        if (\App\Models\Article::delete($data['id'])) {
            AppHelper::sendResponse(200, ['message' => 'Artigo removido']);
        } else {
            AppHelper::sendResponse(500, ['error' => 'Erro ao remover']);
        }
    }
}
