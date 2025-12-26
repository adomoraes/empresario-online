<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\AppHelper;
use App\Config\HttpResponseException; // <--- Importante!
use PDO;
use OpenApi\Attributes as OA;

class InterviewController
{
    #[OA\Get(
        path: '/interviews',
        tags: ['Entrevistas'],
        summary: 'Lista todas as entrevistas',
        responses: [
            new OA\Response(response: 200, description: 'Lista de entrevistas')
        ]
    )]
    public function index()
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT i.id, i.title, i.slug, i.published_at,
                   GROUP_CONCAT(c.name) as categories
            FROM interviews i
            LEFT JOIN interview_categories ic ON i.id = ic.interview_id
            LEFT JOIN categories c ON ic.category_id = c.id
            GROUP BY i.id
            ORDER BY i.published_at DESC
        ";
        $stmt = $pdo->query($sql);
        AppHelper::sendResponse(200, ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    #[OA\Get(
        path: '/interview',
        tags: ['Entrevistas'],
        summary: 'Busca uma entrevista por ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Entrevista encontrada'),
            new OA\Response(response: 404, description: 'Entrevista não encontrada')
        ]
    )]
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            AppHelper::sendResponse(400, ['error' => 'ID necessário']);
            return;
        }

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM interviews WHERE id = ?");
        $stmt->execute([$id]);
        $interview = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$interview) {
            AppHelper::sendResponse(404, ['error' => 'Entrevista não encontrada']);
            return;
        }

        $stmtCat = $pdo->prepare("
            SELECT c.id, c.name 
            FROM categories c
            JOIN interview_categories ic ON c.id = ic.category_id
            WHERE ic.interview_id = ?
        ");
        $stmtCat->execute([$id]);
        $interview['categories'] = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        if (!isset($_REQUEST['user'])) {
            $this->tryIdentifyUser();
        }

        if (isset($_REQUEST['user'])) {
            foreach ($interview['categories'] as $cat) {
                \App\Models\UserHistory::record(
                    $_REQUEST['user']['id'],
                    $cat['id'],
                    'interview',
                    $id
                );
            }
        }

        AppHelper::sendResponse(200, ['data' => $interview]);
    }

    #[OA\Post(
        path: '/interviews',
        tags: ['Admin'],
        summary: 'Cria uma nova entrevista',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'interviewee', 'category_ids'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'interviewee', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'category_ids', type: 'array', items: new OA\Items(type: 'integer'))
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Criado')]
    )]
    public function store()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['title']) || empty($data['interviewee']) || empty($data['category_ids'])) {
            AppHelper::sendResponse(400, ['error' => 'Dados incompletos']);
            return;
        }

        try {
            $id = \App\Models\Interview::create($data);
            AppHelper::sendResponse(201, ['message' => 'Entrevista criada', 'id' => $id]);
        } catch (HttpResponseException $e) {
            throw $e; // Sucesso! Deixa passar.
        } catch (\Throwable $e) {
            AppHelper::sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    #[OA\Put(
        path: '/interviews',
        tags: ['Admin'],
        summary: 'Atualiza uma entrevista',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id', 'title', 'interviewee'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'interviewee', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'category_ids', type: 'array', items: new OA\Items(type: 'integer'))
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Atualizado')]
    )]
    public function update()
    {
        $data = AppHelper::getJsonInput();

        if (empty($data['id']) || empty($data['title'])) {
            AppHelper::sendResponse(400, ['error' => 'Dados incompletos (id, title são obrigatórios)']);
            return;
        }

        try {
            \App\Models\Interview::update($data['id'], $data);
            AppHelper::sendResponse(200, ['message' => 'Entrevista atualizada']);
        } catch (HttpResponseException $e) {
            // FIX CRÍTICO: Se for sucesso, relança a exceção para não cair no catch genérico!
            throw $e;
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => 'Erro ao atualizar: ' . $e->getMessage()]);
        }
    }

    #[OA\Delete(
        path: '/interviews',
        tags: ['Admin'],
        summary: 'Remove uma entrevista',
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

        try {
            if (\App\Models\Interview::delete($data['id'])) {
                AppHelper::sendResponse(200, ['message' => 'Entrevista removida']);
            } else {
                AppHelper::sendResponse(500, ['error' => 'Erro ao remover']);
            }
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            AppHelper::sendResponse(500, ['error' => 'Erro: ' . $e->getMessage()]);
        }
    }

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
            $stmt = $pdo->prepare("SELECT u.id, u.role FROM personal_access_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) $_REQUEST['user'] = $user;
        }
    }
}
